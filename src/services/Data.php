<?php

namespace rocketpark\mux\services;

use Craft;
use yii\base\Component;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset;
use GuzzleHttp\Client;
use craft\helpers\App;
use MuxPhp;
use MuxPhp\Configuration;
use MuxPhp\Api\MetricsApi;
use MuxPhp\ApiException;
use yii\base\Exception;

/**
 * Data service
 *
 * @property-read array $cacheTtl Cache TTL strategies
 * @property-read array $timespans Available timespans
 * @property-read array $metrics Available metrics for charts
 */
class Data extends Component
{
    // Cache TTL strategies (in seconds)
    const CACHE_TTL = [
        'edit_screen' => 300,    // 5 minutes - edit screen needs fresher data
        'dashboard' => 1800,     // 30 minutes - dashboard widgets
        'frontend' => 3600,      // 1 hour - public displays
        'api' => 900,           // 15 minutes - API endpoints
    ];
    
    // Available timespans
    const TIMESPANS = [
        '1week' => '7:days',
        '1month' => '30:days', 
        '3months' => '90:days',
        '6months' => '180:days',
        '1year' => '365:days'
    ];
    
    // Available metrics for charts
    const METRICS = [
        'views' => [
            'label' => 'Total Views',
            'api_metric' => 'views',
            'suffix' => '',
            'format' => 'number'
        ],
        'unique_viewers' => [
            'label' => 'Total Unique Viewers',
            'api_metric' => 'unique_viewers', 
            'suffix' => '',
            'format' => 'number'
        ],
        'playing_time' => [
            'label' => 'Total Playing Time',
            'api_metric' => 'playing_time',
            'suffix' => 'm',
            'format' => 'minutes'
        ],
        'avg_playing_time' => [
            'label' => 'Avg Playing Time',
            'api_metric' => 'playing_time',
            'suffix' => 's', 
            'format' => 'avg_seconds'
        ],
        'avg_completion' => [
            'label' => 'Avg Completion',
            'api_metric' => 'view_max_playhead_position',
            'suffix' => '%',
            'format' => 'percentage'
        ]
    ];

    /**
     * Creates MUX Data API Configuration
     * @return Configuration 
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
     * Get data for a specific asset
     * @param string $assetId
     * @param string $context Context for cache strategy (edit_screen, dashboard, frontend, api)
     * @param bool $forceRefresh Force refresh regardless of cache
     * @param string $timespan Timespan for data (1week, 1month, etc.)
     * @return array
     */
    public function getAssetData(string $assetId, string $context = 'edit_screen', bool $forceRefresh = false, string $timespan = '1week'): array
    {
        // Determine if we should use cache
        $useCache = $this->shouldUseCache() && !$forceRefresh;
        $cacheTtl = self::CACHE_TTL[$context] ?? self::CACHE_TTL['edit_screen'];
        
        // Generate cache key with context and timespan
        $cacheKey = "mux_asset_data_{$assetId}_{$context}_{$timespan}";
        
        if ($useCache) {
            $cachedData = Craft::$app->getCache()->get($cacheKey);
            if ($cachedData !== false) {
                Craft::info("Returning cached data for asset {$assetId}, context {$context}, timespan {$timespan}", 'mux');
                return $cachedData;
            }
        }

        // Fetch fresh data from API with the specified timespan
        $data = $this->fetchAssetDataFromApi($assetId, $timespan);
        
        // Cache the data if caching is enabled
        if ($useCache) {
            Craft::$app->getCache()->set($cacheKey, $data, $cacheTtl);
            Craft::info("Cached asset data for {$assetId}, context {$context}, timespan {$timespan}, TTL {$cacheTtl}s", 'mux');
        } else {
            Craft::info("Skipped caching for asset {$assetId} (dev mode or forced refresh)", 'mux');
        }

        return $data;
    }

    /**
     * Determine if caching should be used based on environment
     * @return bool
     */
    private function shouldUseCache(): bool
    {
        // Don't cache in dev environment
        if (Craft::$app->getConfig()->general->devMode) {
            return false;
        }

        
        // Don't cache when debugging
        if (YII_DEBUG) {
            return false;
        }
        
        return true;
    }

