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
            'mux_assets.passthrough'
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
