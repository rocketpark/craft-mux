<?php

namespace rocketpark\mux\models;

use Craft;
use craft\base\Model;
use craft\base\Chippable;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use rocketpark\mux\Mux;
use rocketpark\mux\records\MuxVolume as MuxVolumeRecord;

/**
 * MuxVolume model - for virtual volume organization
 */
class MuxVolume extends Model implements Chippable
{
    public ?int $id = null;
    public string $name = '';
    public string $handle = '';
    public int $sortOrder = 0;
    public ?string $uid = null;

    // Computed properties
    public array $folders = [];
    public bool $hasFolders = false;

    /**
     * @inheritdoc
     */
    public static function get(string|int $id): ?static
    {
        /** @phpstan-ignore-next-line */
        return Mux::$plugin->volumes->getVolumeById($id);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->id;
    }

     /**
     * @inheritdoc
     */
    public function getUiLabel(): string
    {
        return Craft::t('site', $this->name);
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string
    {
        if (!$this->id || !Craft::$app->getUser()->getIsAdmin()) {
            return null;
        }
        return UrlHelper::cpUrl("settings/assets/volumes/$this->id");
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
            'url' => Craft::t('app', 'URL')
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        
        // Required fields
        $rules[] = [['name', 'handle'], 'required'];
        
        // String validation
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        
        // Unique validation for name and handle separately
        $rules[] = [['name'], UniqueValidator::class, 'targetClass' => MuxVolumeRecord::class];
        $rules[] = [['handle'], UniqueValidator::class, 'targetClass' => MuxVolumeRecord::class];
        
        // Handle validation with reserved words
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => [
                'dateCreated',
                'dateUpdated',
                'edit',
                'id',
                'temp',
                'title',
                'uid',
            ],
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function getFolders(): array
    {
        return Mux::$plugin->folders->getFoldersByVolumeId($this->id);
    }

    public function getHasFolders(): bool
    {
        return $this->getFolders() !== [];
    }
}