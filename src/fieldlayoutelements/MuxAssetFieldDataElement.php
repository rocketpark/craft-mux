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

        // Fetch data from Mux Data API
        $muxData = Mux::$plugin->data->getAssetData($element->asset_id);
        
        // Prepare chart data for Craft's chart system
        $chartData = Mux::$plugin->data->prepareChartData($muxData['views_timeseries']);

        $variables = [
            'editable' => !$static,
            'muxAsset' => $element,
            'muxData' => $muxData,
            'chartData' => $chartData,
            'siteId' => $element->siteId
        ];

        return Craft::$app->getView()->renderTemplate(
            'mux/_includes/data',
            $variables,
        );
    }
}
