<?php

namespace rocketpark\mux\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\validators\ArrayValidator;

/**
 * Mux settings
 *
 * @property-read string $pluginName The public-facing name of the plugin
 * @property-read string $muxTokenId Mux's TokenID for Authentication
 * @property-read string $muxTokenSecret Mux's TokenSecret for Authentication
 * @property-read bool $muxSecurePlayback The MUX Asset has secure playback
 * @property-read string $maxResolutionTier The MUX Asset resolution tier
 * @property-read string $mp4Support Enable static MP4 renditions
 * @property-read string $staticRenditions Enable static renditions
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
     * @var string Enable static renditions on your video assets for offline viewing and other use cases.
     */
    public string $staticRenditions = '';

    /**
     * @var string URL of the watermark/overlay image.
     */
    public string $watermark_url = '';

    /**
     * @var string Vertical alignment of the overlay/watermark.
     * @example top
     * @example center
     * @example bottom
     */
    public string $vertical_align = '';

    /**
     * @var string Vertical margin of the overlay/watermark.
     * @example 10%
     * @example 100px
     * @example 0
     */
    public string $vertical_margin = '';

    /**
     * @var string Horizontal alignment of the overlay/watermark.
     * @example left
     * @example middle
     * @example right
     */
    public string $horizontal_align = '';

    /**
     * @var string Horizontal margin of the overlay/watermark.
     * @example 10%
     * @example 100px
     * @example 0
     */
    public string $horizontal_margin = '';

    /**
     * @var string Width of the overlay/watermark.
     * @example 10%
     * @example 100px
     * @example 0
     */
    public string $width = '';

    /**
     * @var string Height of the overlay/watermark.
     * @example 10%
     * @example 100px
     * @example 0
     */
    public string $height = '';

    /**
     * @var string Opacity of the overlay/watermark.
     * @example 100%
     * @example 50%
     * @example 0
     */
    public string $opacity = '';


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
            ['staticRenditions', 'string'],
            ['staticRenditions', 'default', 'value' => 'none'],
            ['watermark_url', 'string'],
            ['watermark_url', 'default', 'value' => ''],
            ['vertical_align', 'string'],
            ['vertical_align', 'default', 'value' => 'top'],
            ['vertical_margin', 'string'],
            ['vertical_margin', 'default', 'value' => '0'],
            ['horizontal_align', 'string'],
            ['horizontal_align', 'default', 'value' => 'left'],
            ['horizontal_margin', 'string'],
            ['horizontal_margin', 'default', 'value' => '0'],
            ['width', 'string'],
            ['height', 'string'],
            ['opacity', 'string'],
            ['opacity', 'default', 'value' => '100']
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
