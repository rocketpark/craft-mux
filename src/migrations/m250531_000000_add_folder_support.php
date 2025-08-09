<?php

namespace rocketpark\mux\migrations;

use Craft;
use craft\db\Migration;

/**
 * Add virtual folder support to Mux Assets
 */
class m250531_000000_add_folder_support extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {

        if (!$this->db->tableExists('{{%mux_volumes}}')) {
            $this->createTable('{{%mux_volumes}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string()->notNull(),
                'sortOrder' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);
        }

        // Create mux_folders table (simplified - no path needed)
        if (!$this->db->tableExists('{{%mux_volumefolders}}')) {
            $this->createTable('{{%mux_volumefolders}}', [
                'id' => $this->primaryKey(),
                'parentId' => $this->integer()->null(),
                'volumeId' => $this->integer()->notNull(),
                'name' => $this->string()->notNull(),
                'path' => $this->string()->null(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);

            // Add foreign key for parent folders
            $this->addForeignKey(
                'fk_mux_volumefolders_parent',
                '{{%mux_volumefolders}}',
                'parentId',
                '{{%mux_volumefolders}}',
                'id',
                'CASCADE',
                null
            );

            // Add foreign key for volume relationship so that folders are deleted if the volume is deleted
            $this->addForeignKey(
                'fk_mux_volumefolders_volume',
                '{{%mux_volumefolders}}',
                'volumeId',
                '{{%mux_volumes}}',
                'id',
                'CASCADE',
                null
            );
        }

        // Add volumeId column to mux_assets table
        if (!$this->db->columnExists('{{%mux_assets}}', 'volumeId')) {
            $this->addColumn('{{%mux_assets}}', 'volumeId', $this->integer()->null()->after('asset_id'));

            // Add foreign key for volume relationship
            $this->addForeignKey(
                'fk_mux_assets_volume',
                '{{%mux_assets}}',
                'volumeId',
                '{{%mux_volumes}}',
                'id',
                'SET NULL',
                null
            );
        }

        // Add folderId column to mux_assets table
        if (!$this->db->columnExists('{{%mux_assets}}', 'folderId')) {
            $this->addColumn('{{%mux_assets}}', 'folderId', $this->integer()->null()->after('volumeId'));
            
            // Add foreign key for folder relationship
            $this->addForeignKey(
                'fk_mux_assets_folder',
                '{{%mux_assets}}',
                'folderId',
                '{{%mux_volumefolders}}',
                'id',
                'SET NULL',
                null
            );
        }

        // Create default "Videos" volume
        $defaultVolumeId = $this->createDefaultVolume();
        $defaultFolderId = $this->createDefaultFolder();

        // Assign the default volume and folder to all existing MuxAsset elements that don't have a volumeId or folderId
        $this->assignDefaultVolumeToAssets($defaultVolumeId, $defaultFolderId);

        return true;
    }

    /**
     * Creates a default "Videos" volume
     * 
     * @return int The ID of the created volume
     */
    private function createDefaultVolume(): int
    {
        // Check if a "Videos" volume already exists
        $existingVolume = $this->db->createCommand('SELECT id FROM {{%mux_volumes}} WHERE name = :name', [
            ':name' => 'Videos'
        ])->queryOne();

        if ($existingVolume) {
            return $existingVolume['id'];
        }

        // Create the default "Videos" volume
        $this->insert('{{%mux_volumes}}', [
            'name' => 'Videos',
            'handle' => 'videos',
            'sortOrder' => 1,
            'dateCreated' => new \yii\db\Expression('NOW()'),
            'dateUpdated' => new \yii\db\Expression('NOW()'),
            'uid' => \craft\helpers\StringHelper::UUID()
        ]);

        return $this->db->getLastInsertID();
    }

    /**
     * Creates a default "Videos" folder
     * 
     * @return int The ID of the created folder
     */
    private function createDefaultFolder(): int
    {   
        $defaultVolumeId = $this->createDefaultVolume();

        // Check if a "Videos" folder already exists
        $existingFolder = $this->db->createCommand('SELECT id FROM {{%mux_volumefolders}} WHERE name = :name', [
            ':name' => 'Videos'
        ])->queryOne();

        if ($existingFolder) {
            return $existingFolder['id'];
        }

        // Create the default "Videos" folder
        $this->insert('{{%mux_volumefolders}}', [
            'volumeId' => $defaultVolumeId,
            'name' => 'Videos',
            'path' => '',
            'dateCreated' => new \yii\db\Expression('NOW()'),
            'dateUpdated' => new \yii\db\Expression('NOW()'),
            'uid' => \craft\helpers\StringHelper::UUID()
        ]);

        return $this->db->getLastInsertID();
    }

    /**
     * Assigns the default volume to all existing MuxAsset elements that don't have a volumeId
     * 
     * @param int $defaultVolumeId The ID of the default volume
     */
    private function assignDefaultVolumeToAssets(int $defaultVolumeId, int $defaultFolderId): void
    {
        // Update all MuxAsset elements that don't have a volumeId
        $this->update('{{%mux_assets}}',
            ['volumeId' => $defaultVolumeId], 
            ['volumeId' => null]
        );

        // Update all MuxAsset elements that don't have a folderId
        $this->update('{{%mux_assets}}',
            ['folderId' => $defaultFolderId], 
            ['folderId' => null]
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // Remove foreign keys and columns from mux_assets, and drop folder/volume tables
        if ($this->db->columnExists('{{%mux_assets}}', 'folderId')) {
            // Drop foreign keys if they exist
            $table = '{{%mux_assets}}';
            $fkNames = [
                'fk_mux_assets_folder',
                'fk_mux_assets_volume',
            ];
            foreach ($fkNames as $fk) {
                if ($this->db->getTableSchema($table, true)?->getColumn('folderId') !== null) {
                    try {
                        $this->dropForeignKey($fk, $table);
                    } catch (\Throwable $e) {
                        // Ignore if FK doesn't exist
                    }
                }
            }
            $this->dropColumn($table, 'folderId');
            $this->dropColumn($table, 'volumeId');
        }

        // Drop mux_folders table and its FKs if they exist
        if ($this->db->tableExists('{{%mux_folders}}')) {
            $table = '{{%mux_folders}}';
            $fkNames = [
                'fk_mux_folders_parent',
                'fk_mux_folders_volume',
            ];
            foreach ($fkNames as $fk) {
                try {
                    $this->dropForeignKey($fk, $table);
                } catch (\Throwable $e) {
                    // Ignore if FK doesn't exist
                }
            }
            $this->dropTableIfExists($table);
        }

        // Drop mux_folders table
        $this->dropTableIfExists('{{%mux_volumefolders}}');
         // Drop mux_volumes table
         $this->dropTableIfExists('{{%mux_volumes}}');

        return true;
    }
} 