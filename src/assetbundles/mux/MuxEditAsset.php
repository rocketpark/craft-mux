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
class MuxEditAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================
    public static $alreadyRegistered = false;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        
        if (!self::$alreadyRegistered) {
            self::$alreadyRegistered = true;
            $this->sourcePath = '@rocketpark/mux/web/dist';

            $this->js = [
                'https://cdn.jsdelivr.net/npm/@mux/mux-player'
            ];

            $this->css = [
                'css/mux-element-edit.css',
            ];

            $this->depends = [
                //CpAsset::class,
                //VueAsset::class,
            ];
        }
    }
}
