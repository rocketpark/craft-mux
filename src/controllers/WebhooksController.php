<?php

namespace rocketpark\mux\controllers;

use Craft;

use craft\web\Controller;
use rocketpark\mux\helpers\Permission as PermissionHelper;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset;
use rocketpark\mux\jobs\HandleMuxWebhookJob;
use rocketpark\mux\services\Webhooks;
use GuzzleHttp\Client;
use MuxPhp;
use Throwable;
use yii\web\Response;


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
     * @return Response
     *
     * Usage example: https://site.com/actions/mux/webhooks/mux-webhooks
     */
    public function actionMuxWebhooks(): Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $response = Craft::$app->getResponse();
        $webhooks = Mux::$plugin->webhooks;

        // Verify the Mux-Signature header against the raw body before trusting anything in the payload
        $secret = $webhooks->getSigningSecret();
        if ($secret === '') {
            Mux::error('Rejected Mux webhook: no webhook signing secret is configured. Add it in Mux → Settings → Webhook Signing Secret.');
            $response->setStatusCode(401);
            return $response;
        }

        $rawBody = $request->getRawBody();
        $signature = $request->getHeaders()->get(Webhooks::SIGNATURE_HEADER);

        if (!$webhooks->verifySignature($rawBody, $signature, $secret)) {
            Mux::warning(sprintf(
                'Rejected Mux webhook from %s: %s Mux-Signature header.',
                $request->getUserIP() ?? 'unknown IP',
                $signature === null ? 'missing' : 'invalid or expired'
            ));
            $response->setStatusCode(401);
            return $response;
        }

        // Parse the exact body that was signed
        $params = json_decode($rawBody, true);
        if (!is_array($params)) {
            $response->setStatusCode(400);
            return $response;
        }

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
            // Singular (what Mux sends): video.asset.static_rendition.*
            'video.asset.static_rendition.created',
            'video.asset.static_rendition.preparing',
            'video.asset.static_rendition.ready',
            'video.asset.static_rendition.deleted',
            // Plural (legacy, if Mux ever sends): video.asset.static_renditions.*
            'video.asset.static_renditions.preparing',
            'video.asset.static_renditions.ready',
            'video.asset.static_renditions.deleted',
        ];

        if (!isset($params['type']) || !in_array($params['type'], $allowedTypes, true)) {
            // Ignore unknown or unsupported webhook types
            $response->setStatusCode(204); // No Content
            return $response;
        }

        // Mux may deliver the same event more than once; only queue it the first time
        $eventId = isset($params['id']) ? (string) $params['id'] : null;
        if (!$webhooks->markEventProcessed($eventId)) {
            $response->setStatusCode(200);
            return $response;
        }

        try {
            Craft::$app->queue->push(new HandleMuxWebhookJob([
                'webhookData' => $params,
                'timestamp' => microtime(true),
                'priority' => 100,
            ]), 100);
        } catch (Throwable $e) {
            // Let Mux's retry through instead of dropping it as a duplicate
            $webhooks->forgetProcessedEvent($eventId);
            throw $e;
        }

        $response->setStatusCode(200);
        return $response;
    }
}