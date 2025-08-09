<?php

namespace rocketpark\mux\records;

use Craft;
use craft\db\ActiveRecord;

/**
 * MuxVolume record - for virtual volume organization
 *
 * @property int $id ID
 * @property int|null $parentId Parent folder ID
 * @property string $name Folder name
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property string $uid Uid
 */
class MuxVolume extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%mux_volumes}}';
    }


} 