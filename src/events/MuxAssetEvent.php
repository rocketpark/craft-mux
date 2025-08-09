<?php
/**
 * @link https://rocketpark.com/
 * @copyright Copyright (c) RocketPark.
 */

namespace rocketpark\mux\events;

use craft\events\CancelableEvent;
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;

/**
 * Event triggered before and after MuxAsset operations.
 *
 * @author Rocket Park. <support@rocketpark.com>
 */
class MuxAssetEvent extends CancelableEvent
{
    /**
     * @var MuxAssetElement The MuxAsset element.
     */
    public MuxAssetElement $asset;

    /**
     * @var bool Whether this is a new asset.
     */
    public bool $isNew = false;

    /**
     * @var array Additional event data.
     */
    public array $eventData = [];
}
