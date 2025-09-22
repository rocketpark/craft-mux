<?php
/**
 * @link https://rocketpark.com/
 * @copyright Copyright (c) Rocket Park.
 */

namespace rocketpark\mux\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\Db;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset;
use yii\console\ExitCode;

/**
 * Allows you to assign volumeId and folderId to MuxAsset elements
 * @example
 * Example usages:
 * 
 * # Assign volumeId 1 to all assets with null volumeId
 * php craft mux/assign/assign --volumeId=1
 * 
 * # Assign folderId 2 to all assets with null folderId  
 * php craft mux/assign/assign --folderId=2
 * 
 * # Assign both volumeId 1 and folderId 2 to assets with null values
 * php craft mux/assign/assign --volumeId=1 --folderId=2
 * 
 * # Dry run to see what would be changed without making changes
 * php craft mux/assign/assign --volumeId=1 --folderId=2 --dryRun
 * 
 * # Force assignment without confirmation prompts
 * php craft mux/assign/assign --volumeId=1 --folderId=2 --force
 * 
 * # Show verbose output during processing
 * php craft mux/assign/assign --volumeId=1 --folderId=2 --verbose
 * 
 * # Show statistics about assets with null volumeId or folderId
 * php craft mux/assign/stats
 * 
 * # List all assets that have null volumeId or folderId
 * php craft mux/assign/list
 *
 * # List all volumes and folders with their id and name
 * php craft mux/assign/assign/list-volumes-and-folders
 *
 * @author RocketPark. <support@rocketpark.com>
 * @since 2.4.0
 */
class AssignController extends Controller
{
    /**
     * @var int|null The volume ID to assign
     */
    public $volumeId;

    /**
     * @var int|null The folder ID to assign
     */
    public $folderId;

    /**
     * @var bool Whether to show verbose output
     */
    public $verbose = false;

    /**
     * @var bool Whether to force assignment without confirmation
     */
    public $force = false;

    /**
     * @var bool Whether to only show what would be assigned without making changes
     */
    public $dryRun = false;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        
        switch ($actionID) {
            case 'assign':
                $options[] = 'volumeId';
                $options[] = 'folderId';
                $options[] = 'verbose';
                $options[] = 'force';
                $options[] = 'dryRun';
                break;
        }
        
