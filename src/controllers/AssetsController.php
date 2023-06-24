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
        $this->requirePermission('mux-viewAssets');

        return $this->renderTemplate('mux/dashboard/index.twig', []);
    }


    /**
     * Saves an asset element.
     *
     * @throws Throwable
     */
    public function actionSave(): Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $asset = Mux::$plugin->assets->buildAssetFromPost();

        if (Mux::$plugin->assets->saveAsset($asset)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => true,
                    'errors' => $asset->getErrors(),
                    'message' => Craft::t('mux', 'Asset saved.'),
                ]);
            }
            Craft::$app->getSession()->setNotice(Craft::t('mux', 'Asset saved.'));   
        } else {
            Craft::$app->getSession()->setError(Craft::t('mux', 'Couldn’t save asset.'));
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $asset->getErrors(),
                    'message' => Craft::t('mux', 'Could not save MuxAssetElement.')
                ]);
            }
        }
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
    public static function actionUploadAsset()
    {
        return MUX::$plugin->assets->uploadMuxAsset();
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


    // Private Methods
    // =========================================================================


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


    /**
     * mux/assets action
     */

    public function actionDashboard(): Response
    {
        $variables = [];
        PermissionHelper::controllerPermissionCheck('mux:dashboard');
        $settings = Mux::$settings;
        $pluginName = $settings->pluginName;
        $templateTitle = Craft::t('mux', 'Dashboard');
        $view = Craft::$app->getView();

        // Asset bundle
        try {
            $view->registerAssetBundle(MuxDashboardAsset::class);
        } catch (InvalidConfigException $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }
        $variables['baseAssetsUrl'] = Craft::$app->assetManager->getPublishedUrl(
            '@rocketpark/mux/web/dist',
            true
        );

        $variables['controllerHandle'] = 'dashboard';
        $variables['pluginName'] = $pluginName;
        $variables['title'] = $templateTitle;
        $variables['crumbs'] = [
            [
                'label' => $pluginName,
                'url' => UrlHelper::cpUrl('mux'),
            ],
            [
                'label' => $templateTitle,
                'url' => UrlHelper::cpUrl('mux/dashboard'),
            ],
        ];
        $variables['docTitle'] = "{$pluginName} - {$templateTitle}";
        $variables['selectedSubnavItem'] = 'dashboard';;
        $variables['settings'] = $settings;

        // Render the template
        return $this->renderTemplate('mux/dashboard/index', $variables);
    }
}
