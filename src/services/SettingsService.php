<?php

namespace rocketpark\mux\services;


use Craft;
use yii\base\Component;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use rocketpark\mux\Mux;

use GuzzleHttp;
use MuxPhp;



/**                             
 * @property-read Settings $settings
 */

/**
 * Settings service
 */
class SettingsService extends Component
{

    public static function saveSettings($plugin, $settings)
    {

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            return null;
        };

        return true;
    }
}
