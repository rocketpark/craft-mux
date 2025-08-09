# Mux Plugin Events

This plugin provides comprehensive events for all major operations on MuxAsset, MuxFolder, and MuxVolume entities. These events allow you to hook into the plugin's workflow and customize behavior.

## Event Types

### MuxAsset Events

**Available Events:**
- `EVENT_BEFORE_CREATE_ASSET` - Before creating a new asset
- `EVENT_AFTER_CREATE_ASSET` - After creating a new asset
- `EVENT_BEFORE_UPDATE_ASSET` - Before updating an existing asset
- `EVENT_AFTER_UPDATE_ASSET` - After updating an existing asset
- `EVENT_BEFORE_UPLOAD_ASSET` - Before uploading an asset to Mux
- `EVENT_AFTER_UPLOAD_ASSET` - After uploading an asset to Mux
- `EVENT_BEFORE_DELETE_ASSET` - Before deleting an asset
- `EVENT_AFTER_DELETE_ASSET` - After deleting an asset
- `EVENT_BEFORE_MOVE_ASSET` - Before moving an asset to another folder
- `EVENT_AFTER_MOVE_ASSET` - After moving an asset to another folder
- `EVENT_BEFORE_SYNCHRONIZE_MUX_ASSET` - Before synchronizing with Mux API (existing)

### MuxFolder Events

**Available Events:**
- `EVENT_BEFORE_CREATE_FOLDER` - Before creating a new folder
- `EVENT_AFTER_CREATE_FOLDER` - After creating a new folder
- `EVENT_BEFORE_UPDATE_FOLDER` - Before updating an existing folder
- `EVENT_AFTER_UPDATE_FOLDER` - After updating an existing folder
- `EVENT_BEFORE_DELETE_FOLDER` - Before deleting a folder
- `EVENT_AFTER_DELETE_FOLDER` - After deleting a folder

### MuxVolume Events

**Available Events:**
- `EVENT_BEFORE_CREATE_VOLUME` - Before creating a new volume
- `EVENT_AFTER_CREATE_VOLUME` - After creating a new volume
- `EVENT_BEFORE_UPDATE_VOLUME` - Before updating an existing volume
- `EVENT_AFTER_UPDATE_VOLUME` - After updating an existing volume
- `EVENT_BEFORE_DELETE_VOLUME` - Before deleting a volume
- `EVENT_AFTER_DELETE_VOLUME` - After deleting a volume

## Usage Examples

### 1. Logging Asset Operations

```php
use rocketpark\mux\Mux;
use rocketpark\mux\events\MuxAssetEvent;
use rocketpark\mux\services\Assets;
use yii\base\Event;

Event::on(
    Assets::class,
    Assets::EVENT_AFTER_CREATE_ASSET,
    function (MuxAssetEvent $event) {
        $asset = $event->asset;
        Craft::info("New MuxAsset created: {$asset->title} (ID: {$asset->id})", 'mux');
    }
);
```

### 2. Custom Validation Before Upload

```php
use rocketpark\mux\events\MuxAssetUploadEvent;
use rocketpark\mux\services\Assets;

Event::on(
    Assets::class,
    Assets::EVENT_BEFORE_UPLOAD_ASSET,
    function (MuxAssetUploadEvent $event) {
        // Custom validation logic
        if (strlen($event->title) < 5) {
            $event->isValid = false;
            Craft::error('Asset title must be at least 5 characters long');
        }
    }
);
```

### 3. Auto-categorization After Asset Creation

```php
use rocketpark\mux\events\MuxAssetEvent;
use rocketpark\mux\services\Assets;

Event::on(
    Assets::class,
    Assets::EVENT_AFTER_CREATE_ASSET,
    function (MuxAssetEvent $event) {
        $asset = $event->asset;
        
        // Auto-categorize based on title
        if (strpos(strtolower($asset->title), 'tutorial') !== false) {
            // Move to tutorials folder
            $tutorialsFolder = Mux::$plugin->folders->findFolder(['name' => 'Tutorials']);
            if ($tutorialsFolder) {
                Mux::$plugin->assets->moveAsset($asset, $tutorialsFolder);
            }
        }
    }
);
```

