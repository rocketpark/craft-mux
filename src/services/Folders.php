<?php

namespace rocketpark\mux\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\Db;
use rocketpark\mux\elements\MuxAsset;
use rocketpark\mux\models\FolderCriteria;
use rocketpark\mux\models\MuxFolder;
use rocketpark\mux\models\MuxVolume;
use rocketpark\mux\records\MuxFolder as MuxFolderRecord;
use rocketpark\mux\events\MuxFolderEvent;
use yii\db\Exception;
use rocketpark\mux\Mux;
use Throwable;

/**
 * Folders service - for virtual folder organization
 *
 * @property-read array<int,MuxFolder|null> $foldersById Cached folders by ID
 * @property-read array<string,MuxFolder|null> $foldersByUid Cached folders by UID
 * @property-read array<int,MuxFolder|null> $rootFolders Cached root folders
 */
class Folders extends Component
{
    // Event constants
    public const EVENT_BEFORE_CREATE_FOLDER = 'beforeCreateFolder';
    public const EVENT_AFTER_CREATE_FOLDER = 'afterCreateFolder';
    // public const EVENT_BEFORE_UPDATE_FOLDER = 'beforeUpdateFolder';
    // public const EVENT_AFTER_UPDATE_FOLDER = 'afterUpdateFolder';
    public const EVENT_BEFORE_DELETE_FOLDER = 'beforeDeleteFolder';
    public const EVENT_AFTER_DELETE_FOLDER = 'afterDeleteFolder';

    // Folder Table Name
    public const MUX_FOLDERS_TABLE = '{{%mux_volumefolders}}';

    /**
     * @var array<int,MuxFolder|null>
     * @see getFolderById()
     */
    private array $_foldersById = [];

    /**
     * @var array<string,MuxFolder|null>
     * @see getFolderByUid()
     */
    private array $_foldersByUid = [];

    /**
     * @var array<int,MuxFolder|null>
     * @see getRootFolderByVolumeId()
     */
    private array $_rootFolders = [];

    /**
     * Returns a folder by its ID.
     */
    public function getFolderById(int $folderId): ?MuxFolder
    {
        if (!array_key_exists($folderId, $this->_foldersById)) {
            $result = $this->createFolderQuery()
                ->where(['id' => $folderId])
                ->one();

            $this->_foldersById[$folderId] = $result ? new MuxFolder($result) : null;
        }

        return $this->_foldersById[$folderId];
    }

    /**
     * Returns a folder by its UID.
     */
    public function getFolderByUid(string $uid): ?MuxFolder
    {
        $record = MuxFolderRecord::findOne(['uid' => $uid]);
        return $record ? $this->_createFolderFromRecord($record->toArray()) : null;
    }

    /**
     * Returns all folders.
     */
    public function getAllFolders(): array
    {
        $records = MuxFolderRecord::find()->orderBy(['name' => SORT_ASC])->all();
        return array_map([$this, '_createFolderFromRecord'], $records);
    }

    /**
     * Returns root folders.
     */
    public function getRootFolders(): array
    {
        $records = MuxFolderRecord::find()
            ->where(['parentId' => null])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        return array_map([$this, '_createFolderFromRecord'], $records);
    }

    /**
     * Returns child folders.
     */
    public function getChildFolders(int $parentId): array
    {
        $records = MuxFolderRecord::find()
            ->where(['parentId' => $parentId])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        return array_map([$this, '_createFolderFromRecord'], $records);
    }


    /**
     * Stores a folder record in the database.
     *
     * @param MuxFolder $folder
     * @throws Exception if the folder cannot be saved
     */
    public function storeFolderRecord(MuxFolder $folder): void
    {
        if (!$folder->id) {
            $record = new MuxFolderRecord();
        } else {
            $record = MuxFolderRecord::findOne(['id' => $folder->id]);
            if (!$record) {
                throw new Exception("No folder record found with ID {$folder->id}");
            }
        }

        $record->parentId = $folder->parentId;
        $record->volumeId = $folder->volumeId;
        $record->name = $folder->name;
        $record->path = $folder->path;
        
        if (!$record->save()) {
            $errors = $record->getErrors();
            $errorMessage = "Failed to save folder '{$folder->name}': " . json_encode($errors);
            throw new Exception($errorMessage);
        }

        $folder->id = $record->id;
        $folder->uid = $record->uid;
    }

