<?php

namespace rocketpark\mux\gql\types;

use Craft;
use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use rocketpark\mux\helpers\Gql as GqlHelper;
use rocketpark\mux\Mux;
use rocketpark\mux\services\SignedPlayback;

/**
 * Class PlaybackIdType
 *
 * A Mux playback ID with its policy, plus signed playback tokens and ready-to-use URLs.
 *
 * Tokens and URLs for `signed` playback IDs are only returned when the schema has the
 * “Mux → Generate signed playback tokens and URLs” permission (`muxSignedPlayback:read`);
 * otherwise those fields resolve to `null`. URLs for `public` playback IDs are always returned.
 *
 * @author    rocketpakr
 * @package   Mux
 */
class PlaybackIdType extends ObjectType
{
    /**
     * @var string
     */
    public $name = 'PlaybackID';

    /**
     * @var string
     */
    public $description = 'Mux Asset Playback Ids';

    /**
     * Token `type` argument values, mapped to token audiences.
     */
    private const TOKEN_TYPES = [
        'v' => SignedPlayback::AUD_VIDEO,
        'video' => SignedPlayback::AUD_VIDEO,
        't' => SignedPlayback::AUD_THUMBNAIL,
        'thumbnail' => SignedPlayback::AUD_THUMBNAIL,
        'g' => SignedPlayback::AUD_GIF,
        'gif' => SignedPlayback::AUD_GIF,
        's' => SignedPlayback::AUD_STORYBOARD,
        'storyboard' => SignedPlayback::AUD_STORYBOARD,
    ];

    /**
     * @inheritdoc
     */
    public function __construct($config)
    {
        $config = array_merge($config, [
            'name' => self::getName(),
            'description' => $this->description,
            'fields' => self::class . '::getFieldDefinition',
        ]);
        parent::__construct($config);
    }

