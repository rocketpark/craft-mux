<?php

namespace rocketpark\mux\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\App;
use craft\migrations\BaseContentRefactorMigration;
use rocketpark\mux\elements\MuxAsset;

/**
 * m240426_041638_craft5 migration.
 */
class m240426_041638_craft5 extends BaseContentRefactorMigration
{
    /**
     * @inheritdoc
     */
    protected bool $preserveOldData = true;

    public function safeUp(): bool
    {
        App::maxPowerCaptain();

        $this->updateElements(
            (new Query())->from(MuxAsset::tableName()),
            \Craft::$app->getFields()->getLayoutByType(MuxAsset::class),
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240426_041638_craft5 cannot be reverted.\n";
        return false;
    }
}
