<?php

/**
 * Mux plugin for Craft CMS
 *
 * @link      https://rocketpark.com/
 * @copyright Copyright (c) 2025 rocketpark
 */

 namespace rocketpark\mux\assetbundles\mux;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class MuxAssetIndexAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = '@rocketpark/mux/web/dist';
        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/mux-asset-index.js',
            'js/mux-asset-mover.js',
            'js/mux-volume-folder-selector-modal.js',
        ];

        parent::init();
    }
}