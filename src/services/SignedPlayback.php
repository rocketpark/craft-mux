<?php

/**
 * Mux plugin for Craft CMS
 *
 * @link      https://rocketpark.com/
 * @copyright Copyright (c) 2023 rocketpark
 */

namespace rocketpark\mux\services;

use Craft;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use DateTime;
use Firebase\JWT\JWT as FirebaseJWT;
use rocketpark\mux\Mux;
use rocketpark\mux\models\Settings;
use rocketpark\mux\models\SignedKey;
use rocketpark\mux\records\SignedKeys as SignedKeysRecord;
use Throwable;
use yii\base\Component;

/**
 * Signed Playback service
 *
 * Creates the JSON Web Tokens Mux requires for `signed` playback IDs and builds playback,
 * image, storyboard, caption and download URLs for both `public` and `signed` playback IDs.
 *
 * For a signed playback ID every URL modifier (thumbnail `width`, `time`, stream
 * `max_resolution`, …) has to be a claim inside the token, and the URL may only carry
 * `?token=`. For a public playback ID the same modifiers go into the query string instead.
 *
 * Token expiry is aligned to a fixed rotation window so that the same request returns the
 * same token for the length of a window. That keeps rendered pages, GraphQL responses and
 * CDN caches stable, while still guaranteeing that every token handed out stays valid for at
 * least the requested lifetime.
 *
 * @see https://www.mux.com/docs/guides/secure-video-playback
 */
class SignedPlayback extends Component
{
    // Constants
    // =========================================================================

    /** Token audience for video playback, captions/subtitles and MP4 downloads. */
    public const AUD_VIDEO = 'v';

    /** Token audience for still thumbnails. */
    public const AUD_THUMBNAIL = 't';

    /** Token audience for animated GIF/WebP previews. */
    public const AUD_GIF = 'g';

    /** Token audience for timeline hover storyboards. */
    public const AUD_STORYBOARD = 's';

    /** Default token lifetime in seconds (24 hours). */
    public const DEFAULT_LIFETIME = 86400;

    /** Default upper bound for a requested token lifetime in seconds (365 days). */
    public const DEFAULT_MAX_LIFETIME = 31536000;

    /** Default rotation window in seconds (1 hour). */
    public const DEFAULT_WINDOW = 3600;

    public const STREAM_HOST = 'https://stream.mux.com';
    public const IMAGE_HOST = 'https://image.mux.com';

    /**
     * Claims Mux reads from a token, per audience. `playback_restriction_id` is valid for every
     * audience and is handled separately. Anything else passed in is dropped, so a modifier that
     * does not apply to a URL type (e.g. `fit_mode` on a GIF) never reaches Mux.
     */
    public const AUDIENCE_CLAIMS = [
        self::AUD_VIDEO => [
            'max_resolution',
            'min_resolution',
            'rendition_order',
            'redundant_streams',
            'roku_trick_play',
            'default_subtitles_lang',
            'program_start_time',
            'program_end_time',
            'asset_start_time',
            'asset_end_time',
        ],
        self::AUD_THUMBNAIL => [
            'time',
            'width',
            'height',
            'rotate',
            'fit_mode',
            'flip_v',
            'flip_h',
            'latest',
            'program_time',
        ],
        self::AUD_GIF => [
            'start',
            'end',
            'width',
            'height',
            'fps',
        ],
        self::AUD_STORYBOARD => [
            'program_start_time',
            'program_end_time',
        ],
    ];

    /** Claims the plugin sets itself; callers can never override them. */
    private const RESERVED_CLAIMS = ['sub', 'aud', 'exp', 'kid', 'iat', 'nbf'];

    private const CACHE_PREFIX = 'mux-jwt:';

    /** Element cache tag added to caches that contain signed playback tokens. */
    public const CACHE_TAG = 'mux:signed-playback';

    // Properties
    // =========================================================================

    /**
     * @var SignedKey[]|null Signing keys, loaded once per request.
     */
    private ?array $_signingKeys = null;

