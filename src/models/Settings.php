<?php

namespace rocketpark\mux\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\validators\ArrayValidator;

/**
 * Mux settings
 */
class Settings extends Model
{

    /**
     * @var string The public-facing name of the plugin
     */
    public string $pluginName = 'Mux';

    /**
     * @var bool Mux's TokenID for Authentication
     */
    public string $muxTokenId = '';

    /**
     * @var bool Mux's TokenSecret for Authentication
     */
    public string $muxTokenSecret = '';



    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            ['pluginName', 'string'],
            ['pluginName', 'default', 'value' => 'Mux'],
            ['muxTokenId', 'string'],
            ['muxTokenSecret', 'string']
        ];
    }

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [],
            ],
        ];
    }
}