    /**
     * Fetch asset data from Mux API (extracted for clarity)
     * @param string $assetId
     * @param string $timespan
     * @return array
     */
    private function fetchAssetDataFromApi(string $assetId, string $timespan = '1week'): array
    {
        $config = $this->muxConf();
        $apiInstance = new MetricsApi(new Client(), $config);
        
        $data = [
            'views' => 0,
            'unique_viewers' => 0,
            'playing_time' => 0,
            'avg_playing_time' => 0,
            'avg_completion' => 0,
            'views_timeseries' => []
        ];

        try {
            // Use the specified timespan instead of hardcoded 7:days
            $timeframe = self::TIMESPANS[$timespan] ?? self::TIMESPANS['1week'];
            
            $options = [
                'timeframe' => [$timeframe],
                'filters' => ["asset_id:{$assetId}"],
            ];

            // Views - using correct array parameter format
            $viewsResponse = $apiInstance->getOverallValues('views', $options);
            $viewsData = $viewsResponse->getData();
            $data['views'] = !empty($viewsData) ? ($viewsData->getValue() ?? 0) : 0;

            // Unique Viewers
            $uniqueViewersResponse = $apiInstance->getOverallValues('unique_viewers', $options);
            $uniqueViewersData = $uniqueViewersResponse->getData();
            $data['unique_viewers'] = !empty($uniqueViewersData) ? ($uniqueViewersData->getValue() ?? 0) : 0;

            // Playing Time (in milliseconds from API, convert to minutes for display)
            $playingTimeResponse = $apiInstance->getOverallValues('playing_time', $options);
            $playingTimeData = $playingTimeResponse->getData();
            $playingTimeMilliseconds = !empty($playingTimeData) ? ($playingTimeData->getValue() ?? 0) : 0;
            $playingTimeSeconds = $playingTimeMilliseconds / 1000; // Convert milliseconds to seconds
            $data['playing_time'] = round($playingTimeSeconds / 60, 2); // Convert to minutes for display

            // Average Playing Time (calculate from playing time and views, in seconds)
            if ($data['views'] > 0) {
                $data['avg_playing_time'] = round($playingTimeSeconds / $data['views'], 1); // Average in seconds
            }

            // Calculate average completion percentage
            $asset = MuxAsset::find()->asset_id($assetId)->one();
            if ($asset && $asset->duration && $data['views'] > 0) {
                try {
                    // Try to get the maximum playhead position reached
                    $maxPlayheadResponse = $apiInstance->getOverallValues('view_max_playhead_position', $options);
                    $maxPlayheadData = $maxPlayheadResponse->getData();
                    $maxPlayheadMilliseconds = !empty($maxPlayheadData) ? ($maxPlayheadData->getValue() ?? 0) : 0;
                    
                    if ($maxPlayheadMilliseconds > 0) {
                        // Convert asset duration to milliseconds and calculate percentage
                        $assetDurationMilliseconds = $asset->duration * 1000;
                        $completion = ($maxPlayheadMilliseconds / $assetDurationMilliseconds) * 100;
                        // Cap at 100% and ensure it's not negative
                        $data['avg_completion'] = round(min(100, max(0, $completion)), 2);
                    } else {
                        // Fallback: calculate based on average watch time per view
                        $avgWatchTimePerView = $data['views'] > 0 ? ($playingTimeSeconds / $data['views']) : 0;
                        $completion = ($avgWatchTimePerView / $asset->duration) * 100;
                        // Cap at 100% and ensure it's not negative  
                        $data['avg_completion'] = round(min(100, max(0, $completion)), 2);
                    }
                } catch (ApiException $e) {
                    // If max playhead position is not available, calculate based on average watch time
                    if ($data['views'] > 0) {
                        $avgWatchTimePerView = $playingTimeSeconds / $data['views'];
                        $completion = ($avgWatchTimePerView / $asset->duration) * 100;
                        // Cap at 100% and ensure it's not negative
                        $data['avg_completion'] = round(min(100, max(0, $completion)), 2);
                    }
                }
            }

            // Get timeseries data for the specified timespan
            $this->getViewsTimeseries($assetId, $data, $timespan);

            // Debug logging
            Craft::info("Fetched fresh asset data for {$assetId}, timespan {$timespan}: " . print_r($data, true), 'mux');

        } catch (ApiException $e) {
            Craft::error("Mux Data API error for asset {$assetId}: " . $e->getMessage(), 'mux');
            Craft::error("API Response: " . $e->getResponseBody(), 'mux');
        } catch (Exception $e) {
            Craft::error("Data error for asset {$assetId}: " . $e->getMessage(), 'mux');
        }

        return $data;
    }

