<?php
namespace rocketpark\mux\variables;

use rocketpark\mux\elements\MuxAsset;
use rocketpark\mux\elements\db\MuxAssetQuery;
use Craft;
use yii\base\Behavior;


class MuxAssetBehavior extends Behavior
{
    public function mux(array $criteria = []): MuxAssetQuery
    {
        // Create a query via your element type, and apply any passed criteria:
        return Craft::configure(MuxAsset::find(), $criteria);
    }
}