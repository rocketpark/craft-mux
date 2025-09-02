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

        // Only process relevant Mux webhook event types
        $allowedTypes = [
            'video.asset.ready',
            'video.asset.updated',
            'video.asset.deleted',
            //'video.asset.errored',
            //'video.asset.track.created',
            //'video.asset.track.ready',
            'video.asset.track.errored',
            //'video.asset.track.deleted',
            'video.upload.asset_created',
            //'video.upload.cancelled',
            //'video.upload.created',
            //'video.upload.errored',
            //'video.asset.warning',
            'video.asset.static_renditions.preparing',
            'video.asset.static_renditions.ready',
            'video.asset.static_renditions.deleted',
        ];

        if (!isset($params['type']) || !in_array($params['type'], $allowedTypes, true)) {
            // Ignore unknown or unsupported webhook types
            $response = Craft::$app->getResponse();
            $response->setStatusCode(204); // No Content
            return $response;
        }
        
        Craft::$app->queue->push(new HandleMuxWebhookJob([
            'webhookData' => $params,
            'timestamp' => microtime(true),
            'priority' => 100,
        ]), 100);

        $response = Craft::$app->getResponse();
        $response->setStatusCode(200);
        return $response;
    }
}