    /**
     * Get metric timeseries data for charts
     * @param string $assetId
     * @param string $metric
     * @param string $timespan
     * @param bool $useCache
     * @return array
     */
    public function getMetricTimeseries(string $assetId, string $metric, string $timespan = '1week', bool $useCache = true): array
    {
        // Generate cache key
        $cacheKey = "mux_timeseries_{$assetId}_{$metric}_{$timespan}";
        
        if ($useCache) {
            $cachedData = Craft::$app->getCache()->get($cacheKey);
            if ($cachedData !== false) {
                return $cachedData;
            }
        }

        $config = $this->muxConf();
        $apiInstance = new MetricsApi(new Client(), $config);
        
        $timeseriesData = [];
        
        try {
            $timeframe = self::TIMESPANS[$timespan] ?? self::TIMESPANS['1week'];
            
            $options = [
                'timeframe' => [$timeframe],
                'filters' => ["asset_id:{$assetId}"],
                'group_by' => $this->getGroupByForTimespan($timespan)
            ];
            
            // Special handling for avg_completion - calculate completion percentage per day
            if ($metric === 'avg_completion') {
                $timeseriesData = $this->getCompletionTimeseries($assetId, $timeframe, $options, $useCache);
            } else {
                // Get the API metric name for other metrics
                $apiMetric = self::METRICS[$metric]['api_metric'] ?? $metric;
                
                $timeseriesResponse = $apiInstance->getMetricTimeseriesData($apiMetric, $options);
                $responseData = $timeseriesResponse->getData() ?? [];
                
                foreach ($responseData as $dataPoint) {
                    if (is_array($dataPoint) && count($dataPoint) >= 2) {
                        $timestamp = $dataPoint[0];
                        $value = $dataPoint[1];
                        
                        // Format value based on metric type
                        $formattedValue = $this->formatMetricValue($metric, $value, $assetId);
                        
                        if ($timestamp) {
                            $timeseriesData[] = [
                                'date' => $timestamp,
                                'value' => $formattedValue
                            ];
                        }
                    }
                }
            }
            
            // Cache the data (removed enhancement step)
            if ($useCache) {
                $cacheTtl = self::CACHE_TTL['edit_screen']; // Use edit_screen TTL as default
                Craft::$app->getCache()->set($cacheKey, $timeseriesData, $cacheTtl);
            }
            
        } catch (ApiException $e) {
            Craft::warning("Could not fetch timeseries data for metric {$metric}, asset {$assetId}: " . $e->getMessage(), 'mux');
            Craft::info("API Response: " . $e->getResponseBody(), 'mux');
        }
        
        return $timeseriesData;
    }

    /**
     * Get completion percentage timeseries by calculating daily completion rates
     * @param string $assetId
     * @param string $timeframe
     * @param array $options
     * @param bool $useCache
     * @return array
     */
    private function getCompletionTimeseries(string $assetId, string $timeframe, array $options, bool $useCache): array
    {
        $config = $this->muxConf();
        $apiInstance = new MetricsApi(new Client(), $config);
        $completionData = [];
        
        try {
            // Get asset duration for percentage calculation
            $asset = MuxAsset::find()->asset_id($assetId)->one();
            if (!$asset || !$asset->duration) {
                Craft::warning("Asset duration not available for completion calculation: {$assetId}", 'mux');
                return [];
            }
            
            $assetDurationMs = $asset->duration * 1000; // Convert to milliseconds
            
            // Try to get view_max_playhead_position timeseries
            try {
                $playheadResponse = $apiInstance->getMetricTimeseriesData('view_max_playhead_position', $options);
                $playheadData = $playheadResponse->getData() ?? [];
                
                foreach ($playheadData as $dataPoint) {
                    if (is_array($dataPoint) && count($dataPoint) >= 2) {
                        $timestamp = $dataPoint[0];
                        $maxPlayheadMs = (float)$dataPoint[1];
                        
                        if ($timestamp && $maxPlayheadMs > 0) {
                            // Calculate completion percentage for this day
                            $completionPercent = min(100, max(0, ($maxPlayheadMs / $assetDurationMs) * 100));
                            
                            $completionData[] = [
                                'date' => $timestamp,
                                'value' => round($completionPercent, 2)
                            ];
                        }
                    }
                }
                
            } catch (ApiException $e) {
                // If view_max_playhead_position isn't available, try alternative calculation
                Craft::info("view_max_playhead_position not available, trying alternative calculation", 'mux');
                
                // Alternative: Use playing_time and views to estimate completion
                $completionData = $this->calculateCompletionFromPlayingTime($assetId, $options, $assetDurationMs);
            }
            
        } catch (ApiException $e) {
            Craft::warning("Error fetching completion timeseries for asset {$assetId}: " . $e->getMessage(), 'mux');
        }
        
        return $completionData;
    }

