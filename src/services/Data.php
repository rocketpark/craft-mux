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
 */
class Data extends Component
{
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
     * @return array
     */
    public function getAssetData(string $assetId): array
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
            // Use array format for API calls - this is the key fix!
            $options = [
                'timeframe' => ["7:days"],
                'filters' => ["asset_id:{$assetId}"],
            ];

            // Views - using correct array parameter format
            $viewsResponse = $apiInstance->getOverallValues('views', $options);

            //Craft::dd($viewsResponse);
            
            $viewsData = $viewsResponse->getData();
            // getData() returns an array, get the first item
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

            // Get timeseries data for views over last 7 days for the graph
            $this->getViewsTimeseries($assetId, $data);

            // Debug logging
            Craft::info("Asset data for {$assetId}: " . print_r($data, true), 'mux');

        } catch (ApiException $e) {
            Craft::error("Mux Data API error for asset {$assetId}: " . $e->getMessage(), 'mux');
            Craft::error("API Response: " . $e->getResponseBody(), 'mux');
        } catch (Exception $e) {
            Craft::error("Data error for asset {$assetId}: " . $e->getMessage(), 'mux');
        }

        return $data;
    }

    /**
     * Get views timeseries data for the last 7 days (for the graph only)
     * @param string $assetId
     * @param array &$data
     */
    private function getViewsTimeseries(string $assetId, array &$data): void
    {
        try {
            $config = $this->muxConf();
            $apiInstance = new MetricsApi(new Client(), $config);
            
            $options = [
                'timeframe' => ["7:days"],
                'filters' => ["asset_id:{$assetId}"],
                'group_by' => 'day'
            ];
            
            // Fixed timeseries call with proper array parameter format
            $timeseriesResponse = $apiInstance->getMetricTimeseriesData('views', $options);

            $timeseriesData = $timeseriesResponse->getData() ?? [];
            
            // Debug: Log the timeseries response structure
            Craft::info("Timeseries response for asset {$assetId}: " . print_r($timeseriesData, true), 'mux');
            
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
            Craft::info("Final timeseries data for asset {$assetId}: " . print_r($data['views_timeseries'], true), 'mux');
            
        } catch (ApiException $e) {
            Craft::warning("Could not fetch timeseries data for asset {$assetId}: " . $e->getMessage(), 'mux');
            Craft::warning("API Response: " . $e->getResponseBody(), 'mux');
            $data['views_timeseries'] = [];
        }
    }

    /**
     * Prepare chart data for Craft's chart system
     * @param array $timeseriesData
     * @return array
     */
    public function prepareChartData(array $timeseriesData): array
    {
        $rows = [];
        
        foreach ($timeseriesData as $dataPoint) {
            $date = $dataPoint['date'];
            $views = $dataPoint['views'];
            
            // Format date for Craft charts (Y-m-d format)
            $formattedDate = date('Y-m-d', strtotime($date));
            $rows[] = [$formattedDate, (int)$views];
        }

        $chartData = [
            'columns' => [
                ['label' => Craft::t('mux', 'Date'), 'type' => 'date'],
                ['label' => Craft::t('mux', 'Views'), 'type' => 'number'],
            ],
            'rows' => $rows
        ];
        
        Craft::info("Prepared chart data: " . print_r($chartData, true), 'mux');
        
        return $chartData;
    }
}