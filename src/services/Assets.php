<?php

namespace rocketpark\mux\services;

use Craft;
use craft\db\Query;
use craft\elements\GlobalSet;
use craft\errors\ElementNotFoundException;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use DomainException;
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
use MuxPhp\Models\AssetMetadata;
use MuxPhp\Models\AssetResponse;
use MuxPhp\Models\ListAssetsResponse;
use MuxPhp\Models\Upload;
use Psr\Log\LogLevel;
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;
use rocketpark\mux\models\MuxAsset as MuxAsset;
use rocketpark\mux\records\Assets as MuxAssetsRecord;
use rocketpark\mux\jobs\CleanupMuxAssetsJob;
use rocketpark\mux\records\MuxVolume as MuxVolumeRecord;
use rocketpark\mux\records\MuxFolder as MuxFolderRecord;
use rocketpark\mux\events\MuxAssetEvent;
use rocketpark\mux\events\MuxAssetUploadEvent;
use rocketpark\mux\events\MuxAssetMoveEvent;
use rocketpark\mux\events\MuxAssetSyncEvent;
use yii\base\Exception;
use yii\base\InvalidArgumentException as BaseInvalidArgumentException;
use yii\base\InvalidConfigException;
use function json_encode;


/**                             
 * @property-read Assets $assets
 */
class Assets extends Component
{

    public const EVENT_BEFORE_SYNCHRONIZE_MUX_ASSET = 'beforeSynchronizeMuxAsset';
    public const EVENT_BEFORE_CREATE_ASSET = 'beforeCreateAsset';
    public const EVENT_AFTER_CREATE_ASSET = 'afterCreateAsset';
    public const EVENT_BEFORE_UPDATE_ASSET = 'beforeUpdateAsset';
    public const EVENT_AFTER_UPDATE_ASSET = 'afterUpdateAsset';
    public const EVENT_BEFORE_UPLOAD_ASSET = 'beforeUploadAsset';
    public const EVENT_AFTER_UPLOAD_ASSET = 'afterUploadAsset';
    public const EVENT_BEFORE_DELETE_ASSET = 'beforeDeleteAsset';
    public const EVENT_AFTER_DELETE_ASSET = 'afterDeleteAsset';
    public const EVENT_BEFORE_MOVE_ASSET = 'beforeMoveAsset';
    public const EVENT_AFTER_MOVE_ASSET = 'afterMoveAsset';

    private const META_KEY = 'meta';

    /**
     * Default MuxAsset Attributes
     * @var string[]
     */
    private $defaultAttributes = [
        'title' => "",
        'volumeId' => null,
        'folderId' => null,
        'asset_id' => "",
        'created_at' => "",
        'asset_status' => "",
        'duration' => "",
        'max_stored_resolution' => "",
        'max_stored_frame_rate' => "",
        'resolution_tier' => "",
        'max_resolution_tier' => "",
        'encoding_tier' => "",
        'aspect_ratio' => "",
        'playback_ids' => [],
        'tracks' => [],
        'errors' => "",
        'per_title_encode' => null,
        'upload_id' => "",
        'is_live' => "",
        'passthrough' => "",
        'live_stream_id' => "",
        'master' => [],
        'master_access' => "",
        'mp4_support' => "",
        'source_asset_id' => "",
        'normalize_audio' => "",
        'static_renditions' => [],
        'recording_times' => [],
        'non_standard_input_reasons' => [],
        'test' => "",
        'ingest_type' => "",
        'meta' => [],
    ];

    /**
     * Hydrate Asset
     * @param array $params 
     * @param Asset|MuxAssetElement|MuxAsset $asset 
     * @return Asset|MuxAssetElement|MuxAsset 
     */
    private function hydrateAsset(array $params, Asset|MuxAssetElement|MuxAsset $asset): Asset|MuxAssetElement|MuxAsset
    {
        foreach ($params as $key => $value) {
            if ($key === self::META_KEY) {
                $asset->$key = $this->createMetaData($params['title'] ?? '', $params['id'] ?? '', '');
            } else if ($key === 'volumeId' || $key === 'folderId') {
                $asset->$key = is_null($value) ? null : (int)$value;
            } else if ($key === 'static_renditions' || $key === 'mp4_support') {
                // Skip these fields as they're handled separately in the controller
                // to avoid type conflicts and ensure proper API integration
                continue;
            } else {
                $asset->$key = $value;
            }
        }

        return $asset;
    }

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
     * Get valid request parameters for asset hydration
     * @param array $requestParams
     * @return array
     */
    private function getValidRequestParams(array $requestParams): array
    {
        return array_intersect_key($requestParams, $this->defaultAttributes);
    }