    /**
     * Deletes a folder (legacy method).
     */
    public function deleteFolderLegacy(int $id): bool
    {
        $record = MuxFolderRecord::findOne($id);
        if (!$record) return false;
        // Optionally: delete child folders and/or reassign assets
        return (bool)$record->delete();
    }

    /**
     * Creates a folder from a record.
     */
    private function _createFolderFromRecord(array $data): MuxFolder
    {
        $folder = new MuxFolder();
        $folder->id = $data['id'];
        $folder->parentId = $data['parentId'];
        $folder->name = $data['name'];
        $folder->uid = $data['uid'];

        return $folder;
    }

    /**
     * Creates a folder from an array (database result).
     */
    public function _createFolderFromArray(array $data): MuxFolder
    {
        $folder = new MuxFolder();
        $folder->id = $data['id'];
        $folder->volumeId = $data['volumeId'];
        $folder->parentId = $data['parentId'];
        $folder->name = $data['name'];
        $folder->path = $data['path'] ?? null;
        $folder->uid = $data['uid'];

        return $folder;
    }

    /**
     * Creates a new folder.
     */
    public function createFolder(MuxFolder $folder): void
    {
        // Fire before create event
        $event = new MuxFolderEvent([
            'folder' => $folder,
            'isNew' => true,
        ]);
        
        $this->trigger(self::EVENT_BEFORE_CREATE_FOLDER, $event);
        
        if ($event->isValid === false) {
            throw new Exception('Folder creation was cancelled by event handler.');
        }

        $parent = $folder->getParent();

        if (!$parent) {
            throw new Exception('Folder ' . $folder->id . ' doesn’t have a parent.');
        }
        
        $existingFolder = $this->findFolder([
            'parentId' => $folder->parentId,
            'name' => $folder->name,
        ]);

        if ($existingFolder && (!$folder->id || $folder->id !== $existingFolder->id)) {
            throw new Exception(Craft::t('app',
                'A folder with the name “{folderName}” already exists in the volume.',
                ['folderName' => $folder->name]));
        }

        $this->storeFolderRecord($folder);

        // Fire after create event
        $this->trigger(self::EVENT_AFTER_CREATE_FOLDER, $event);
    }

    /**
     * Renames a folder by its ID.
     *
     * @param int $folderId
     * @param string $newName
     * @return string The new folder name after cleaning it.
     * @throws Exception If the folder to be renamed can't be found or trying to rename the top folder.
     * @throws Exception
     * @throws Exception
     */
    public function renameFolderById(int $folderId, string $newName): string
    {
        $folder = $this->getFolderById($folderId);

        if (!$folder) {
            throw new Exception(Craft::t('app', 'No folder exists with the ID “{id}”', [
                'id' => $folderId,
            ]));
        }

        if (!$folder->parentId) {
            throw new Exception(Craft::t('app', 'It’s not possible to rename the top folder of a Volume.'));
        }

        $conflictingFolder = $this->findFolder([
            'parentId' => $folder->parentId,
            'name' => $newName,
        ]);

        if ($conflictingFolder) {
            throw new Exception(Craft::t('app', 'A folder with the name “{folderName}” already exists in the folder.', [
                'folderName' => $newName,
            ]));
        }

        $parentFolderPath = dirname($folder->path);
        $newFolderPath = (($parentFolderPath && $parentFolderPath !== '.') ? $parentFolderPath . '/' : '') . $newName . '/';

        $descendantFolders = $this->getAllDescendantFolders($folder);

        foreach ($descendantFolders as $descendantFolder) {
            $descendantFolder->path = preg_replace('#^' . $folder->path . '#', $newFolderPath, $descendantFolder->path);
            $this->storeFolderRecord($descendantFolder);
        }

        // Now change the affected folder
        $folder->name = $newName;
        $folder->path = $newFolderPath;
        $this->storeFolderRecord($folder);

        return $newName;
    }

