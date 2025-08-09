<?php

namespace rocketpark\mux\fieldlayoutelements;

use Craft;
use craft\models\FieldLayoutTab;
use rocketpark\mux\fieldlayoutelements\MuxAssetFieldDataElement;

/**
 * Data Tab
 * @since 2.0.0
 */
class MuxAssetFieldDataTab extends FieldLayoutTab
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->name = Craft::t('mux', 'Data');
    }

    /**
     * @inheritdoc
     */
    public function getElements(): array
    {
        return [
            new MuxAssetFieldDataElement(),
        ];
    }
}
