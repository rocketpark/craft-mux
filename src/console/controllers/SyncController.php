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

/**
 * Allows you to sync MUX assets
 *
 * @author RocketPark. <support@rocketpark.com>
 * @since 3.0
 */
class SyncController extends Controller
{
    public $defaultAction = 'mux assets';

    public function actionAll()
    {
        $this->_syncMuxAssets();
        return ExitCode::OK;
    }

    /**
     * Reset Mux Asset data.
     */
    public function actionMuxAssets(): int
    {
        $this->_syncMuxAssets();
        return ExitCode::OK;
    }

    private function _syncMuxAssets(): void
    {
        $this->stdout('Syncing MUX Assets…' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        Mux::$plugin->assets->syncAllMuxAssets();
        $this->stdout('Finished Syncing Mux Assets' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
    }
}