    /**
     * Deletes a folder by its ID.
     *
     * @param int|array $folderIds
     * @param bool $deleteDir Should the volume directory be deleted along the record, if applicable. Defaults to true.
     * @throws Exception if the volume cannot be fetched from folder.
     */
    public function deleteFoldersByIds(int|array $folderIds, bool $deleteDir = true): void
    {
        $folderIds = array_values(array_unique((array)$folderIds));
        if (!$folderIds) return;
        $this->_deleteFoldersAndContents($folderIds, $deleteDir);
    }

    /**
     * Deletes a folder by its ID.
     *
     * @param int $folderId
     * @param bool $deleteDir Should the volume directory be deleted along the record, if applicable. Defaults to true.
     * @throws Exception if the volume cannot be fetched from folder.
     */
    public function deleteFolder(int $folderId): bool
    {
        $folder = $this->getFolderById($folderId);
        
        if (!$folder) {
            return false;
        }
        
        // Fire before delete event
        $event = new MuxFolderEvent([
            'folder' => $folder,
            'isNew' => false,
        ]);
        
        $this->trigger(self::EVENT_BEFORE_DELETE_FOLDER, $event);
        
        if ($event->isValid === false) {
            return false;
        }
        
        $this->_deleteFoldersAndContents([$folderId], true);
        
        // Fire after delete event
        $this->trigger(self::EVENT_AFTER_DELETE_FOLDER, $event);
        
        return true;
    }

    /**
     * Ensures a folder entry exists in the DB for the full path. Depending on the use, it’s also possible to ensure a physical folder exists.
     *
     * @param string $fullPath The path to ensure the folder exists at.
     * @param MuxVolume $volume
     * @return MuxFolder
     * @throws VolumeException if something went catastrophically wrong creating the folder.
     */
    public function ensureFolderByFullPathAndVolume(string $fullPath, MuxVolume $volume): MuxFolder
    {
        $parentFolder = $this->getRootFolderByVolumeId($volume->id);
        $folderModel = $parentFolder;
        $parentId = $parentFolder->id;

        if ($fullPath !== '') {
            // Split the path into segments
            $parts = preg_split('/\\\\|\//', trim($fullPath, '/\\'));
            $path = '';

            // Create each folder segment recursively
            while (($part = array_shift($parts)) !== null) {
                $path .= $part . '/';

                $parameters = new FolderCriteria([
                    'path' => $path,
                    'volumeId' => $volume->id,
                ]);

                // Create the record for current segment if needed
                if (($folderModel = $this->findFolder($parameters)) === null) {
                    $folderModel = new MuxFolder();
                    $folderModel->volumeId = $volume->id;
                    $folderModel->parentId = $parentId;
                    $folderModel->name = $part;
                    $folderModel->path = $path;
                    $this->storeFolderRecord($folderModel);
                }

                // Set up for next iteration
                $folderId = $folderModel->id;
                $parentId = $folderId;
            }
        }

        return $folderModel;
    }