    // Public Methods
    // =========================================================================

    /**
     * Returns whether a playback ID entry (`['id' => …, 'policy' => …]`) is signed.
     */
    public static function isSigned(?array $playbackId): bool
    {
        return ($playbackId['policy'] ?? null) === 'signed';
    }

    /**
     * Returns whether `$aud` is a valid token audience.
     */
    public static function isValidAudience(string $aud): bool
    {
        return array_key_exists($aud, self::AUDIENCE_CLAIMS);
    }

    /**
     * Default token lifetime in seconds, from the `signedTokenLifetime` setting.
     */
    public function getDefaultLifetime(): int
    {
        $value = (int) ($this->settings()?->signedTokenLifetime ?? self::DEFAULT_LIFETIME);

        return $value > 0 ? min($value, $this->getMaxLifetime()) : self::DEFAULT_LIFETIME;
    }

    /**
     * Upper bound for any requested token lifetime in seconds, from the `maxSignedTokenLifetime` setting.
     */
    public function getMaxLifetime(): int
    {
        $value = (int) ($this->settings()?->maxSignedTokenLifetime ?? self::DEFAULT_MAX_LIFETIME);

        return $value > 0 ? $value : self::DEFAULT_MAX_LIFETIME;
    }

    /**
     * Rotation window in seconds, from the `signedTokenWindow` setting.
     */
    public function getWindow(): int
    {
        $value = (int) ($this->settings()?->signedTokenWindow ?? self::DEFAULT_WINDOW);

        return $value > 0 ? $value : self::DEFAULT_WINDOW;
    }

    /**
     * Default `playback_restriction_id` claim, from the env-aware `playbackRestrictionId` setting.
     */
    public function getDefaultPlaybackRestrictionId(): ?string
    {
        $value = trim((string) App::parseEnv($this->settings()?->playbackRestrictionId ?? ''));

        return $value !== '' ? $value : null;
    }

    /**
     * Describes where the default playback restriction comes from, for the control panel.
     *
     * `source` is `config` (set in `config/mux.php`), `env` (the setting references an environment
     * variable), `setting` (an ID saved in the plugin settings) or `none`.
     *
     * @return array{id: string|null, raw: string, source: string}
     */
    public function getDefaultPlaybackRestrictionInfo(): array
    {
        $raw = trim((string) ($this->settings()?->playbackRestrictionId ?? ''));
        $plugin = Mux::getInstance();

        if ($plugin && $plugin->settings->isSettingOverridden('playbackRestrictionId')) {
            $source = 'config';
        } elseif (str_starts_with($raw, '$')) {
            $source = 'env';
        } elseif ($raw !== '') {
            $source = 'setting';
        } else {
            $source = 'none';
        }

        return [
            'id' => $this->getDefaultPlaybackRestrictionId(),
            'raw' => $raw,
            'source' => $source,
        ];
    }

    /**
     * Clamps a requested lifetime to `1 … maxSignedTokenLifetime`, falling back to the default.
     */
    public function resolveLifetime(?int $expiresIn): int
    {
        if ($expiresIn === null || $expiresIn <= 0) {
            return $this->getDefaultLifetime();
        }

        return min($expiresIn, $this->getMaxLifetime());
    }

    /**
     * Returns `[exp, stableUntil]` for a lifetime.
     *
     * `exp` is the first window boundary strictly after `now + lifetime`, so a token is valid for
     * more than `lifetime` and at most `lifetime + window` seconds. The same `exp` (and therefore
     * the same token) is returned until `stableUntil`, which is always in the future. Anything
     * that caches a token or a page containing one must expire by `stableUntil` to keep the
     * "at least `lifetime` remaining" guarantee.
     *
     * The window is never longer than the lifetime, so short-lived tokens stay short-lived.
     *
     * @return array{0:int,1:int}
     */
    public function getExpiry(int $lifetime, ?int $now = null): array
    {
        $now ??= DateTimeHelper::currentTimeStamp();
        $window = max(1, min($this->getWindow(), $lifetime));

        $exp = (intdiv($now + $lifetime, $window) + 1) * $window;

        return [$exp, $exp - $lifetime];
    }

