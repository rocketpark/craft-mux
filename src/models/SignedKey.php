<?php

namespace rocketpark\mux\models;

use Craft;
use craft\base\Model;

/**
 * Mux Signed Key model
 */
class SignedKey extends Model
{
    public $id;
    public $key_id;
    public $created_at;
    public $private_key;

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            ['key_id', 'required'],
            ['private_key', 'required'],
            ['created_at', 'required']
        ]);
    }
}
