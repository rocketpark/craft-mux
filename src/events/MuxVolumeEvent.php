<?php
/**
 * @link https://rocketpark.com/
 * @copyright Copyright (c) RocketPark.
 */

namespace rocketpark\mux\events;

use craft\events\CancelableEvent;
use rocketpark\mux\models\MuxVolume;

/**
 * Event triggered before and after MuxVolume operations.
 *
 * @author Rocket Park. <support@rocketpark.com>
 */
class MuxVolumeEvent extends CancelableEvent
{
    /**
     * @var MuxVolume The MuxVolume model.
     */
    public MuxVolume $volume;

    /**
     * @var bool Whether this is a new volume.
     */
    public bool $isNew = false;

    /**
     * @var array Additional event data.
     */
    public array $eventData = [];
}
