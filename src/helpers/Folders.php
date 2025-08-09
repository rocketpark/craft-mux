<?php

namespace rocketpark\mux\helpers;

use Craft;
use craft\helpers\ArrayHelper;
use rocketpark\mux\Mux;
use rocketpark\mux\models\MuxFolder;


class Folders
{

    /**
     * Moves a folder and all its descendants to a new parent folder.
     * This is much simpler than mirroring - we just update the existing folders.
     *
     * @param MuxFolder $folderToMove The folder being moved
     * @param MuxFolder $destinationFolder The destination parent folder
     * @return array map of original folder ID => new folder ID (for asset updates)
     */
    public static function moveFolderStructure(MuxFolder $folderToMove, MuxFolder $destinationFolder): array
    {
        $folders = Mux::$plugin->folders;
        $folderIdChanges = [];
        
        // Debug logging
        Craft::info("Moving folder ID {$folderToMove->id} ({$folderToMove->name}) to destination ID {$destinationFolder->id} ({$destinationFolder->name})", 'mux');
        Craft::info("Original folder - parentId: {$folderToMove->parentId}, path: {$folderToMove->path}", 'mux');
        Craft::info("Destination folder - parentId: {$destinationFolder->parentId}, path: {$destinationFolder->path}", 'mux');
        
        // Get all descendant folders that need to be updated
        $sourceTree = $folders->getAllDescendantFolders($folderToMove);
        $allFoldersToUpdate = [$folderToMove];
        $allFoldersToUpdate = array_merge($allFoldersToUpdate, $sourceTree);
        
        // Get the old parent folder to calculate path changes
        $oldParent = $folderToMove->getParent();
        $oldParentPath = $oldParent ? $oldParent->path : '';
        $newParentPath = $destinationFolder->path;
        
        // Calculate the old base path (the path of the folder being moved)
        $oldBasePath = $folderToMove->path;
        
        Craft::info("Old parent path: {$oldParentPath}, New parent path: {$newParentPath}, Old base path: {$oldBasePath}", 'mux');
        
        // First, update all child folders (to avoid foreign key constraint issues)
        foreach ($allFoldersToUpdate as $folder) {
            if ($folder->id === $folderToMove->id) {
                continue; // Skip the main folder for now
            }
            
            $originalId = $folder->id;
            $originalParentId = $folder->parentId;
            $originalPath = $folder->path;
            
            // Child folders keep their existing parentId (they stay children of the moved folder)
            
            // Update volumeId if moving to a different volume
            if ($folder->volumeId !== $destinationFolder->volumeId) {
                $folder->volumeId = $destinationFolder->volumeId;
                Craft::info("Updating child folder {$folder->id} volumeId to {$folder->volumeId}", 'mux');
            }
            
            // Update path for child folders
            $relativePath = substr($folder->path, strlen($oldBasePath));
            $newBasePath = rtrim($newParentPath, '/') . '/' . $folderToMove->name . '/';
            $folder->path = $newBasePath . ltrim($relativePath, '/');
            Craft::info("Updating child folder {$folder->id} path from '{$originalPath}' to '{$folder->path}'", 'mux');
            
            // Save the folder
            try {
                $folders->storeFolderRecord($folder);
                Craft::info("Successfully saved child folder {$folder->id}", 'mux');
            } catch (\Exception $e) {
                Craft::error("Failed to save child folder {$folder->id}: " . $e->getMessage(), 'mux');
                throw $e;
            }
            
            // Track the ID change
            $folderIdChanges[$originalId] = $folder->id;
        }
        
        // Now update the main folder being moved
        $originalParentId = $folderToMove->parentId;
        $originalPath = $folderToMove->path;
        
        // Update parentId for the main folder being moved
        $folderToMove->parentId = $destinationFolder->id;
        Craft::info("Updating main folder {$folderToMove->id} parentId from {$originalParentId} to {$folderToMove->parentId}", 'mux');
        
        // Update volumeId if moving to a different volume
        if ($folderToMove->volumeId !== $destinationFolder->volumeId) {
            $folderToMove->volumeId = $destinationFolder->volumeId;
            Craft::info("Updating main folder {$folderToMove->id} volumeId to {$folderToMove->volumeId}", 'mux');
        }
        
        // Update path for the main folder
        if ($newParentPath) {
            $folderToMove->path = rtrim($newParentPath, '/') . '/' . $folderToMove->name . '/';
        } else {
            $folderToMove->path = $folderToMove->name . '/';
        }
        Craft::info("Updating main folder {$folderToMove->id} path from '{$originalPath}' to '{$folderToMove->path}'", 'mux');
        
        // Save the main folder
        try {
            $folders->storeFolderRecord($folderToMove);
            Craft::info("Successfully saved main folder {$folderToMove->id}", 'mux');
        } catch (\Exception $e) {
            Craft::error("Failed to save main folder {$folderToMove->id}: " . $e->getMessage(), 'mux');
            throw $e;
        }
        
        // Track the ID change for the main folder
        $folderIdChanges[$folderToMove->id] = $folderToMove->id;
        
        return $folderIdChanges;
    }
    
