<?php

namespace rocketpark\mux\controllers;

use Craft;
use craft\errors\VolumeException;
use craft\web\Controller;
use craft\web\Response;
use rocketpark\mux\Mux;
use rocketpark\mux\helpers\Folders;
use rocketpark\mux\elements\MuxAsset;
use rocketpark\mux\models\MuxFolder;
use yii\web\BadRequestHttpException;

class FoldersController extends Controller
{
    protected array|bool|int $allowAnonymous = false;

    public function actionGetPath()
    {
        $folderId = Craft::$app->getRequest()->getBodyParam('folderId');

        if (!$folderId) {
            return $this->asJson(['success' => false, 'message' => 'Folder ID is required.']);
        }

        $folder = Mux::$plugin->folders->getFolderById($folderId);
        
        if (!$folder) {
            return $this->asJson(['success' => false, 'message' => 'Folder not found.']);
        }

        // Build the path to this folder
        $path = [];
        $currentFolder = $folder;
        
        while ($currentFolder) {
            array_unshift($path, [
                'id' => $currentFolder->id,
                'name' => $currentFolder->name
            ]);
            
            $currentFolder = $currentFolder->getParent();
        }

        return $this->asJson(['success' => true, 'path' => $path]);
    }

    public function actionCreate()
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $parentId = $request->getBodyParam('parentId');
        $folderName = $request->getBodyParam('folderName');

        $folders = Mux::$plugin->folders;
        $parentFolder = $folders->findFolder(['id' => $parentId]);

        if (!$parentFolder) {
            return $this->asJson(['success' => false, 'message' => 'Parent folder not found.']);
        }

