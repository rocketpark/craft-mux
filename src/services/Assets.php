<?php

namespace rocketpark\mux\services;


use Craft;
use craft\db\Query;
use craft\elements\GlobalSet;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use Exception as GlobalException;
use Throwable;
use yii\base\Component;
use rocketpark\mux\Mux;
use GuzzleHttp\Client;
use InvalidArgumentException;
use MuxPhp;
use MuxPhp\ApiException;
use MuxPhp\Configuration;
use MuxPhp\Models\Asset;
use MuxPhp\Models\AssetResponse;
use MuxPhp\Models\ListAssetsResponse;
use MuxPhp\Models\Upload;
use Psr\Log\LogLevel;
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;
//use rocketpark\mux\models\Asset as MuxAsset;
use rocketpark\mux\models\MuxAsset as MuxAsset;
use rocketpark\mux\records\Assets as MuxAssetsRecord;
use rocketpark\mux\events\MuxAssetSyncEvent;
use rocketpark\mux\jobs\UpdateMuxAssetElement;
use yii\base\Exception;
use yii\base\InvalidArgumentException as BaseInvalidArgumentException;
use yii\base\InvalidConfigException;
use function json_encode;

/**                             
 * @property-read Assets $assets
 */

/**
 * Assets service
 */
class Assets extends Component
{

    public const EVENT_BEFORE_SYNCHRONIZE_MUX_ASSET = 'beforeSynchronizeMuxAsset';

    /**
     * Creates MUX API Configuration
     * @return Configuration 
     * @throws BaseInvalidArgumentException 
     */
    public function muxConf(): MuxPhp\Configuration
    {
        $settings = Mux::$settings;
        // Authentication Setup
        return MuxPhp\Configuration::getDefaultConfiguration()
            ->setUsername(App::parseEnv($settings->muxTokenId))
            ->setPassword(App::parseEnv($settings->muxTokenSecret));
    }

    /**
     * Builds a asset model from POST data.
     *
     * @return MuxAsset
     * @throws Exception
     */
    public function buildAssetFromPost(): MuxAsset
    {
        $request = Craft::$app->getRequest();
        $params = $request->getParam('asset');
       
        $asset = new MuxAsset();
        $asset->id = isset($params['id']) ? $params['id'] : null; 
        $asset->asset_id = $params['asset_id'];
        $asset->created_at = $params['created_at'];
        $asset->asset_status = isset($params['asset_status']) ? $params['asset_status'] : "";
        $asset->duration = isset($params['duration']) ? $params['duration'] :  "";
        $asset->max_stored_resolution = isset($params['max_stored_resolution']) ? $params['max_stored_resolution'] : "";
        $asset->max_stored_frame_rate = isset($params['max_stored_frame_rate']) ? $params['max_stored_frame_rate'] : "";
        $asset->aspect_ratio = isset($params['aspect_ratio']) ? $params['aspect_ratio'] : "";
        $asset->playback_ids = isset($params['playback_ids']) ? $params['playback_ids'] : [];
        $asset->tracks = isset($params['tracks']) ? $params['tracks'] : [];
        $asset->errors = isset($params['errors']) ? $params['errors'] : "";
        $asset->per_title_encode = isset($params['per_title_encode']) ? $params['per_title_encode'] : null;
        $asset->upload_id = isset($params['upload_id']) ? $params['upload_id'] : "";
        $asset->is_live = isset($params['is_live']) ? $params['is_live'] : "";
        $asset->passthrough = isset($params['passthrough']) ? $params['passthrough'] : "";
        $asset->live_stream_id = isset($params['live_stream_id']) ? $params['live_stream_id'] : "";
        $asset->master = isset($params['master']) ? $params['master'] : [];
        $asset->master_access = isset($params['master_access']) ? $params['master_access'] : "";
        $asset->mp4_support = isset($params['mp4_support']) ? $params['mp4_support'] : "";
        $asset->source_asset_id = isset($params['source_asset_id']) ? $params['source_asset_id'] : "";
        $asset->normalize_audio = isset($params['normalize_audio']) ? $params['normalize_audio'] : "";
        $asset->static_renditions = isset($params['static_renditions']) ? $params['static_renditions'] : [];
        $asset->recording_times = isset($params['recording_times']) ? $params['recording_times'] : [];
        $asset->non_standard_input_reasons = isset($params['non_standard_input_reasons']) ? $params['non_standard_input_reasons'] : "";
        $asset->test = isset($params['test']) ? $params['test'] : "";

        return $asset;
    }

