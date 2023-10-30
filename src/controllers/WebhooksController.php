<?php

namespace rocketpark\mux\controllers;

use Craft;

use craft\web\Controller;
use rocketpark\mux\helpers\Permission as PermissionHelper;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset;
use GuzzleHttp\Client;
use MuxPhp;


/**
 * Webhooks controller
 */
class WebhooksController extends Controller
{


    // Constants
    // =========================================================================


    // Protected Properties
    // =========================================================================

    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE;

    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if ($action->id === 'mux-webhooks') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }


    /**
     * Mux Webhooks
     * @return void
     * 
     * Usage example: https://site.com/actions/mux/webhooks/mux-webhooks
     */
    public function actionMuxWebhooks()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();

        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );

        switch ($params['type']) {
            case 'video.asset.ready':
                // We need to make sure the status is set to ready.
                Mux::$plugin->assets->updateAssetElementWithMuxAsset($params['data']);
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.asset.updated':
                Mux::$plugin->assets->updateAssetElementWithMuxAsset($params['data']);
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.asset.deleted':
                if(MuxAsset::findOne(['asset_id' => $params['data']['id']]) !== null) {
                    Mux::$plugin->assets->deleteAsset($params['data']['id']);
                }
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.asset.errored':
                //Mux::error(json_encode($params), 'mux');
                break;
            case 'video.asset.track.created':
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.asset.track.ready':
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.asset.track.errored':
                //Mux::error(json_encode($params), 'mux');
                break;
            case 'video.asset.track.deleted':
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.upload.asset_created':
                $el = MuxAsset::findOne(['asset_id' => $params['data']['asset_id']]);
                if($el === null) {
                    $asset = $apiInstance->getAsset($params['data']['asset_id']);
                    Mux::$plugin->assets->createOrUpdateMuxAsset($asset->getData());
                }
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.upload.cancelled':
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.upload.created':
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.upload.errored':
                //Mux::error(json_encode($params), 'mux');
                break;
            case 'video.asset.warning':
                //Mux::info(json_encode($params), 'mux');
                break;
        }

        $response = Craft::$app->getResponse();
        $response->setStatusCode(200);
        return $response;
    }
}