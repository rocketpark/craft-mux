<?php

namespace rocketpark\mux\records;

use Craft;
use craft\db\ActiveRecord;

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
