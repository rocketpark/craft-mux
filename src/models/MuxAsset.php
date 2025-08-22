<?php

namespace rocketpark\mux\models;

use Craft;
use craft\i18n;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\validators\ArrayValidator;


/**
 * Mux Asset Model
 *
 * @property-read int|null $id
 * @property-read string|null $asset_id
 * @property-read int|null $volumeId
 * @property-read int|null $folderId
 * @property-read string|null $created_at
 * @property-read string|null $asset_status
 * @property-read float|null $duration
 * @property-read string|null $max_stored_resolution
 * @property-read string|null $max_stored_frame_rate
 * @property-read string|null $resolution_tier
 * @property-read string|null $max_resolution_tier
 * @property-read string|null $encoding_tier
 * @property-read string|null $aspect_ratio
 * @property-read array|null $playback_ids
 * @property-read array|null $tracks
 * @property-read string|null $errors
 * @property-read array|null $per_title_encode
 * @property-read string|null $upload_id
 * @property-read bool|null $is_live
 * @property-read string|null $passthrough
 * @property-read string|null $live_stream_id
 * @property-read array|null $master
 * @property-read string|null $master_access
 * @property-read string|null $mp4_support
 * @property-read string|null $source_asset_id
 * @property-read bool|null $normalize_audio
 * @property-read array|null $static_renditions
 * @property-read array|null $recording_times
 * @property-read array|null $non_standard_input_reasons
 * @property-read bool|null $test
 * @property-read string|null $ingest_type
 * @property-read array|null $meta
 */
class MuxAsset extends Model
{
    public ?int $id = null;
    public ?string $asset_id = null;
    public ?int $volumeId = null;
    public ?int $folderId = null;
    public ?string $created_at = null;
    public ?string $asset_status = null;
    public ?float $duration = null;
    public ?string $max_stored_resolution = null;
    public ?string $max_stored_frame_rate = null;
    public ?string $resolution_tier = null;
    public ?string $max_resolution_tier = null;
    public ?string $encoding_tier = null;
    public ?string $aspect_ratio = null;
    public ?array $playback_ids = null;
    public ?array $tracks = null;
    public ?string $errors = null;
    public ?array $per_title_encode = null;
    public ?string $upload_id = null;
    public ?bool $is_live = null;
    public ?string $passthrough = null;
    public ?string $live_stream_id = null;
    public ?array $master = null;
    public ?string $master_access = null;
    public ?string $mp4_support = null;
    public ?string $source_asset_id = null;
    public ?bool $normalize_audio = null;
    public ?array $static_renditions = null;
    public ?array $recording_times = null;
    public ?array $non_standard_input_reasons = null;
    public ?bool $test = null;
    public ?string $ingest_type = null;
    public ?array $meta = null;

    /**
     * Get Playback Id
     * @return string|null
     */
    public function playbackId(): ?string
    {
        return $this->playback_ids[0]['id'] ?? null;
    }


    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['playback_ids','tracks','master','static_rendition','recording_times','non_standard_input_reasons', 'meta'], 'default', 'value' => '{}'],
            [['playback_ids','tracks','master','static_rendition','recording_times','non_standard_input_reasons', 'meta'], 'filter', 'filter' => 'json_decode'],
            [['playback_ids','tracks','master','static_rendition','recording_times','non_standard_input_reasons', 'meta'], 'safe'],
        ];
    }

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [];
    }
}
