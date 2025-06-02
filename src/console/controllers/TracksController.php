<?php
/**
 * @link https://rocketpark.com/
 * @copyright Copyright (c) Rocket Park.
 */

namespace rocketpark\mux\console\controllers;

use craft\console\Controller;
use craft\helpers\Console;
use rocketpark\mux\Mux;
use yii\console\ExitCode;

use craft\db\Command;
use craft\helpers\Db;
use yii\db\Query;

/**
 * Allows you to sync MUX assets
 *
 * @author RocketPark. <support@rocketpark.com>
 * @since 3.0
 */
class TracksController extends Controller
{
    public $defaultAction = 'generate audio tracks subtitles';

    /**
     * @return int
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws GlobalException
     */
    public function actionGenerateAudioTracksSubtitles(): int
    {
        $rows = (new Query())
        ->select(['id', 'asset_id', 'tracks'])
        ->from('{{%mux_assets}}')
        ->all();

        $results = [];

        foreach ($rows as $row) {
            $tracks = json_decode($row['tracks'], true);

            if (!is_array($tracks)) {
                continue;
            }

            $hasTextTrack = false;
            $audioTrackId = null;

            foreach ($tracks as $track) {
                if (($track['type'] ?? null) === 'text') {
                    $hasTextTrack = true;
                    break;
                }

                if (($track['type'] ?? null) === 'audio') {
                    $audioTrackId = $track['id'] ?? null;
                }
            }

            if (!$hasTextTrack && $audioTrackId) {
                $results[] = [
                    'asset_id' => $row['asset_id'],
                    'audio_track_id' => $audioTrackId,
                ];
            }
        }

        if (empty($results)) {
            $this->stdout('No audio tracks found without subtitles.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        foreach($results as $result) {
            $this->_generateAudioTracksSubtitles($result['asset_id'], $result['audio_track_id']);
        }

        return ExitCode::OK;
    }

    private function _generateAudioTracksSubtitles(string $asset_id, string $track_id): void
    {
        $this->stdout('Generating audio track subtitles for asset ID: ' . $asset_id . ' and track ID: ' . $track_id . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
        Mux::$plugin->assets->generateAssetTrackSubtitles($asset_id, $track_id);
        $this->stdout('Finished generating audio track subtitles for asset ID: ' . $asset_id . ' and track ID: ' . $track_id . PHP_EOL . PHP_EOL, Console::FG_GREEN);
    }
}