<?php

/**
 * Mux plugin for Craft CMS
 *
 *
 * @link      https://rocketpark.com/
 * @copyright Copyright (c) 2023 rocketpark
 */

namespace rocketpark\mux\helpers;

require CRAFT_VENDOR_PATH . '/autoload.php';
use \Firebase\JWT\JWT as FBJWT;
 
 /**
  * @author    rocketpark
  * @package   Mux
  * @since     1.0.0
  */
 class JWT {
    /**
     * Get JWT
     * @param string $playbackId 
     * @param string $keyId 
     * @param string $keySecret 
     * @param string $type, v = video, t = thumbnail, g = gif, s = storyboard
     * @param null|array $options ["time" => 10, "width" => 640, "fit_mode" => "smartcrop"]
     * @return string 
     * @throws DomainException 
     */
    public static function getJWT(string $playbackId, string $keyId, string $keySecret, $type = "v", ?array $options ): string
    {

        $payload = [
            "sub" => $playbackId,
            "aud" => $type,                   // v = video, t = thumbnail, g = gif, s = storyboard
            "exp" => time() + (24 * 60 * 60), // Expiry time in epoch - in this case now + 24 hours
            "kid" => $keyId,
        ];

        if(!empty($options)) {
            // Optional, include any additional manipulations
            // "time"     => 10,
            // "width"    => 640,
            // "fit_mode" => "smartcrop"
            array_merge($options, $payload);
        }

        $jwt = FBJWT::encode($payload, base64_decode($keySecret), 'RS256');

        return $jwt;
    }
 }