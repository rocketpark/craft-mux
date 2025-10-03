<?php

namespace rocketpark\mux\controllers;

use Craft;
use craft\web\Controller;
use rocketpark\mux\Mux;
use yii\web\Response;

/**
 * Data Controller
 */
class DataController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    /**
     * Get asset data for a specific timespan
     * @return Response
     */
    public function actionGetAssetData(): Response
    {
        $this->requireAcceptsJson();
        
        $request = Craft::$app->getRequest();
        $assetId = $request->getBodyParam('assetId');
        $timespan = $request->getBodyParam('timespan', '1week');
        $useCache = $request->getBodyParam('useCache', true);
        
        if (!$assetId) {
            return $this->asJson([
                'success' => false,
                'message' => 'Asset ID is required'
            ]);
        }

        try {
            // Fix: Use correct parameter order and force refresh when useCache is false
            $data = Mux::$plugin->data->getAssetData($assetId, 'edit_screen', !$useCache, $timespan);
            
            return $this->asJson([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Craft::error("Error fetching asset data: " . $e->getMessage(), 'mux');
            
            return $this->asJson([
                'success' => false,
                'message' => 'Error fetching data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get chart data for a specific metric and timespan
     * @return Response
     */
    public function actionGetChartData(): Response
    {
        $this->requireAcceptsJson();
        
        $request = Craft::$app->getRequest();
        $assetId = $request->getBodyParam('assetId');
        $metric = $request->getBodyParam('metric', 'views');
        $timespan = $request->getBodyParam('timespan', '1week');
        $useCache = $request->getBodyParam('useCache', true);
        
        if (!$assetId) {
            return $this->asJson([
                'success' => false,
                'message' => 'Asset ID is required'
            ]);
        }

        try {
            $timeseriesData = Mux::$plugin->data->getMetricTimeseries($assetId, $metric, $timespan, $useCache);
            
            // Use the updated prepareChartData method with metric parameter
            $chartData = Mux::$plugin->data->prepareChartData($timeseriesData, $metric);
            
            return $this->asJson([
                'success' => true,
                'chartData' => $chartData,
                'metric' => $metric,
                'timespan' => $timespan
            ]);
        } catch (\Exception $e) {
            Craft::error("Error fetching chart data: " . $e->getMessage(), 'mux');
            
            return $this->asJson([
                'success' => false,
                'message' => 'Error fetching chart data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Clear cache for an asset
     * @return Response
     */
    public function actionClearCache(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        
        $request = Craft::$app->getRequest();
        $assetId = $request->getBodyParam('assetId');
        
        if (!$assetId) {
            return $this->asJson([
                'success' => false,
                'message' => 'Asset ID is required'
            ]);
        }

        try {
            Mux::$plugin->data->clearAssetCache($assetId);
            
            return $this->asJson([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return $this->asJson([
                'success' => false,
                'message' => 'Error clearing cache: ' . $e->getMessage()
            ]);
        }
    }
}