    /**
     * Deletes folders and their contents.
     *
     * @param array $rootFolderIds
     * @param bool $deleteDir Should the volume directory be deleted along the record, if applicable. Defaults to true.
     */
    private function _deleteFoldersAndContents(array $folderIds, bool $deleteRemote): void
    {
        // 1: Collect all folder IDs including descendants
        $allFolderIds = $this->_collectAllFolderIdsBulk($folderIds);
        if (empty($allFolderIds)) {
            return;
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // 2: Delete all Craft elements for these folders
            $assetIdsForMux = $this->deleteMuxAssetsForFolderIds($allFolderIds);

            // 3: Delete the folders themselves
            MuxFolderRecord::deleteAll(['id' => $allFolderIds]);

            $transaction->commit();

            // 4: Cleanup on Mux (queue it) if requested
            if ($deleteRemote && !empty($assetIdsForMux)) {
                Mux::$plugin->assets->queueCleanupMuxAssets($assetIdsForMux);
            }

        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Deletes MuxAsset elements for the given folder IDs and returns the remote Mux IDs.
     * @param array $folderIds
     * @return array
     */
    private function deleteMuxAssetsForFolderIds(array $folderIds): array
    {
        $assetIds = [];
        $elementService = Craft::$app->getElements();

        $assets = MuxAsset::find()->folderId($folderIds)->all();

        foreach ($assets as $asset) {
            if (!empty($asset->asset_id)) {
                $assetIds[] = $asset->asset_id;
            }
            $elementService->deleteElement($asset, true); // hard delete
        }

        return array_values(array_unique($assetIds));
    }

    /**
     * Bulk descendant collection for multiple roots in ONE query.
     * Uses path prefix + volume constraints like getAllDescendantFolders(),
     * but aggregates all roots to avoid N calls.
     */
    private function _collectAllFolderIdsBulk(array $rootFolderIds): array
    {
        // Load roots once
        $roots = [];
        foreach ($rootFolderIds as $id) {
            $f = $this->getFolderById((int)$id);
            if ($f) $roots[] = $f;
        }
        if (!$roots) return [];

        // Build a single query that matches any root’s path prefix within its volume
        $query = (new \craft\db\Query())
            ->select(['id'])
            ->from([self::MUX_FOLDERS_TABLE]);

        // always include the roots themselves
        $ids = array_map(fn($f) => $f->id, $roots);

        // Add (volumeId AND path LIKE 'rootPath%') OR … for each root that has a path
        $orWhere = ['or'];
        foreach ($roots as $root) {
            // If path is null, treat it as only the root (no descendants by path)
            if ($root->path !== null) {
                $orWhere[] = [
                    'and',
                    ['volumeId' => $root->volumeId],
                    ['not', ['parentId' => null]],
                    ['like', 'path', \craft\helpers\Db::escapeForLike($root->path) . '%', false],
                ];
            }
        }

        if (count($orWhere) > 1) {
            $query->orWhere($orWhere);
        } else {
            // No descendant conditions; just return roots
            return array_values(array_unique($ids));
        }

        $descendantIds = $query->column();

        return array_values(array_unique(array_merge($ids, $descendantIds)));
    }



    /**
     * Returns a DbCommand object prepped for retrieving assets.
     *
     * @return Query
     */
    public function createFolderQuery(): Query
    {
        return (new Query())
            ->select(['id', 'parentId', 'volumeId', 'name', 'path', 'uid'])
            ->from([self::MUX_FOLDERS_TABLE]);
    }

    /**
     * Finds the first folder that matches a given criteria.
     *
     * @param mixed $criteria
     * @return MuxFolder|null
     */
    public function findFolder(mixed $criteria = []): ?MuxFolder
    {
        if (!$criteria instanceof FolderCriteria) {
            $criteria = new FolderCriteria($criteria);
        }

        $criteria->limit = 1;
        $folder = $this->findFolders($criteria);

        if (!empty($folder)) {
            return array_pop($folder);
        }

        return null;
    }

    /**
     * Finds folders that match a given criteria.
     *
     * @param mixed $criteria
     * @return MuxFolder[]
     */
    public function findFolders(mixed $criteria = []): array
    {
        if (!$criteria instanceof FolderCriteria) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = $this->createFolderQuery();

        $this->_applyFolderConditions($query, $criteria);

        if ($criteria->order) {
            $query->orderBy($criteria->order);
        }

        if ($criteria->offset) {
            $query->offset($criteria->offset);
        }

        if ($criteria->limit) {
            $query->limit($criteria->limit);
        }

        $results = $query->all();
        $folders = [];

        foreach ($results as $result) {
            $folder = new MuxFolder($result);
            $this->_foldersById[$folder->id] = $folder;
            $folders[$folder->id] = $folder;
        }

        return $folders;
    }


    /**
     * Returns all of the folders that are descendants of a given folder.
     *
     * @param MuxFolder $parentFolder
     * @param string $orderBy
     * @param bool $withParent Whether the parent folder should be included in the results
     * @param bool $asTree Whether the folders should be returned hierarchically
     * @return array<int,MuxFolder> The descendant folders, indexed by their IDs
     */
    public function getAllDescendantFolders(
        MuxFolder $parentFolder,
        string $orderBy = 'path',
        bool $withParent = true,
        bool $asTree = false,
    ): array {
        $query = $this->createFolderQuery()
            ->where([
                'and',
                ['volumeId' => $parentFolder->volumeId],
                ['not', ['parentId' => null]],
            ]);

        if ($parentFolder->path !== null) {
            $query->andWhere(['like', 'path', Db::escapeForLike($parentFolder->path) . '%', false]);
        }

        if ($orderBy) {
            $query->orderBy($orderBy);
        }

        if (!$withParent) {
            $query->andWhere(['not', ['id' => $parentFolder->id]]);
        }

        $results = $query->all();
        $descendantFolders = [];

        foreach ($results as $result) {
            $folder = new MuxFolder($result);
            $this->_foldersById[$folder->id] = $folder;
            $descendantFolders[$folder->id] = $folder;
        }

        if ($asTree) {
            return $this->_getFolderTreeByFolders($descendantFolders);
        }

        return $descendantFolders;
    }

    /**
     * Returns the root folder for a volume.
     * @param int $volumeId
     * @return MuxFolder|null
     */
    public function getRootFolderByVolumeId(int $volumeId): ?MuxFolder
    {
        
        if (!array_key_exists($volumeId, $this->_rootFolders)) {            
            $volume = Mux::$plugin->volumes->getVolumeById($volumeId);
            if (!$volume) {
                return $this->_rootFolders[$volumeId] = null;
            }

            $folder = $this->findFolder([
                'volumeId' => $volumeId,
                'parentId' => ':empty:',  // This finds folders with parentId = null
            ]);

            if (!$folder) {
                // If no root folder exists, CREATE ONE automatically!
                $folder = new MuxFolder();
                $folder->volumeId = $volume->id;
                $folder->parentId = null;
                $folder->name = $volume->name;
                $folder->path = '';
                $this->storeFolderRecord($folder);
            }

            $this->_rootFolders[$volumeId] = $folder;
        }
      
        return $this->_rootFolders[$volumeId];
    }

    /**
     * Applies WHERE conditions to a DbCommand query for folders.
     *
     * @param Query $query
     * @param FolderCriteria $criteria
     */
    private function _applyFolderConditions(Query $query, FolderCriteria $criteria): void
    {
        if ($criteria->id) {
            $query->andWhere(Db::parseNumericParam('id', $criteria->id));
        }

        if ($criteria->volumeId) {
            $query->andWhere(Db::parseNumericParam('volumeId', $criteria->volumeId));
        }

        if ($criteria->parentId) {
            $query->andWhere(Db::parseNumericParam('parentId', $criteria->parentId));
        }

        if ($criteria->name) {
            $query->andWhere(Db::parseParam('name', $criteria->name));
        }

        if ($criteria->uid) {
            $query->andWhere(Db::parseParam('uid', $criteria->uid));
        }

        if ($criteria->path !== null) {
            // Does the path have a comma in it?
            if (str_contains($criteria->path, ',')) {
                // Escape the comma.
                $query->andWhere(Db::parseParam('path', str_replace(',', '\,', $criteria->path)));
            } else {
                $query->andWhere(Db::parseParam('path', $criteria->path));
            }
        }
    }

    /**
     * Returns whether any folders exist which match a given criteria.
     *
     * @param mixed $criteria
     * @return bool
     */
    public function foldersExist($criteria = null): bool
    {
        if (!($criteria instanceof FolderCriteria)) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = (new Query())
            ->from([self::MUX_FOLDERS_TABLE]);

        $this->_applyFolderConditions($query, $criteria);

        return $query->exists();
    }

} 