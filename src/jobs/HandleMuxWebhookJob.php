<?php

namespace rocketpark\mux\jobs;

use Craft;
use craft\queue\BaseJob;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset;
use rocketpark\mux\records\Assets as MuxAssetsRecord;
use GuzzleHttp\Client;
use MuxPhp;
use Throwable;

class HandleMuxWebhookJob extends BaseJob
{
    public $webhookData;
    public $timestamp;
    public $priority = 100;

    public function __construct($config = [])
    {
        parent::__construct($config);
        // Set timestamp when job is created
        $this->timestamp = $this->timestamp ?? microtime(true);
    }

    public function execute($queue): void
    {
        $params = $this->webhookData;
        $type = $params['type'] ?? '';
        $data = $params['data'] ?? [];

        // For video.asset.static_rendition.* events, data.id is the rendition id; use data.asset_id for the asset
        $assetId = (strpos($type, 'video.asset.static_rendition') === 0 && !empty($data['asset_id']))
            ? $data['asset_id']
            : ($data['id'] ?? null);

        if ($assetId) {
           $mutexKey = "mux-webhook-{$assetId}";
           $mutex = Craft::$app->getMutex();

           // Try to acquire the lock with a 60-second timeout
            if ($mutex->acquire($mutexKey, 60)) {
                try {
                    $config = Mux::$plugin->assets->muxConf();
                    $apiInstance = new MuxPhp\Api\AssetsApi(
                        new Client(),
                        $config
                    );
                    // Mux::info($params['type'], 'mux');

                    switch ($params['type']) {
                        case 'video.asset.ready':
                            // We need to make sure the status is set to ready.
                            Mux::$plugin->assets->updateAssetElementWithMuxAsset($params['data'], true);
                            //Mux::info(json_encode($params), 'mux');
                            break;
                        case 'video.asset.updated':
                            // Exclude static_renditions from this payload: video.asset.updated fires
                            // for general asset changes and may arrive before an async rendition
                            // deletion completes, reverting the local state. static_renditions is
                            // authoritative only from video.asset.static_renditions.* events.
                            $updatedData = $params['data'];
                            unset($updatedData['static_renditions']);
                            Mux::$plugin->assets->updateAssetElementWithMuxAsset($updatedData, true);
                            //Mux::info(json_encode($params), 'mux');
                            break;
                        case 'video.asset.deleted':
                            if (MuxAsset::findOne(['asset_id' => $params['data']['id']]) !== null) {
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
                            // Handle track processing errors by updating the asset with latest track data
                            $assetId = $params['data']['asset_id'];
                            $el = MuxAsset::findOne(['asset_id' => $assetId]);
                            if ($el !== null) {
                                $asset = $apiInstance->getAsset($assetId);
                                Mux::$plugin->assets->createOrUpdateMuxAsset($asset->getData());
                            }
                            Mux::error("Track processing error for asset {$assetId}: " . json_encode($params['data']['error']), 'mux');
                            break;
                        case 'video.asset.track.deleted':
                            //Mux::info(json_encode($params), 'mux');
                            break;
                        case 'video.upload.asset_created':
                            $el = MuxAsset::findOne(['asset_id' => $params['data']['asset_id']]);
                            if (!$el) {
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
                        case 'video.asset.static_rendition.created':
                            // Same as .preparing: upsert stub so UI shows the new rendition immediately
                            $resolution = $data['resolution'] ?? null;
                            if ($resolution !== null) {
                                $record = MuxAssetsRecord::find()->where(['asset_id' => $assetId])->one();
                                if ($record !== null) {
                                    $current = !empty($record->static_renditions)
                                        ? (is_array($record->static_renditions) ? $record->static_renditions : json_decode($record->static_renditions, true))
                                        : ['status' => 'preparing', 'files' => []];
                                    $files = $current['files'] ?? [];
                                    $found = false;
                                    foreach ($files as &$file) {
                                        if (($file['resolution'] ?? '') === $resolution) {
                                            $file['status'] = 'preparing';
                                            if (!empty($data['id'])) {
                                                $file['id'] = $data['id'];
                                            }
                                            $found = true;
                                            break;
                                        }
                                    }
                                    unset($file);
                                    if (!$found) {
                                        $stub = ['resolution' => $resolution, 'status' => 'preparing'];
                                        if (!empty($data['id'])) {
                                            $stub['id'] = $data['id'];
                                        }
                                        $files[] = $stub;
                                    }
                                    Mux::$plugin->assets->updateLocalStaticRenditions($assetId, [
                                        'status' => 'preparing',
                                        'files'  => array_values($files),
                                    ]);
                                }
                            }
                            break;
                        case 'video.asset.static_rendition.preparing':
                            $resolution = $data['resolution'];
                            $record = MuxAssetsRecord::find()->where(['asset_id' => $assetId])->one();
                            if ($record !== null) {
                                $current = !empty($record->static_renditions)
                                    ? (is_array($record->static_renditions) ? $record->static_renditions : json_decode($record->static_renditions, true))
                                    : ['status' => 'preparing', 'files' => []];
                                $files = $current['files'] ?? [];

                                // Upsert: update existing stub by resolution, or append
                                $found = false;
                                foreach ($files as &$file) {
                                    if (($file['resolution'] ?? '') === $resolution) {
                                        $file['status'] = 'preparing';
                                        $found = true;
                                        break;
                                    }
                                }
                                unset($file);
                                if (!$found) {
                                    $files[] = ['resolution' => $resolution, 'status' => 'preparing'];
                                }

                                Mux::$plugin->assets->updateLocalStaticRenditions($assetId, [
                                    'status' => 'preparing',
                                    'files'  => array_values($files),
                                ]);
                            }
                            break;
                        case 'video.asset.static_rendition.ready':
                            $rendition = $data;
                            $record = MuxAssetsRecord::find()->where(['asset_id' => $assetId])->one();
                            if ($record !== null) {
                                $current = !empty($record->static_renditions)
                                    ? (is_array($record->static_renditions) ? $record->static_renditions : json_decode($record->static_renditions, true))
                                    : ['status' => 'ready', 'files' => []];
                                $files = $current['files'] ?? [];

                                $fileEntry = [
                                    'id'         => $rendition['id'],
                                    'name'       => $rendition['name'] ?? null,
                                    'ext'        => $rendition['ext'] ?? null,
                                    'height'     => $rendition['height'] ?? null,
                                    'width'      => $rendition['width'] ?? null,
                                    'bitrate'    => $rendition['bitrate'] ?? null,
                                    'filesize'   => $rendition['filesize'] ?? null,
                                    'resolution' => $rendition['resolution'],
                                    'status'     => 'ready',
                                ];

                                // Upsert by resolution (stub may lack an id) or by rendition id
                                $found = false;
                                foreach ($files as &$file) {
                                    if (($file['resolution'] ?? '') === $rendition['resolution']
                                        || ($file['id'] ?? '') === $rendition['id']) {
                                        $file  = $fileEntry;
                                        $found = true;
                                        break;
                                    }
                                }
                                unset($file);
                                if (!$found) {
                                    $files[] = $fileEntry;
                                }

                                // Derive overall status: ready only if ALL files are ready
                                $allReady = !empty($files) && array_reduce(
                                    $files,
                                    fn(bool $carry, array $f) => $carry && ($f['status'] ?? '') === 'ready',
                                    true
                                );

                                Mux::$plugin->assets->updateLocalStaticRenditions($assetId, [
                                    'status' => $allReady ? 'ready' : 'preparing',
                                    'files'  => array_values($files),
                                ]);
                            }
                            break;
                        case 'video.asset.static_rendition.deleted':
                            // Use the webhook payload directly when present; otherwise remove the deleted rendition from local state
                            $deletedRenditionId = $data['id'];
                            $webhookRenditions = $data['static_renditions'] ?? null;

                            if (!empty($webhookRenditions) && !empty($webhookRenditions['files'] ?? [])) {
                                $activeFiles = array_values(array_filter(
                                    $webhookRenditions['files'],
                                    fn(array $f) => ($f['status'] ?? '') !== 'deleted'
                                ));
                                $newStaticRenditions = empty($activeFiles) ? null : [
                                    'status' => $webhookRenditions['status'],
                                    'files'  => $activeFiles,
                                ];
                            } else {
                                // Payload lacks full static_renditions: remove this rendition from current record
                                $record = MuxAssetsRecord::find()->where(['asset_id' => $assetId])->one();
                                $newStaticRenditions = null;
                                if ($record !== null && !empty($record->static_renditions)) {
                                    $current = is_array($record->static_renditions)
                                        ? $record->static_renditions
                                        : json_decode($record->static_renditions, true);
                                    $files = $current['files'] ?? [];
                                    $files = array_values(array_filter(
                                        $files,
                                        fn(array $f) => ($f['id'] ?? '') !== $deletedRenditionId
                                    ));
                                    $newStaticRenditions = empty($files) ? null : [
                                        'status' => $current['status'] ?? 'ready',
                                        'files'  => $files,
                                    ];
                                }
                            }

                            Mux::$plugin->assets->updateLocalStaticRenditions($assetId, $newStaticRenditions);
                            break;
                    }
                } catch (Throwable $e) {
                    Mux::error("Error handling Mux Webhook: {$e->getMessage()}", 'mux');
                } finally {
                    $mutex->release($mutexKey);
                }
            }
        } else {
            Mux::error("Asset ID not found in webhook data: " . json_encode($this->webhookData), 'mux');
        }
    }
    protected function defaultDescription(): string
    {
        return Craft::t('mux', 'Handling Mux Webhook');
    }
}
