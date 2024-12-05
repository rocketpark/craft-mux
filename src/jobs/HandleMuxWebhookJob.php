<?php

namespace rocketpark\mux\jobs;

use Craft;
use craft\queue\BaseJob;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset;
use GuzzleHttp\Client;
use MuxPhp;


class HandleMuxWebhookJob extends BaseJob
{
    public $webhookData;

    public function execute($queue): void
    {
        $params = $this->webhookData;

        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );
        // Mux::info($params['type'], 'mux');

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
            case 'video.asset.static_renditions.preparing':
                // Mux::info("Preparing Static Renditions", 'mux');
                Mux::$plugin->assets->updateAssetElementWithMuxAsset($params['data']);
                break;
            case 'video.asset.static_renditions.ready':
                // Mux::info("Static Renditions Ready", 'mux');
                // Mux::info(json_encode($params), 'mux');
                Mux::$plugin->assets->updateAssetElementWithMuxAsset($params['data']);
                break;
            case 'video.asset.static_renditions.deleted':
                // Mux::info("Static Renditions Deleted", 'mux');
                // Mux::info(json_encode($params), 'mux');
                Mux::$plugin->assets->updateAssetElementWithMuxAsset($params['data']);
                break;
        }

    }
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Handling Mux Webhook');
    }
}