<?php

namespace rocketpark\mux\controllers;

use Craft;
use craft\elements\GlobalSet;
use craft\elements\User;
use craft\errors\MissingComponentException;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use rocketpark\mux\helpers\Permission as PermissionHelper;
use rocketpark\mux\Mux;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use craft\records\Session;
use craft\web\Session as SessionWeb;
use craft\helpers\Json;

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
     */
    public function actionMuxWebhooks()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();

        //\yii\helpers\VarDumper::dump($params, 5, true);exit;

        switch ($params['type']) {
            case 'video.asset.ready':
                // We need to make sure the status is set to ready.
                Mux::$plugin->assets->updateAssetElementWithMuxAsset($params['data']);
                break;
            case 'video.asset.updated':
                Craft::$app->getSession()->setNotice('Mux Asset Updated');
                break;
            case 'video.asset.deleted':
                Mux::info(json_encode($params), 'mux');
                break;
            case 'video.asset.errored':
                Mux::error(json_encode($params), 'mux');
                break;
            case 'video.asset.track.created':
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.asset.track.ready':
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.asset.track.errored':
                Mux::error(json_encode($params), 'mux');
                break;
            case 'video.asset.track.deleted':
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.upload.asset_created':
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.upload.cancelled':
                Mux::error(json_encode($params), 'mux');
                break;
            case 'video.upload.created':
                //Mux::info(json_encode($params), 'mux');
                break;
            case 'video.upload.errored':
                Mux::error(json_encode($params), 'mux');
                break;
            case 'video.asset.warning':
                Mux::info(json_encode($params), 'mux');
                break;
        }


        if ($request->getAcceptsJson()) {
            //Craft::$app->getSession()->setNotice($params['type']);
            return $this->asJson($params);
        };
    }
}