<?php

namespace rocketpark\mux\controllers;

use Craft;
use craft\db\Query;
use craft\elements\GlobalSet;
use craft\helpers\Console;
use craft\web\Controller;
use craft\helpers\UrlHelper;
use Exception as GlobalException;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Request;
use yii\web\NotFoundHttpException;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use Throwable;

use rocketpark\mux\helpers\Permission as PermissionHelper;
use rocketpark\mux\Mux;
use rocketpark\mux\assetbundles\mux\MuxDashboardAsset;
use rocketpark\mux\models\MuxAsset as MuxAsset;
use rocketpark\mux\records\Assets as MuxAssetsRecord;
use rocketpark\mux\records\MuxFolder as MuxFolderRecord;
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;
use yii\base\InvalidArgumentException;

use craft\elements\actions\MoveAssets as CraftMoveAssets;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;

/**
 * Assets controller
 */
class AssetsController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    // Public Methods
    // =========================================================================

    /**
     * @throws ForbiddenHttpException
     */
    public function actionIndex(string|null $defaultSource = null): Response
    {
        $this->requireCpRequest();

        $variables = [
            'elementType' => MuxAssetElement::class,
        ];

        if ($defaultSource) {
            $defaultSourcePath = ArrayHelper::filterEmptyStringsFromArray(explode('/', $defaultSource));
            $volumesService = Mux::$plugin->volumes;
            $volume = $volumesService->getVolumeByHandle(array_shift($defaultSourcePath));

            if ($volume) {
                $foldersService = Mux::$plugin->folders;
                $variables['defaultSource'] = "volume:$volume->uid";

                if (!empty($defaultSourcePath)) {
                    $subfolder = $foldersService->findFolder([
                        'volumeId' => $volume->id,
                        'path' => sprintf('%s/', implode('/', $defaultSourcePath)),
                    ]);
                    if ($subfolder) {
                        $sourcePath = [];
                        /** @var VolumeFolder[] $folders */
                        $folders = [];
                        while ($subfolder) {
                            array_unshift($folders, $subfolder);
                            $subfolder = $subfolder->getParent();
                        }
                        foreach ($folders as $i => $folder) {
                            if ($i < count($folders) - 1) {
                                $folder->setHasChildren(true);
                            }
                            $sourcePath[] = $folder->getSourcePathInfo();
                        }
                        $variables['defaultSourcePath'] = $sourcePath;
                    }
                }
            }
        }
        
        return $this->renderTemplate('mux/elements/_index', $variables);
    }

    /**
     * Edit Mux Asset Element
     * @param int $elementId
     * @return Response
     */ 
    public function actionEdit(int $elementId): Response
    {
        $this->requireCpRequest();

        $strictSite = $this->request->getAcceptsJson();

        $user = static::currentUser();

        $element = MuxAssetElement::find()->id($elementId)->one();

        // Permissions
        $canSave = $element->canSave($user);
        // Check if user has permission to edit assets
        //PermissionHelper::controllerPermissionCheck('mux:assets-edit');

        if (!$element) {
            return $this->redirect(UrlHelper::cpUrl('mux/assets'));
        }

        $redirectUrl = ElementHelper::postEditUrl($element);

        $fieldLayout = $element->getFieldLayout();
        $html = $fieldLayout->createForm($element)->render();

        $response = $this->asCpScreen()
            ->editUrl($element->getCpEditUrl())
            ->title($element->title)
            ->crumbs($element->getCrumbs())
            ->metaSidebarHtml($element->getSidebarHtml(false) . Cp::metadataHtml($element->getMetadata()))
            ->addTab('0', Craft::t('app', 'Content'), '#tab01-content', true)
            ->addTab('1', Craft::t('app', 'Tracks'), '#tab02-tracks', false)
            ->contentHtml($html);

        // Add save and continue editing option
        $response->addAltAction(Craft::t('app', 'Save and continue editing'),[
            'redirect' => $element->getCpEditUrl(),
            'shortcut' => true,
            'retainScroll' => true,
            'eventData' => ['autosave' => false],
        ]);

        $response->submitButtonLabel(Craft::t('app', 'Save'))
            ->action('mux/assets/save')
            ->redirectUrl($redirectUrl);

        $response->actionMenuItems(fn() => $element->id ? array_filter(
            $element->getActionMenuItems(),
            fn(array $item) => !str_starts_with($item['id'] ?? '', 'action-edit-'),
        ) : []);

        $response->registerAssetBundle(MuxDashboardAsset::class);
        return $response;
    }

    /**
     * Saves an asset element.
     *
     * @throws Throwable
     */
    public function actionSave(): Response
    {
        // Check if user has permission to edit assets
        PermissionHelper::controllerPermissionCheck('mux:assets-edit');

        // Ensure this is a POST request
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();
        $assetsService = Mux::$plugin->assets;

        // Build asset from POST data
        $asset = Mux::$plugin->assets->buildAssetElementFromPost();

        // Try saving the asset
        // if ($assetsService->saveAsset($asset)) {
        if(Craft::$app->getElements()->saveElement($asset)) {
            // Successful save
            return $this->_handleSaveResponse(true, 'Asset saved.', $asset->getErrors(), $request);
        }

        // Unsuccessful save
        $session->setError(Craft::t('mux', 'Couldn’t save asset.'));
        return $this->handleSaveResponse(false, 'Could not save MuxAssetElement.', $asset->getErrors(), $request);
    }

    /**
     * Create MUX Asset Element
     * @return Response 
     * @throws BadRequestHttpException 
     * @throws InvalidConfigException 
     * @throws GlobalException 
     * @throws InvalidArgumentException 
     */
    public function actionCreate(): Response
    {
        PermissionHelper::controllerPermissionCheck('mux:assets-create');

        $this->requirePostRequest();

        Mux::info('Creating Mux Asset Element (mux\controllers\actionCreate())', 'mux');
        $element = Mux::$plugin->assets->buildAssetElementFromPost();

        if (!Craft::$app->getElements()->saveElement($element)) {
            return $this->asModelFailure(
                $element,
                Craft::t('mux', 'Couldn’t save mux asset element.'),
                'MuxAssetElement'
            );
        }

        return $this->asModelSuccess(
            $element,
            Craft::t('app', 'Element saved.'),
            data: [
                'id' => $element->id,
                'title' => $element->title,
                'url' => $element->getUrl(),
            ],
        );
    }


    /**
     * Updates Mux Asset Element
     *
     * @throws Throwable
     */
    public function actionUpdate(): Response
    {

        PermissionHelper::controllerPermissionCheck('mux:assets-edit');

        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();

        $element = MuxAssetElement::find()->id($params['id'])->one();

        // Currently only updating these two properties
        $element->title = $params['title'];
        $element->passthrough = $params['passthrough'];
        $meta = [
            'title' => $params['title'],
            'external_id' => $params['id'],
        ];
        $element->meta = $meta;

        if (!Craft::$app->getElements()->saveElement($element)) {
            return $this->asModelFailure(
                $element,
                Craft::t('mux', 'Couldn’t save mux asset element.'),
                'MuxAssetElement'
            );
        }
    
        return $this->asModelSuccess(
            $element,
            Craft::t('mux', 'Element saved.'),
            data: [
                'id' => $element->id,
                'title' => $element->title,
                'url' => $element->getUrl(),
            ],
        );
    }

    /**
     * Move Mux Asset Element
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionMove()
    {
        $this->requirePostRequest();
        $this->requirePermission('mux:assets-edit');
        $request = Craft::$app->getRequest();
        $elementIds = $request->getBodyParam('elementIds');
        $targetFolderId = $request->getBodyParam('targetFolderId');

        $elements = MuxAssetElement::find()->id($elementIds)->all();

        foreach ($elements as $element) {
            $element->folderId = $targetFolderId;
            Craft::$app->getElements()->saveElement($element);
        }

        return $this->asJson([
            'success' => true,
            'message' => Craft::t('mux', 'Assets moved successfully.'),
        ]);

    }


    /**
     * Use Mux Asset
     * @param int|null $limit
     * @param int|null $page
     * @return \MuxPhp\Models\ListAssetsResponse
     */
    public static function actionUseAssets(?int $limit, ?int $page): \MuxPhp\Models\ListAssetsResponse
    {
        return Mux::$plugin->assets->useMuxAssets($limit, $page);
    }

    /**
     * Use Asset Elements
     * @param null|int $limit 
     * @param null|int $page 
     * @return Response 
     * @throws InvalidConfigException 
     */
    public function actionUseAssetElements(?int $limit,?int $page)
    {   
        $offset = $limit * ($page - 1);
        return $this->asJson(MuxAssetElement::find()->limit($limit)->offset($offset)->all());
    }

    /**
     * Use Assets Element Count
     * Get the total number of Mux Assets Elements
     * @return Response 
     * @throws InvalidConfigException 
     */
    public function actionUseAssetElementsCount()
    {   
        return $this->asJson(MuxAssetElement::find()->count());
    }

    /**
     * Get MUX Asset Element By ID
     * @param null|int $id 
     * @return Response 
     * @throws InvalidConfigException 
     */
    public function actionUseAssetElementById(?int $id)
    {   
        return $this->asJson(MuxAssetElement::find()->id($id)->one());
    }

    /**
     * Upload Mux Asset
     * @return mixed
     */
    public function actionUploadAsset()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $title = $request->getBodyParam('title');
        $volumeUid = $request->getBodyParam('volumeUid');
        $folderId = $request->getBodyParam('folderId');

        if ($request->getAcceptsJson()) {
            return MUX::$plugin->assets->uploadMuxAsset($title, $volumeUid, $folderId);
        };
    }


    /**
     * Get Mux Upload By Asset ID
     * @return void|Response
     * @throws BadRequestHttpException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionGetUploadById()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $body = $request->getRawBody();
        $params = craft\helpers\Json::decode($body);

        if ($request->getAcceptsJson()) {
            Craft::$app->getSession()->setNotice('Mux Asset Uploaded.');
            return $this->asJson(MUX::$plugin->assets->getUploadById($params['id']));
        };
    }

    /**
     * Get Mux Asset By ID
     * @return void|Response
     * @throws BadRequestHttpException
     */
    public function actionGetAssetById()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $body = $request->getRawBody();
        $params = craft\helpers\Json::decode($body);

        if ($request->getAcceptsJson()) {
            return $this->asJson(MUX::$plugin->assets->getMuxAssetById($params['id']));
        };
    }

    /**
     * Delete Mux Asset By ID
     *  - First deletes the asset record then deletes the mux asset from MUX server.
     * @return void|Response
     * @throws BadRequestHttpException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionDeleteAssetById()
    {
        $this->requirePostRequest();

        PermissionHelper::controllerPermissionCheck('mux:assets-delete');

        $request = Craft::$app->getRequest();
        $body = $request->getRawBody();
        $params = craft\helpers\Json::decode($body);

        $element = MuxAssetElement::find(['id' => $params['id']])->one();
    
        if (!$element) {
            return false;
        }

        if (!Craft::$app->getElements()->deleteElement($element)) {
            return $this->asModelFailure(
                $element,
                Craft::t('mux', 'Couldn’t delete mux asset element.'),
                'MuxAssetElement'
            );
        }
    
        return $this->asModelSuccess(
            $element,
            Craft::t('app', 'Element deleted.'),
            'MuxAssetElement',
            [
                "success" => true,
            ]
        );

    }

    /**
     * Add Mux Asset Track By Id
     * @return void|Response
     * @throws BadRequestHttpException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionAddAssetTrackById()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $body = $request->getRawBody();
        $params = craft\helpers\Json::decode($body);

        if(!isset($params['id']) || !isset($params['track'])) {
            if($request->getAcceptsJson()) {

                return $this->asJson([
                    'error' => 'Invalid parameters provided.'
                ]);
            } else {
                Craft::$app->getSession()->setError('Invalid parameters provided.');
                return;
            }
        }

        if(MUX::$plugin->assets->addMuxAssetTrackById($params['id'], $params['track'])) {
            if(MUX::$plugin->assets->updateAssetElementWithMuxAssetById($params['id'])) {
                if ($request->getAcceptsJson()) {
                    Craft::$app->getSession()->setNotice('Mux Asset Track Added.');

                    $this->setSuccessFlash(Craft::t('mux', 'Mux Asset Track Added.', [
                        'type' => GlobalSet::displayName(),
                    ]));
        
                    return $this->asJson([
                        'success' => true
                    ]);
                };
            }
        }

        if ($request->getAcceptsJson()) {
            Craft::$app->getSession()->setNotice('Mux Asset Track Not Added.');
            $this->setFailFlash(Craft::t('mux', 'Mux Asset Track Not Added.', [
                'type' => GlobalSet::displayName(),
            ]));
            

            return $this->asJson([
                'success' => false
            ]);
        };
        
    }
    

    /**
     * Delete Asset Track By Id
     * @requestParams $params['id'], $params['track_id']
     * @return void|Response
     * */
    public function actionDeleteAssetTrackById(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $body = $request->getRawBody();
        $params = craft\helpers\Json::decode($body);
        

        if (empty($params['id']) || empty($params['track_id'])) {
            $errorMsg = Craft::t('mux', 'Invalid parameters provided.');
            Craft::$app->getSession()->setError($errorMsg);

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'error' => $errorMsg
                ]);
            }
            return $this->asJson([
                'success' => false,
                'error' => $errorMsg
            ]);
        }

        if(MUX::$plugin->assets->deleteMuxAssetTrackById($params['id'], $params['track_id'])) {
            if(MUX::$plugin->assets->updateAssetElementWithMuxAssetById($params['id'])) {
                if ($request->getAcceptsJson()) {
                    Craft::$app->getSession()->setNotice('Mux Asset Track Deleted.');

                    $this->setSuccessFlash(Craft::t('mux', 'Mux Asset Track Deleted.', [
                        'type' => GlobalSet::displayName(),
                    ]));
        
                    return $this->asJson([
                        'success' => true
                    ]);
                };
            }
        }

        if ($request->getAcceptsJson()) {
            Craft::$app->getSession()->setNotice('Mux Asset Track Not Deleted.');
            $this->setFailFlash(Craft::t('mux', 'Mux Asset Track Not Deleted.', [
                'type' => GlobalSet::displayName(),
            ]));

            return $this->asJson([
                'success' => false
            ]);
        };

        return $this->asJson([
            'success' => false
        ]);

    }

    /**
     * Sync Asset By Id
     * @requestParams $params['assetId']
     * @return void|Response
     */
    public function actionSyncAssetById(): Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();

        if ($request->getAcceptsJson()) {
            
            if (!Mux::$plugin->assets->syncAssetById($params['assetId'])) {
                Craft::$app->getSession()->setNotice('Couldn\'t sync asset.');
                $this->setFailFlash(Craft::t('mux', 'Couldn\'t sync asset.', [
                    'type' => GlobalSet::displayName(),
                ]));
            }

            $this->setSuccessFlash(Craft::t('mux', 'Assets updated from MUX!', [
                'type' => GlobalSet::displayName(),
            ]));

            return $this->asJson([
                'success' => true
            ]);
        }

        return $this->asJson([
            'success' => false
        ]);
    }


    /**
     * Update MP4 Support
     * @requestParams $params['assetId'], $params['mp4Support']
     * @deprecated
     * @return void|Response
     */
    public function actionUpdateMp4Support(): Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();

        if ($request->getAcceptsJson()) {
            if (!Mux::$plugin->assets->updateMuxAssetMP4Support($params['assetId'], $params['mp4Support'])) {
                Craft::$app->getSession()->setNotice('Couldn\'t update MUX asset mp4 support.');
                $this->setFailFlash(Craft::t('mux', 'Couldn\'t update MUX asset mp4 support.', [
                    'type' => GlobalSet::displayName(),
                ]));
            }

            $this->setSuccessFlash(Craft::t('mux', 'Asset mp4 support updated.', [
                'type' => GlobalSet::displayName(),
            ]));

            return $this->asJson([
                'success' => true
            ]);
        }

        return $this->asJson([
            'success' => false
        ]);
    }

    /**
     * Update MUX Asset Static Renditions
     * @requestParams $params['assetId'], $params['staticRendition']
     * @return void|Response
     */
    public function actionUpdateStaticRenditions(): Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();

        if ($request->getAcceptsJson()) {
            if (!Mux::$plugin->assets->updateMuxAssetStaticRenditions($params['assetId'], $params['staticRendition'])) {
                Craft::$app->getSession()->setNotice('Couldn\'t update MUX asset static rendition.');
                $this->setFailFlash(Craft::t('mux', 'Couldn\'t update MUX asset static rendition.', [
                    'type' => GlobalSet::displayName(),
                ]));
            }

            $this->setSuccessFlash(Craft::t('mux', 'Asset static rendition updated.', [
                'type' => GlobalSet::displayName(),
            ]));

            return $this->asJson([
                'success' => true
            ]);
        }

        return $this->asJson([
            'success' => false
        ]);

    }

    /**
     * Move one or more assets.
     *
     * @return Response
     * @throws BadRequestHttpException if the asset or the target folder cannot be found
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws VolumeException
     * @throws Throwable
     * @throws ElementNotFoundException
     */
    public function actionMoveAsset(): Response
    {
        $this->requireAcceptsJson();

        $assetsService = Mux::$plugin->assets;

        // Get the asset
        $assetId = $this->request->getRequiredBodyParam('assetId');
        $asset = MuxAssetElement::find()->id($assetId)->one(); 

        if ($asset === null) {
            throw new BadRequestHttpException('The Asset cannot be found');
        }

        // Get the target folder
        $folderId = $this->request->getBodyParam('folderId', $asset->folderId);
        $folder = MuxFolderRecord::findOne(['id' => $folderId]);
        $volume = $folder->getVolume()->one();

        if ($folder === null) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Check if it's possible to delete objects in the source volume and save assets in the target volume.
        // $this->requireVolumePermissionByFolder('saveAssets', $folder);
        // $this->requireVolumePermissionByAsset('deleteAssets', $asset);
        // $this->requirePeerVolumePermissionByAsset('savePeerAssets', $asset);
        // $this->requirePeerVolumePermissionByAsset('deletePeerAssets', $asset);
        
        $result = $assetsService->moveAssets([$asset->id], $folderId, $volume->id);

        if (!$result) {
            return $this->asJson([
                'assetId' => $asset->id,
            ]);
        }

        return $this->asSuccess();
    }

    /**
     * Returns the total number of assets, and their total file size, based on their IDs and/or folder IDs.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionMoveInfo(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();

        $folderIds = Craft::$app->getRequest()->getBodyParam('folderIds', []);
        $assetIds = Craft::$app->getRequest()->getBodyParam('assetIds', []);

        if (!empty($folderIds)) {
            // Add descendant folders
            $assetsService = Mux::$plugin->assets;
            foreach ($folderIds as $folderId) {
                $folder = $assetsService->getFolderById($folderId);
                if (!$folder) {
                    throw new BadRequestHttpException("Invalid folder ID: $folderId");
                }
                $descendants = $assetsService->getAllDescendantFolders($folder);
                array_push($folderIds, ...array_keys($descendants));
            }
        }

        $query = (new Query())
            ->from(MuxAssetsRecord::tableName())
            ->where([
                'or',
                ['id' => $assetIds],
                ['folderId' => array_unique($folderIds)],
            ]);
        $count = (int)$query->count();
        $totalSize = (int)$query->sum('[[size]]');

        return $this->asJson([
            'count' => $count,
            'totalSize' => $totalSize,
        ]);
    }

    /**
     * Show in folder action.
     * Find asset by id and Return source path info for each folder up until the one the asset is in.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws \yii\web\MethodNotAllowedHttpException
     */
    public function actionShowInFolder(): Response
    {
        $this->requireCpRequest();

        $assetId = Craft::$app->getRequest()->getRequiredParam('assetId');

        $asset = MuxAssetElement::find()->id($assetId)->one();
        if ($asset === null) {
            throw new BadRequestHttpException("Invalid asset ID: $assetId");
        }

        // get the folder for selected asset
        $folder = $asset->getFolder();
        $sourcePath[] = $folder->getSourcePathInfo();

        // for a JSON response (e.g. via element actions)
        if ($this->request->getAcceptsJson()) {
            // get all the way up to the root folder, cause we need source path info for each step
            while (($parent = $folder->getParent()) !== null) {
                $sourcePath[] = $parent->getSourcePathInfo();
                $folder = $parent;
            }

            $data = [
                'filename' => $asset->filename,
                'sourcePath' => array_reverse($sourcePath),
            ];

            return $this->asJson($data);
        }

        // for a redirect response (e.g. element action menu items)
        $uri = StringHelper::ensureLeft(UrlHelper::prependCpTrigger($sourcePath[0]['uri']), '/');
        $url = UrlHelper::urlWithParams($uri, [
            'search' => $asset->filename,
            'includeSubfolders' => '0',
            'sourcePathStep' => "folder:$folder->uid",
        ]);

        return $this->redirect($url);
    }

    // Private Methods
    // =========================================================================

    /**
     * Handles the response after attempting to save an asset
     *
     * @param bool $success Whether the save was successful
     * @param string $message The response message
     * @param array $errors Any errors that occurred
     * @param Request $request The current request
     * @return Response
     */
    private function _handleSaveResponse(bool $success, string $message, array $errors, Request $request): Response
    {
        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => $success,
                'errors' => $errors,
                'message' => Craft::t('mux', $message),
            ]);
        }

        if ($success) {
            Craft::$app->getSession()->setNotice(Craft::t('mux', $message));
        }

        return $this->redirectToPostedUrl();
    }

    private function _updateAssetPermission($asset): void
    {
        // if (Craft::$app->getEdition() !== Craft::Pro) {
        //     return;
        // }

        $suffix = ':' . $asset->uid;

        $userService = Craft::$app->getUser();
        $currentUser = $userService->getIdentity();
        $permissions = Craft::$app->getUserPermissions()->getPermissionsByUserId($currentUser->id);
        $permissions[] = "mux-manageasset{$suffix}";

        // Add all nested permissions according to top-level permissions set

        Craft::$app->getUserPermissions()->saveUserPermissions($currentUser->id, $permissions);
    }
}
