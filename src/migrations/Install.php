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
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);

            //$this->db->createCommand('ALTER TABLE {{%mux_assets}} MODIFY COLUMN eid INT(11) UNSIGNED NOT NULL AUTO_INCREMENT')->execute();

            // give it a foreign key to the elements table
            $this->addForeignKey(
                null,
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

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%mux_assets}}');
        $this->dropTableIfExists('{{%mux_signed_keys}}');

        return true;
    }
}
