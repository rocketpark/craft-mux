<?php
/**
 * @copyright Copyright (c) RocketPark 
 */

namespace rocketpark\mux\fieldlayoutelements;

use Craft;
use craft\models\FieldLayoutTab;
use rocketpark\mux\fieldlayoutelements\MuxAssetFieldLTracksElement;

/**
 * @since 2.0.0
 */
class MuxAssetFieldTracksTab extends FieldLayoutTab
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->name = Craft::t('mux', 'Tracks');
    }

    /**
     * @inheritdoc
     */
    public function getElements(): array
    {
        return [
            new MuxAssetFieldTracksElement(),
        ];
    }
}