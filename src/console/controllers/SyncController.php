<?php
/**
 * @link https://rocketpark.com/
 * @copyright Copyright (c) Rocket Park.
 */

namespace rocketpark\mux\console\controllers;

use craft\console\Controller;
use craft\helpers\Console;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset;
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

    /**
     * Sync all MUX assets
     */
    public function actionAll()
    {
        $this->_syncMuxAssets();
        return ExitCode::OK;
    }


    private function _syncMuxAssets(): void
    {
        $this->stdout('Syncing MUX Assets…' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        
        // Get all available volumes
        $volumes = Mux::$plugin->volumes->getAllVolumes();
        
        if (empty($volumes)) {
            $this->stdout('Error: No volumes exist. Please create a volume first.' . PHP_EOL, Console::FG_RED);
            return;
        }
        
        // Check if this is an initial sync (no MuxAssets exist yet)
        $existingAssets = MuxAsset::find()->limit(1)->count();
        $isInitialSync = $existingAssets === 0;
        
        $selectedVolumeId = null;
        $selectedFolderId = null;
        
        // Volume Selection
        if ($isInitialSync && count($volumes) === 1) {
            // Auto-select the only volume
            $volume = $volumes[0];
            $selectedVolumeId = $volume->id;
            $this->stdout("Auto-selecting volume: {$volume->name} (ID: {$volume->id})" . PHP_EOL . PHP_EOL, Console::FG_CYAN);
        } else {
            // Prompt user to select a volume
            $this->stdout('Please select a volume for syncing new assets:' . PHP_EOL, Console::FG_CYAN);
            $volumeOptions = [];
            foreach ($volumes as $volume) {
                $volumeOptions[$volume->id] = "ID: {$volume->id} - {$volume->name}";
            }
            
            $selectedVolumeId = (int) Console::select('Select volume:', $volumeOptions);
            $this->stdout(PHP_EOL);
        }
        
        // Folder Selection
        $folders = Mux::$plugin->folders->findFolders(['volumeId' => $selectedVolumeId]);
        
        if (empty($folders) || count($folders) === 1) {
            // Use root folder if no folders or only one folder exists
            $rootFolder = Mux::$plugin->folders->getRootFolderByVolumeId($selectedVolumeId);
            $selectedFolderId = $rootFolder->id;
            $this->stdout("Using root folder: {$rootFolder->name} (ID: {$rootFolder->id})" . PHP_EOL . PHP_EOL, Console::FG_CYAN);
        } else {
            // Prompt user to select a folder when multiple folders exist
            $this->stdout('Please select a folder for syncing new assets:' . PHP_EOL, Console::FG_CYAN);
            $folderOptions = [];
            foreach ($folders as $folder) {
                $pathDisplay = $folder->path ? " ({$folder->path})" : " (root)";
                $folderOptions[$folder->id] = "ID: {$folder->id} - {$folder->name}{$pathDisplay}";
            }
            
            $selectedFolderId = (int) Console::select('Select folder:', $folderOptions);
            $this->stdout(PHP_EOL);
        }
        
        // Perform the sync with selected volume and folder
        Mux::$plugin->assets->syncAllMuxAssets($selectedVolumeId, $selectedFolderId);
        
        $this->stdout('Finished Syncing Mux Assets' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
    }
}