<?php

namespace rocketpark\mux\records;

use Craft;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;
use rocketpark\mux\elements\MuxAsset;

/**
 * MuxFolder record - for virtual folder organization
 *
 * @property int $id ID
 * @property int|null $parentId Parent folder ID
 * @property string $name Folder name
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property string $uid Uid
 */
class MuxFolder extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%mux_volumefolders}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'unique', 'targetAttribute' => ['name', 'parentId']],
        ];
    }

    /**
     * Returns the folder's parent.
     */
    public function getParent(): ActiveQueryInterface
    {
        return $this->hasOne(self::class, ['id' => 'parentId']);
    }

    /**
     * Returns the folder's children.
     */
    public function getChildren(): ActiveQueryInterface
    {
        return $this->hasMany(self::class, ['parentId' => 'id']);
    }

    /**
     * Returns the folder's assets.
     */
    public function getAssets(): ActiveQueryInterface
    {
        return $this->hasMany(MuxAsset::class, ['folderId' => 'id']);
    }

    /**
     * Returns the folder's volume.
     */
    public function getVolume(): ActiveQueryInterface
    {
        return $this->hasOne(MuxVolume::class, ['id' => 'volumeId']);
    }
} 