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
class Asset extends Model
{
    public int $id;
    public string $assetId;
    public mixed $assetData;
    public string $dateCreated;
    public string $dateUpdated;
    public string $uid;


    public function playbackId(): string
    {
        return $this->assetData->playback_ids[0]['id'];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['assetId', 'assetData'], 'required'],
            [['dateCreated', 'dateUpdated'], DateTimeValidator::class],
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
