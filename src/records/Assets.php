<?php

namespace rocketpark\mux\records;

use Craft;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;
use MuxPhp\Models\Asset;

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

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getData(): ActiveQueryInterface
    {
        return $this->hasOne(Asset::class, ['asset_id' => 'id']);
    }
}
