<?php

namespace rocketpark\mux\migrations;

use Craft;
use craft\db\Migration;

/**
 * m230905_145824_addSignedKeysTable migration.
 */
class m230905_145824_addSignedKeysTable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
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
        echo "m230905_145824_addSignedKeysTable cannot be reverted.\n";

        return true;
    }
}