    /**
     * Alternative completion calculation using playing_time and views
     * @param string $assetId
     * @param array $options
     * @param float $assetDurationMs
     * @return array
     */
    private function calculateCompletionFromPlayingTime(string $assetId, array $options, float $assetDurationMs): array
    {
        $config = $this->muxConf();
        $apiInstance = new MetricsApi(new Client(), $config);
        $completionData = [];
        
        try {
            // Get playing_time and views for each day
            $playingTimeResponse = $apiInstance->getMetricTimeseriesData('playing_time', $options);
            $viewsResponse = $apiInstance->getMetricTimeseriesData('views', $options);
            
            $playingTimeData = $playingTimeResponse->getData() ?? [];
            $viewsData = $viewsResponse->getData() ?? [];
            
            // Create lookup arrays by date
            $playingTimeByDate = [];
            $viewsByDate = [];
            
            foreach ($playingTimeData as $dataPoint) {
                if (is_array($dataPoint) && count($dataPoint) >= 2) {
                    $playingTimeByDate[$dataPoint[0]] = (float)$dataPoint[1]; // milliseconds
                }
            }
            
            foreach ($viewsData as $dataPoint) {
                if (is_array($dataPoint) && count($dataPoint) >= 2) {
                    $viewsByDate[$dataPoint[0]] = (int)$dataPoint[1];
                }
            }
            
            // Calculate completion percentage for each date
            foreach ($playingTimeByDate as $date => $totalPlayingTimeMs) {
                $views = $viewsByDate[$date] ?? 0;
                
                if ($views > 0 && $totalPlayingTimeMs > 0) {
                    // Average playing time per view
                    $avgPlayingTimeMs = $totalPlayingTimeMs / $views;
                    
                    // Calculate completion percentage
                    $completionPercent = min(100, max(0, ($avgPlayingTimeMs / $assetDurationMs) * 100));
                    
                    $completionData[] = [
                        'date' => $date,
                        'value' => round($completionPercent, 2)
                    ];
                }
            }
            
            // Sort by date
            usort($completionData, function($a, $b) {
                return strcmp($a['date'], $b['date']);
            });
            
        } catch (ApiException $e) {
            Craft::warning("Error in alternative completion calculation: " . $e->getMessage(), 'mux');
        }
        
        return $completionData;
    }

    /**
     * Get views timeseries data for the specified timespan
     * @param string $assetId
     * @param array &$data
     * @param string $timespan
     */
    private function getViewsTimeseries(string $assetId, array &$data, string $timespan = '1week'): void
    {
        try {
            $config = $this->muxConf();
            $apiInstance = new MetricsApi(new Client(), $config);
            
            $timeframe = self::TIMESPANS[$timespan] ?? self::TIMESPANS['1week'];
            $groupBy = $this->getGroupByForTimespan($timespan);
            
            $options = [
                'timeframe' => [$timeframe],
                'filters' => ["asset_id:{$assetId}"],
                'group_by' => $groupBy
            ];
            
            // Fixed timeseries call with proper array parameter format
            $timeseriesResponse = $apiInstance->getMetricTimeseriesData('views', $options);

            $timeseriesData = $timeseriesResponse->getData() ?? [];
            
            // Debug: Log the timeseries response structure
            Craft::info("Timeseries response for asset {$assetId}, timespan {$timespan}: " . print_r($timeseriesData, true), 'mux');
            
            // Process the array-based data structure
            foreach ($timeseriesData as $dataPoint) {
                // The data comes as arrays with numeric indices:
                // [0] = timestamp (e.g., "2025-08-08T00:00:00Z")
                // [1] = metric value (views)
                // [2] = possibly another metric or cumulative value
                
                if (is_array($dataPoint) && count($dataPoint) >= 2) {
                    $timestamp = $dataPoint[0]; // First element is the timestamp
                    $views = (int)$dataPoint[1]; // Second element is the views count
                    
                    if ($timestamp) {
                        $data['views_timeseries'][] = [
                            'date' => $timestamp,
                            'views' => $views
                        ];
                    }
                }
            }
            
            // Debug: Log the final timeseries data
            Craft::info("Final timeseries data for asset {$assetId}, timespan {$timespan}: " . print_r($data['views_timeseries'], true), 'mux');
            
        } catch (ApiException $e) {
            Craft::warning("Could not fetch timeseries data for asset {$assetId}, timespan {$timespan}: " . $e->getMessage(), 'mux');
            Craft::warning("API Response: " . $e->getResponseBody(), 'mux');
            $data['views_timeseries'] = [];
        }
    }

