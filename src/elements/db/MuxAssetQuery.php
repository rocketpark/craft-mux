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
    public $ingest_type = null;
    public $meta = null;
    public mixed $trackMaxWidth = null;
    public mixed $trackMaxHeight = null;
    public mixed $trackMaxFrameRate = null;
    public mixed $trackDuration = null;
    public mixed $trackPrimary = null;
    public mixed $trackMaxChannels = null;
    public mixed $trackType = null;
    public mixed $trackTextType = null;
    public mixed $trackTextSource = null;
    public mixed $trackStatus = null;
    public mixed $trackName = null;
    public mixed $trackLanguageCode = null;
    public mixed $trackId = null;
    public mixed $metaTitle = null;
    public mixed $metaExternalId = null;
    public mixed $metaCreatorId = null;


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

    public function ingest_type(string|null $value): self
    {
        $this->ingest_type = $value;

        return $this;
    }

    public function meta(array|string|null $value): self
    {
        $this->meta = $value;

        return $this;
    }

    public function trackMaxWidth(mixed $value): self { $this->trackMaxWidth = $value; return $this; }
    public function trackMaxHeight(mixed $value): self { $this->trackMaxHeight = $value; return $this; }
    public function trackMaxFrameRate(mixed $value): self { $this->trackMaxFrameRate = $value; return $this; }
    public function trackDuration(mixed $value): self { $this->trackDuration = $value; return $this; }
    public function trackPrimary(mixed $value): self { $this->trackPrimary = $value; return $this; }
    public function trackMaxChannels(mixed $value): self { $this->trackMaxChannels = $value; return $this; }
    public function trackType(mixed $value): self { $this->trackType = $value; return $this; }
    public function trackTextType(mixed $value): self { $this->trackTextType = $value; return $this; }
    public function trackTextSource(mixed $value): self { $this->trackTextSource = $value; return $this; }
    public function trackStatus(mixed $value): self { $this->trackStatus = $value; return $this; }
    public function trackName(mixed $value): self { $this->trackName = $value; return $this; }
    public function trackLanguageCode(mixed $value): self { $this->trackLanguageCode = $value; return $this; }
    public function trackId(mixed $value): self { $this->trackId = $value; return $this; }
    public function metaTitle(mixed $value): self { $this->metaTitle = $value; return $this; }
    public function metaExternalId(mixed $value): self { $this->metaExternalId = $value; return $this; }
    public function metaCreatorId(mixed $value): self { $this->metaCreatorId = $value; return $this; }

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
            'mux_assets.ingest_type',
            'mux_assets.meta',
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

        if($this->duration){
            // Query the duration as a number
            $this->subQuery->andWhere(Db::parseParam('mux_assets.duration', $this->duration));
        }

        if($this->meta){
            $jsonCondition = new Expression('JSON_CONTAINS(mux_assets.meta, :meta)');
            $this->subQuery->andWhere($jsonCondition, [':meta' => json_encode($this->meta)]);
        }

        // Check if any track filtering is required
        if (
            $this->trackType || $this->trackTextType || $this->trackTextSource ||
            $this->trackStatus || $this->trackName || $this->trackLanguageCode || $this->trackId ||
            $this->trackMaxWidth || $this->trackMaxHeight || $this->trackMaxFrameRate ||
            $this->trackDuration || $this->trackPrimary || $this->trackMaxChannels
        ) {

            $this->subQuery->leftJoin(
                ['jt' => new Expression("JSON_TABLE(
                    mux_assets.tracks,
                    '$[*]' COLUMNS (
                        type VARCHAR(100) PATH '$.type',
                        text_type VARCHAR(100) PATH '$.text_type',
                        text_source VARCHAR(100) PATH '$.text_source',
                        status VARCHAR(100) PATH '$.status',
                        name VARCHAR(255) PATH '$.name',
                        language_code VARCHAR(50) PATH '$.language_code',
                        id VARCHAR(100) PATH '$.id',
                        max_width INT PATH '$.max_width',
                        max_height INT PATH '$.max_height',
                        max_frame_rate DOUBLE PATH '$.max_frame_rate',
                        duration DOUBLE PATH '$.duration',
                        `primary` BOOLEAN PATH '$.primary',
                        max_channels INT PATH '$.max_channels'
                    )
                )")],
                'TRUE'
            );

            // Apply WHERE conditions
            if ($this->trackType !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.type', $this->trackType));
            }
            if ($this->trackTextType !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.text_type', $this->trackTextType));
            }
            if ($this->trackTextSource !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.text_source', $this->trackTextSource));
            }
            if ($this->trackStatus !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.status', $this->trackStatus));
            }
            if ($this->trackName !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.name', $this->trackName));
            }
            if ($this->trackLanguageCode !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.language_code', $this->trackLanguageCode));
            }
            if ($this->trackId !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.id', $this->trackId));
            }
            if ($this->trackMaxWidth !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.max_width', $this->trackMaxWidth));
            }
            if ($this->trackMaxHeight !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.max_height', $this->trackMaxHeight));
            }
            if ($this->trackMaxFrameRate !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.max_frame_rate', $this->trackMaxFrameRate));
            }
            if ($this->trackDuration !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.duration', $this->trackDuration));
            }
            if ($this->trackPrimary !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.primary', $this->trackPrimary));
            }
            if ($this->trackMaxChannels !== null) {
                $this->subQuery->andWhere(Db::parseParam('jt.max_channels', $this->trackMaxChannels));
            }
        }

        if($this->metaTitle || $this->metaExternalId || $this->metaCreatorId) {
            $this->subQuery->leftJoin(
                ['jm' => new Expression("JSON_TABLE(
                    mux_assets.meta,
                    '$' COLUMNS (
                        `title` VARCHAR(255) PATH '$.title',
                        external_id VARCHAR(100) PATH '$.external_id',
                        creator_id VARCHAR(100) PATH '$.creator_id'
                    )
                )")],
                'TRUE'
            );

            if ($this->metaTitle !== null) {
                $this->subQuery->andWhere(Db::parseParam('jm.title', $this->metaTitle));
            }
            if ($this->metaExternalId !== null) {
                $this->subQuery->andWhere(Db::parseParam('jm.external_id', $this->metaExternalId));
            }
            if ($this->metaCreatorId !== null) {
                $this->subQuery->andWhere(Db::parseParam('jm.creator_id', $this->metaCreatorId));
            }
        }

        return parent::beforePrepare();
    }
    
}