    /**
     * Builds a asset model from POST data.
     *
     * @return MuxAssetElement
     * @throws Exception
     */
    public function buildAssetFromPost(): MuxAssetElement
    {
        $request = Craft::$app->getRequest();
        $requestParams = $request->getBodyParams();

        $asset = new MuxAssetElement();

         // Only include valid request parameters
        $validRequestParams = $this->getValidRequestParams($requestParams);
        $params = array_merge($this->defaultAttributes, $validRequestParams);

        // If the volumeId is set in the request, update the asset's volumeId.
        if (isset($requestParams['volumeId'])) {
            // If requestParams['volumeId'] is different from the asset's volumeId, update the asset's volumeId.
            if ($requestParams['volumeId'] !== $asset->volumeId) {
                $asset->volumeId = $requestParams['volumeId'];
            }
        }

        // If the folderId is set in the request, update the asset's folderId.
        if (isset($requestParams['folderId'])) {
            // If requestParams['folderId'] is different from the asset's folderId, update the asset's folderId.
            if ($requestParams['folderId'] !== $asset->folderId) {
                $asset->folderId = $requestParams['folderId'];
            }
        }

        return $this->hydrateAsset($params, $asset);
    }

    /**
     * Build Asset Element From Post
     * @return MuxAssetElement 
     * @throws InvalidConfigException 
     */
    public function buildAssetElementFromPost(): MuxAssetElement
    {
        $request = Craft::$app->getRequest();
        $requestParams = $request->getBodyParams();

        $asset = MuxAssetElement::findOne(['asset_id' => $requestParams['asset_id']]);

        if(!$asset) {
            $asset = new MuxAssetElement();
        }

        // Only include valid request parameters
        // $validRequestParams = $this->getValidRequestParams($requestParams);
        // $params = array_merge($this->defaultAttributes, $validRequestParams);

        // Handle title (Craft element field)
        if (isset($requestParams['title'])) {
            $asset->title = $requestParams['title'];
        }

        // Handle volumeUid separately since it's not in defaultAttributes
        if (isset($requestParams['volumeUid'])) {
            $volume = MuxVolumeRecord::findOne(['uid' => $requestParams['volumeUid']]);
            $asset->volumeId = $volume ? $volume->id : null;
        }

        // Handle folderId separately since it's not in defaultAttributes
        if (isset($requestParams['folderId'])) {
            $asset->folderId = is_null($requestParams['folderId']) ? null : (int)$requestParams['folderId'];
        }

        if(isset($requestParams['static_renditions']) && $requestParams['static_renditions'] !== 'none') {
            $asset->static_renditions = [];
        }

        if(isset($requestParams['mp4_support']) && $requestParams['mp4_support'] !== 'none') {
            $asset->mp4_support = $requestParams['mp4_support'];
        }

        $asset->meta = $this->createMetaData($requestParams['title'] ?? '', is_null($asset->id) ? $requestParams['id'] ?? '' : $asset->id, '');

        return $asset;
        //return $this->hydrateAsset($params, $asset);
    }


