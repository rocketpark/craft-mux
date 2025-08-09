<?php
/**
 * @link https://rocketpark.com/
 * @copyright Copyright (c) RocketPark.
 */

namespace rocketpark\mux\events;

use craft\events\CancelableEvent;

/**
 * Event triggered before and after MuxAsset upload operations.
 *
 * @author Rocket Park. <support@rocketpark.com>
 */
class MuxAssetUploadEvent extends CancelableEvent
{
    /**
     * @var string The upload title.
     */
    public string $title;

    /**
     * @var string|null The volume UID.
     */
    public ?string $volumeUid = null;

    /**
     * @var string|null The folder ID.
     */
    public ?string $folderId = null;

    /**
     * @var array|null The upload response data.
     */
    public ?array $uploadData = null;

    /**
     * @var array Additional event data.
     */
    public array $eventData = [];
}
