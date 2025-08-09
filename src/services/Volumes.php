<?php

namespace rocketpark\mux\services;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use rocketpark\mux\Mux;
use rocketpark\mux\models\MuxVolume;
use rocketpark\mux\records\MuxVolume as MuxVolumeRecord;
use rocketpark\mux\elements\MuxAsset;
use Throwable;
use yii\db\Exception;

/**
 * Volumes service - for virtual volume organization
 */
class Volumes extends Component
{
    public const MUX_VOLUMES_TABLE = '{{%mux_volumes}}';

    /**
     * @var MemoizableArray<Volume>|null
     * @see _volumes()
     */
    private ?MemoizableArray $_volumes = null;

    /**
     * Serializer
     *
     * @since 3.5.14
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['_volumes']);
        return $vars;
    }

    /**
     * Returns all of the volume IDs.
     *
     * @return int[]
     */
    public function getAllVolumeIds(): array
    {
        return array_values(array_map(fn(MuxVolume $volume) => $volume->id, $this->getAllVolumes()));
    }

    /**
     * Returns the total number of volumes.
     *
     * @return int
     */
    public function getTotalVolumes(): int
    {
        return count($this->getAllVolumes());
    }

     /**
     * Returns a memoizable array of all volumes.
     *
     * @return MemoizableArray<Volume>
     */
    private function _volumes(): MemoizableArray
    {
        if (!isset($this->_volumes)) {
            $this->_volumes = new MemoizableArray(
                $this->_createVolumeQuery()->all(),
                fn(array $result) => Craft::createObject(MuxVolume::class, [$result]),
            );
        }

        return $this->_volumes;
    }

    /**
     * Returns all volumes.
     *
     * @return MuxVolume[]
     */
    public function getAllVolumes(): array
    {
        return $this->_volumes()->all();
    }

     /**
     * Returns a volume by its ID.
     *
     * @param int $volumeId
     * @return MuxVolume|null
     */
    public function getVolumeById(int $volumeId): ?MuxVolume
    {
        return $this->_volumes()->firstWhere('id', $volumeId);
    }

    /**
     * Returns a volume by its UID.
     *
     * @param string $volumeUid
     * @return MuxVolume|null
     */
    public function getVolumeByUid(string $volumeUid): ?MuxVolume
    {
        return $this->_volumes()->firstWhere('uid', $volumeUid, true);
    }

    /**
     * Returns a volume by its handle.
     *
     * @param string $handle
     * @return MuxVolume|null
     */
    public function getVolumeByHandle(string $handle): ?MuxVolume
    {
        return $this->_volumes()->firstWhere('handle', $handle, true);
    }

    /**
     * Creates or updates a volume.
     *
     * ---
     *
     * ```php
     * use rocketpark\mux\models\MuxVolume;
     *
     * $volume = new MuxVolume([
     *     'name' => 'Content Images',
     *     'handle' => 'contentImages',
     * ]);
     *
     * if (!Craft::$app->volumes->saveVolume(($volume))) {
     *     throw new Exception('Couldn’t save volume.');
     * }
     * ```
     *
     * @param MuxVolume $volume the volume to be saved.
     * @param bool $runValidation Whether the volume should be validated
     * @return bool Whether the volume was saved successfully
     * @throws Throwable
     */
    public function saveVolume(MuxVolume $volume, bool $runValidation = true): bool
    {
        $isNewVolume = !$volume->id;

        // Fire a 'beforeSaveVolume' event
        // if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_VOLUME)) {
        //     $this->trigger(self::EVENT_BEFORE_SAVE_VOLUME, new VolumeEvent([
        //         'volume' => $volume,
        //         'isNew' => $isNewVolume,
        //     ]));
        // }

        if ($runValidation && !$volume->validate()) {
            Craft::info('Volume not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewVolume) {
            if (!$volume->uid) {
                $volume->uid = StringHelper::UUID();
            }

            $volume->sortOrder = (new Query())
                    ->from([self::MUX_VOLUMES_TABLE])
                    ->max('[[sortOrder]]') + 1;
        } elseif (!$volume->uid) {
            $volume->uid = Db::uidById(self::MUX_VOLUMES_TABLE, $volume->id);
        }

        // Create or get the record
        if ($isNewVolume) {
            $record = new MuxVolumeRecord();
        } else {
            $record = MuxVolumeRecord::findOne($volume->id);
            if (!$record) {
                throw new Exception("No volume exists with the ID '{$volume->id}'");
            }
        }

        // Set the record attributes
        $record->name = $volume->name;
        $record->handle = $volume->handle;
        $record->uid = $volume->uid;
        $record->sortOrder = $volume->sortOrder;

        // Save the record
        if (!$record->save()) {
            $volume->addErrors($record->getErrors());
            return false;
        }

        // Update the model with the saved record data
        $volume->id = $record->id;
        $volume->uid = $record->uid;

        // Clear the memoized volumes cache
        $this->_volumes = null;

        return true;
    }

    /**
     * Deletes an asset volume by its ID.
     *
     * @param int $volumeId
     * @return bool
     * @throws Throwable
     */
    public function deleteVolumeById(int $volumeId): bool
    {
        $volume = $this->getVolumeById($volumeId);

        if (!$volume) {
            return false;
        }

        return $this->deleteVolume($volume);
    }

    /**
     * Deletes an asset volume.
     *
     * @param MuxVolume $volume The volume to delete
     * @return bool
     * @throws Throwable
     */
    public function deleteVolume(MuxVolume $volume): bool
    {
        // Fire a 'beforeDeleteVolume' event
        // if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_VOLUME)) {
        //     $this->trigger(self::EVENT_BEFORE_DELETE_VOLUME, new VolumeEvent([
        //         'volume' => $volume,
        //     ]));
        // }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();
        
        try {
            // 1. Delete from database first (source of truth)
            $assets = MuxAsset::find()->volumeId($volume->id)->all();
            $assetIds = array_map(fn($a) => $a->asset_id, $assets);
            
            foreach ($assets as $asset) {
                Craft::$app->getElements()->deleteElement($asset, true);
            }
            
            // Delete folders and volume
            $folders = Mux::$plugin->folders->findFolders(['volumeId' => $volume->id]);
            foreach ($folders as $folder) {
                Mux::$plugin->folders->deleteFolder($folder->id);
            }
            
            MuxVolumeRecord::deleteAll(['id' => $volume->id]);
            
            $transaction->commit();
            
            // 2. Clean up Mux assets
            Mux::$plugin->assets->queueCleanupMuxAssets(array_values(array_unique($assetIds)));
            
            return true;
            
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Returns a DbCommand object prepped for retrieving volumes.
     *
     * @return Query
     */
    private function _createVolumeQuery(): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'uid',
            ])
            ->from([self::MUX_VOLUMES_TABLE])
            ->orderBy(['sortOrder' => SORT_ASC]);

        return $query;
    }

    /**
     * Gets a volume's record by uid.
     *
     * @param string $uid
     * @return MuxVolumeRecord
     */
    private function _getVolumeRecord(string $uid): MuxVolumeRecord
    {
        $query = MuxVolumeRecord::find();
        $query->andWhere(['uid' => $uid]);
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @var MuxVolumeRecord */
        return $query->one() ?? new MuxVolumeRecord();
    }

}