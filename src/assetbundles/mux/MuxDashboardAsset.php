<?php

/**
 * Mux plugin for Craft CMS
 *
 * @link      https://rocketpark.com/
 * @copyright Copyright (c) 2022 rocketpark
 */

namespace rocketpark\mux\assetbundles\mux;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;

/**
 * @author    rocketpark
 * @package   Mux
 * @since     1.0.0
 */
class MuxDashboardAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = '@rocketpark/mux/web/dist';

        $this->js = [
            'https://cdn.jsdelivr.net/npm/@mux/mux-player',
            'js/mux-dashboard.js',
        ];

        $this->css = [
            'css/mux-dashboard.css',
        ];

        $this->depends = [
            CpAsset::class,
            //VueAsset::class,
        ];

        parent::init();
    }
}
