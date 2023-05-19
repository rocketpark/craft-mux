<?php
/**
 * @copyright Copyright (c) RocketPark 
 */

namespace rocketpark\mux\fieldlayoutelements;

use Craft;
use craft\models\FieldLayoutTab;
use rocketpark\mux\fieldlayoutelements\MuxAssetFieldLayoutElement;

/**
 * @since 2.0.0
 */
class MuxAssetFieldContentTab extends FieldLayoutTab
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->name = Craft::t('mux', 'MuxAsset');
    }

    /**
     * @inheritdoc
     */
    public function getElements(): array
    {
        return [
            new MuxAssetFieldLayoutElement(),
        ];
    }
}