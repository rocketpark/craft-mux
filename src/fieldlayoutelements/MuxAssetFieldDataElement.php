<?php

namespace rocketpark\mux\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\Template;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset;

/**
 * Data Field Layout Element
 * @since 2.0.0
 */
class MuxAssetFieldDataElement extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'muxAsset';

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     * @param MuxAsset $element
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element || !$element->asset_id) {
            return '<p>' . Craft::t('mux', 'Data will be available once the asset is processed.') . '</p>';
        }

        // Fetch data from Mux Data API with edit_screen context
        // This ensures fresh data for content managers
        $muxData = Mux::$plugin->data->getAssetData($element->asset_id, 'edit_screen');
        
        // Get initial chart data using the new timeseries system
        $defaultMetric = 'views';
        $defaultTimespan = '1week';
        $timeseriesData = Mux::$plugin->data->getMetricTimeseries($element->asset_id, $defaultMetric, $defaultTimespan);
        $chartData = Mux::$plugin->data->prepareChartData($timeseriesData, $defaultMetric);

        // Get available options
        $availableTimespans = Mux::$plugin->data->getAvailableTimespans();
        $availableMetrics = Mux::$plugin->data->getAvailableMetrics();

        $variables = [
            'editable' => !$static,
            'muxAsset' => $element,
            'muxData' => $muxData,
            'chartData' => $chartData,
            'availableTimespans' => $availableTimespans,
            'availableMetrics' => $availableMetrics,
            'defaultTimespan' => $defaultTimespan,
            'defaultMetric' => $defaultMetric,
            'siteId' => $element->siteId
        ];

        return Craft::$app->getView()->renderTemplate(
            'mux/_includes/data',
            $variables,
        );
    }
}
