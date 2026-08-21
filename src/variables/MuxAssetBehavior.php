<?php
namespace rocketpark\mux\variables;

use Craft;
use craft\helpers\App;
use rocketpark\mux\constants\StaticRenditions;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset;
use rocketpark\mux\elements\db\MuxAssetQuery;
use rocketpark\mux\records\SignedKeys;
use yii\base\Behavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class MuxAssetBehavior extends Behavior
{
    public function mux(array $criteria = []): MuxAssetQuery
    {
        // Create a query via your element type, and apply any passed criteria:
        return Craft::configure(MuxAsset::find(), $criteria);
    }

    /**
     * Expose settings as a property-like getter, so you can use `craft.mux.settings` in templates.
     * This does not interfere with the mux() method above.
     */
    public function muxSettings()
    {
        $settings = Mux::$settings;
        
        // Manually parse environment variables for the settings that need it
        if ($settings) {
            // Dynamically parse all public string properties for environment variable references.
            // parseEnv() can return null (unresolved $VAR) or bool, neither of which is assignable
            // to these strictly-typed string properties, so fall back to the raw value in that case.
            foreach (get_object_vars($settings) as $key => $value) {
                if (is_string($value)) {
                    $parsed = App::parseEnv($value);
                    $settings->$key = is_string($parsed) ? $parsed : $value;
                }
            }
        }
        
        return $settings;
    }

    public function muxRenditionOptions(): array
    {
        return StaticRenditions::ALL_RENDITION_OPTIONS;
    }

    public function muxSpecificResolutions(): array
    {
        return StaticRenditions::SPECIFIC_RESOLUTIONS;
    }

    public function signedKeys(): ActiveQuery
    {
        return SignedKeys::find();
    }

}