    /**
     * Keeps only the claims Mux accepts for `$aud` and normalizes their values.
     *
     * Adds the default `playback_restriction_id` unless one was passed (`null` keeps the default; pass an empty string to
     * leave it out for a single token).
     */
    public function filterClaims(string $aud, array $claims): array
    {
        $allowed = self::AUDIENCE_CLAIMS[$aud] ?? [];
        $filtered = [];

        foreach ($allowed as $name) {
            if (!array_key_exists($name, $claims)) {
                continue;
            }

            $value = $this->normalizeValue($claims[$name]);

            if ($value !== null) {
                $filtered[$name] = $value;
            }
        }

        // `null` (e.g. an unset Twig variable) keeps the default; only an explicit '' leaves it out.
        if (isset($claims['playback_restriction_id'])) {
            $restriction = $this->normalizeValue($claims['playback_restriction_id']);
        } else {
            $restriction = $this->getDefaultPlaybackRestrictionId();
        }

        if ($restriction !== null && $restriction !== '') {
            $filtered['playback_restriction_id'] = (string) $restriction;
        }

        ksort($filtered);

        return $filtered;
    }

    /**
     * Returns the signing key to use: the one matching `$keyId`, or the oldest key when no ID is given.
     */
    public function getSigningKey(?string $keyId = null): ?SignedKey
    {
        $keys = $this->getSigningKeys();

        if ($keyId === null || $keyId === '') {
            return $keys[0] ?? null;
        }

        foreach ($keys as $key) {
            if ($key->key_id === $keyId) {
                return $key;
            }
        }

        Mux::warning("Mux signing key \"{$keyId}\" was requested but does not exist.");

        return null;
    }

    /**
     * Returns all stored signing keys, oldest first.
     *
     * @return SignedKey[]
     */
    public function getSigningKeys(): array
    {
        if ($this->_signingKeys === null) {
            $this->_signingKeys = array_map(
                fn(SignedKeysRecord $record) => new SignedKey($record->getAttributes()),
                SignedKeysRecord::find()->orderBy(['id' => SORT_ASC])->all()
            );
        }

        return $this->_signingKeys;
    }

    /**
     * Forgets the signing keys loaded for this request (after a key was created or deleted).
     */
    public function reset(): void
    {
        $this->_signingKeys = null;
    }

    /**
     * Returns a signed JWT for a playback ID, or `null` when no signing key is available.
     *
     * Also limits any element cache being collected (Twig `{% cache %}`, GraphQL response cache)
     * so it never serves this token past its stable period.
     *
     * @param string $playbackId Mux playback ID (must have the `signed` policy for Mux to accept the token)
     * @param string $aud `v` video, `t` thumbnail, `g` GIF, `s` storyboard
     * @param array $claims URL modifiers for this audience, plus an optional `playback_restriction_id`
     * @param int|null $expiresIn Minimum lifetime in seconds; defaults to `signedTokenLifetime`, capped at `maxSignedTokenLifetime`
     * @param string|null $keyId Signing key ID; defaults to the oldest stored key
     */
    public function getToken(string $playbackId, string $aud = self::AUD_VIDEO, array $claims = [], ?int $expiresIn = null, ?string $keyId = null): ?string
    {
        if ($playbackId === '' || !self::isValidAudience($aud)) {
            return null;
        }

        $key = $this->getSigningKey($keyId);

        if (!$key) {
            if ($keyId === null) {
                Mux::warning("Cannot sign Mux playback ID \"{$playbackId}\": no signing keys exist. Create one under Mux → Settings → Signing Keys.");
            }
            return null;
        }

        $claims = $this->filterClaims($aud, $claims);
        [$exp, $stableUntil] = $this->getExpiry($this->resolveLifetime($expiresIn));

        $this->limitCacheExpiry($stableUntil);

        $cache = Craft::$app->getCache();
        $cacheKey = self::CACHE_PREFIX . sha1(json_encode([$playbackId, $aud, $claims, $exp, $key->key_id]));
        $token = $cache->get($cacheKey);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        $payload = array_merge($claims, [
            'sub' => $playbackId,
            'aud' => $aud,
            'exp' => $exp,
            'kid' => $key->key_id,
        ]);

        try {
            $token = FirebaseJWT::encode($payload, base64_decode((string) $key->private_key), 'RS256', $key->key_id);
        } catch (Throwable $e) {
            Mux::error("Could not sign Mux playback ID \"{$playbackId}\" with key \"{$key->key_id}\": {$e->getMessage()}");
            return null;
        }

        $ttl = $stableUntil - DateTimeHelper::currentTimeStamp();

        if ($ttl > 0) {
            $cache->set($cacheKey, $token, $ttl);
        }

        return $token;
    }

