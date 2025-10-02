<?php

namespace rocketpark\mux\services;


use Craft;
use yii\base\Component;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use rocketpark\mux\Mux;

use GuzzleHttp;
use MuxPhp;



/**                             
 * @property-read Settings $settings Plugin settings
 */

/**
 * Settings service
 */
class SettingsService extends Component
{

    /**
     * Save settings
     * @param Plugin $plugin
     * @param array $settings
     * @return bool|null
     */
    public static function saveSettings($plugin, $settings)
    {

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            return null;
        };

        return true;
    }

    /**
     * Get settings with configuration file overrides applied
     */
    public function getSettingsWithOverrides(): array
    {
        $settings = Mux::$settings->getAttributes();
        
        // Load configuration file overrides
        $configOverrides = Craft::$app->getConfig()->getConfigFromFile('mux');
        
        if (!empty($configOverrides)) {
            $settings = array_merge($settings, $configOverrides);
        }
        
        return $settings;
    }

    /**
     * Check if a setting is overridden by configuration file
     */
    public function isSettingOverridden(string $settingName): bool
    {
        $configOverrides = Craft::$app->getConfig()->getConfigFromFile('mux');
        
        return !empty($configOverrides[$settingName]);
    }

    /**
     * Get the effective value of a setting (considering overrides)
     */
    public function getEffectiveSetting(string $settingName, $defaultValue = null)
    {
        $configOverrides = Craft::$app->getConfig()->getConfigFromFile('mux');
        
        if (!empty($configOverrides[$settingName])) {
            return $configOverrides[$settingName];
        }
        
        return Mux::$settings->$settingName ?? $defaultValue;
    }

}
