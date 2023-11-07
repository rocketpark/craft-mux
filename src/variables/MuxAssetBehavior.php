<?php
namespace rocketpark\mux\variables;

use rocketpark\mux\elements\MuxAsset;
use rocketpark\mux\elements\db\MuxAssetQuery;
use Craft;
use rocketpark\mux\models\SignedKey;
use rocketpark\mux\records\SignedKeys;
use yii\base\Behavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class MuxAssetBehavior extends Behavior
{
    public function mux(array $criteria = []): MuxAssetQuery
    {
        // Create a query via your element type, and apply any passed criteria:
        return Craft::configure(MuxAsset::find(), $criteria);
    }

    public function signedKeys(): ActiveQuery
    {
        return SignedKeys::find();
    }

}