    /**
     * Returns a singleton instance to ensure one type per schema.
     *
     * @return PlaybackIdType
     */
    public static function getType(): PlaybackIdType
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([]));
    }

    /**
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'PlaybackID';
    }

    /**
     * Define fields for this type.
     *
     * @return array
     */
    public static function getFieldDefinition(): array
    {
        $common = [
            'expiresIn' => [
                'type' => Type::int(),
                'description' => 'Minimum token lifetime in seconds. Defaults to the `signedTokenLifetime` setting (24 hours) and is capped at `maxSignedTokenLifetime`. Signed playback IDs only.',
            ],
            'playback_restriction_id' => [
                'type' => Type::string(),
                'description' => 'Mux playback restriction to enforce. Defaults to the plugin’s `playbackRestrictionId` setting; pass an empty string to sign without one. Signed playback IDs only.',
            ],
        ];

        $videoArgs = [
            'max_resolution' => ['type' => Type::string(), 'description' => 'Highest rendition in the manifest, e.g. `720p`.'],
            'min_resolution' => ['type' => Type::string(), 'description' => 'Lowest rendition in the manifest, e.g. `480p`.'],
            'rendition_order' => ['type' => Type::string(), 'description' => 'Rendition order in the manifest (`desc`).'],
            'redundant_streams' => ['type' => Type::boolean(), 'description' => 'Include redundant streams in the manifest.'],
            'roku_trick_play' => ['type' => Type::boolean(), 'description' => 'Add Roku trick play (timeline hover previews).'],
            'default_subtitles_lang' => ['type' => Type::string(), 'description' => 'BCP47 language code of the default subtitles track.'],
            'asset_start_time' => ['type' => Type::float(), 'description' => 'Instant clipping: start time relative to the asset, in seconds.'],
            'asset_end_time' => ['type' => Type::float(), 'description' => 'Instant clipping: end time relative to the asset, in seconds.'],
            'program_start_time' => ['type' => Type::int(), 'description' => 'Instant clipping: start time as an epoch timestamp.'],
            'program_end_time' => ['type' => Type::int(), 'description' => 'Instant clipping: end time as an epoch timestamp.'],
        ];

        $thumbnailArgs = [
            'time' => ['type' => Type::float(), 'description' => 'Video timestamp in seconds to take the thumbnail from.'],
            'width' => ['type' => Type::int(), 'description' => 'Width in pixels.'],
            'height' => ['type' => Type::int(), 'description' => 'Height in pixels.'],
            'fit_mode' => ['type' => Type::string(), 'description' => '`preserve`, `stretch`, `crop`, `smartcrop` or `pad`.'],
            'rotate' => ['type' => Type::int(), 'description' => 'Clockwise rotation: 90, 180 or 270.'],
            'flip_v' => ['type' => Type::boolean(), 'description' => 'Flip vertically.'],
            'flip_h' => ['type' => Type::boolean(), 'description' => 'Flip horizontally.'],
        ];

        $animatedArgs = [
            'start' => ['type' => Type::float(), 'description' => 'Start time in seconds (default 0).'],
            'end' => ['type' => Type::float(), 'description' => 'End time in seconds (default start + 5, max 10 seconds long).'],
            'width' => ['type' => Type::int(), 'description' => 'Width in pixels (max 640).'],
            'height' => ['type' => Type::int(), 'description' => 'Height in pixels (max 640).'],
            'fps' => ['type' => Type::int(), 'description' => 'Frame rate (default 15, max 30).'],
        ];

        $fields = [
            'id' => [
                'type' => Type::string(),
                'description' => 'ID of the playback'
            ],
            'policy' => [
                'type' => Type::string(),
                'description' => 'Policy of the playback'
            ],
            'token' => [
                'type' => Type::string(),
                'description' => 'A signed playback token (JWT) for a `signed` playback ID; `null` for public playback IDs or when the schema lacks the “Generate signed playback tokens” permission. Pass the URL modifiers for the token type as arguments: Mux reads them from the token, not the URL.',
                'args' => array_merge([
                    'type' => [
                        'type' => Type::string(),
                        'defaultValue' => 'v',
                        'description' => 'Token type: `v` (video, captions, MP4 downloads), `t` (thumbnail), `g` (GIF/animated WebP) or `s` (storyboard).',
                    ],
                ], $common, $videoArgs, $thumbnailArgs, $animatedArgs),
            ],
            'streamUrl' => [
                'type' => Type::string(),
                'description' => 'HLS playback URL (`https://stream.mux.com/{id}.m3u8`), with `?token=` for signed playback IDs.',
                'args' => array_merge($common, $videoArgs),
            ],
            'thumbnailUrl' => [
                'type' => Type::string(),
                'description' => 'Still thumbnail URL (`https://image.mux.com/{id}/thumbnail.{format}`), with `?token=` for signed playback IDs.',
                'args' => array_merge([
                    'format' => ['type' => Type::string(), 'defaultValue' => 'webp', 'description' => '`webp`, `jpg` or `png`.'],
                ], $common, $thumbnailArgs),
            ],
            'animatedUrl' => [
                'type' => Type::string(),
                'description' => 'Animated preview URL (`https://image.mux.com/{id}/animated.{format}`), with `?token=` for signed playback IDs.',
                'args' => array_merge([
                    'format' => ['type' => Type::string(), 'defaultValue' => 'gif', 'description' => '`gif` or `webp`.'],
                ], $common, $animatedArgs),
            ],
            'storyboardUrl' => [
                'type' => Type::string(),
                'description' => 'Timeline hover storyboard URL (`https://image.mux.com/{id}/storyboard.{format}`), with `?token=` for signed playback IDs.',
                'args' => array_merge([
                    'format' => ['type' => Type::string(), 'defaultValue' => 'vtt', 'description' => '`vtt`, `json`, `jpg`, `png` or `webp`.'],
                ], $common),
            ],
            'textTrackUrl' => [
                'type' => Type::string(),
                'description' => 'Caption/subtitle WebVTT URL (`https://stream.mux.com/{id}/text/{trackId}.vtt`), with `?token=` for signed playback IDs.',
                'args' => array_merge([
                    'trackId' => ['type' => Type::nonNull(Type::string()), 'description' => 'ID of a `text` track.'],
                ], $common),
            ],
            'staticRenditionUrl' => [
                'type' => Type::string(),
                'description' => 'Static rendition (MP4/M4A) URL (`https://stream.mux.com/{id}/{name}`), with `?token=` for signed playback IDs.',
                'args' => array_merge([
                    'name' => ['type' => Type::nonNull(Type::string()), 'description' => 'Rendition file name, e.g. `highest.mp4`, `1080p.mp4` or `audio.m4a`.'],
                    'download' => ['type' => Type::string(), 'description' => 'File name to download as.'],
                ], $common),
            ],
        ];

        return Craft::$app->getGql()->prepareFieldDefinitions($fields, self::getName());
    }

    /**
     * @inheritdoc
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = $resolveInfo->fieldName;
        $playbackId = is_array($source) ? $source : (array) $source;

        if (in_array($fieldName, ['id', 'policy'], true)) {
            return $playbackId[$fieldName] ?? null;
        }

        if (empty($playbackId['id'])) {
            return null;
        }

        // Signed tokens/URLs need the schema permission; public URLs don't contain secrets.
        if (SignedPlayback::isSigned($playbackId) && !GqlHelper::canGenerateSignedPlayback()) {
            return null;
        }

        $service = Mux::getInstance()->signedPlayback;
        $expiresIn = $arguments['expiresIn'] ?? null;
        $claims = self::claimArguments($arguments);

        switch ($fieldName) {
            case 'token':
                if (!SignedPlayback::isSigned($playbackId)) {
                    return null;
                }
                $type = strtolower(trim((string) ($arguments['type'] ?? 'v')));
                if (!isset(self::TOKEN_TYPES[$type])) {
                    throw new UserError(sprintf('Invalid token type "%s". Use v (video), t (thumbnail), g (gif) or s (storyboard).', $arguments['type']));
                }
                return $service->getToken($playbackId['id'], self::TOKEN_TYPES[$type], $claims, $expiresIn);
            case 'streamUrl':
                return $service->streamUrl($playbackId, $claims, $expiresIn);
            case 'thumbnailUrl':
                return $service->thumbnailUrl($playbackId, $claims, (string) ($arguments['format'] ?? 'webp'), $expiresIn);
            case 'animatedUrl':
                return $service->animatedUrl($playbackId, $claims, (string) ($arguments['format'] ?? 'gif'), $expiresIn);
            case 'storyboardUrl':
                return $service->storyboardUrl($playbackId, (string) ($arguments['format'] ?? 'vtt'), $claims, $expiresIn);
            case 'textTrackUrl':
                return $service->textTrackUrl($playbackId, (string) $arguments['trackId'], $claims, $expiresIn);
            case 'staticRenditionUrl':
                return $service->staticRenditionUrl($playbackId, (string) $arguments['name'], $arguments['download'] ?? null, $claims, $expiresIn);
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }

    /**
     * Returns the URL modifier arguments, keeping an explicit empty `playback_restriction_id`
     * (meaning “no restriction”) but dropping arguments that were not passed.
     */
    private static function claimArguments(array $arguments): array
    {
        $claims = $arguments;
        unset($claims['type'], $claims['expiresIn'], $claims['format'], $claims['trackId'], $claims['name'], $claims['download']);

        foreach ($claims as $name => $value) {
            if ($value === null) {
                unset($claims[$name]);
            }
        }

        return $claims;
    }
}
