<?php

namespace rocketpark\mux\migrations;

use Craft;
use craft\db\Migration;

/**
 * m250417_194557_newAssetModelParams migration.
 */
class m250417_194557_newAssetModelParams extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $table = $this->db->schema->getTableSchema('{{%mux_assets}}');
        // add new columns to mux_assets table
        if (!isset($table->columns['ingest_type'])) {
            $this->addColumn('{{%mux_assets}}', 'ingest_type', $this->string()->null()->after('test'));
        }

        if(!isset($table->columns['meta'])) {
            $this->addColumn('{{%mux_assets}}', 'meta', $this->json()->null()->after('ingest_type'));
        }

        if (isset($table->columns['meta']) && $table->columns['meta']->type !== 'json') {
            $this->alterColumn('{{%mux_assets}}', 'meta', $this->json()->null());
        }

        // Copy existing asset_id and passthrough to meta JSON field, include blank creator_id
        $rows = $this->db->createCommand('SELECT id, asset_id, passthrough FROM {{%mux_assets}}')->queryAll();

        $rows = $this->db->createCommand('SELECT id, meta, asset_id, passthrough FROM {{%mux_assets}}')->queryAll();

        foreach ($rows as $row) {
            $meta = [];

            // If meta exists and is a string, attempt to decode it
            if (!empty($row['meta'])) {
                $decoded = json_decode($row['meta'], true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }

            // Ensure meta has the correct structure
            $meta = [
                'title' => !empty($meta['title']) ? $meta['title'] : ($row['passthrough'] ?? ''),
                'external_id' => !empty($meta['external_id']) ? $meta['external_id'] : ($row['id'] ?? ''),
                'creator_id' => !empty($meta['creator_id']) ? $meta['creator_id'] : ''
            ];

            // Update the row with clean JSON
            $this->update(
                '{{%mux_assets}}',
                ['meta' => $meta], // Pass array directly for Craft to handle JSON encoding
                ['id' => $row['id']]
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m250417_194557_newAssetModelParams cannot be reverted.\n";
        return false;
    }
}
