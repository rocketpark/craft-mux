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
    public string $pluginName = 'MUX';

    /**
     * @var string Mux's TokenID for Authentication
     */
    public string $muxTokenId = '';

    /**
     * @var string Mux's TokenSecret for Authentication
     */
    public string $muxTokenSecret = '';

    /**
     * @var int The MUX Asset has secure playback
     */
    public bool $muxSecurePlayback = false;

    /**
     * @var string The MUX Asset resolution teir
     */
    public string $maxResolutionTier = '';

    /**
     * @var string Enable static MP4 renditions on your video assets for offline viewing and other use cases.
     */
    public string $mp4Support = '';

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            ['pluginName', 'string'],
            ['pluginName', 'default', 'value' => 'MUX'],
            ['muxTokenId', 'string'],
            ['muxTokenSecret', 'string'],
            ['muxSecurePlayback', 'boolean'],
            ['muxSecurePlayback', 'default', 'value' => false],
            ['maxResolutionTier', 'string'],
            ['maxResolutionTier', 'default', 'value' => '1080p'],
            ['mp4Support', 'string'],
            ['mp4Support', 'default', 'value' => 'none'],
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