    /**
     * Mirrors a folder structure within a volume.
     *
     * @param MuxFolder $sourceParentFolder Folder whose nested folder structure should be mirrored.
     * @param MuxFolder $destinationFolder The destination folder
     * @param array $targetTreeMap map of relative path => existing folder ID
     * @return array map of original folder ID => new folder ID
     */
    public static function mirrorFolderStructure(MuxFolder $sourceParentFolder, MuxFolder $destinationFolder, array $targetTreeMap = []): array
    {
        $folders = Mux::$plugin->folders;
        $sourceTree = $folders->getAllDescendantFolders($sourceParentFolder);
        $previousParent = $sourceParentFolder->getParent();
        $sourcePrefixLength = strlen($previousParent->path);
        $folderIdChanges = [];

        foreach ($sourceTree as $sourceFolder) {
            $relativePath = substr($sourceFolder->path, $sourcePrefixLength);

            // If we have a target tree map, try to see if we should just point to an existing folder.
            if (!empty($targetTreeMap) && isset($targetTreeMap[$relativePath])) {
                $folderIdChanges[$sourceFolder->id] = $targetTreeMap[$relativePath];
            } else {
                $folder = new MuxFolder();
                $folder->name = $sourceFolder->name;
                $folder->volumeId = $destinationFolder->volumeId;
                $folder->path = ltrim(rtrim($destinationFolder->path, '/') . '/' . $relativePath, '/');

                // Any and all parent folders should be already mirrored
                $folder->parentId = ($folderIdChanges[$sourceFolder->parentId] ?? $destinationFolder->id);
                $folders->createFolder($folder);

                $folderIdChanges[$sourceFolder->id] = $folder->id;
            }
        }

        return $folderIdChanges;
    }

    /**
     * Create an asset transfer list based on a list of assets and an array of
     * changing folder IDs.
     *
     * @param MuxAssetElement[] $assets List of assets
     * @param array $folderIdChanges A map of folder ID changes
     * @return array
     */
    public static function fileTransferList(array $assets, array $folderIdChanges): array
    {
        $fileTransferList = [];

        // Build the transfer list for files
        foreach ($assets as $asset) {
            $newFolderId = $folderIdChanges[$asset->folderId];
            
            // Get the new folder to determine if volume needs to be updated
            $newFolder = Mux::$plugin->folders->getFolderById($newFolderId);
            
            $transferItem = [
                'assetId' => $asset->id,
                'folderId' => $newFolderId,
                'force' => true,
            ];
            
            // Include volumeId if the volume is changing
            if ($newFolder && $asset->volumeId !== $newFolder->volumeId) {
                $transferItem['volumeId'] = $newFolder->volumeId;
            }
            
            $fileTransferList[] = $transferItem;
        }

        return $fileTransferList;
    }

    /**
     * Sorts a folder tree by the volume sort order.
     *
     * @param MuxFolder[] $tree array passed by reference of the sortable folders.
     */
    public static function sortFolderTree(array &$tree): void
    {
        /** @phpstan-ignore parameterByRef.type */
        ArrayHelper::multisort($tree, fn($folder) => $folder->getVolume()->sortOrder);
    }
}