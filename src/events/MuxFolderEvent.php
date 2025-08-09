<?php
/**
 * @link https://rocketpark.com/
 * @copyright Copyright (c) RocketPark.
 */

namespace rocketpark\mux\events;

use craft\events\CancelableEvent;
use rocketpark\mux\models\MuxFolder;

/**
 * Event triggered before and after MuxFolder operations.
 *
 * @author Rocket Park. <support@rocketpark.com>
 */
class MuxFolderEvent extends CancelableEvent
{
    /**
     * @var MuxFolder The MuxFolder model.
     */
    public MuxFolder $folder;

    /**
     * @var bool Whether this is a new folder.
     */
    public bool $isNew = false;

    /**
     * @var array Additional event data.
     */
    public array $eventData = [];
}