    /**
     * Builds a Mux URL for a playback ID.
     *
     * Public playback IDs get the modifiers as query parameters. Signed playback IDs get only
     * `?token=` (plus any `$passthrough` parameters Mux allows next to a token, such as
     * `download`) and return `null` when no token can be created.
     *
     * @param array $playbackId `['id' => …, 'policy' => 'public'|'signed']`
     * @param string $aud Token audience for this URL
     * @param string $url Base URL without query string
     * @param array $params URL modifiers (become claims for signed IDs)
     * @param array $passthrough Query parameters that are never part of the token
     */
    public function buildUrl(array $playbackId, string $aud, string $url, array $params = [], ?int $expiresIn = null, ?string $keyId = null, array $passthrough = []): ?string
    {
        $id = (string) ($playbackId['id'] ?? '');

        if ($id === '') {
            return null;
        }

        $passthrough = $this->queryValues($passthrough);

        if (!self::isSigned($playbackId)) {
            // `playback_restriction_id` is a token-only claim.
            unset($params['playback_restriction_id']);
            $query = $this->queryValues(array_intersect_key($params, array_flip(self::AUDIENCE_CLAIMS[$aud] ?? [])));
            return $this->appendQuery($url, array_merge($query, $passthrough));
        }

        $token = $this->getToken($id, $aud, $params, $expiresIn, $keyId);

        if ($token === null) {
            return null;
        }

        return $this->appendQuery($url, array_merge(['token' => $token], $passthrough));
    }

    /**
     * HLS stream URL: `https://stream.mux.com/{id}.m3u8`
     */
    public function streamUrl(array $playbackId, array $params = [], ?int $expiresIn = null, ?string $keyId = null): ?string
    {
        return $this->buildUrl($playbackId, self::AUD_VIDEO, self::STREAM_HOST . '/' . rawurlencode((string) ($playbackId['id'] ?? '')) . '.m3u8', $params, $expiresIn, $keyId);
    }

    /**
     * Still thumbnail URL: `https://image.mux.com/{id}/thumbnail.{format}`
     */
    public function thumbnailUrl(array $playbackId, array $params = [], string $format = 'webp', ?int $expiresIn = null, ?string $keyId = null): ?string
    {
        $format = $this->normalizeFormat($format, ['webp', 'jpg', 'png'], 'webp');

        return $this->buildUrl($playbackId, self::AUD_THUMBNAIL, $this->imageBase($playbackId) . "/thumbnail.{$format}", $params, $expiresIn, $keyId);
    }

    /**
     * Animated preview URL: `https://image.mux.com/{id}/animated.{format}`
     */
    public function animatedUrl(array $playbackId, array $params = [], string $format = 'gif', ?int $expiresIn = null, ?string $keyId = null): ?string
    {
        $format = $this->normalizeFormat($format, ['gif', 'webp'], 'gif');

        return $this->buildUrl($playbackId, self::AUD_GIF, $this->imageBase($playbackId) . "/animated.{$format}", $params, $expiresIn, $keyId);
    }

