<?php
/**
 * @link https://rocketpark.com/
 * @copyright Copyright (c) RocketPark.
 */

namespace rocketpark\mux\events;

use craft\events\CancelableEvent;
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;
use rocketpark\mux\models\MuxFolder;

/**
 * Event triggered before and after MuxAsset move operations.
 *
 * @author Rocket Park. <support@rocketpark.com>
 */
class MuxAssetMoveEvent extends CancelableEvent
{
    /**
     * @var MuxAssetElement The MuxAsset elements being moved.
     */
    public array $elementIds;

    /**
     * @var int|null The source volume.
     */
    public ?int $volumeId = null;

    /**
     * @var MuxFolder|null The destination folder.
     */
    public ?int $targetFolderId = null;

    /**
     * @var array Additional event data.
     */
    public array $eventData = [];
}
