<?php

namespace rocketpark\mux\controllers;

use Craft;
use craft\elements\GlobalSet;
use craft\helpers\Console;
use craft\web\Controller;
use craft\helpers\UrlHelper;
use Exception as GlobalException;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
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
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;
use yii\base\InvalidArgumentException;

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
    public function actionIndex(): Response
    {
//        $this->requirePermission('mux:assets');
//        PermissionHelper::controllerPermissionCheck('mux:assets');

        return $this->renderTemplate('mux/elements/_index', []);
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
        $asset = $assetsService->buildAssetFromPost();

        // Try saving the asset
        if ($assetsService->saveAsset($asset)) {
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

        // Currently only upating these two properties
        $element->title = $params['title'];
        $element->passthrough = $params['passthrough'];

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
        $passthrough = $request->getBodyParam('passthrough');
        if ($request->getAcceptsJson()) {
            return MUX::$plugin->assets->uploadMuxAsset($passthrough);
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
    public function actionDeleteAssetTrackById()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $body = $request->getRawBody();
        $params = craft\helpers\Json::decode($body);
        

        if(!isset($params['id']) || !isset($params['track_id'])) {
            if($request->getAcceptsJson()) {
                return $this->asJson([
                    'error' => 'Invalid parameters provided.'
                ]);
            } else {
                Craft::$app->getSession()->setError('Invalid parameters provided.');
                return;
            }
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

    }

    /**
     * Sync Asset By Id
     * @requestParams $params['assetId']
     * @return void|Response
     */
    public function actionSyncAssetById(): response
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
    }


    /**
     * Update MP4 Support
     * @requestParams $params['assetId'], $params['mp4Support']
     * @return void|Response
     */
    public function actionUpdateMp4Support(): response
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
        if (Craft::$app->getEdition() !== Craft::Pro) {
            return;
        }

        $suffix = ':' . $asset->uid;

        $userService = Craft::$app->getUser();
        $currentUser = $userService->getIdentity();
        $permissions = Craft::$app->getUserPermissions()->getPermissionsByUserId($currentUser->id);
        $permissions[] = "mux-manageasset{$suffix}";

        // Add all nested permissions according to top-level permissions set

        Craft::$app->getUserPermissions()->saveUserPermissions($currentUser->id, $permissions);
    }
}
