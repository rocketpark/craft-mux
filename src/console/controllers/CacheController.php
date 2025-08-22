<?php
/**
 * @link https://rocketpark.com/
 * @copyright Copyright (c) Rocket Park.
 */

namespace rocketpark\mux\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset;
use yii\console\ExitCode;

/**
 * Allows you to manage MUX data cache
 *
 * @author RocketPark. <support@rocketpark.com>
 * @since 2.0
 */
class CacheController extends Controller
{
    /**
     * @var string|null The asset ID to clear cache for (optional)
     */
    public $assetId;

    /**
     * @var bool Whether to show verbose output
     */
    public $verbose = false;

    /**
     * @var bool Whether to force cache clearing without confirmation
     */
    public $force = false;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        
        switch ($actionID) {
            case 'clear':
                $options[] = 'assetId';
                $options[] = 'verbose';
                $options[] = 'force';
                break;
            case 'clear-all':
                $options[] = 'verbose';
                $options[] = 'force';
                break;
        }
        
        return $options;
    }

    public function optionAliases(): array
    {
        return [
            'a' => 'assetId',
            'v' => 'verbose',
            'f' => 'force',
        ];
    }

    /**
     * Clear cache for a specific asset or all assets
     */
    public function actionClear(): int
    {
        if ($this->assetId) {
            return $this->clearAssetCache($this->assetId);
        } else {
            return $this->actionClearAll();
        }
    }

    /**
     * Clear cache for all MUX assets
     */
    public function actionClearAll(): int
    {
        if (!$this->force) {
            if (!$this->confirm('This will clear all MUX data cache. Are you sure?')) {
                $this->stdout('Operation cancelled.' . PHP_EOL, Console::FG_YELLOW);
                return ExitCode::OK;
            }
        }

        $this->stdout('Clearing MUX data cache for all assets...' . PHP_EOL, Console::FG_GREEN);

        // Get all MUX assets
        $assets = MuxAsset::find()->all();
        $totalAssets = count($assets);
        $clearedCount = 0;
        $errorCount = 0;

        if ($totalAssets === 0) {
            $this->stdout('No MUX assets found.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        foreach ($assets as $asset) {
            try {
                if ($this->verbose) {
                    $this->stdout("Clearing cache for asset: {$asset->asset_id} ({$asset->title})..." . PHP_EOL);
                }

                $this->clearCacheForAsset($asset->asset_id);
                $clearedCount++;

                if ($this->verbose) {
                    $this->stdout("✓ Cache cleared for asset: {$asset->asset_id}" . PHP_EOL, Console::FG_GREEN);
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->stdout("✗ Error clearing cache for asset {$asset->asset_id}: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
            }
        }

        $this->stdout(PHP_EOL . "Cache clearing completed!" . PHP_EOL, Console::FG_GREEN);
        $this->stdout("Assets processed: {$totalAssets}" . PHP_EOL);
        $this->stdout("Successfully cleared: {$clearedCount}" . PHP_EOL, Console::FG_GREEN);
        
        if ($errorCount > 0) {
            $this->stdout("Errors: {$errorCount}" . PHP_EOL, Console::FG_RED);
        }

        return ExitCode::OK;
    }

    /**
     * Clear cache for a specific asset
     */
    public function actionClearAsset($assetId = null): int
    {
        $assetId = $assetId ?: $this->assetId;
        
        if (!$assetId) {
            $this->stdout('Asset ID is required. Use --assetId=<asset_id> or provide it as an argument.' . PHP_EOL, Console::FG_RED);
            return ExitCode::DATAERR;
        }

        return $this->clearAssetCache($assetId);
    }

    /**
     * Show cache statistics for MUX-related cache entries
     */
    public function actionStats(): int
    {
        $this->stdout('MUX Data Cache Statistics' . PHP_EOL . PHP_EOL, Console::FG_CYAN);

        // Get all MUX assets
        $assets = MuxAsset::find()->all();
        $totalAssets = count($assets);

        $this->stdout("Total MUX Assets: {$totalAssets}" . PHP_EOL);

        if ($totalAssets === 0) {
            return ExitCode::OK;
        }

        $cachedAssets = 0;
        $totalCacheEntries = 0;

        foreach ($assets as $asset) {
            $assetCacheCount = $this->getCacheEntriesForAsset($asset->asset_id);
            if ($assetCacheCount > 0) {
                $cachedAssets++;
                $totalCacheEntries += $assetCacheCount;

                if ($this->verbose) {
                    $this->stdout("Asset {$asset->asset_id} ({$asset->title}): {$assetCacheCount} cache entries" . PHP_EOL);
                }
            }
        }

        $this->stdout("Assets with cached data: {$cachedAssets}" . PHP_EOL);
        $this->stdout("Total cache entries: {$totalCacheEntries}" . PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * Clear all general cache (not just MUX)
     */
    public function actionFlushAll(): int
    {
        if (!$this->force) {
            if (!$this->confirm('This will clear ALL application cache. Are you sure?')) {
                $this->stdout('Operation cancelled.' . PHP_EOL, Console::FG_YELLOW);
                return ExitCode::OK;
            }
        }

        $this->stdout('Flushing all application cache...' . PHP_EOL, Console::FG_GREEN);
        
        try {
            Craft::$app->getCache()->flush();
            $this->stdout('✓ All cache flushed successfully' . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stdout("✗ Error flushing cache: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Clear cache for a specific asset
     */
    private function clearAssetCache(string $assetId): int
    {
        // Verify asset exists
        $asset = MuxAsset::find()->asset_id($assetId)->one();
        if (!$asset) {
            $this->stdout("Asset with ID '{$assetId}' not found." . PHP_EOL, Console::FG_RED);
            return ExitCode::DATAERR;
        }

        if (!$this->force) {
            if (!$this->confirm("Clear cache for asset '{$assetId}' ({$asset->title})?")) {
                $this->stdout('Operation cancelled.' . PHP_EOL, Console::FG_YELLOW);
                return ExitCode::OK;
            }
        }

        try {
            $this->stdout("Clearing cache for asset: {$assetId} ({$asset->title})..." . PHP_EOL, Console::FG_GREEN);
            
            $this->clearCacheForAsset($assetId);
            
            $this->stdout("✓ Cache cleared successfully for asset: {$assetId}" . PHP_EOL, Console::FG_GREEN);
            
            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stdout("✗ Error clearing cache for asset {$assetId}: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Clear cache entries for a specific asset
     */
    private function clearCacheForAsset(string $assetId): void
    {
        $cache = Craft::$app->getCache();
        
        // Check if the enhanced Data service exists with clearAssetCache method
        if (method_exists(Mux::$plugin->data, 'clearAssetCache')) {
            Mux::$plugin->data->clearAssetCache($assetId);
            return;
        }
        
        // Fallback: Clear common MUX cache patterns
        $cacheKeys = [
            // JWT tokens (from MuxAsset element)
            "{$assetId}-video",
            "{$assetId}-thumbnail", 
            "{$assetId}-storyboard",
            // Add other common cache patterns here as needed
        ];
        
        foreach ($cacheKeys as $key) {
            $cache->delete($key);
        }
        
        // Try to clear any keys that might be using the asset ID
        // Note: This is a basic implementation - for production you might want
        // to track cache keys more systematically
        
        Craft::info("Cleared basic cache entries for asset {$assetId}", 'mux');
    }

    /**
     * Get number of cache entries for an asset (basic implementation)
     */
    private function getCacheEntriesForAsset(string $assetId): int
    {
        $cache = Craft::$app->getCache();
        $count = 0;

        // Check common cache patterns
        $cacheKeys = [
            "{$assetId}-video",
            "{$assetId}-thumbnail", 
            "{$assetId}-storyboard",
        ];

        foreach ($cacheKeys as $key) {
            if ($cache->exists($key)) {
                $count++;
            }
        }

        return $count;
    }
}