### 4. Folder Creation Notifications

```php
use rocketpark\mux\events\MuxFolderEvent;
use rocketpark\mux\services\Folders;

Event::on(
    Folders::class,
    Folders::EVENT_AFTER_CREATE_FOLDER,
    function (MuxFolderEvent $event) {
        $folder = $event->folder;
        
        // Send notification to admin
        $message = "New Mux folder created: {$folder->name}";
        // Your notification logic here
    }
);
```

### 5. Volume Cleanup Before Deletion

```php
use rocketpark\mux\events\MuxVolumeEvent;
use rocketpark\mux\services\Volumes;

Event::on(
    Volumes::class,
    Volumes::EVENT_BEFORE_DELETE_VOLUME,
    function (MuxVolumeEvent $event) {
        $volume = $event->volume;
        
        // Custom cleanup logic
        $assetCount = MuxAsset::find()->volumeId($volume->id)->count();
        
        if ($assetCount > 0) {
            Craft::warning("Deleting volume '{$volume->name}' with {$assetCount} assets");
        }
    }
);
```

### 6. Asset Move Tracking

```php
use rocketpark\mux\events\MuxAssetMoveEvent;
use rocketpark\mux\services\Assets;

Event::on(
    Assets::class,
    Assets::EVENT_AFTER_MOVE_ASSET,
    function (MuxAssetMoveEvent $event) {
        $asset = $event->asset;
        $sourceFolder = $event->sourceFolder;
        $destinationFolder = $event->destinationFolder;
        
        $sourceName = $sourceFolder ? $sourceFolder->name : 'Root';
        $destName = $destinationFolder ? $destinationFolder->name : 'Root';
        
        Craft::info("Asset '{$asset->title}' moved from '{$sourceName}' to '{$destName}'", 'mux');
    }
);
```

### 7. Preventing Specific Operations

```php
use rocketpark\mux\events\MuxAssetEvent;
use rocketpark\mux\services\Assets;

Event::on(
    Assets::class,
    Assets::EVENT_BEFORE_DELETE_ASSET,
    function (MuxAssetEvent $event) {
        $asset = $event->asset;
        
        // Prevent deletion of assets with specific tags
        if (isset($asset->meta['protected']) && $asset->meta['protected'] === true) {
            $event->isValid = false;
            Craft::error("Cannot delete protected asset: {$asset->title}");
        }
    }
);
```

## Event Classes

### MuxAssetEvent

Properties:
- `MuxAssetElement $asset` - The asset element
- `bool $isNew` - Whether this is a new asset
- `array $eventData` - Additional event data

### MuxAssetUploadEvent

Properties:
- `string $title` - The upload title
- `string|null $volumeUid` - The volume UID
- `string|null $folderId` - The folder ID
- `array|null $uploadData` - The upload response data (after upload)
- `array $eventData` - Additional event data

### MuxAssetMoveEvent

Properties:
- `MuxAssetElement $asset` - The asset being moved
- `MuxFolder|null $sourceFolder` - The source folder
- `MuxFolder|null $destinationFolder` - The destination folder
- `array $eventData` - Additional event data

### MuxFolderEvent

Properties:
- `MuxFolder $folder` - The folder model
- `bool $isNew` - Whether this is a new folder
- `array $eventData` - Additional event data

### MuxVolumeEvent

Properties:
- `MuxVolume $volume` - The volume model
- `bool $isNew` - Whether this is a new volume
- `array $eventData` - Additional event data

## Installation

Register your event listeners in your plugin's or module's `init()` method:

```php
public function init()
{
    parent::init();
    
    // Register your event listeners here
    Event::on(/* your event listeners */);
}
```

All events are cancelable, meaning you can set `$event->isValid = false` to prevent the operation from continuing.