    /**
     * Build Asset Element From Post
     * @return MuxAssetElement 
     * @throws InvalidConfigException 
     */
    public function buildAssetElementFromPost(): MuxAssetElement
    {
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();

        $asset = MuxAssetElement::findOne(['asset_id' => $params['asset_id']]);

        if(!$asset) {
            $asset = new MuxAssetElement();
        }
        
        $asset->title = $params['title'];
        $asset->asset_id = $params['asset_id'];
        $asset->created_at = $params['created_at'];
        $asset->asset_status = isset($params['asset_status']) ? $params['asset_status'] : "";
        $asset->duration = isset($params['duration']) ? $params['duration'] :  "";
        $asset->max_stored_resolution = isset($params['max_stored_resolution']) ? $params['max_stored_resolution'] : "";
        $asset->max_stored_frame_rate = isset($params['max_stored_frame_rate']) ? $params['max_stored_frame_rate'] : "";
        $asset->aspect_ratio = isset($params['aspect_ratio']) ? $params['aspect_ratio'] : "";
        $asset->playback_ids = isset($params['playback_ids']) ? $params['playback_ids'] : [];
        $asset->tracks = isset($params['tracks']) ? $params['tracks'] : [];
        $asset->errors = isset($params['errors']) ? $params['errors'] : "";
        $asset->per_title_encode = isset($params['per_title_encode']) ? $params['per_title_encode'] : null;
        $asset->upload_id = isset($params['upload_id']) ? $params['upload_id'] : "";
        $asset->is_live = isset($params['is_live']) ? $params['is_live'] : "";
        $asset->passthrough = isset($params['passthrough']) ? ($params['passthrough'] == $params['asset_id'] ? $params['title'] : $params['passthrough']) : $params['title'];
        $asset->live_stream_id = isset($params['live_stream_id']) ? $params['live_stream_id'] : "";
        $asset->master = isset($params['master']) ? $params['master'] : [];
        $asset->master_access = isset($params['master_access']) ? $params['master_access'] : "";
        $asset->mp4_support = isset($params['mp4_support']) ? $params['mp4_support'] : "";
        $asset->source_asset_id = isset($params['source_asset_id']) ? $params['source_asset_id'] : "";
        $asset->normalize_audio = isset($params['normalize_audio']) ? $params['normalize_audio'] : "";
        $asset->static_renditions = isset($params['static_renditions']) ? $params['static_renditions'] : [];
        $asset->recording_times = isset($params['recording_times']) ? $params['recording_times'] : [];
        $asset->non_standard_input_reasons = isset($params['non_standard_input_reasons']) ? $params['non_standard_input_reasons'] : "";
        $asset->test = isset($params['test']) ? $params['test'] : "";

        return $asset;
    }


    /**
     * Saves a mux asset.
     *
     * @param MuxAssetElement $element
     * @param bool $runValidation
     * @return bool
     * @throws Throwable
     */
    public function saveAsset(MuxAssetElement $element)
    {
        if (!Craft::$app->getElements()->saveElement($element)) {
            return true;
        }
    
        return false;
    }

    /**
     * Delete Asset Record
     * @param string $id 
     * @return bool 
    */
    public function deleteAsset(String $id): bool
    {
       $element = MuxAssetElement::find(['assetId' => $id])->one();
    
        if (!$element) {
            return false;
        }
    
        try {
            return (bool) Craft::$app->getElements()->deleteElement($element);
        } catch (Throwable $e) {
            throw new Exception("Unable to delete Asset Record: {$e->getMessage()}");
            return false;
        }
    }

    

    /**
     * Pull Paginated MUX Assets
     * @param null|int $limit 
     * @param null|int $page 
     * @return ListAssetsResponse 
     * @throws ApiException 
     * @throws InvalidArgumentException 
     */
    public function useMuxAssets(?int $limit = 20, ?int $page = 1): ListAssetsResponse
    {
        $config = Mux::$plugin->assets->muxConf();
        // API Client Initialization
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );

