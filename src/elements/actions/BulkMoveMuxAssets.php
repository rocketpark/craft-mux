<?php

namespace rocketpark\mux\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use rocketpark\mux\elements\MuxAsset;
use rocketpark\mux\Mux;

/**
 * Bulk Move Mux Assets action
 */
class BulkMoveMuxAssets extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('mux', 'Move to folder');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        $folders = Mux::$plugin->folders->getAllFolders();
        
        $folderOptions = [
            ['label' => Craft::t('mux', 'Root'), 'value' => '']
        ];
        
        foreach ($folders as $folder) {
            $folderOptions[] = [
                'label' => $folder->name,
                'value' => $folder->id,
            ];
        }

        return Craft::$app->getView()->renderTemplate('mux/_includes/bulk-move-assets-action', [
            'folderOptions' => $folderOptions,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $folderId = Craft::$app->getRequest()->getRequiredBodyParam('folderId');
        $folderId = $folderId ? (int)$folderId : null;

        $elementsService = Craft::$app->getElements();
        $moved = 0;

        foreach ($query->all() as $asset) {
            /** @var MuxAsset $asset */
            $asset->folderId = $folderId;
            
            if ($elementsService->saveElement($asset)) {
                $moved++;
            }
        }

        if ($moved === 0) {
            $this->setMessage(Craft::t('mux', 'No assets moved.'));
            return false;
        }

        if ($moved === 1) {
            $this->setMessage(Craft::t('mux', 'Asset moved.'));
        } else {
            $this->setMessage(Craft::t('mux', '{num} assets moved.', ['num' => $moved]));
        }

        return true;
    }
} 