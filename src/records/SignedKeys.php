<?php

namespace rocketpark\mux\records;

use Craft;
use craft\db\ActiveRecord;
use rocketpark\mux\models\SignedKey;

/**
 * Signed Keys record
 */
class SignedKeys extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%mux_signed_keys}}';
    }
}
