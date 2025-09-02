<?php

namespace rocketpark\mux\records;

use Craft;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;
use MuxPhp\Models\Asset;
use rocketpark\mux\elements\MuxAsset;

/**
 * Assets record
 *
 * @property int $id ID
 * @property string $asset_id Asset ID
 * @property string $created_at Created at
 * @property string $asset_status Asset status
 * @property float $duration Duration
 * @property string $max_stored_resolution Max stored resolution
 * @property float $max_stored_frame_rate Max stored frame rate
 * @property string $encoding_tier Encoding tier
 * @property string $max_resolution_tier Max resolution tier
 * @property string $resolution_tier Resolution tier
 * @property string $aspect_ratio Aspect ratio
 * @property array $playback_ids Playback IDs
 * @property array $tracks Tracks
 * @property string $errors Errors
 * @property bool $per_title_encode Per title encode
 * @property string $upload_id Upload ID
 * @property bool $is_live Is live
 * @property string $passthrough Passthrough
 * @property string $live_stream_id Live stream ID
 * @property array $master Master
 * @property string $master_access Master access
 * @property string $mp4_support MP4 support
 * @property string $source_asset_id Source asset ID
 * @property bool $normalize_audio Normalize audio
 * @property array $static_renditions Static renditions
 * @property array $recording_times Recording times
 * @property array $non_standard_input_reasons Non standard input reasons
 * @property bool $test Test
 * @property string $ingest_type Ingest type
 * @property array $meta Meta
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property string $uid Uid
 */
class Assets extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%mux_assets}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(MuxAsset::class, ['id' => 'id']);
    }

    public function getData(): ActiveQueryInterface
    {
        return $this->hasOne(Asset::class, ['asset_id' => 'id']);
    }
}
