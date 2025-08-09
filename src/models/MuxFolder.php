<?php
/**
 * @link https://rocketpark.com
 * @copyright Copyright (c) Rocket Park, Inc.
 * @license https://rocketpark.com/license/
 */
namespace rocketpark\mux\models;

use Craft;
use craft\base\Model;
use craft\helpers\Html;
use DateTime;
use rocketpark\mux\Mux;
use rocketpark\mux\records\MuxFolder as MuxFolderRecord;
use yii\base\InvalidConfigException;

/**
 * MuxFolder model - for virtual folder organization
 */
class MuxFolder extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var int|string|null Parent ID
     */
    public string|int|null $parentId = null;

    /**
     * @var int|null Volume ID
     */
    public ?int $volumeId = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Path
     */
    public ?string $path = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;

     /**
     * @var MuxFolder[]|null
     */
    private ?array $_children = null;

    /**
     * @var bool
     */
    private bool $_hasChildren;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'parentId', 'volumeId'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * Use the folder name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->name ?: static::class;
    }

    /**
     * Returns the volume this folder belongs to.
     *
     * @return Volume
     * @throws InvalidConfigException if [[volumeId]] is invalid
     */
    public function getVolume(): ?MuxVolume
    {
        if (!isset($this->volumeId)) {
            return null;
        }

        if (($volume = Mux::$plugin->volumes->getVolumeById($this->volumeId)) === null) {
            throw new InvalidConfigException('Invalid volume ID: ' . $this->volumeId);
        }

        return $volume;
    }

    /**
     * Returns the folder's display name
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }

    /**
     * Returns the folder's breadcrumb path
     */
    public function getPath(): array
    {
        $path = [$this];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent);
            $parent = $parent->parent;
        }
        
        return $path;
    }

    /**
     * Returns info about the folder for an element index’s source path configuration.
     *
     * @return array|null
     */
    public function getSourcePathInfo(): ?array
    {
        if (!$this->volumeId) {
            return null;
        }

        $volume = $this->getVolume();
        $userSession = Craft::$app->getUser();
        $canView = true; //$userSession->checkPermission("viewAssets:$volume->uid");
        $canCreate = true; //$userSession->checkPermission("createFolders:$volume->uid");
        $canDelete = true; //$userSession->checkPermission("deletePeerAssets:$volume->uid");
        $canMove = true; //$canDelete && $userSession->checkPermission("savePeerAssets:$volume->uid");

        $info = [
            'uri' => sprintf('mux/assets/%s%s', $volume->handle, $this->path ? sprintf('/%s', trim($this->path, '/')) : ''),
            'folderId' => (int)$this->id,
            'hasChildren' => $this->getHasChildren(),
            'canView' => $canView,
            'canCreate' => $canCreate,
            'canMoveSubItems' => $canMove,
        ];

        // Is this a root folder?
        if (!$this->parentId) {
            $info += [
                'key' => "volume:$volume->uid",
                'icon' => 'home',
                'label' => Craft::t('app', '{volume} root', [
                    'volume' => Html::encode(Craft::t('site', $volume->name)),
                ]),
                'handle' => $volume->handle,
            ];
        } else {
            $canRename = true; // $canCreate && $userSession->checkPermission("deleteAssets:$volume->uid");

            $info += [
                'key' => "folder:$this->uid",
                'label' => Html::encode($this->name),
                'criteria' => [
                    'folderId' => $this->id,
                ],
                'canRename' => $canRename,
                'canMove' => $canMove,
                'canDelete' => $canDelete,
            ];
        }

        return $info;
    }

    /**
     * Returns whether the folder has any child folders.
     *
     * @return bool
     */
    public function getHasChildren(): bool
    {
        if (isset($this->_children)) {
            return !empty($this->_children);
        }

        if (!isset($this->_hasChildren)) {
            $this->_hasChildren = Mux::$plugin->folders->foldersExist(['parentId' => $this->id]);
        }

        return $this->_hasChildren;
    }

    /**
     * Sets whether the folder has any child folders.
     *
     * @param bool $value
     */
    public function setHasChildren(bool $value)
    {
        $this->_hasChildren = $value;
    }

    /**
     * Set the child folders.
     *
     * @param MuxFolder[] $children
     */
    public function setChildren(array $children): void
    {
        $this->_children = $children;
    }

    /**
     * Get Children
     * @return array 
     */
    public function getChildren(): array
    {
        return $this->_children ?? ($this->_children = Mux::$plugin->folders->findFolders(['parentId' => $this->id]));
    }    
    
    /**
     * Returns the folder's parent.
     */
    public function getParent(): ?MuxFolder
    {
        if (!$this->parentId) {
            return null;
        }
        
        return Mux::$plugin->folders->getFolderById($this->parentId);
    }

    /**
     * Add a child folder manually.
     *
     * @param MuxFolder $folder
     */
    public function addChild(MuxFolder $folder): void
    {
        if (!isset($this->_children)) {
            $this->_children = [];
        }

        $this->_children[] = $folder;
    }
} 