        try {

            // Check if it's possible to create subfolders in the target volume.
            //$this->requireVolumePermissionByFolder('createFolders', $parentFolder);

            $folderModel = new MuxFolder();
            $folderModel->name = $folderName;
            $folderModel->parentId = $parentId;
            $folderModel->volumeId = $parentFolder->volumeId;
            $folderModel->path = $parentFolder->path . $folderName . '/';

            $folders->createFolder($folderModel);
            
            return $this->asSuccess(data: [
                'folderName' => $folderModel->name,
                'folderUid' => $folderModel->uid,
                'folderId' => $folderModel->id,
            ]);
        } catch (\Exception $e) {
            return $this->asFailure($e->getMessage());
        }
    }

    public function actionDelete()
    {
        $this->requireAcceptsJson();

        $folderId = $this->request->getRequiredBodyParam('folderId');

        $folders = Mux::$plugin->folders;
        $folder = $folders->getFolderById($folderId);

        if (!$folder) {
            return $this->asJson(['success' => false, 'message' => 'Folder cannot be found']);
        }

        $folders->deleteFoldersByIds($folderId);

        return $this->asSuccess();
    }

    public function actionRename()
    {

        $this->requireAcceptsJson();
        $folders = Mux::$plugin->folders;
        $request = Craft::$app->getRequest();
        $folderId = $request->getBodyParam('folderId');
        $newName = $request->getBodyParam('newName');

        $folder = $folders->getFolderById($folderId);

        if (!$folder) {
            return $this->asJson(['success' => false, 'message' => 'Folder not found.']);
        }

        // Check if it's possible to delete objects and create folders in the target volume.
        //$this->requireVolumePermissionByFolder('deleteAssets', $folder);
        //$this->requireVolumePermissionByFolder('createFolders', $folder);


        $newName = $folders->renameFolderById($folderId, $newName);

        return $this->asSuccess(data: [
            'newName' => $newName,
        ]);
    }

    public function actionGetAll()
    {
        try {
            $folders = Mux::$plugin->folders->getAllFolders();
            
            return $this->asJson(['success' => true, 'folders' => $folders]);
        } catch (\Exception $e) {
            return $this->asJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Moves a folder.
     *
     * @return Response
     * @throws BadRequestHttpException if the folder to move, or the destination parent folder, cannot be found
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws VolumeException
     * @throws Throwable
     */
    public function actionMoveFolder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $folderBeingMovedId = $this->request->getRequiredBodyParam('folderId');
        $newParentFolderId = $this->request->getRequiredBodyParam('parentId');
        $force = $this->request->getBodyParam('force', false);
        $merge = !$force ? $this->request->getBodyParam('merge', false) : false;

        $folders = Mux::$plugin->folders;
        $folderToMove = $folders->getFolderById($folderBeingMovedId);
        $destinationFolder = $folders->getFolderById($newParentFolderId);

        if ($folderToMove === null) {
            throw new BadRequestHttpException('The folder you are trying to move does not exist');
        }

        if ($destinationFolder === null) {
            throw new BadRequestHttpException('The destination folder does not exist');
        }

        // Check if it's possible to delete objects in the source volume, create folders
        // in the target volume, and save assets in the target volume.
        // $this->requireVolumePermissionByFolder('deleteAssets', $folderToMove);
        // $this->requireVolumePermissionByFolder('createFolders', $destinationFolder);
        // $this->requireVolumePermissionByFolder('saveAssets', $destinationFolder);

        $targetVolume = $destinationFolder->getVolume();

        $existingFolder = $folders->findFolder([
            'parentId' => $newParentFolderId,
            'name' => $folderToMove->name,
        ]);

        // If there's a conflict and `force`/`merge` flags weren't passed in, then STOP RIGHT THERE!
        if ($existingFolder && !$force && !$merge) {
            // Throw a prompt
            return $this->asJson([
                'conflict' => Craft::t('app', 'Folder “{folder}” already exists at target location', ['folder' => $folderToMove->name]),
                'folderId' => $folderBeingMovedId,
                'parentId' => $newParentFolderId,
            ]);
        }

        // Handle conflicts
        if ($existingFolder) {
            if ($force) {
                // Delete the existing folder
                try {
                    $folders->deleteFoldersByIds($existingFolder->id);
                } catch (VolumeException $exception) {
                    Craft::$app->getErrorHandler()->logException($exception);
                    return $this->asFailure(Craft::t('app', 'Directories cannot be deleted while moving assets.'));
                }
            } else {
                // Merge - we'll move assets from the source folder into the existing folder
                // This is more complex and would require additional logic
                $sourceTree = $folders->getAllDescendantFolders($folderToMove);
                $allSourceFolderIds = array_keys($sourceTree);
                $allSourceFolderIds[] = $folderBeingMovedId;
                /** @var MuxAsset[] $foundAssets */
                $foundAssets = MuxAsset::find()
                    ->folderId($allSourceFolderIds)
                    ->all();

                $folderIdChanges = Folders::moveFolderStructure($folderToMove, $destinationFolder);
                $fileTransferList = Folders::fileTransferList($foundAssets, $folderIdChanges);

                return $this->asSuccess(data: [ 
                    'transferList' => $fileTransferList,
                    'newFolderUid' => $folderToMove->uid,
                    'newFolderId' => $folderToMove->id,
                ]);
            }
        }

        // Get all assets that will be affected
        $sourceTree = $folders->getAllDescendantFolders($folderToMove);
        $allSourceFolderIds = array_keys($sourceTree);
        $allSourceFolderIds[] = $folderBeingMovedId;
        /** @var MuxAsset[] $foundAssets */
        $foundAssets = MuxAsset::find()
            ->folderId($allSourceFolderIds)
            ->all();

        // Move the folder structure (this updates the folders in place)
        $folderIdChanges = Folders::moveFolderStructure($folderToMove, $destinationFolder);

        // Create the file transfer list for assets
        $fileTransferList = Folders::fileTransferList($foundAssets, $folderIdChanges);

        return $this->asSuccess(data: [
            'transferList' => $fileTransferList,
            'newFolderUid' => $folderToMove->uid,
            'newFolderId' => $folderToMove->id,
        ]);
    }

} 