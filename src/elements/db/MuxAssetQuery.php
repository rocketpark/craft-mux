<?php

namespace rocketpark\mux\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use yii\db\Expression;

/**
 * Mux Asset query
 */
class MuxAssetQuery extends ElementQuery
{

    public $asset_id = null;
    public $asset_status = null;
    public $aspect_ratio = null;
    public $duration = null;
    public $is_live = false;
    public $upload_id = null;
    public $playback_ids = null;
    public $tracks = null;
    public $passthrough = null;
    public $mp4_support = null;
    public $created_at = null;
    public $static_renditions = null;
    public $errors = null;
    public $max_stored_resolution = null;
    public $max_stored_frame_rate = null;
    public $resolution_tier = null;
    public $max_resolution_tier = null;

    public function asset_id(mixed $value): self
    {
        $this->asset_id = $value;

        return $this;
    }

    public function asset_status(mixed $value): self
    {
        $this->asset_status = $value;

        return $this;
    }

    public function aspect_ratio(mixed $value): self
    {
        $this->aspect_ratio = $value;

        return $this;
    }

    public function duration(mixed $value): self
    {
        $this->duration = $value;

        return $this;
    }

    public function is_live(bool|null $value): self
    {
        $this->is_live = $value;

        return $this;
    }

    public function upload_id(mixed $value): self
    {
        $this->upload_id = $value;

        return $this;
    }

    public function playback_ids(array|string|null $value): self
    {
        $this->playback_ids = $value;
  
        return $this;
    }

    public function tracks(array|string|null $value): self
    {
        $this->tracks = $value;
        return $this;
    }

    public function passthrough(string|null $value): self
    {
        $this->passthrough = $value;

        return $this;
    }

    public function mp4_support(string|null $value): self
    {
        $this->mp4_support = $value;

        return $this;
    }

    public function created_at(string|null $value): self
    {
        $this->created_at = $value;

        return $this;
    }

    public function static_renditions(object|string|null $value): self
    {
        if (is_string($value)) {
            // Attempt to decode the JSON string into an object
            $decoded = json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->static_renditions = $decoded;
            } else {
                throw new \InvalidArgumentException('Invalid JSON string provided for static_renditions');
            }
        } elseif (is_object($value) || is_null($value)) {
            $this->static_renditions = $value;
        } else {
            throw new \InvalidArgumentException('static_renditions must be an object, valid JSON string, or null');
        }
        return $this;
    }

    public function errors(string|null $value): self
    {
        $this->errors = $value;

        return $this;
    }

    public function max_stored_resolution(string|null $value): self
    {
        $this->max_stored_resolution = $value;

        return $this;
    }

    public function max_stored_frame_rate(string|null $value): self
    {
        $this->max_stored_frame_rate = $value;

        return $this;
    }

    public function resolution_tier(string|null $value): self
    {
        $this->resolution_tier = $value;

        return $this;
    }

    public function max_resolution_tier(string|null $value): self
    {
        $this->max_resolution_tier = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {

        $this->joinElementTable('mux_assets');

        $this->query->select([
            'mux_assets.id',
            'mux_assets.asset_id',
            'mux_assets.asset_status',
            'mux_assets.aspect_ratio',
            'mux_assets.duration',
            'mux_assets.is_live',
            'mux_assets.upload_id',
            'mux_assets.playback_ids',
            'mux_assets.tracks',
            'mux_assets.passthrough',
            'mux_assets.mp4_support',
            'mux_assets.created_at',
            'mux_assets.static_renditions',
            'mux_assets.errors',
            'mux_assets.max_stored_resolution',
            'mux_assets.max_stored_frame_rate',
            'mux_assets.resolution_tier',
            'mux_assets.max_resolution_tier',
        ]);

        if ($this->aspect_ratio) {
            $this->subQuery->andWhere(Db::parseParam('mux_assets.aspect_ratio', $this->aspect_ratio));
        }

        if($this->asset_id) {
            $this->subQuery->andWhere(Db::parseParam('mux_assets.asset_id', $this->asset_id));
        }

        if($this->asset_status) {
            $this->subQuery->andWhere(Db::parseParam('mux_assets.asset_status', $this->asset_status));
        }

        if($this->is_live){
            $this->subQuery->andWhere(Db::parseParam('mux_assets.is_live', $this->is_live));
        }

        if($this->upload_id){
            $this->subQuery->andWhere(Db::parseParam('mux_assets.upload_id', $this->upload_id));
        }

        if($this->passthrough){
            $this->subQuery->andWhere(Db::parseParam('mux_assets.passthrough', $this->passthrough));
        }

        if($this->playback_ids){
            $jsonCondition = new Expression('JSON_CONTAINS(mux_assets.playback_ids, :playback_ids)');
            $this->subQuery->andWhere($jsonCondition, [':playback_ids' => json_encode($this->playback_ids)]);
        }

        if($this->tracks){
            $jsonCondition = new Expression('JSON_CONTAINS(mux_assets.tracks, :tracks)');
            $this->subQuery->andWhere($jsonCondition, [':tracks' => json_encode($this->tracks)]);
        }

        if($this->mp4_support){
            $this->subQuery->andWhere(Db::parseParam('mux_assets.mp4_support', $this->mp4_support));
        }

        if($this->created_at){
            // Convert `created_at` to a readable date, allowing comparisons against a date range or specific date.
            $date = date('Y-m-d', $this->created_at); // Convert your timestamp to a date string (e.g., '2024-12-04')
            // Use Db::parseDateParam for date-specific filtering
            $this->subQuery->andWhere(Db::parseDateParam('FROM_UNIXTIME(mux_assets.created_at)', $date));
        }

        if($this->static_renditions){
            $jsonCondition = new Expression('JSON_CONTAINS(mux_assets.static_renditions, :static_renditions)');
            $this->subQuery->andWhere($jsonCondition, [':static_renditions' => json_encode($this->static_renditions)]);
        }

        // if($this->tracks){
        //     $jsonCondition = new Expression(
        //         "JSON_CONTAINS_PATH(mux_assets.tracks, 'all', '$.tracks[*]') :tracks"
        //     );
        //     $this->subQuery->andWhere($jsonCondition, [':tracks' => json_encode($this->tracks, JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT)]);
        // }

        // if ($this->tracks) {
        //     $jsonPath = '$.tracks[*]';
        //     $jsonCondition = new Expression("JSON_SEARCH(mux_assets.tracks, 'all', :jsonPath, :tracks) IS NOT NULL");
        //     $this->subQuery->andWhere($jsonCondition, [
        //         ':jsonPath' => $jsonPath,
        //         ':tracks' => json_encode($this->tracks, JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT)
        //     ]);
        // }

        if($this->duration){
            // Query the duration as a number
            $this->subQuery->andWhere(Db::parseParam('mux_assets.duration', $this->duration));
        }

        return parent::beforePrepare();
    }
    
}
