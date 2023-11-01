<?php

namespace rocketpark\mux\migrations;

use Craft;
use craft\db\Migration;

/**
 * m231101_010205_newAssetModelParams migration.
 */
class m231101_010205_newAssetModelParams extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $table = $this->db->schema->getTableSchema('{{%mux_assets}}');
        // add new columns to mux_assets table
        if (!isset($table->columns['resolution_tier'])) {
            $this->addColumn('{{%mux_assets}}', 'resolution_tier', $this->string()->null()->after('max_stored_frame_rate'));
        }

        if(!isset($table->columns['max_resolution_tier'])) {
            $this->addColumn('{{%mux_assets}}', 'max_resolution_tier', $this->string()->null()->after('max_stored_frame_rate'));
        }

        if(!isset($table->columns['encoding_tier'])) {
            $this->addColumn('{{%mux_assets}}', 'encoding_tier', $this->string()->null()->after('max_stored_frame_rate'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m231101_010205_newAssetModelParams cannot be reverted.\n";
        return false;
    }
}
