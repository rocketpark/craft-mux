<?php

namespace rocketpark\mux\records;

use Craft;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Assets record
 *
 * @property int $id ID
 * @property int $assetId Asset ID
 * @property string $assetData Asset data
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property string $uid Uid
 */
class Assets extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%mux_assets}}';
    }
}
