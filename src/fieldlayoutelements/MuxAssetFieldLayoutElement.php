<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace rocketpark\mux\fieldlayoutelements;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\Template;
use craft\web\View;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset;
use rocketpark\mux\assetbundles\mux\MuxEditAsset;

/**
 * @since 2.0.0
 */
class MuxAssetFieldLayoutElement extends BaseNativeField
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
     * @param MuxAssetElement $element
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {

        //Craft::$app->getView()->registerAssetBundle(MuxEditAsset::class, View::POS_HEAD);

        $titleField = new TitleField();
        $titleFieldHtml = Template::raw($titleField->formHtml($element));

       

        $siteId = $element->siteId;
        $variables = [
            'editable' => !$static,
            'muxAsset' => $element,
            'titleFieldHtml' => $titleFieldHtml,
            'siteId' => $siteId
        ];

        return Craft::$app->getView()->renderTemplate(
            'mux/_includes/fields',
            $variables,
        );
    }
}