        return $options;
    }

    public function optionAliases(): array
    {
        return [
            'v' => 'volumeId',
            'f' => 'folderId',
            'V' => 'verbose',
            'F' => 'force',
            'd' => 'dryRun',
        ];
    }

    /**
     * Assign volumeId and folderId to MuxAsset elements that have null values
     */
    public function actionAssign(): int
    {
        // Validate required parameters
        if (!$this->volumeId && !$this->folderId) {
            $this->stdout('Error: At least one of --volumeId or --folderId must be provided.' . PHP_EOL, Console::FG_RED);
            $this->stdout('Usage: php craft mux/assign/assign --volumeId=1 --folderId=2' . PHP_EOL);
            return ExitCode::DATAERR;
        }

        // Validate volumeId if provided
        if ($this->volumeId) {
            $volume = Mux::$plugin->volumes->getVolumeById($this->volumeId);
            if (!$volume) {
                $this->stdout("Error: Volume with ID '{$this->volumeId}' not found." . PHP_EOL, Console::FG_RED);
                return ExitCode::DATAERR;
            }
            $this->stdout("Target Volume: {$volume->name} (ID: {$this->volumeId})" . PHP_EOL, Console::FG_CYAN);
        }

        // Validate folderId if provided
        if ($this->folderId) {
            $folder = Mux::$plugin->folders->getFolderById($this->folderId);
            if (!$folder) {
                $this->stdout("Error: Folder with ID '{$this->folderId}' not found." . PHP_EOL, Console::FG_RED);
                return ExitCode::DATAERR;
            }
            $this->stdout("Target Folder: {$folder->name} (ID: {$this->folderId})" . PHP_EOL, Console::FG_CYAN);
        }

        // Find assets with null volumeId and/or folderId
        $query = MuxAsset::find();
        
        // Build conditions for null values
        $conditions = ['or'];
        
        if ($this->volumeId) {
            $conditions[] = ['volumeId' => null];
        }
        
        if ($this->folderId) {
            $conditions[] = ['folderId' => null];
        }

        $query->where($conditions);
        $assets = $query->all();
        $totalAssets = count($assets);

        if ($totalAssets === 0) {
            $this->stdout('No MuxAsset elements found with null volumeId or folderId.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout(PHP_EOL . "Found {$totalAssets} MuxAsset element(s) to update:" . PHP_EOL, Console::FG_GREEN);

        // Show what will be updated
        foreach ($assets as $asset) {
            $updates = [];
            if ($this->volumeId && $asset->volumeId === null) {
                $updates[] = "volumeId: null → {$this->volumeId}";
            }
            if ($this->folderId && $asset->folderId === null) {
                $updates[] = "folderId: null → {$this->folderId}";
            }
            
            $this->stdout("  - Asset ID: {$asset->asset_id} ({$asset->title})" . PHP_EOL);
            $this->stdout("    Updates: " . implode(', ', $updates) . PHP_EOL);
        }

        // Confirm before proceeding
        if (!$this->force && !$this->dryRun) {
            if (!$this->confirm(PHP_EOL . 'Do you want to proceed with these assignments?')) {
                $this->stdout('Operation cancelled.' . PHP_EOL, Console::FG_YELLOW);
                return ExitCode::OK;
            }
        }

        if ($this->dryRun) {
            $this->stdout(PHP_EOL . 'Dry run completed. No changes were made.' . PHP_EOL, Console::FG_CYAN);
            return ExitCode::OK;
        }

        // Perform the assignments
        $this->stdout(PHP_EOL . 'Assigning volumeId and folderId...' . PHP_EOL, Console::FG_GREEN);
        
        $successCount = 0;
        $errorCount = 0;

        foreach ($assets as $asset) {
            try {
                $updated = false;
                
                if ($this->volumeId && $asset->volumeId === null) {
                    $asset->volumeId = $this->volumeId;
                    $updated = true;
                }
                
                if ($this->folderId && $asset->folderId === null) {
                    $asset->folderId = $this->folderId;
                    $updated = true;
                }

                if ($updated) {
                    if ($this->verbose) {
                        $this->stdout("Updating asset: {$asset->asset_id} ({$asset->title})..." . PHP_EOL);
                    }

                    if (Craft::$app->getElements()->saveElement($asset)) {
                        $successCount++;
                        if ($this->verbose) {
                            $this->stdout("✓ Successfully updated asset: {$asset->asset_id}" . PHP_EOL, Console::FG_GREEN);
                        }
                    } else {
                        $errorCount++;
                        $errors = implode(', ', $asset->getFirstErrors());
                        $this->stdout("✗ Failed to update asset {$asset->asset_id}: {$errors}" . PHP_EOL, Console::FG_RED);
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->stdout("✗ Error updating asset {$asset->asset_id}: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
            }
        }

        // Summary
        $this->stdout(PHP_EOL . "Assignment completed!" . PHP_EOL, Console::FG_GREEN);
        $this->stdout("Assets processed: {$totalAssets}" . PHP_EOL);
        $this->stdout("Successfully updated: {$successCount}" . PHP_EOL, Console::FG_GREEN);
        
        if ($errorCount > 0) {
            $this->stdout("Errors: {$errorCount}" . PHP_EOL, Console::FG_RED);
        }

        return ExitCode::OK;
    }

    /**
     * Show statistics about MuxAsset elements with null volumeId or folderId
     */
    public function actionStats(): int
    {
        $this->stdout('MuxAsset Assignment Statistics' . PHP_EOL . PHP_EOL, Console::FG_CYAN);

        // Get total assets
        $totalAssets = MuxAsset::find()->count();
        $this->stdout("Total MuxAsset elements: {$totalAssets}" . PHP_EOL);

        if ($totalAssets === 0) {
            return ExitCode::OK;
        }

        // Count assets with null volumeId
        $nullVolumeIdCount = MuxAsset::find()->where(['volumeId' => null])->count();
        $this->stdout("Assets with null volumeId: {$nullVolumeIdCount}" . PHP_EOL);

        // Count assets with null folderId
        $nullFolderIdCount = MuxAsset::find()->where(['folderId' => null])->count();
        $this->stdout("Assets with null folderId: {$nullFolderIdCount}" . PHP_EOL);

        // Count assets with both null
        $bothNullCount = MuxAsset::find()->where(['and', ['volumeId' => null], ['folderId' => null]])->count();
        $this->stdout("Assets with both null: {$bothNullCount}" . PHP_EOL);

        // Count assets with either null
        $eitherNullCount = MuxAsset::find()->where(['or', ['volumeId' => null], ['folderId' => null]])->count();
        $this->stdout("Assets with either null: {$eitherNullCount}" . PHP_EOL);

        // Show volume distribution
        $this->stdout(PHP_EOL . "Volume distribution:" . PHP_EOL);
        $volumeStats = Craft::$app->getDb()->createCommand(
            'SELECT volumeId, COUNT(*) as count FROM {{%mux_assets}} GROUP BY volumeId ORDER BY count DESC'
        )->queryAll();

        foreach ($volumeStats as $stat) {
            $volumeId = $stat['volumeId'] ?? 'NULL';
            $count = $stat['count'];
            $this->stdout("  Volume ID {$volumeId}: {$count} assets" . PHP_EOL);
        }

        // Show folder distribution
        $this->stdout(PHP_EOL . "Folder distribution:" . PHP_EOL);
        $folderStats = Craft::$app->getDb()->createCommand(
            'SELECT folderId, COUNT(*) as count FROM {{%mux_assets}} GROUP BY folderId ORDER BY count DESC'
        )->queryAll();

        foreach ($folderStats as $stat) {
            $folderId = $stat['folderId'] ?? 'NULL';
            $count = $stat['count'];
            $this->stdout("  Folder ID {$folderId}: {$count} assets" . PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * List assets that have null volumeId or folderId
     */
    public function actionList(): int
    {
        $this->stdout('MuxAsset elements with null volumeId or folderId:' . PHP_EOL . PHP_EOL, Console::FG_CYAN);

        // Find assets with null volumeId and/or folderId
        $query = MuxAsset::find();
        $query->where(['or', ['volumeId' => null], ['folderId' => null]]);
        $assets = $query->all();

        if (empty($assets)) {
            $this->stdout('No assets found with null volumeId or folderId.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        foreach ($assets as $asset) {
            $volumeId = $asset->volumeId ?? 'NULL';
            $folderId = $asset->folderId ?? 'NULL';
            
            $this->stdout("Asset ID: {$asset->asset_id}" . PHP_EOL);
            $this->stdout("  Title: {$asset->title}" . PHP_EOL);
            $this->stdout("  Volume ID: {$volumeId}" . PHP_EOL);
            $this->stdout("  Folder ID: {$folderId}" . PHP_EOL);
            $this->stdout("  Status: {$asset->asset_status}" . PHP_EOL);
            $this->stdout("  Created: {$asset->dateCreated->format('Y-m-d H:i:s')}" . PHP_EOL);
            $this->stdout(PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * List all volumes and folders with their id and name
     */
    public function actionListVolumesAndFolders(): int
    {
        $this->stdout('Volumes and folders with their id and name:' . PHP_EOL . PHP_EOL, Console::FG_CYAN);

        $volumes = Mux::$plugin->volumes->getAllVolumes();
        $folders = Mux::$plugin->folders->getAllFolders();

        $this->stdout("Volumes:" . PHP_EOL);

        foreach ($volumes as $volume) {
            $this->stdout("Volume ID: {$volume->id} - Name: {$volume->name}" . PHP_EOL);
        }

        $this->stdout(PHP_EOL . "Folders:" . PHP_EOL);
        foreach ($folders as $folder) {
            $this->stdout("Folder ID: {$folder->id} - Name: {$folder->name}" . PHP_EOL);
        }

        return ExitCode::OK;
    }
}
