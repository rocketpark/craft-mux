<?php

namespace rocketpark\mux\models;

use Craft;

use craft\base\Model;
use craft\helpers\App;
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
 * @property-read array $staticRenditions Default static renditions for new uploads
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
     * @var bool|string The MUX Asset has secure playback
     */
    public bool|string $muxSecurePlayback = false;

    /**
     * @var string The MUX Asset resolution teir
     */
    public string $maxResolutionTier = '';

    /**
     * @var string Enable static MP4 renditions on your video assets for offline viewing and other use cases.
     */
    public string $mp4Support = '';

    /**
     * @var array Default static renditions applied to new video uploads (e.g. ['1080p', '720p']).
     */
    public array $staticRenditions = [];

    /**
     * Yii2 magic setter — called by setAttribute() during settings hydration.
     * Handles legacy single-string values stored in older DB rows gracefully.
     */
    public function setStaticRenditions(mixed $value): void
    {
        if (is_string($value)) {
            $this->staticRenditions = ($value !== '' && $value !== 'none') ? [$value] : [];
        } else {
            $this->staticRenditions = (array)($value ?? []);
        }
    }

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
     * @var string Maximum upload file size in kB.
     * @example 1000000000
     * @example 0
     * @default 700MB in kB
     */
    public string $maxUploadFileSize = '716800'; // 700MB in kB

    /**
     * @var string Upload chunk size in kB.
     * @example 1048576
     * @example 0
     * @default 30720 in kB
     */
    public string $uploadChunkSize = '30720'; // 30MB in kB


    /**
     * @var string Default generated subtitle language.
     * @var string
     * @example en (English)
     * @example es (Spanish)
     * @example fr (French)
     * @example de (German)
     * @example it (Italian)
     * @example pt (Portuguese)
     */
    public string $defaultGeneratedSubtitleLanguage = 'en';

    /**
     * @var bool|string Whether to automatically generate captions when a new video is uploaded.
     */
    public bool|string $autoGenerateCaptions = true;


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
            ['muxSecurePlayback', 'default', 'value' => false],
            ['maxResolutionTier', 'string'],
            ['maxResolutionTier', 'default', 'value' => '1080p'],
            ['mp4Support', 'string'],
            ['mp4Support', 'default', 'value' => 'none'],
            ['staticRenditions', 'each', 'rule' => ['string']],
            ['staticRenditions', 'default', 'value' => []],
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
            ['opacity', 'default', 'value' => '100'],
            ['maxUploadFileSize', 'string'],
            ['maxUploadFileSize', 'default', 'value' => '716800'],
            ['uploadChunkSize', 'string'],
            ['uploadChunkSize', 'default', 'value' => '30720'],
            ['defaultGeneratedSubtitleLanguage', 'string'],
            ['defaultGeneratedSubtitleLanguage', 'default', 'value' => 'en'],
            ['autoGenerateCaptions', 'default', 'value' => true],
            [['muxSecurePlayback', 'autoGenerateCaptions'], 'validateBooleanMenuEnvSetting'],
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
                'attributes' => [
                    'muxTokenId',
                    'muxTokenSecret',
                    'maxResolutionTier',
                    'watermark_url',
                    'vertical_align',
                    'vertical_margin',
                    'horizontal_align',
                    'horizontal_margin',
                    'width',
                    'height',
                    'opacity',
                    'mp4Support',
                    'muxSecurePlayback',
                    'maxUploadFileSize',
                    'uploadChunkSize',
                    'defaultGeneratedSubtitleLanguage',
                    'autoGenerateCaptions',
                ],
            ],
        ];
    }

    /**
     * Boolean menu fields with `includeEnvVars: true` — use Craft’s boolean env parsing, not Yii’s narrow `boolean` rule.
     *
     * @see App::parseBooleanEnv()
     */
    public function validateBooleanMenuEnvSetting(string $attribute, ?array $params = null): void
    {
        $value = $this->$attribute;

        if (App::parseBooleanEnv($value) !== null) {
            return;
        }

        if (is_string($value) && preg_match('/^\$[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
            return;
        }

        $this->addError($attribute, Craft::t('mux', '{attribute} must be a boolean or a valid environment variable reference.', [
            'attribute' => $this->getAttributeLabel($attribute),
        ]));
    }
}
