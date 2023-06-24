<?php
/**
 * @link https://rocketpark.com/
 * @copyright Copyright (c) RocketPark.
 */

namespace rocketpark\mux\events;

use craft\events\CancelableEvent;
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;
use MuxPhp\Models\Asset;

/**
 * Event triggered just before a synchronized asset is going to be saved.
 *
 * @author Rocket Park. <support@rocketpark.com>
 */
class MuxAssetSyncEvent extends CancelableEvent
{
    /**
     * @var MuxAssetElement Craft mux asset element being synchronized.
     */
    public MuxAssetElement $element;

    /**
     * @var Asset Source MUX API asset.
     */
    public Asset $source;

    /**
     * @var array List of Mux metafields for the asset.
     */
    public array $metafields;
}