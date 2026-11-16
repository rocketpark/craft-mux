<?php

/**
 * Mux plugin for Craft CMS
 *
 *
 * @link      https://rocketpark.com/
 * @copyright Copyright (c) 2023 rocketpark
 */

namespace rocketpark\mux\helpers;

use Firebase\JWT\JWT as FBJWT;
use rocketpark\mux\Mux;
use rocketpark\mux\services\SignedPlayback;

/**
 * @author    rocketpark
 * @package   Mux
 * @since     1.0.0
 * @deprecated in 2.8.0. Use `Mux::getInstance()->signedPlayback->getToken()`, which also picks the
 * signing key, filters claims per token type, aligns expiry to the rotation window and caches tokens.
 */
class JWT
{
    /**
     * Get JWT
     * @param string $playbackId
     * @param string $keyId
     * @param string $keySecret Base64-encoded private key
     * @param string $type v = video, t = thumbnail, g = gif, s = storyboard
     * @param null|array $options Claims for this token type, e.g. ["time" => 10, "width" => 640, "fit_mode" => "smartcrop"]
     * @param null|int $expiresIn Minimum lifetime in seconds; defaults to the `signedTokenLifetime` setting
     * @return string
     * @throws \DomainException
     */
    public static function getJWT(string $playbackId, string $keyId, string $keySecret, string $type = 'v', ?array $options = null, ?int $expiresIn = null): string
    {
        $service = Mux::getInstance()?->signedPlayback ?? new SignedPlayback();
        [$exp] = $service->getExpiry($service->resolveLifetime($expiresIn));

        $payload = array_merge($service->filterClaims($type, $options ?? []), [
            'sub' => $playbackId,
            'aud' => $type, // v = video, t = thumbnail, g = gif, s = storyboard
            'exp' => $exp,
            'kid' => $keyId,
        ]);

        return FBJWT::encode($payload, base64_decode($keySecret), 'RS256', $keyId);
    }
}
