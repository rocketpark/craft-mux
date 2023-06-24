<?php

namespace rocketpark\mux\jobs;

use craft\queue\BaseJob;
use rocketpark\mux\elements\MuxAsset;
use rocketpark\mux\Mux;
use rocketpark\mux\records\Assets as MuxAssetRecord;
use GuzzleHttp\Client;
use MuxPhp;


/**
 * Updates the attributes for a Mux Asset Element.
 */
class UpdateMuxAssetElement extends BaseJob
{
    public int $muxAssetId;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $config = Mux::$plugin->assets->muxConf();
        $apiInstance = new MuxPhp\Api\AssetsApi(
            new Client(),
            $config
        );
        
        if ($muxAsset = MuxAsset::find()->assetId($this->muxAssetId)->one()) {
            $muxAssetData = $apiInstance->getAsset($this->muxAssetId);

            foreach ($muxAssetData as $key => $value) {
                if($key == 'id') {
                    $muxAsset['asset_id'] =  $value;
                } elseif ($key = 'status') {
                    $muxAsset['asset_status'] =  $value;
                } else {
                    $muxAsset[$key] = $value;
                }
            }
            
            /** @var MuxAssetRecord $assetData */
            $assetRecord = MuxAssetRecord::find()->where(['asset_id' => $this->muxAssetId])->one();
            foreach ($assetRecord->getAttributes() as $key => $value) {
                if($key != 'id') {
                    $assetRecord->setAttribute($key, $muxAsset[$key]);
                }
            }

            $assetRecord->save();
            sleep(1); // Avoid rate limiting
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return null;
    }
}