        return $apiInstance->listAssets($limit, $page);
    }

    // public function useAssets(?int $limit = 20, ?int $page = 1)
    // {
    //     $records = MuxAssetsRecord::find()->limit($limit)->offset($limit * ($page - 1))->all();

    // }

    /**
     * Create MUX Asset
     *  - Uses a url to create asset
     *  - TODO: allow interface to accept urls
     * @param mixed $data
     * @return AssetResponse
     * @throws Exception
     */
    public static function createMuxAsset(mixed $data): AssetResponse
    {
        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );

        $create_asset_request = json_decode('{"input":[{"url":' . $data['url'] . '}],"playback_policy":["public"]}', true);

        try {
            $result = $apiInstance->createAsset($create_asset_request);
            Mux::info("Asset created successfully: ". $result, 'mux');
        } catch (\Exception $e) {
            throw new Exception("Unable to retrieve video from Mux: {$e->getMessage()}");
        }

        return $result;
    }

    /**
     * Upload Asset to MUX
     * @return string|false 
     * @throws Exception 
     * @throws ApiException 
     * @throws InvalidArgumentException 
     */
    public static function uploadMuxAsset()
    {

        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\DirectUploadsApi(
            new Client(),
            $config
        );

        $createAssetRequest = new MuxPhp\Models\CreateAssetRequest(["playback_policy" => [MuxPhp\Models\PlaybackPolicy::_PUBLIC]]);
        $createUploadRequest = new MuxPhp\Models\CreateUploadRequest(["timeout" => 3600, "new_asset_settings" => $createAssetRequest, "cors_origin" => UrlHelper::siteUrl()]);

        $upload = $apiInstance->createDirectUpload($createUploadRequest);

        return json_encode($upload->getData());
    }

    /**
     * Update MUX Asset
     * @param MuxAsset $asset 
     * @return void 
     */
    public static function updateMuxAsset(MuxAsset $asset)
    {
        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );

        $update_asset_request = ['passthrough' => $asset->passthrough];

        try {
            $result = $apiInstance->updateAsset($asset->asset_id, $update_asset_request);
            return $result->getData();
        } catch (Exception $e) {
            throw new Exception("Exception when calling AssetsApi->updateAsset: {$e->getMessage()}");
        }

    }



    /**
     * Get MUX Upload by ID
     * @param null|string $id 
     * @return Upload|null|void 
     */
    public static function getUploadById(?string $id)
    {
        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\DirectUploadsApi(
            new Client(),
            $config
        );

        try {
            $result = $apiInstance->getDirectUpload($id);
            return $result->getData();
        } catch (Exception $e) {
            throw new Exception("Exception when calling DirectUploadsApi->getDirectUpload: {$e->getMessage()}");
        }
    }

    /**
     * Get MUX Asset by ID
     * @param null|string $id 
     * @return Asset|null|void 
     */
    public static function getMuxAssetById(?string $id)
    {
        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );

        try {
            $result = $apiInstance->getAsset($id);
            if ($result->getData()->getStatus() != 'ready') {
                //print("    waiting for asset to become ready...\n");
                while (true) {
                    // ------ get-asset ------
                    $waitingAsset = $apiInstance->getAsset($result->getData()->getId());
                    assert($waitingAsset->getData()->getId() != null);
                    assert($waitingAsset->getData()->getId() == $result->getData()->getId());
                    if ($waitingAsset->getData()->getStatus() != 'ready') {
                        //print("    still waiting for asset to become ready...\n");
                        sleep(1);
                    } else {
                        // ------ get-asset-input-info ------
                        $assetInputInfo = $apiInstance->getAssetInputInfo($result->getData()->getId());
                        assert($assetInputInfo->getData() != null);
                        break;
                    }
                }
            }
            //Mux::info("Getting Mux Asset from MUX (mux\services\assets\getMuxAssetById): " . $id, 'mux');
            return $result->getData();
        } catch (\Exception $e) {
            throw new Exception("Exception when calling AssetsApi->getAsset: {$e->getMessage()} ");
            return false;
        }
    }
    
    /**
     * Get Mux Asset
     * @param null|string $id 
     * @return array 
     * @throws Exception 
     */
    public static function getMuxAsset(?string $id): array
    {
        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );

        try {
            $result = $apiInstance->getAsset($id);
            return json_decode(json_encode($result->getData()), true);
        } catch (\Exception $e) {
            throw new Exception("Exception when calling AssetsApi->getAsset: {$e->getMessage()} ");
            return false;
        }
        
    }

    /**
     * Delete MUX Asset by ID
     * @param null|string $id 
     * @return true[]|void 
     */
    public function deleteAssetById(?string $id)
    {
        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );

        try {
            $apiInstance->deleteAsset($id);
            return ['success' => true];
        } catch (\Exception $e) {
            throw new Exception("Exception when calling AssetsApi->deleteAsset: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    /**
     * Add MUX Asset Track by Asset ID
     * @param null|string $id 
     * @param null|array $track
     * @return true[]|void 
     */
    public function addMuxAssetTrackById(?string $id, ?array $track)
    {
        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );

        try {
            $result = $apiInstance->createAssetTrack($id, $track);
            return true;
        } catch (\Exception $e) {
            throw new Exception("Exception when calling AssetsApi->createAssetTrack: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Delete MUX Asset Track by ID
     * @param null|string $id 
     * @param null|string $track_id 
     * @return MuxAsset
     * @throws Exception 
     */
    public function deleteMuxAssetTrackById(?string $id, ?string $track_id)
    {
        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );

        try {
            $apiInstance->deleteAssetTrack($id, $track_id);
            return true;
            
        } catch (\Exception $e) {
            throw new Exception("Exception when calling AssetsApi->deleteAssetTrack: {$e->getMessage()}");
            return false;
        }
    }


    /**
     * Update Asset Element With Mux Asset By ID
     * @param string $id // Mux Asset ID || the element.asset_id
     * @param array $muxAssetArray 
     * @return bool
     */
    public function updateAssetElementWithMuxAssetById(?string $id)
    {

        $muxAsset =  $this->getMuxAsset($id);

        $elements = MuxAssetElement::find()
        ->asset_id($id)
        ->limit(1)
        ->unique()
        ->all();

        if(!$elements) {
            return false;
        }

        foreach($elements as $element) {
            foreach ($muxAsset as $key => $value) {
                if($key == 'id') {
                    $element->asset_id = $value;
                    continue;
                } else if($key == 'status') {
                    $element->asset_status = $value;
                    continue;
                }
                $element[$key] = $value;
            }

            try {    
                $this->saveAsset($element);
                return true;
                
            } catch (\Exception $e) {
                throw new Exception("Exception when calling updating element: {$e->getMessage()}");
                return false;
            }
        }
        
    }

    /**
     * Update Asset Element With Mux Asset
     * @param array $muxAssetArray 
     * @return bool
     */
    public function updateAssetElementWithMuxAsset(?array $muxAssetArray)
    {

        $muxAsset =  $muxAssetArray;

        $elements = MuxAssetElement::find()
        ->asset_id($muxAsset['id'])
        ->limit(1)
        ->unique()
        ->all();

        if(!$elements) {
            return false;
        }

        foreach($elements as $element) {
            foreach ($muxAsset as $key => $value) {
                if($key == 'id') {
                    $element->asset_id = $value;
                    continue;
                } else if($key == 'status') {
                    $element->asset_status = $value;
                    continue;
                } else if($key == 'passthrough') {
                    if($element->title != $value) {
                        $element->title = $value;
                    }
                }
                $element[$key] = $value;
            }

            try {    
                $this->saveAsset($element);
                return true;
                
            } catch (\Exception $e) {
                throw new Exception("Exception when calling updating element: {$e->getMessage()}");
                return false;
            }
        }
        
    }

    public function createOrUpdateMuxAsset(Asset $asset) 
    {
        
        $attributes = [
            //"title" => $asset->getPassthrough(),
            "asset_id" => $asset->getId(),
            "created_at" => $asset->getCreatedAt(),
            "asset_status" => $asset->getStatus(),
            "duration" => $asset->getDuration(),
            "max_stored_resolution" => $asset->getMaxStoredResolution(),
            "max_stored_frame_rate" => $asset->getMaxStoredFrameRate(),
            "aspect_ratio" => $asset->getAspectRatio(),
            "playback_ids" => !empty($asset->getPlaybackIds()) ? array_map(function ($playbackId) {
                return [
                    'id' => $playbackId->getId(),
                    'policy' => $playbackId->getPolicy()
                ];
            }, $asset->getPlaybackIds()) : [],
            "tracks" => !empty($asset->getTracks()) ?  array_map(function ($track) {
                return [
                    'id' => $track->getId(),
                    'type' => $track->getType(),
                    'text_source' => $track->getTextSource(),
                    'text_type' => $track->getTextType(),
                    'language_code' => $track->getLanguageCode(),
                    'name' => $track->getName(),
                    'closed_captions' => $track->getClosedCaptions(),
                    'duration' => $track->getDuration(),
                    'max_width' => $track->getMaxWidth(),
                    'max_height' => $track->getMaxHeight(),
                    'max_frame_rate' => $track->getMaxFrameRate(),
                    'max_channel_layout' => $track->getMaxChannelLayout(),
                ];
            }, $asset->getTracks()) : [],
            "errors" => $asset->getErrors(),
            "per_title_encode" => "",
            "upload_id" => $asset->getUploadId(),
            "is_live" => $asset->getIsLive(),
            "passthrough" => $asset->getPassthrough(),
            "live_stream_id" => $asset->getLiveStreamId(),
            "master" => $asset->getMaster(),
            "master_access" => $asset->getMasterAccess(),
            "mp4_support" => $asset->getMp4Support(),
            "source_asset_id" => $asset->getSourceAssetId(),
            "normalize_audio" => $asset->getNormalizeAudio(),
            "static_renditions" => $asset->getStaticRenditions(),
            "recording_times" => $asset->getRecordingTimes(),
            "non_standard_input_reasons" => $asset->getNonStandardInputReasons(),
            "test" => $asset->getTest()
        ];

        /** @var MuxAssetRecord $assetData */
        $assetRecord = MuxAssetsRecord::find()->where(['asset_id' => $asset['id']])->one();
        if($assetRecord) {
            $assetRecord->setAttributes($attributes, false);
            $assetRecord->save();
        }

        // Find the mux asset element or create one
        /** @var MuxAssetElement|null $muxAssetElement */
        $muxAssetElement = MuxAssetElement::find()
            ->asset_id($asset['id'])
            ->status(null)
            ->one();
            
        if ($muxAssetElement === null) {
            /** @var MuxAssetElement $muxAssetElement */
            $muxAssetElement = new muxAssetElement();
            $muxAssetElement->title = !empty($asset['passthrough']) ? $asset['passthrough'] : $asset['id'];
        } else {
            $muxAssetElement->title = $asset['passthrough'];
        }

        // Set attributes on the element to emulate it having been loaded with JOINed data:
        $muxAssetElement->setAttributes($attributes, false);

        $event = new MuxAssetSyncEvent([
            'element' => $muxAssetElement,
            'source' => $asset
        ]);

        $this->trigger(self::EVENT_BEFORE_SYNCHRONIZE_MUX_ASSET, $event);

        if (!$event->isValid) {
            Mux::info("Synchronization of MUX Asset ID #{$asset['id']} was stopped by a plugin.", 'mux');

            return false;
        }

        if (!Craft::$app->getElements()->saveElement($muxAssetElement)) {
            Mux::error("Failed to synchronize MUX Asset ID #{$asset['id']}.", 'mux');

            return false;
        }

        return true;
    }


    public function syncAllMuxAssets() :void
    {
        $limit = 50;
        $page = 1;

        $config = Mux::$plugin->assets->muxConf();
        // API Client Initialization
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );

        do {
            $assets = $apiInstance->listAssets($limit, $page)->getData();

            // This helps to prevent a loop occuring with webhook enabled
            if(empty($assets)) {
                $this->deleteOrphanedMuxElements($assets);
                break;
            }

            foreach ($assets as $asset) {
                $this->createOrUpdateMuxAsset($asset);
                // Craft::$app->getQueue()->push(new UpdateMuxAssetElement([
                //     'description' => Craft::t('mux', 'Updating MUX asset “{id}”', [
                //     'id' => $asset->getId(),
                //     ]),
                //     'asset_id' => $asset->getId(),
                // ]));
            }

            // Remove any mux assets elements that are no longer in MUX just in case.
            $this->deleteOrphanedMuxElements($assets);
           
            $page++;
        } while (!empty($assets) && count($assets) >= $limit);
    }

    /**
     * Delete all orphaned Mux Asset Elements
     * @param array $assets 
     * @return void 
     * @throws BaseInvalidArgumentException 
     * @throws GlobalException 
     * @throws InvalidConfigException 
     */
    public function deleteOrphanedMuxElements(Array $assets): void
    {
        $muxAssetIds = ArrayHelper::getColumn($assets, 'id');
        $deletableMuxAssetElements = MuxAssetElement::find()->asset_id(['not', $muxAssetIds])->all();

        foreach ($deletableMuxAssetElements as $element) {
            Craft::$app->elements->deleteElement($element);
        }
    }
}