    /**
     * Storyboard URL: `https://image.mux.com/{id}/storyboard.{format}`
     */
    public function storyboardUrl(array $playbackId, string $format = 'vtt', array $params = [], ?int $expiresIn = null, ?string $keyId = null): ?string
    {
        $format = $this->normalizeFormat($format, ['vtt', 'json', 'jpg', 'png', 'webp'], 'vtt');

        return $this->buildUrl($playbackId, self::AUD_STORYBOARD, $this->imageBase($playbackId) . "/storyboard.{$format}", $params, $expiresIn, $keyId);
    }

    /**
     * Caption/subtitle track URL: `https://stream.mux.com/{id}/text/{trackId}.vtt`
     */
    public function textTrackUrl(array $playbackId, string $trackId, array $params = [], ?int $expiresIn = null, ?string $keyId = null): ?string
    {
        if ($trackId === '') {
            return null;
        }

        $url = self::STREAM_HOST . '/' . rawurlencode((string) ($playbackId['id'] ?? '')) . '/text/' . rawurlencode($trackId) . '.vtt';

        return $this->buildUrl($playbackId, self::AUD_VIDEO, $url, $params, $expiresIn, $keyId);
    }

    /**
     * Static rendition (MP4/M4A) URL: `https://stream.mux.com/{id}/{name}`, e.g. `highest.mp4` or `1080p.mp4`.
     *
     * @param string|null $download File name for the `download` parameter (forces a download in browsers)
     */
    public function staticRenditionUrl(array $playbackId, string $name, ?string $download = null, array $params = [], ?int $expiresIn = null, ?string $keyId = null): ?string
    {
        if ($name === '') {
            return null;
        }

        $url = self::STREAM_HOST . '/' . rawurlencode((string) ($playbackId['id'] ?? '')) . '/' . rawurlencode($name);
        $passthrough = ($download !== null && $download !== '') ? ['download' => $download] : [];

        return $this->buildUrl($playbackId, self::AUD_VIDEO, $url, $params, $expiresIn, $keyId, $passthrough);
    }

    // Private Methods
    // =========================================================================

    private function settings(): ?Settings
    {
        return Mux::getInstance()?->getSettings();
    }

    /**
     * Tells the element cache collector (GraphQL response cache, `{% cache %}` tags) to expire
     * no later than `$timestamp`.
     */
    private function limitCacheExpiry(int $timestamp): void
    {
        $elements = Craft::$app->getElements();

        if (!$elements->getIsCollectingCacheInfo()) {
            return;
        }

        // Craft ignores the expiry date of a collection without tags (e.g. a `{% cache %}` block
        // that only outputs tokens for an element fetched outside it), so always add one.
        $elements->collectCacheTags([self::CACHE_TAG]);
        $elements->setCacheExpiryDate((new DateTime())->setTimestamp($timestamp));
    }

    private function imageBase(array $playbackId): string
    {
        return self::IMAGE_HOST . '/' . rawurlencode((string) ($playbackId['id'] ?? ''));
    }

    private function normalizeFormat(string $format, array $allowed, string $default): string
    {
        $format = strtolower(ltrim(trim($format), '.'));

        return in_array($format, $allowed, true) ? $format : $default;
    }

    /**
     * Drops `null` and empty-string values; trims strings. Booleans and numbers are kept as-is.
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            return $value === '' ? null : $value;
        }

        if (is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Converts values to their query-string form (`true`/`false` for booleans), dropping empty ones.
     */
    private function queryValues(array $params): array
    {
        $query = [];

        foreach ($params as $name => $value) {
            if (in_array($name, self::RESERVED_CLAIMS, true)) {
                continue;
            }

            $value = $this->normalizeValue($value);

            if ($value === null) {
                continue;
            }

            $query[$name] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        return $query;
    }

    private function appendQuery(string $url, array $query): string
    {
        if (empty($query)) {
            return $url;
        }

        return $url . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
