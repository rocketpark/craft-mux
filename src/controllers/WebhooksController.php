<?php

namespace rocketpark\mux\controllers;

use Craft;

use craft\web\Controller;
use rocketpark\mux\helpers\Permission as PermissionHelper;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset;
use rocketpark\mux\jobs\HandleMuxWebhookJob;
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
        
        Craft::$app->queue->push(new HandleMuxWebhookJob([
            'webhookData' => $params,
        ]));

        $response = Craft::$app->getResponse();
        $response->setStatusCode(200);
        return $response;
    }
}