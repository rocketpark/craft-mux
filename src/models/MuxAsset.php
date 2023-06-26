<?php

namespace rocketpark\mux\models;

use Craft;
use craft\i18n;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\validators\ArrayValidator;


/**
 * Mux Asset Model
 */
class MuxAsset extends Model
{
    public $id;
    public $asset_id;
    public $created_at;
    public $asset_status;
    public $duration;
    public $max_stored_resolution;
    public $max_stored_frame_rate;
    public $aspect_ratio;
    public $playback_ids;
    public $tracks;
    public $errors;
    public $per_title_encode;
    public $upload_id;
    public $is_live;
    public $passthrough;
    public $live_stream_id;
    public $master;
    public $master_access;
    public $mp4_support;
    public $source_asset_id;
    public $normalize_audio;
    public $static_renditions;
    public $recording_times;
    public $non_standard_input_reasons;
    public $test;

    public function playbackId(): string
    {
        return $this->playback_ids[0]['id'];
    }

    //    public function allLocales(): string {
    //        return Craft::$app->i18n->getAllLocales();
    //    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['playback_ids','tracks','master','static_rendition','recording_times','non_standard_input_reasons'], 'default', 'value' => '{}'],
            [['playback_ids','tracks','master','static_rendition','recording_times','non_standard_input_reasons'], 'filter', 'filter' => 'json_decode'],
            [['playback_ids','tracks','master','static_rendition','recording_times','non_standard_input_reasons'], 'safe'],
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
