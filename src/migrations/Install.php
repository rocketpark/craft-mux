<?php

namespace rocketpark\mux\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%mux_assets}}')) {
            // create the mux_assets table
            $this->createTable('{{%mux_assets}}', [
                'id' => $this->primaryKey(),
                'asset_id' => $this->char(255)->notNull(),
                'folderId' => $this->integer()->null(),
                'volumeId' => $this->integer()->null(),
                'created_at' => $this->string()->notNull(),
                'asset_status' => $this->string()->null(),
                'duration' => $this->double()->null(),
                'max_stored_resolution' => $this->string()->null(),
                'max_stored_frame_rate' => $this->double()->null(),
                'resolution_tier' => $this->string()->null(),
                'max_resolution_tier' => $this->string()->null(),
                'encoding_tier' => $this->string()->null(),
                'aspect_ratio' => $this->string()->null(),
                'playback_ids'=> $this->json()->null(),
                'tracks' => $this->json()->null(),
                'errors' => $this->string()->null(),
                'per_title_encode' => $this->boolean()->null(),
                'upload_id' => $this->string()->null(),
                'is_live' => $this->boolean()->null(),
                'passthrough' => $this->string()->null(),
                'live_stream_id' => $this->string()->null(),
                'master'=> $this->json()->null(),
                'master_access' => $this->string()->null(),
                'mp4_support' => $this->string()->null(),
                'source_asset_id' => $this->string()->null(),
                'normalize_audio' => $this->boolean()->null(),
                'static_renditions' => $this->json()->null(),
                'recording_times' => $this->json()->null(),
                'non_standard_input_reasons' => $this->json()->null(),
                'test' => $this->boolean()->null(),
                'ingest_type' => $this->string()->null(),
                'meta' => $this->json()->null(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);

            //$this->db->createCommand('ALTER TABLE {{%mux_assets}} MODIFY COLUMN eid INT(11) UNSIGNED NOT NULL AUTO_INCREMENT')->execute();

            // give it a foreign key to the elements table
            $this->addForeignKey(
                'fk_mux_assets_element',
                '{{%mux_assets}}',
                'id',
                '{{%elements}}',
                'id',
                'CASCADE',
                null
            );

        }

        if (!$this->db->tableExists('{{%mux_signed_keys}}')) {
            // create the mux_singed_keys table
            $this->createTable('{{%mux_signed_keys}}', [
                'id' => $this->primaryKey(),
                'key_id' => $this->char(255)->notNull(),
                'private_key' => $this->text()->notNull(),
                'created_at' => $this->string()->notNull()
            ]);
        }

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

        if (!$this->db->tableExists('{{%mux_volumefolders}}')) {
            $this->createTable('{{%mux_volumefolders}}', [
                'id' => $this->primaryKey(),
                'parentId' => $this->integer()->null(),
                'name' => $this->string()->notNull(),
                'path' => $this->string()->null(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);

            // Add foreign key for volume relationship
            $this->addForeignKey(
                'fk_mux_volumefolders_volume',
                '{{%mux_volumefolders}}',
                'volumeId',
                '{{%mux_volumes}}',
                'id',
                'CASCADE',
                null
            );

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
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // Drop foreign keys before dropping tables to avoid errors
        $tableFkMap = [
            '{{%mux_assets}}' => [
                'fk_mux_assets_element',
                'fk_mux_assets_volume',
                'fk_mux_assets_folder',
            ],
            '{{%mux_volumefolders}}' => [
                'fk_mux_volumefolders_volume',
                'fk_mux_volumefolders_parent',
            ],
        ];

        foreach ($tableFkMap as $table => $fks) {
            foreach ($fks as $fk) {
                try {
                    $this->dropForeignKey($fk, $table);
                } catch (\Throwable $e) {
                    // Ignore if FK doesn't exist
                }
            }
        }

        // Now drop tables (in order: children first)
        $this->dropTableIfExists('{{%mux_assets}}');
        $this->dropTableIfExists('{{%mux_signed_keys}}');
        $this->dropTableIfExists('{{%mux_volumefolders}}');
        $this->dropTableIfExists('{{%mux_volumes}}');

        return true;
    }
}
