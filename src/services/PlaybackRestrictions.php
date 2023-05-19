<?php

namespace rocketpark\mux\services;

use Craft;
use yii\base\Component;

use rocketpark\mux\Mux;
use GuzzleHttp\Client;
use craft\helpers\UrlHelper;
use craft\helpers\App;

use MuxPhp;
use MuxPhp\ApiException;
use MuxPhp\Configuration;
use MuxPhp\Models\Asset;
use MuxPhp\Models\AssetResponse;
use MuxPhp\Models\ListAssetsResponse;
use MuxPhp\Models\Upload;
use yii\base\Exception;

/**
 * Playback Restrictions service
 */
class PlaybackRestrictions extends Component
{

     /**
     * Creates MUX API Configuration
     * @return Configuration 
     * @throws BaseInvalidArgumentException 
     */
    private function muxConf(): MuxPhp\Configuration
    {
        $settings = Mux::$settings;
        // Authentication Setup
        return MuxPhp\Configuration::getDefaultConfiguration()
            ->setUsername(App::parseEnv($settings->muxTokenId))
            ->setPassword(App::parseEnv($settings->muxTokenSecret));
    }

    /**
     * Create Playback Restriction
     * @param null|string $create_playback_restriction_request
     * {
     *   "referrer": {
     *       "allowed_domains": [
     *          "*.example.com"
     *        ],
     *        "allow_no_referrer": true
     *    }
     * }
     * @return true[]|void 
     */
    public function createPlaybackRestriction(?array $request)
    {
        $config = Mux::$plugin->playbackRestrictions->muxConf();
        $apiInstance = new MuxPhp\Api\PlaybackRestrictionsApi(
            new Client(),
            $config
        );

        try {
            //$create_playback_restriction_request = json_decode('{"referrer":{"allowed_domains":["*.example.com"],"allow_no_referrer":true}}',true);
            $apiInstance->createPlaybackRestriction($request);
            return ['success' => true];
        } catch (Exception $e) {
            throw new Exception("Exception when calling PlaybackRestrictionsApi->createPlaybackRestriction: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete Playback Restriction
     * @param null|string $playback_restriction_id
     * @return true[]|void 
     */
    public function deletePlaybackRestriction(?string $playback_restriction_id)
    {
        $config = Mux::$plugin->playbackRestrictions->muxConf();
        $apiInstance = new MuxPhp\Api\PlaybackRestrictionsApi(
            new Client(),
            $config
        );

        try {
            $apiInstance->deletePlaybackRestriction($playback_restriction_id);
            return ['success' => true];
        } catch (Exception $e) {
            throw new Exception("Exception when calling PlaybackRestrictionsApi->deletePlaybackRestriction: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update Referrer Domain Restriction
     * @param null|string $playback_restriction_id
     * @param null|string $request
     * {
     *    "allowed_domains": [
     *       "*.example.com"
     *     ],
     *     "allow_no_referrer": true
     * }
     * @return true[]|void 
     */
    public function updateReferrerDomainRestriction(?string $playback_restriction_id, ?array $request)
    {
        $config = Mux::$plugin->playbackRestrictions->muxConf();
        $apiInstance = new MuxPhp\Api\PlaybackRestrictionsApi(
            new Client(),
            $config
        );

        try {
            // $request = json_decode('{"allowed_domains":["*.example.com"],"allow_no_referrer":true}',true);
            $apiInstance->updateReferrerDomainRestriction($playback_restriction_id, $request);
            return ['success' => true];
        } catch (Exception $e) {
            throw new Exception("Exception when calling PlaybackRestrictionsApi->updateReferrerDomainRestriction: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    /**
     * Get Playback Restriction
     * @param null|string $playback_restriction_id
     * @return true[]|void 
     */
    public function getPlaybackRestriction(?string $playback_restriction_id)
    {
        $config = Mux::$plugin->playbackRestrictions->muxConf();
        $apiInstance = new MuxPhp\Api\PlaybackRestrictionsApi(
            new Client(),
            $config
        );

        try {
            $result = $apiInstance->getPlaybackRestriction($playback_restriction_id);
            return [
                'success' => true,
                'result' => $result
            ];
        } catch (Exception $e) {
            throw new Exception("Exception when calling PlaybackRestrictionsApi->getPlaybackRestriction: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List Playback Restrictions
     * @param null|int $page defaults to 1
     * @param null|int $limit defaults to 50
     * @return true[]|void 
     */
    public function listPlaybackRestrictions(?int $page = 1, ?int $limit = 50)
    {
        $config = Mux::$plugin->playbackRestrictions->muxConf();
        $apiInstance = new MuxPhp\Api\PlaybackRestrictionsApi(
            new Client(),
            $config
        );

        try {
            $result = $apiInstance->listPlaybackRestrictions($page, $limit);
            return [
                'success' => true,
                'result' => $result
            ];
        } catch (Exception $e) {
            throw new Exception("Exception when calling PlaybackRestrictionsApi->listPlaybackRestrictions: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