    /**
     * Saves a mux asset.
     *
     * @param MuxAssetElement $element
     * @return bool
     * @throws Throwable
     */
    public function saveAsset(MuxAssetElement $element): bool
    {
        $mutexKey = "mux-asset-{$element->asset_id}";
        $mutex = Craft::$app->getMutex();

        // Try to acquire the lock with a 30-second timeout
        if (!$mutex->acquire($mutexKey, 30)) {
            Mux::warning("Could not acquire mutex lock for asset {$element->asset_id}", 'mux');
            return false;
        }

        try {

            $isNew = !$element->id;
            
            // Fire before event
            $event = new MuxAssetEvent([
                'asset' => $element,
                'isNew' => $isNew,
            ]);
            
            $this->trigger($isNew ? self::EVENT_BEFORE_CREATE_ASSET : self::EVENT_BEFORE_UPDATE_ASSET, $event);
            
            if ($event->isValid === false) {
                return false;
            }

            // Log the attributes of the element for debugging
            //Mux::info('Saving asset with attributes: ' . json_encode($element->getAttributes()), 'mux');

            $success = Craft::$app->getElements()->saveElement($element);
            
            if ($success) {
                Mux::info("Asset saved successfully" . $element->id . " : " . $element->asset_id, 'mux');
                // Fire after event
                $this->trigger($isNew ? self::EVENT_AFTER_CREATE_ASSET : self::EVENT_AFTER_UPDATE_ASSET, $event);
            }

            return $success;
        } catch (Throwable $e) {
            Mux::error("Error saving asset {$element->asset_id}: {$e->getMessage()}", 'mux');
            return false;
        } finally {
            $mutex->release($mutexKey);
        }
        
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
        
        // Fire before delete event
        $event = new MuxAssetEvent([
            'asset' => $element,
            'isNew' => false,
        ]);
        
        $this->trigger(self::EVENT_BEFORE_DELETE_ASSET, $event);
        
        if ($event->isValid === false) {
            return false;
        }
    
        try {
            $success = (bool) Craft::$app->getElements()->deleteElement($element);
            
            if ($success) {
                // Fire after delete event
                $this->trigger(self::EVENT_AFTER_DELETE_ASSET, $event);
            }
            
            return $success;
        } catch (Throwable $e) {
            throw new Exception("Unable to delete Asset Record: {$e->getMessage()}");
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
        $settings = Mux::$settings;
        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );

        $policy = Mux::$plugin->assets->getPlaybackPolicy();
        $create_asset_request = json_decode(sprintf('{"input":[{"url":"%s","generated_subtitles": [{"language_code": "en","name": "English CC"}]}],"playback_policy":["%s"],"max_resolution_teir":"%s"}', $data['url'], $policy, App::parseEnv($settings->maxResolutionTier)), true);

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
     * @param null|string $passthrough
     * @return string|false 
     * @throws Exception 
     * @throws ApiException 
     * @throws InvalidArgumentException 
     */
    public static function uploadMuxAsset(string $title, ?string $volumeUid, ?string $folderId)
    {
        $service = Mux::$plugin->assets;
        
        // Fire before upload event
        $event = new MuxAssetUploadEvent([
            'title' => $title,
            'volumeUid' => $volumeUid,
            'folderId' => $folderId,
        ]);
        
        $service->trigger(self::EVENT_BEFORE_UPLOAD_ASSET, $event);
        
        if ($event->isValid === false) {
            return false;
        }

        $settings = Mux::$settings;
        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\DirectUploadsApi(
            new Client(),
            $config
        );

        $policy = Mux::$plugin->assets->getPlaybackPolicy();
        
        $subtitles = new MuxPhp\Models\AssetGeneratedSubtitleSettings(["language_code" => "en", "name" => "English CC"]);
        $inputSettings = new MuxPhp\Models\InputSettings(["generated_subtitles" => [$subtitles]]);
        $staticRenditions = [];

        if ($settings->staticRenditions !== 'none') {
            $staticRenditions[] = new MuxPhp\Models\CreateStaticRenditionRequest([
                'resolution' => $settings->staticRenditions,
            ]);
        }

        $passthrough = [];

        if($volumeUid) {
            $volume = MuxVolumeRecord::findOne(['uid' => $volumeUid]);
            $passthrough['volumeId'] = $volume ? (int)$volume->id : null;
        }

        if($folderId) {
            $passthrough['folderId'] = (int)$folderId;
        }

        $createAssetRequest = new MuxPhp\Models\CreateAssetRequest([
            "inputs" => [$inputSettings],
            "playback_policy" => [$policy],
            "max_resolution_tier" => $settings->maxResolutionTier,
            "mp4_support" => $settings->mp4Support, //-- DEPRECATED
            "static_renditions" => $staticRenditions,
            "passthrough" => json_encode($passthrough),
            "meta" => new MuxPhp\Models\AssetMetadata([
                "title" => $title,
                "external_id" => '',
                "creator_id" => '',
            ])
        ]);

        $createUploadRequest = new MuxPhp\Models\CreateUploadRequest(["timeout" => 3600, "new_asset_settings" => $createAssetRequest, "cors_origin" => UrlHelper::siteUrl()]);

        $upload = $apiInstance->createDirectUpload($createUploadRequest);
        $uploadData = json_encode($upload->getData());
        
        // Fire after upload event
        $event->uploadData = json_decode($uploadData, true);
        $service->trigger(self::EVENT_AFTER_UPLOAD_ASSET, $event);
        
        return $uploadData;
    }

    /**
     * Update MUX Asset
     * @param array $params
     * @return void 
     */
    public static function updateMuxAsset(array $params)
    {
        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );
        $update_asset_request = [
            'passthrough' => $params['passthrough'],
            'meta' => [
                'title' => $params['meta']['title'],
                'external_id' => (string) $params['meta']['external_id'],
                'creator_id' => (string) $params['meta']['creator_id'],
            ]
        ];

        try {
            $result = $apiInstance->updateAsset($params['asset_id'], $update_asset_request);
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
            return $result->getData();
            /*
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
            }*/
            //Mux::info("Getting Mux Asset from MUX (mux\services\assets\getMuxAssetById): " . $id, 'mux');
            
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
    public static function getMuxAsset(?string $id): array|bool
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
     * Delete a Mux asset by ID.
     * Returns true if deleted or already absent (404).
     * Returns false for non-retryable failures.
     * For retryable errors (429/5xx), throws so the caller can back off & retry.
     */
    public function deleteAssetById(?string $id, ?MuxPhp\Api\AssetsApi $api = null): bool
    {
        if (empty($id)) return true;

        $api ??= new MuxPhp\Api\AssetsApi(new Client(), $this->muxConf());

        try {
            $api->deleteAsset($id);
            Mux::info("Deleted Mux asset via API: {$id}.", 'mux');
            return true;
        } catch (\MuxPhp\ApiException $e) {
            $code = $e->getCode();
            if ($code === 404) {
                Mux::info("Mux asset {$id} not found (404); treating as already deleted.", 'mux');
                return true;
            }
            if ($code === 429 || $code >= 500) {
                throw $e; // let the job retry inside its loop
            }
            Mux::error("Non-retryable error deleting {$id}: HTTP {$code} {$e->getMessage()}", 'mux');
            return false;
        }
    }

    /**
     * Delete MUX Asset by Volume ID
     * @param int $volumeId
     * @return bool
     */
    public function deleteAssetByVolumeId(int $volumeId): bool
    {
        $assets = MuxAssetElement::find()->volumeId($volumeId)->all();
        foreach($assets as $asset) {
            $this->deleteAsset($asset->id);
        }

        return true;
    }

    /**
     * Delete MUX Asset by Folder ID
     * @param int $folderId
     * @return bool
     */
    public function deleteAssetByFolderId(int $folderId): bool
    {
        $assets = MuxAssetElement::find()->folderId($folderId)->all();
        foreach($assets as $asset) {
            $this->deleteAsset($asset->id);
        }

        return true;
    }


    /**
     * Queue cleanup of Mux assets.
     * @param array $assetIds
     * @return void
     */
    public function queueCleanupMuxAssets(array $assetIds): void
    {
        if (empty($assetIds)) {
            Mux::info('queueCleanupMuxAssets called with empty array; nothing to enqueue.', 'mux');
            return;
        }
        $count = count($assetIds);
        Mux::info("Enqueuing CleanupMuxAssetsJob for {$count} asset(s).", 'mux');

        Craft::$app->getQueue()->push(new CleanupMuxAssetsJob([
            'assetIds' => array_values(array_unique($assetIds)),
            'description' => "Cleanup {$count} Mux asset(s)"
        ]));
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
     * @return Boolean
     * @throws Exception 
     */
    public function deleteMuxAssetTrackById(?string $id, ?string $track_id): bool
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
    public function updateAssetElementWithMuxAssetById(?string $id, bool $isWebhookUpdate = false): bool
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
            // Mark as webhook update to prevent sync back to Mux
            if ($isWebhookUpdate) {
                $element->isWebhookUpdate = true;
            }
            foreach ($muxAsset as $key => $value) {
                if(array_key_exists($key, $this->defaultAttributes) || $key === 'status') {
                    if($key == 'id') {
                        $element->asset_id = $value;
                        continue;
                    } else if($key == 'status') {
                        $element->asset_status = $value;
                        continue;
                    }

                    $element->$key = $value;
                }
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
     * Has Changes
     * @param array $muxAsset
     * @param array $elements
     * @return bool
     */
    public function hasChanges(array $muxAsset, array $elements): bool
    {
        foreach ($elements as $element) {
            foreach ($muxAsset as $key => $value) {
                // Skip if the key isn't relevant
                if (!array_key_exists($key, $this->defaultAttributes) && $key !== 'status') {
                    continue;
                }
    
                switch ($key) {
                    case 'status':
                        if ($element->asset_status !== $value) {
                            return true;
                        }
                        break;
    
                    case 'id':
                        if ($element->asset_id !== $value) {
                            return true;
                        }
                        break;
    
                    case 'passthrough':
                        if ($element->passthrough !== $value) {
                            return true;
                        }
                        break;
    
                    case 'mp4_support':
                        if ($element->mp4_support !== $value) {
                            return true;
                        }
                        break;
    
                    case 'static_renditions':
                        // Normalize data before comparing
                        $normalizedElement = $this->_normalizeData((array)$element->static_renditions);
                        $normalizedValue = $this->_normalizeData((array)$value);
    
                        if ($normalizedElement !== $normalizedValue) {
                            return true;
                        }
                        break;

                    case 'meta':
                        // Normalize data before comparing
                        $normalizedElement = $this->_normalizeData((array)$element->meta);
                        $normalizedValue = $this->_normalizeData((array)$value);

                        if ($normalizedElement !== $normalizedValue) {
                            return true;
                        }
                        break;
                }
            }
        }

        return false;
    }

    /**
     * Update Asset Element With Mux Asset
     * @param array $muxAssetArray 
     * @return bool
     */
    public function updateAssetElementWithMuxAsset(?array $muxAssetArray, bool $isWebhookUpdate = false): bool
    {

        $muxAsset = $muxAssetArray;
        
        $elements = MuxAssetElement::find()
            ->asset_id($muxAsset['id'])
            ->limit(1)
            ->unique()
            ->all();

        if(!$elements) {
            return false;
        }

        foreach($elements as $element) {
            // Mark as webhook update to prevent sync back to Mux
            if ($isWebhookUpdate) {
                $element->isWebhookUpdate = true;
            }

            foreach ($muxAsset as $key => $value) {
                if(array_key_exists($key, $this->defaultAttributes) || $key === 'status') {
                    if($key == 'id') {
                        $element->asset_id = $value;
                        continue;
                    } else if($key == 'status') {
                        $element->asset_status = $value;
                        continue;
                    } else if($key == 'passthrough') {
                        $parsed = json_decode($value, true);
                        if(isset($parsed['volumeId']) && $element->volumeId != $parsed['volumeId']) {
                            // Validate that the volume exists before setting it
                            $volumeExists = MuxVolumeRecord::findOne(['id' => $parsed['volumeId']]);
                            if ($volumeExists) {
                                $element->volumeId = $parsed['volumeId'];
                            } else {
                                // Log warning and don't update the volumeId
                                Mux::warning("Volume ID {$parsed['volumeId']} from passthrough data doesn't exist, keeping current volumeId {$element->volumeId}", 'mux');
                            }
                        }
                        if(isset($parsed['folderId']) && $element->folderId != $parsed['folderId']) {
                            // Validate that the folder exists before setting it
                            $folderExists = MuxFolderRecord::findOne(['id' => $parsed['folderId']]);
                            if ($folderExists) {
                                $element->folderId = $parsed['folderId'];
                            } else {
                                // Log warning and don't update the folderId
                                Mux::warning("Folder ID {$parsed['folderId']} from passthrough data doesn't exist, keeping current volumeId {$element->folderId}", 'mux');
                            }
                        }
                        continue;
                    } else if($key == 'static_renditions') {
                        if (
                            !empty($element->static_renditions) && 
                            (!isset($muxAsset['static_renditions']) || empty($muxAsset['static_renditions']))
                        ) {
                            // If static_renditions is missing in $muxAsset, reset to default (null)
                            $element->static_renditions = [];
                        } else {
                            // Otherwise, update it with the value from $muxAsset
                            $element->static_renditions = $value;
                        }
                        continue;
                    }
                    
                    // Only assign if the key exists in defaultAttributes (excluding special cases)
                    if (array_key_exists($key, $this->defaultAttributes)) {
                        $element->$key = $value;
                    }
                }
            }

            
            if(!$this->saveAsset($element)) {
                return false;
            }
        }

        return true;
        
    }

    /**
     * Create or Update MUX Asset
     * @param Asset $asset
     * @return bool
     * @throws Exception
     * @throws Throwable
     * @throws \craft\errors\ElementNotFoundException
     */
    public function createOrUpdateMuxAsset(Asset $asset): bool
    {
        
        $attributes = [
            //"title" => $asset->getPassthrough(),
            "asset_id" => $asset->getId(),
            "created_at" => $asset->getCreatedAt(),
            "asset_status" => $asset->getStatus(),
            "duration" => $asset->getDuration(),
            // max_stored_resolution is deprecated in the Mux API, so we should avoid using it if possible.
            "max_stored_resolution" => method_exists($asset, 'getMaxStoredResolution') ? $asset->getMaxStoredResolution() : null,
            "max_stored_frame_rate" => $asset->getMaxStoredFrameRate(),
            "resolution_tier" => $asset->getResolutionTier(),
            "max_resolution_tier" => $asset->getMaxResolutionTier(),
            // encoding_tier is deprecated in the Mux API, so we should avoid using it if possible.
            // If you need to maintain backward compatibility, you can check if the method exists before calling it.
            // Otherwise, you may want to remove this line entirely.
            // Example for backward compatibility:
            "encoding_tier" => method_exists($asset, 'getEncodingTier') ? $asset->getEncodingTier() : null,
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
            "static_renditions" => !empty($asset->getStaticRenditions()) && !empty($asset->getStaticRenditions()->getFiles()) ? $asset->getStaticRenditions()->getFiles() : [],
            "recording_times" => $asset->getRecordingTimes(),
            "non_standard_input_reasons" => !empty($asset->getNonStandardInputReasons()) ? json_decode($asset->getNonStandardInputReasons(), true): [],
            "test" => $asset->getTest(),
            "ingest_type" => $asset->getIngestType(),
            "meta" => !empty($asset->getMeta()) ? [
                    'title' => $asset->getMeta()->getTitle(),
                    'external_id' => $asset->getMeta()->getExternalId(),
                    'creator_id' => $asset->getMeta()->getCreatorId(),
                ] : [],
        ];

        /** @var MuxAssetRecord $assetData */
        $assetRecord = MuxAssetsRecord::find()->where(['asset_id' => $asset->getId()])->one();
        if($assetRecord) {
            $assetRecord->setAttributes($attributes, false);
            $assetRecord->save();
        }

        // Find the mux asset element or create one
        /** @var MuxAssetElement|null $muxAssetElement */
        $muxAssetElement = MuxAssetElement::find()
            ->asset_id($asset->getId())
            ->status(null)
            ->one();

        // Get the title from the meta data
        $title = !empty($asset->getMeta()) ? $asset->getMeta()->getTitle() : '';
            
        if ($muxAssetElement === null) {
            /** @var MuxAssetElement $muxAssetElement */
            $muxAssetElement = new muxAssetElement();
            $muxAssetElement->title = $title;
            $muxAssetElement->meta = [
                'title' => $title,
                'external_id' => $asset->getId(),
                'creator_id' => '',
            ];
            $volumeId = null;
            $folderId = null;
            $passthrough = $asset->getPassthrough();
            if (!empty($passthrough)) {
                $decoded = json_decode($passthrough, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && array_key_exists('volumeId', $decoded)) {
                    $volumeId = $decoded['volumeId'];
                }
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && array_key_exists('folderId', $decoded)) {
                    $folderId = $decoded['folderId'];
                }
            }

            $muxAssetElement->volumeId = $volumeId;
            $muxAssetElement->folderId = $folderId;
        } else {
            $muxAssetElement->title = $title;
            $muxAssetElement->meta = [
                'title' => $title,
                'external_id' => $asset['id'],
                'creator_id' => '',
            ];
        }

        // Set attributes on the element to emulate it having been loaded with JOINed data:
        $muxAssetElement->setAttributes($attributes, false);

        $event = new MuxAssetSyncEvent([
            'element' => $muxAssetElement,
            'source' => $asset
        ]);

        $this->trigger(self::EVENT_BEFORE_SYNCHRONIZE_MUX_ASSET, $event);

        if (!$event->isValid) {
            Mux::info("Synchronization of MUX Asset ID #{$asset->getId()} was stopped by a plugin.", 'mux');

            return false;
        }

        if (!Craft::$app->getElements()->saveElement($muxAssetElement)) {
            Mux::error("Failed to synchronize MUX Asset ID #{$asset->getId()}.", 'mux');

            return false;
        }

        return true;
    }

    /**
     * Update MUX Asset MP4 Support
     * @param string $id
     * @param bool $mp4Support
     * @return bool
     */
    public function updateMuxAssetMP4Support(string|int $assetId, string $mp4Support): bool
    {
        // Validate inputs
        if (empty($assetId) || empty($mp4Support)) {
            Mux::error('Invalid input provided for assetId or mp4Support.'. __METHOD__, 'mux');
            return false;
        }

        try {
            // Initialize the API instance with configuration
            $config = Mux::$plugin->assets->muxConf();
            $apiInstance = new MuxPhp\Api\AssetsApi(new Client(), $config);

            // Create the request payload
            $updateAssetMp4SupportRequest = ['mp4_support' => $mp4Support];

            // Call the API to update MP4 support
            $result = $apiInstance->updateAssetMp4Support($assetId, $updateAssetMp4SupportRequest);
            if (!$result) {
                Mux::info("Failed to update MP4 support for asset ID: {$assetId}. ". __METHOD__, 'mux');
                return false;
            }

            // Synchronize the asset
            /*
            if (!$this->syncAssetById($assetId)) {
                Mux::info("MP4 support updated but failed to sync asset ID: {$assetId}. ". __METHOD__, 'mux');
                return false;
            }*/

            Mux::info("MP4 support updated and asset synchronized successfully for asset ID: {$assetId}. ". __METHOD__, 'mux');
            return true;
        } catch (\MuxPhp\ApiException $apiException) {
            // Handle specific API exceptions
            Mux::error("Mux API Exception: {$apiException->getMessage()}: ". __METHOD__, 'mux');
            return false;
        } catch (\Exception $e) {
            // Handle generic exceptions
            Mux::error("Exception when calling updateAssetMp4Support: {$e->getMessage()}: ". __METHOD__, 'mux');
            return false;
        }
    }

    /**
     * Update MUX Asset Static Renditions
     * @param string|int $assetId
     * @param string $staticRendition
     * @return bool
     */
    public function updateMuxAssetStaticRenditions(string|int $assetId, string $staticRendition): bool
    {
        // Validate inputs
        if (empty($assetId) || empty($staticRendition)) {
            Mux::error('Invalid input provided for assetId or staticRendition.'. __METHOD__, 'mux');
            return false;
        }

        try {
            // Initialize the API instance with configuration
            $config = Mux::$plugin->assets->muxConf();
            $apiInstance = new MuxPhp\Api\AssetsApi(new Client(), $config);

            // Create the request payload
            $staticRendition = new MuxPhp\Models\StaticRendition([
                "resolution" => $staticRendition
            ]);

            // Call the API to create the static rendition
            $result = $apiInstance->createAssetStaticRendition($assetId, $staticRendition);
            if (!$result) {
                Mux::info("Failed to create static rendition for asset ID: {$assetId}. ". __METHOD__, 'mux');
                return false;
            }

            return true;
        } catch (\MuxPhp\ApiException $apiException) {
            // Handle specific API exceptions
            Mux::error("Mux API Exception: {$apiException->getMessage()}: ". __METHOD__, 'mux');
            return false;
        } catch (\Exception $e) {
            // Handle generic exceptions
            Mux::error("Exception when calling updateMuxAssetStaticRenditions: {$e->getMessage()}: ". __METHOD__, 'mux');
            return false;
        }
    }

    public function deleteMuxAssetStaticRenditionById(string|int $assetId, string $staticRenditionId): bool
    {
        // Validate inputs
        if (empty($assetId) || empty($staticRenditionId)) {
            Mux::error('Invalid input provided for assetId or staticRenditionId.'. __METHOD__, 'mux');
            return false;
        }

        try {
            // Initialize the API instance with configuration
            $config = Mux::$plugin->assets->muxConf();
            $apiInstance = new MuxPhp\Api\AssetsApi(new Client(), $config);

            //Mux::info("Deleting static rendition for asset ID: {$assetId} and static rendition ID: {$staticRenditionId}. ". __METHOD__, 'mux');

            // Call the API to delete the static rendition
            $apiInstance->deleteAssetStaticRendition($assetId, $staticRenditionId);
            
            //Mux::info("Successfully deleted static rendition for asset ID: {$assetId}. ". __METHOD__, 'mux');
            return true;
            
        } catch (\MuxPhp\ApiException $apiException) {
            // Handle specific API exceptions
            Mux::error("Mux API Exception: {$apiException->getMessage()}: ". __METHOD__, 'mux');
            return false;
        } catch (\Exception $e) {
            // Handle generic exceptions
            Mux::error("Exception when calling deleteMuxAssetStaticRenditionById: {$e->getMessage()}: ". __METHOD__, 'mux');
            return false;
        }
    }

    /**
     * Sync All Mux Assets
     * @return void 
     * @throws ApiException 
     * @throws InvalidArgumentException 
     * @throws BaseInvalidArgumentException 
     * @throws GlobalException 
     * @throws InvalidConfigException 
     * @throws Exception 
     * @throws Throwable 
     * @throws ElementNotFoundException 
     */
    public function syncAllMuxAssets() :void
    {
        $limit = 50;
        $page = 1;
        $user = Craft::$app->user->getIdentity();

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
     * Sync Mux Asset By ID
     * @param string $id 
     * @return bool
     * @throws ApiException 
     * @throws InvalidArgumentException 
     * @throws BaseInvalidArgumentException 
     * @throws GlobalException 
     * @throws InvalidConfigException 
     * @throws Exception 
     * @throws Throwable 
     * @throws ElementNotFoundException 
     */
    public function syncAssetById(string $id): bool
    {
        $config = Mux::$plugin->assets->muxConf();
        // API Client Initialization
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );

        $asset = $apiInstance->getAsset($id)->getData();

        return $this->createOrUpdateMuxAsset($asset);
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
            Craft::$app->getElements()->deleteElement($element);
        }
    }

    /**
     * Get Playback Policy
     * @return string 
     */
    public function getPlaybackPolicy(): string
    {
        $settings = Mux::$settings;
        return $settings->muxSecurePlayback
            ? MuxPhp\Models\PlaybackPolicy::SIGNED 
            : MuxPhp\Models\PlaybackPolicy::_PUBLIC;
    }

    /**
     * Normalize Data
     * @param mixed $data 
     * @return mixed 
     */
    private function _normalizeData($data) {
        if (is_array($data)) {
            ksort($data); // Sort the keys in alphabetical order
            foreach ($data as $key => $value) {
                $data[$key] = $this->_normalizeData($value); // Recursively sort nested arrays/objects
            }
        }
        return $data;
    }

    /**
     * Get assets by folder ID
     * @param int|null $folderId
     * @return array
     */
    public function getAssetsByFolderId(?int $folderId): array
    {
        return MuxAssetElement::find()
            ->folderId($folderId)
            ->all();
    }

    /**
     * Move assets to a different folder
     * @param array $elementIds
     * @param int|null $targetFolderId
     * @return bool
     */
    public function moveAssets(array $elementIds, ?int $targetFolderId, ?int $volumeId): bool
    {

        // Fire before move event
        $event = new MuxAssetMoveEvent([
            'elementIds' => $elementIds,
            'volumeId' => $volumeId,
            'targetFolderId' => $targetFolderId,
        ]);
        
        $this->trigger(self::EVENT_BEFORE_MOVE_ASSET, $event);
        
        if ($event->isValid === false) {
            return false;
        }

        $elements = MuxAssetElement::find()
            ->id($elementIds)
            ->all();

        foreach ($elements as $element) {
            $element->folderId = $targetFolderId;
            $element->volumeId = $volumeId;
            if (!Craft::$app->getElements()->saveElement($element)) {
                return false;
            }
        }

        // Fire after move event
        $this->trigger(self::EVENT_AFTER_MOVE_ASSET, $event);

        return true;
    }

    /**
     * Create Meta Data
     * @param string|null $title
     * @param string|null $externalId
     * @param string|null $creatorId
     * @return null|array
     */
    private function createMetaData(string|null $title, string|null $externalId, string $creatorId = ''): array
    {
        if (is_null($title) || is_null($externalId)) {
            return [];
        }

        return [
            'title' => $title,
            'external_id' => $externalId,
            'creator_id' => $creatorId,
        ];
    }


}