    /**
     * Format metric value based on type
     * @param string $metric
     * @param mixed $value
     * @param string $assetId
     * @return float|int
     */
    private function formatMetricValue(string $metric, $value, string $assetId)
    {
        $metricConfig = self::METRICS[$metric] ?? null;
        if (!$metricConfig) {
            return (float)$value;
        }
        
        switch ($metricConfig['format']) {
            case 'minutes':
                // Convert milliseconds to minutes
                return round(($value / 1000) / 60, 2);
                
            case 'avg_seconds':
                // This requires views data to calculate average
                $viewsData = $this->getMetricTimeseries($assetId, 'views', '1week', true);
                $totalViews = array_sum(array_column($viewsData, 'value'));
                if ($totalViews > 0) {
                    return round(($value / 1000) / $totalViews, 1);
                }
                return 0;
                
            case 'percentage':
                // For completion percentage, needs asset duration
                $asset = MuxAsset::find()->asset_id($assetId)->one();
                if ($asset && $asset->duration) {
                    $assetDurationMs = $asset->duration * 1000;
                    return round(min(100, max(0, ($value / $assetDurationMs) * 100)), 2);
                }
                return 0;
                
            case 'number':
            default:
                return (int)$value;
        }
    }

    /**
     * Get appropriate group_by parameter for timespan
     * @param string $timespan
     * @return string
     */
    private function getGroupByForTimespan(string $timespan): string
    {
        switch ($timespan) {
            case '1week':
                return 'day';
            case '1month':
                return 'day';
            case '3months':
                return 'day';
            case '6months':
                return 'day';
            case '1year':
                return 'day';
            default:
                return 'day';
        }
    }

    /**
     * Prepare chart data for Craft's chart system
     * @param array $timeseriesData
     * @param string $metric The metric being displayed
     * @return array
     */
    public function prepareChartData(array $timeseriesData, string $metric = 'views'): array
    {
        $rows = [];
        $metricConfig = self::METRICS[$metric] ?? self::METRICS['views'];
        
        foreach ($timeseriesData as $dataPoint) {
            $date = $dataPoint['date'];
            // Handle both 'views' (legacy) and 'value' (new) keys
            $value = $dataPoint['value'] ?? $dataPoint['views'] ?? 0;
            
            // Format date for Craft charts (Y-m-d format)
            $formattedDate = date('Y-m-d', strtotime($date));
            $rows[] = [$formattedDate, (float)$value];
        }

        $chartData = [
            'columns' => [
                ['label' => Craft::t('mux', 'Date'), 'type' => 'date'],
                ['label' => Craft::t('mux', $metricConfig['label']), 'type' => 'number'],
            ],
            'rows' => $rows
        ];
        
        Craft::info("Prepared chart data for metric {$metric}: " . print_r($chartData, true), 'mux');
        
        return $chartData;
    }

    /**
     * Clear cache for specific asset and context
     * @param string $assetId
     * @param string|null $context Specific context to clear, or null for all contexts
     */
    public function clearAssetCache(string $assetId, ?string $context = null): void
    {
        $cache = Craft::$app->getCache();
        
        if ($context) {
            $cacheKey = "mux_asset_data_{$assetId}_{$context}";
            $cache->delete($cacheKey);
            Craft::info("Cleared cache for asset {$assetId}, context {$context}", 'mux');
        } else {
            // Clear all contexts for this asset
            foreach (array_keys(self::CACHE_TTL) as $contextKey) {
                $cacheKey = "mux_asset_data_{$assetId}_{$contextKey}";
                $cache->delete($cacheKey);
            }
            Craft::info("Cleared all cache contexts for asset {$assetId}", 'mux');
        }
    }

    /**
     * Get available timespans
     * @return array
     */
    public function getAvailableTimespans(): array
    {
        return [
            '1week' => Craft::t('mux', '1 Week'),
            '1month' => Craft::t('mux', '1 Month'),
            '3months' => Craft::t('mux', '3 Months'),
            '6months' => Craft::t('mux', '6 Months'),
            '1year' => Craft::t('mux', '1 Year'),
        ];
    }

    /**
     * Get available metrics
     * @return array
     */
    public function getAvailableMetrics(): array
    {
        $metrics = [];
        foreach (self::METRICS as $key => $config) {
            $metrics[$key] = [
                'label' => Craft::t('mux', $config['label']),
                'suffix' => Craft::t('mux', $config['suffix']),
                'format' => $config['format']
            ];
        }
        return $metrics;
    }
}