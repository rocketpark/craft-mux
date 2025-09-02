<?php

namespace rocketpark\mux\elements;

use Craft;
use craft\base\Element;
use craft\controllers\ElementIndexesController;
use craft\controllers\ElementSelectorModalsController;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\User;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Restore;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\search\SearchQuery;
use craft\search\SearchQueryTerm;
use craft\search\SearchQueryTermGroup;
use craft\web\CpScreenResponseBehavior;

use Exception as GlobalException;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\db\MuxAssetQuery;
use rocketpark\mux\elements\actions\SyncAssets;
use rocketpark\mux\elements\actions\MoveMuxAssets;
use rocketpark\mux\fieldlayoutelements\MuxAssetFieldContentTab;
use rocketpark\mux\fieldlayoutelements\MuxAssetFieldTracksTab;
use rocketpark\mux\records\SignedKeys;
use rocketpark\mux\models\SignedKey;
use rocketpark\mux\models\MuxFolder;
use rocketpark\mux\models\MuxVolume;
use rocketpark\mux\helpers\JWT as JWTHelper;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\InvalidArgumentException;
use yii\behaviors\AttributeTypecastBehavior;
use yii\db\Exception;
use yii\web\Response;

use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use Illuminate\Support\Collection;
use rocketpark\mux\fieldlayoutelements\MuxAssetFieldDataTab;

/**
 * Mux Asset element type
 *
 * @property-read string $tableName Database table name
 * @property-read string $displayName Element display name
 * @property-read string $lowerDisplayName Lowercase display name
 * @property-read string $pluralDisplayName Plural display name
 * @property-read string $pluralLowerDisplayName Plural lowercase display name
 * @property-read string|null $refHandle Reference handle
 */
class MuxAsset extends Element
{
    public const TABLE = '{{%mux_assets}}';
    public const TABLE_STD = 'mux_assets';

    // Scenario constants
    // const SCENARIO_WEBHOOK_UPDATE = 'webhook-update';
    // const SCENARIO_USER_UPDATE = 'user-update';
    // const SCENARIO_BULK_IMPORT = 'bulk-import';

    // /**
    //  * @inheritdoc
    //  */
    // public function scenarios()
    // {
    //     $scenarios = parent::scenarios();
    //     // Get all available attributes for this model
    //     $allAttributes = array_keys($this->getAttributes());
    //     $scenarios[self::SCENARIO_WEBHOOK_UPDATE] = $allAttributes;
    //     $scenarios[self::SCENARIO_USER_UPDATE] = $allAttributes;
    //     $scenarios[self::SCENARIO_BULK_IMPORT] = $allAttributes;
    //     return $scenarios;
    // }


    public static function tableName(): string
    {
        return self::TABLE;
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('mux', 'MuxAsset');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('mux', 'MuxAsset');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('mux', 'Assets');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('mux', 'MUX assets');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'muxAsset';
    }

    /**
     * @inheritdoc
     */
    public static function gqlTypeNameByContext(mixed $context): string
    {
        return 'MuxAsset';
    }

    /**
     * @inheritdoc
     */
    public static function trackChanges(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasThumbs(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return false;
    }


    /**
     * @inheritdoc
     */
    public static function find(): ElementQueryInterface
    {
       return Craft::createObject(MuxAssetQuery::class, [static::class]);
    }
    

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return self::_muxAssetAttributes();
    }

    private static function _muxAssetAttributes(): array 
    {
        return [
            'id',
            'asset_id',
            'asset_status',
            'duration',
            'is_live',
            'passthrough',
            'test'
        ];
    }

    public ?string $asset_id = '';
    public ?int $folderId = null;
    public ?int $volumeId = null;
    public ?string $created_at = '';
    public ?string $asset_status = '';
    public ?string $duration = '';
    public ?string $max_stored_resolution = '';
    public ?string $max_stored_frame_rate = '';
    public ?string $resolution_tier = '';
    public ?string $max_resolution_tier = '';
    public ?string $encoding_tier = '';
    public ?string $aspect_ratio = '';
    public ?array  $playback_ids = [];
    public ?array  $tracks = [];
    public ?string $errors = '';
    public ?bool   $per_title_encode = null;
    public ?string $upload_id = '';
    public ?bool   $is_live = null;
    public ?string $passthrough = '';
    public ?string $live_stream_id = '';
    public ?array  $master = [];
    public ?string $master_access = '';
    public ?string $mp4_support = '';
    public ?string $source_asset_id = '';
    public ?string $normalize_audio = '';
    public ?array $static_renditions = [];
    public ?array  $recording_times = [];
    public ?array $non_standard_input_reasons = [];
    public ?bool $test = null;
    public ?string $ingest_type = '';
    public ?array $meta = [];

    /**
     * @var bool Temporary flag to prevent sync loops during webhook processing
     */
    public bool $isWebhookUpdate = false;

    /**
     * Get Playback Id
     * @return string 
     */
    public function getPlaybackId(): string
    {
        return $this->playback_ids[0]['id'];
    }

    /**
     * Secure Playback
     * @return bool 
     */
    public function securePlayback(): bool
    {
        if (empty($this->playback_ids)) {
            return false;
        }
          
        return $this->playback_ids[0]['policy'] === 'signed';
    }

    /**
     * Get Secure Playback
     * @return bool 
     */
    public function getSecurePlayback(): bool
    {
        return $this->securePlayback();
    }

    /**
     * Secure Playback Tokens
     * @return array 
     * @throws InvalidConfigException 
     * @throws NotSupportedException 
     * @throws Exception 
     * @throws InvalidArgumentException 
     * @throws GlobalException 
     */
    public function securePlaybackTokens(): array
    {
        $query = SignedKeys::find()->all();
        return array_map(fn($record) => new SignedKey($record->getAttributes()), $query);
    }

    /**
     * Signed Keys
     * @return array 
     * @throws InvalidConfigException 
     * @throws NotSupportedException 
     * @throws Exception 
     * @throws InvalidArgumentException 
     * @throws GlobalException 
     */
    public function signedKeys(): array
    {
        $query = SignedKeys::find()->all();
        return array_map(fn($record) => new SignedKey($record->getAttributes()), $query);
    }

    /**
     * Get Secure Playback JWT
     * @param null|string $tokenKeyId 
     * @param null|string $mediaType v = video, t = thumbnail, g = gif, s = storyboard
     * @param null|array $options ["time" => 10, "width" => 640, "fit_mode" => "smartcrop"]
     * @return mixed 
     * @throws InvalidConfigException 
     * @throws NotSupportedException 
     * @throws Exception 
     * @throws InvalidArgumentException 
     * @throws GlobalException 
     */
    public function getSecurePlaybackJWT(?string $tokenKeyId, ?string $mediaType = 'v', ?array $options = []): ?string
    {
        if (!$this->securePlayback()) {
            return null; 
        }
    
        $tokenData = $this->getTokenData($tokenKeyId);
    
        if (!$tokenData) {
            return null;
        }
    
        return $this->generateJwt($tokenData, $mediaType, $options);
    }

    /**
     * Return Mux Asset Thumbnail URL
     * @param int $width 
     * @param int $height 
     * @param string $fit_mode
     * @param string $format
     * @return string 
     */
    public function thumb(int $width=300, int $height=169, string $fit_mode = 'smartcrop', string $format='webp'): string
    {
        $options = [
            'width' => $width,
            'height' => $height,
            'fit_mode' => $fit_mode,
        ];

        if ($jwt = $this->getSecurePlaybackJWT(null,'t', $options)) {
            $options = [ 'token' => $jwt ];
        }

        return UrlHelper::urlWithParams("https://image.mux.com/{$this->playback_ids[0]['id']}/thumbnail.{$format}", $options);
    }

    /**
     * @inheritdoc
     */
    protected function thumbSvg(): ?string
    {
        if ($this->isFolder) {
            return file_get_contents(Craft::getAlias('@app/elements/thumbs/folder.svg'));
        }
        
        // Return a video icon or your custom icon for Mux assets
        return file_get_contents(Craft::getAlias('@app/elements/thumbs/video.svg'));
    }

    /**
     * @inheritdoc
     */
    protected function thumbAlt(): ?string
    {
        if ($this->isFolder) {
            return null;
        }
        
        return $this->asset_id;
    }

    /**
     * Get Thumb Url
     * @param int $size 
     * @return string|null 
     */
    public function getThumbUrl(int $size, bool $animated = false): ?string
    {
        if ($this->isFolder) {
            return null;
        }

        $options = [
            'width' => $size,
            'height' => $size,
            'fit_mode' => 'smartcrop',
        ];

        $mediaType = $animated ? 'g' : 't';

        if ($jwt = $this->getSecurePlaybackJWT(null, $mediaType , $options)) {
            $options = [ 'token' => $jwt ];
        }

        if($this->playback_ids) {
            if ($animated) {
                return UrlHelper::urlWithParams("https://image.mux.com/{$this->playback_ids[0]['id']}/animated.gif?width=".$size."&fps=5", $options);
            } else {
                return UrlHelper::urlWithParams("https://image.mux.com/{$this->playback_ids[0]['id']}/thumbnail.webp", $options);
            }
        } else {
            return '';
        }
    }

    /**
     * Get Thumb Html
     * @param int $size 
     * @return string|null 
     */
    public function getThumbHtml(int $size): ?string
    {
        if($this->isFolder) {
            return parent::getThumbHtml($size);
        }

        $height = round($size * 9 / 16);
        $url = $this->getThumbUrl($size);
        $animated = $this->getThumbUrl($size, true);
        
        $baseImg = $url ? Html::img($url, ['width' => $size, 'height' => $height, 'class' => 'mux-base-thumb']) : null;
        $animImg = $animated ? Html::img($animated, ['width' => $size, 'height' => $height, 'class' => 'mux-animated-thumb']) : null;
        $div = Html::tag('figure', $baseImg . $animImg, ['class' => 'mux-thumb-figure']);

        return $div;

    }


    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source): array
    {
        $actions = [];
        $actions[] = Restore::class;

        if (Craft::$app->getUser()->checkPermission('mux:assets-create')) {
            $actions[] = SyncAssets::class;

            // Add move action for folder support
            $actions[] = MoveMuxAssets::class;
        }

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function includeSetStatusAction(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = 'index'): array
    {
        $sources = [];

        $volumes = Mux::$plugin->volumes->getAllVolumes();
        $user = Craft::$app->getUser()->getIdentity();
        
        foreach ($volumes as $volume) {
            $folder = Mux::$plugin->folders->getRootFolderByVolumeId($volume->id);

            $sources[] = self::_assembleSourceInfoForFolder($folder, $user);
            // $sources[] = [
            //     'key' => "volume:{$volume->uid}",
            //     'label' => $volume->name,
            //     'hasThumbs' => true,
            //     'criteria' => ['volumeId' => $volume->id],
            //     'defaultSort' => ['dateCreated', 'desc'],
            // ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    public static function getAssetBundle(): string
    {
        return \rocketpark\mux\assetbundles\mux\MuxAssetIndexAsset::class;
    }

    /**
     * @inheritdoc
     */
    public static function findSource(string $sourceKey, ?string $context): ?array
    {
        if (preg_match('/^volume:[\w\-]+(?:\/.+)?\/folder:([\w\-]+)$/', $sourceKey, $match)) {
            $folder = Mux::$plugin->folders->getFolderByUid($match[1]);
            if ($folder) {
                $source = self::_assembleSourceInfoForFolder($folder, Craft::$app->getUser()->getIdentity());
                $source['keyPath'] = $sourceKey;
                return $source;
            }
        }

        return null;
    }

    public static function sourcePath(string $sourceKey, string $stepKey, ?string $context): ?array
    {
        if (!preg_match('/^folder:([\w\-]+)$/', $stepKey, $match)) {
            return null;
        }

        $folder = Mux::$plugin->folders->getFolderByUid($match[1]);

        if (!$folder) {
            return null;
        }

        $path = [$folder->getSourcePathInfo()];

        while ($parent = $folder->getParent()) {
            array_unshift($path, $parent->getSourcePathInfo());
            $folder = $parent;
        }

        return $path;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'id' => ['label' => Craft::t('app', 'ID')],
            'asset_id' => ['label' => Craft::t('mux', 'Mux Asset ID')],
            'asset_status' => ['label' => Craft::t('mux', 'Asset Status')],
            'aspect_ratio' => ['label' => Craft::t('mux', 'Aspect Ratio')],
            'duration' => ['label' => Craft::t('mux', 'Duration')],
            'securePlayback' => ['type' => AttributeTypecastBehavior::TYPE_STRING, 'label' => Craft::t('mux', 'Secure Playback')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getHtmlAttributes(string $context): array
    {
        if ($this->isFolder) {
            $attributes = [
                'data' => [
                    'is-folder' => true,
                    'folder-id' => $this->folderId,
                    'folder-name' => $this->title,
                    'source-path' => Json::encode($this->sourcePath),
                    'has-children' => Mux::$plugin->folders->foldersExist(['parentId' => $this->folderId]),
                ],
            ];

            $attributes['data']['movable'] = true;

            return $attributes;
        }

        

        return parent::getHtmlAttributes($context);
    }

    /**
     * @inheritdoc
     */
    protected function attributeHtml(string $attribute): string
    {
        if ($this->isFolder) {
            return '';
        }

        switch ($attribute) {
            case 'duration':
                $duration = $this->duration;
                $hours = floor($duration / 3600);
                $minutes = floor(($duration % 3600) / 60);
                $seconds = $duration % 60;

                if ($hours > 0) {
                    return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                } elseif ($minutes > 0) {
                    return sprintf("%02d:%02d", $minutes, $seconds);
                } else {
                    return sprintf("%02d", $seconds);
                }
            case 'asset_status':
                $statusLabels = [
                    'ready' => ['teal', 'Ready'],
                    'processing' => ['purple', 'Pending'],
                    'error' => ['red', 'Error'],
                    'deleting' => ['orange', 'Deleting'],
                    'deleted' => ['gray', 'Deleted'],
                ];

                $status = strtolower($this->asset_status);

                if (isset($statusLabels[$status])) {
                    [$color, $text] = $statusLabels[$status];
                    return "<span class=\"status-label $color\"><span class=\"status $color\"></span><span class=\"status-label-text\">$text</span></span>";
                } else {
                    [$color, $text] = $statusLabels['processing'];
                    return "<span class=\"status-label $color\"><span class=\"status $color\"></span><span class=\"status-label-text\">$text</span></span>";
                }
            case 'securePlayback':
                return $this->securePlayback 
                ? '<svg style="fill:currentColor;height:16px; width:auto;" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 448 512"><!--! Font Awesome Pro 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M224 64c44.2 0 80 35.8 80 80v48H144V144c0-44.2 35.8-80 80-80zM80 144v48H64c-35.3 0-64 28.7-64 64V448c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V256c0-35.3-28.7-64-64-64H368V144C368 64.5 303.5 0 224 0S80 64.5 80 144zM256 320v64c0 17.7-14.3 32-32 32s-32-14.3-32-32V320c0-17.7 14.3-32 32-32s32 14.3 32 32z"/></svg>' 
                : '<svg style="fill:currentColor;height:16px; width:auto;" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 576 512"><!--! Font Awesome Pro 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M432 48c-44.2 0-80 35.8-80 80v64h32c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H304V128C304 57.3 361.3 0 432 0s128 57.3 128 128v72c0 13.3-10.7 24-24 24s-24-10.7-24-24V128c0-44.2-35.8-80-80-80zM384 240H64c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H384c8.8 0 16-7.2 16-16V256c0-8.8-7.2-16-16-16zM256 376H192c-13.3 0-24-10.7-24-24s10.7-24 24-24h64c13.3 0 24 10.7 24 24s-10.7 24-24 24z"/></svg>';
        }

        return parent::attributeHtml($attribute);
    }

    /**
     * Get Card Body Html
     * @return string|null 
     */
    public function getCardBodyHtml(): ?string
    {
        $duration = $this->duration;
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;
        $durationOutput = '';

        if ($hours > 0) {
            $durationOutput = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            $durationOutput = sprintf("%02d:%02d", $minutes, $seconds);
        } else {
            $durationOutput = sprintf("%02d", $seconds) . " sec";
        }
        return Html::tag('p', Html::encode($durationOutput), ['class' => 'mux-duration']);
    }

    
    
    /**
     * @inheritdoc
     */
    protected function htmlAttributes(string $context): array
    {
        $attributes = [
            'data' => [
                'mux-asset-id' => $this->asset_id,
                'mux-asset-status' => $this->asset_status,
            ],
        ];

        // TODO: Check if the user has permission to move the asset
        $attributes['data']['movable'] = true;

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'id',
            'asset_id',
            'asset_status',
            'aspect_ratio',
            'duration',
            'dateCreated',
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineAttributes(): array
    {
        return array_merge(parent::defineAttributes(), [
            'asset_id' => AttributeType::String,
            'folderId' => AttributeType::Number,
            'volumeId' => AttributeType::Number,
            'created_at' => AttributeType::String,
            'asset_status' => AttributeType::String,
            'duration' => AttributeType::String,
            'max_stored_resolution' => AttributeType::String,
            'max_stored_frame_rate' => AttributeType::String,
            'resolution_tier' => AttributeType::String,
            'max_resolution_tier' => AttributeType::String,
            'encoding_tier' => AttributeType::String,
            'aspect_ratio' => AttributeType::String,
            'playback_ids' => AttributeType::Mixed,
            'tracks' => AttributeType::Mixed,
            'errors' => AttributeType::String,
            'per_title_encode' => AttributeType::Bool,
            'upload_id' => AttributeType::String,
            'is_live' => AttributeType::Bool,
            'passthrough' => AttributeType::String,
            'live_stream_id' => AttributeType::String,
            'master' => AttributeType::Mixed,
            'master_access' => AttributeType::String,
            'mp4_support' => AttributeType::String,
            'source_asset_id' => AttributeType::String,
            'normalize_audio' => AttributeType::Bool,
            'static_renditions' => AttributeType::Mixed,
            'recording_times' => AttributeType::Mixed,
            'non_standard_input_reasons' => AttributeType::Mixed,
            'test' => AttributeType::Bool,
            'ingest_type' => AttributeType::String,
            'meta' => AttributeType::Mixed,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['meta'], 'default', 'value' => []],
            [['meta'], 'safe'],
        ]);
    }

    /**
     * @inheritdoc
     */
    protected static function indexElements(ElementQueryInterface $elementQuery, ?string $sourceKey): array
    {
        $assets = [];
        
        // Include folders in the results?
        /** @var MuxAssetQuery $elementQuery */
        if (self::_includeFoldersInIndexElements($elementQuery, $sourceKey, $queryFolder)) {
            $foldersService = Mux::$plugin->folders;
            $folderQuery = self::_createFolderQueryForIndex($elementQuery, $queryFolder);
            $totalFolders = $folderQuery->count();

            if ($totalFolders > $elementQuery->offset) {
                $source = ElementHelper::findSource(static::class, $sourceKey);
                if (isset($source['criteria']['folderId'])) {
                    $baseFolder = $foldersService->getFolderById($source['criteria']['folderId']);
                } else {
                    $baseFolder = $foldersService->getRootFolderByVolumeId($queryFolder->getVolume()->id);
                }
                $baseSourcePathStep = $baseFolder->getSourcePathInfo();

                $folderQuery
                    ->offset($elementQuery->offset)
                    ->limit($elementQuery->limit);

                // Convert database arrays to MuxFolder objects
                $folders = array_map(fn(array $result) => $foldersService->_createFolderFromArray($result), $folderQuery->all());

                $foldersByPath = ArrayHelper::index($folders, fn($folder) => rtrim($folder->path, '/'));

                foreach ($folders as $folder) {
                    $sourcePath = [$baseSourcePathStep];
                    $path = rtrim($baseFolder->path ?? '', '/');
                    $pathSegs = ArrayHelper::filterEmptyStringsFromArray(explode('/', StringHelper::removeLeft($folder->path, $baseFolder->path ?? '')));
                    foreach ($pathSegs as $i => $seg) {
                        $path .= ($path !== '' ? '/' : '') . $seg;
                        if (isset($foldersByPath[$path])) {
                            $stepFolder = $foldersByPath[$path];
                        } else {
                            $stepFolder = $foldersService->findFolder([
                                'volumeId' => $queryFolder->volumeId,
                                'path' => "$path/",
                            ]);
                            if (!$stepFolder) {
                                $stepFolder = $foldersService->ensureFolderByFullPathAndVolume($path, $queryFolder->getVolume());
                            }
                            $foldersByPath[$path] = $stepFolder;
                        }

                        if ($i < count($pathSegs) - 1) {
                            $stepFolder->setHasChildren(true);
                        }
                        $sourcePath[] = $stepFolder->getSourcePathInfo();
                    }

                    $path = rtrim($folder->path, '/');
                    $path = StringHelper::removeRight($path, $folder->name);
                    $path = StringHelper::removeLeft($path, $queryFolder->path ?? '');

                    $assets[] = new self([
                        'isFolder' => true,
                        'volumeId' => $queryFolder->volumeId,
                        'folderId' => $folder->id,
                        'folderPath' => $path,
                        'title' => $folder->name,
                        'uiLabelPath' => ArrayHelper::filterEmptyStringsFromArray(explode('/', $path)),
                        'sourcePath' => $sourcePath,
                    ]);
                }
            }

            // Is there room for any normal assets as well?
            $totalAssets = count($assets);

            /** @phpstan-ignore-next-line */
            if ($totalAssets < $elementQuery->limit) {
                $elementQuery->offset(max($elementQuery->offset - $totalFolders, 0));
                $elementQuery->limit($elementQuery->limit - $totalAssets);
            }
        }

        // if it's a 'foldersOnly' request, or we have enough folders to hit the query limit,
        // return the folders directly
        if (
            self::isFolderIndex() ||
            count($assets) === (int)$elementQuery->limit
        ) {
            return $assets;
        }

        // otherwise merge in the resulting assets
        return array_merge($assets, $elementQuery->all());

    }

    /**
     * @inheritdoc
     */
    public static function indexElementCount(ElementQueryInterface $elementQuery, ?string $sourceKey): int
    {
        $count = 0;

        /** @var MuxAssetQuery $elementQuery */
        if (self::_includeFoldersInIndexElements($elementQuery, $sourceKey, $queryFolder)) {
            try {
                $count += self::_createFolderQueryForIndex($elementQuery, $queryFolder)->count();
            } catch (QueryAbortedException $e) {
                return 0;
            }
        }

        if (!self::isFolderIndex()) {
            $count += parent::indexElementCount($elementQuery, $sourceKey);
        }

        return $count;
    }

    /**
     * Include Folders In Index Elements
     * @param MuxAssetQuery $assetQuery 
     * @param string|null $sourceKey 
     * @param mixed $queryFolder
     * @return bool 
     */
    private static function _includeFoldersInIndexElements(MuxAssetQuery $assetQuery, ?string $sourceKey, ?MuxFolder &$queryFolder = null): bool
    {

        if (
            !Craft::$app->getRequest()->getBodyParam('showFolders') ||
            !str_starts_with($sourceKey, 'volume:') ||
            !is_numeric($assetQuery->folderId)
        ) {
            return false;
        }


        if ($queryFolder === null && $assetQuery->folderId !== null) {
            $foldersService = Mux::$plugin->folders;
            $queryFolder = $foldersService->getFolderById($assetQuery->folderId);
            if (!$queryFolder) {
                return false;
            }
        }

        if ($assetQuery->search) {
            $assetQuery->search = $searchQuery = Craft::$app->getSearch()->normalizeSearchQuery($assetQuery->search);
            $tokens = $searchQuery->getTokens();
            if (count($tokens) !== 1 || !self::_validateSearchTermForIndex(reset($tokens))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate Search Term For Index
     * @param SearchQueryTerm|SearchQueryTermGroup $token 
     * @return bool 
     */
    private static function _validateSearchTermForIndex(SearchQueryTerm|SearchQueryTermGroup $token): bool
    {
        if ($token instanceof SearchQueryTermGroup) {
            foreach ($token->terms as $term) {
                if (!self::_validateSearchTermForIndex($term)) {
                    return false;
                }
            }
            return true;
        }

        /** @var SearchQueryTerm $token */
        return !$token->exclude && !$token->attribute;
    }

    /**
     * @throws QueryAbortedException
     */
    private static function _createFolderQueryForIndex(MuxAssetQuery $assetQuery, ?MuxFolder $queryFolder = null): Query
    {
        if (
            is_array($assetQuery->orderBy) &&
            is_string($firstOrderByCol = array_key_first($assetQuery->orderBy)) &&
            in_array($firstOrderByCol, ['title', 'filename'])
        ) {
            $sortDir = $assetQuery->orderBy[$firstOrderByCol];
        } else {
            $sortDir = SORT_ASC;
        }

        $foldersService = Mux::$plugin->folders;
        $query = $foldersService->createFolderQuery()
            ->orderBy(['name' => $sortDir]);

        if ($assetQuery->includeSubfolders) {
            if ($queryFolder === null) {
                $queryFolder = $foldersService->getFolderById($assetQuery->folderId);
                if (!$queryFolder) {
                    throw new QueryAbortedException();
                }
            }
            $query
                ->where(['volumeId' => $queryFolder->volumeId])
                ->andWhere(['not', ['id' => $queryFolder->id]])
                ->andWhere(['like', 'path', "$queryFolder->path%", false]);
        } else {
            $query->where(['parentId' => $assetQuery->folderId]);
        }

        if ($assetQuery->search) {
            // `search` will already be normalized to a SearchQuery obj via _includeFoldersInIndexElements(),
            // and we already know it only has one token
            /** @var SearchQuery $searchQuery */
            $searchQuery = $assetQuery->search;
            $token = ArrayHelper::firstValue($searchQuery->getTokens());
            $query->andWhere(self::_buildFolderQuerySearchCondition($token));
        }

        return $query;
    }

    /**
     * Build Folder Query Search Condition
     * @param SearchQueryTerm|SearchQueryTermGroup $token 
     * @return array 
     */
    private static function _buildFolderQuerySearchCondition(SearchQueryTerm|SearchQueryTermGroup $token): array
    {
        if ($token instanceof SearchQueryTermGroup) {
            $condition = ['or'];
            foreach ($token->terms as $term) {
                $condition[] = self::_buildFolderQuerySearchCondition($term);
            }
            return $condition;
        }

        $isPgsql = Craft::$app->getDb()->getIsPgsql();

        /** @var SearchQueryTerm $token */
        if ($token->subLeft || $token->subRight) {
            return [$isPgsql ? 'ilike' : 'like', 'name', sprintf('%s%s%s',
                $token->subLeft ? '%' : '',
                $token->term,
                $token->subRight ? '%' : '',
            ), false];
        }

        // Only Postgres supports case-sensitive queries
        if ($isPgsql) {
            return ['=', 'lower([[name]])', mb_strtolower($token->term)];
        }

        return ['name' => $token->term];
    }

    /**
     * Transforms an VolumeFolderModel into a source info array.
     *
     * @param VolumeFolder $folder
     * @param User|null $user
     * @return array
     */
    private static function _assembleSourceInfoForFolder(MuxFolder $folder, ?User $user = null): array
    {
 
        $volume = $folder->getVolume();
        if (!$folder->parentId) {
            $volumeHandle = $volume->handle ?? false;
        } else {
            $volumeHandle = false;
        }

        $userSession = Craft::$app->getUser();
        $canMoveTo = true; //$canUpload && $userSession->checkPermission("deleteAssets:$volume->uid");
        $canMovePeerFilesTo = true; // (
        //     $canMoveTo &&
        //     $userSession->checkPermission("savePeerAssets:$volume->uid") &&
        //     $userSession->checkPermission("deletePeerAssets:$volume->uid")
        // );

        $sourcePathInfo = $folder->getSourcePathInfo();

        $source = [
            'key' => $folder->parentId ? "folder:$folder->uid" : "volume:$volume->uid",
            'label' => $folder->parentId ? $folder->name : Craft::t('site', $folder->name),
            'hasThumbs' => true,
            'criteria' => ['folderId' => $folder->id],
            'defaultSort' => ['dateCreated', 'desc'],
            'defaultSourcePath' => $sourcePathInfo ? [$sourcePathInfo] : null,
            'data' => [
                'volume-handle' => $volumeHandle,
                'folder-id' => $folder->id,
                'can-move-to' => $canMoveTo,
                'can-move-peer-files-to' => $canMovePeerFilesTo,
            ],
        ];

        return $source;
    }

    /**
     * Check if the current request is a folder index.
     * @return bool 
     * @throws InvalidConfigException 
     */
    private static function isFolderIndex(): bool
    {
        return (
            (Craft::$app->controller instanceof ElementIndexesController || Craft::$app->controller instanceof ElementSelectorModalsController) &&
            Craft::$app->getRequest()->getBodyParam('foldersOnly')
        );
    }

    /**
     * @var bool Whether this is a folder.
     */
    public bool $isFolder = false;

    /**
     * @var array|null The source path, if this represents a folder.
     * @internal
     */
    public ?array $sourcePath = null;

    /**
     * @var string|null Folder path
     */
    public ?string $folderPath = null;

    /**
     * @var array|null UI label path
     */
    public ?array $uiLabelPath = null;

    /**
     * @inheritdoc
     */
    protected function crumbs(): array
    {
        $volume = $this->getVolume();

        $crumbs = [
            [
                'label' => Craft::t('mux', 'Mux Assets'),
                'url' => UrlHelper::cpUrl('mux/assets'),
            ],
            [
                'menu' => [
                    'label' => Craft::t('mux', 'Select volume'),
                    'items' => Collection::make(Mux::$plugin->volumes->getAllVolumes())
                        ->map(fn(MuxVolume $v) => [
                            'label' => Craft::t('site', $v->name),
                            'url' => "mux/$v->handle",
                            'selected' => $v->id === $this->volumeId,
                        ])
                        ->all(),
                ],
            ],
        ];

        $uri = "mux/assets/$volume->handle";

        if ($this->folderPath !== null) {
            $subfolders = ArrayHelper::filterEmptyStringsFromArray(explode('/', $this->folderPath));
            foreach ($subfolders as $subfolder) {
                $uri .= "/$subfolder";
                $crumbs[] = [
                    'label' => $subfolder,
                    'url' => UrlHelper::cpUrl($uri),
                ];
            }
        }

        return $crumbs;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        if ($this->isFolder) {
            return false; // Folders can't be viewed directly
        }

        return $user->can('mux:assets');
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        return $user->can('mux:assets-edit');
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        if ($this->isFolder) {
            return false; // Folders can't be deleted through this interface
        }

        return $user->can('mux:assets-delete');
    }

    public function canDeleteForSite(User $user): bool
    {
        return $user->can('mux:assets-delete');
    }

    /**
     * @inheritdoc
     */
    public function canDuplicate(User $user): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function cpEditUrl(): ?string
    {
        if ($this->isFolder) {
            return null; // Folders don't have edit URLs
        }

        return sprintf('mux/assets/edit/%s', $this->getCanonicalId());
    }

    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('mux/assets');
    }

    /**
     * @inheritdoc
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => Craft::t('app', 'MUX'),
                'url' => UrlHelper::cpUrl('mux/assets'),
            ],
            [
                'label' => $this->title,
                'url' => "",
            ],
        ]);
        
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function getFieldLayout(): ?FieldLayout
    {
        
        $fieldLayout = new FieldLayout();
        $tab = new MuxAssetFieldContentTab();
        $tab->name = "Content";

        $tab2 = new MuxAssetFieldTracksTab();
        $tab2->name = 'Tracks';
        $tab2->setLayout($fieldLayout);

        $tab3 = new MuxAssetFieldDataTab();
        $tab3->name = 'Data';
        $tab3->setLayout($fieldLayout);

        $fieldLayout->setTabs([
           $tab, $tab2, $tab3
        ]);

        return $fieldLayout;
    }

    /**
     * @inheritdoc
     */
    public function getSidebarHtml(bool $static): string
    {
        $html = [];
        $html[] = Craft::$app->getView()->renderTemplate('mux/_includes/sidebar', ['muxAsset' => $this]);
        $html[] = parent::getSidebarHtml(false);
        return implode('', $html);
    }


    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        parent::afterSave($isNew);
        
        // Use a single, optimized database operation
        $this->syncToCustomTable($isNew);
    }

    /**
     * Sync element data to custom table efficiently
     */
    private function syncToCustomTable(bool $isNew): void
    {
        // Get all attributes instead of trying to get dirty ones
        $allAttributes = $this->getAttributes();
        
        // Filter to only include our custom fields
        $customFields = array_intersect_key($allAttributes, array_flip([
            'asset_id', 'folderId', 'volumeId', 'created_at', 'asset_status',
            'duration', 'max_stored_resolution', 'max_stored_frame_rate',
            'resolution_tier', 'max_resolution_tier', 'encoding_tier',
            'aspect_ratio', 'playback_ids', 'tracks', 'errors',
            'per_title_encode', 'upload_id', 'is_live', 'passthrough',
            'live_stream_id', 'master', 'master_access', 'mp4_support',
            'source_asset_id', 'normalize_audio', 'static_renditions',
            'recording_times', 'non_standard_input_reasons', 'test',
            'ingest_type', 'meta'
        ]));
        
        // Remove null values to avoid database issues
        $customFields = array_filter($customFields, function($value) {
            return $value !== null;
        });
        
        if (empty($customFields)) {
            return; // No custom fields changed
        }
        
        try {
            if ($isNew) {
                $customFields['id'] = $this->id;
                Db::insert('{{%mux_assets}}', $customFields);
            } else {
                Db::update('{{%mux_assets}}', $customFields, ['id' => $this->id]);
            }
        } catch (\Exception $e) {
            Mux::error("Failed to sync asset data: " . $e->getMessage(), 'mux');
            // Don't throw - let the main save operation complete
        }
    }

    /**
     * Generate Jwt
     * @param SignedKey $tokenData 
     * @param string $type 
     * @param array $options 
     * @return string 
     * @throws InvalidConfigException 
     */
    private function generateJwt(SignedKey $tokenData, string $type, array $options): string 
    {
        $playbackId = $this->playback_ids[0]['id'];
        $cacheId = sprintf("%s-%s-%s", $playbackId, $type, $tokenData->key_id); // playbackId-type-tokenIdx
        $jwt = Craft::$app->getCache()->get($cacheId);

        if(!$jwt) {
            $jwt = JWTHelper::getJWT(
                $playbackId,
                $tokenData->key_id,
                $tokenData->private_key,
                $type,
                $options
            );

            Craft::$app->getCache()->set($cacheId, $jwt, 86400);
        }

        return $jwt;
    }

    
    /**
     * Get Token Data
     * @param string $keyId
     * @return null|SignedKey 
     * @throws InvalidConfigException 
     * @throws NotSupportedException 
     * @throws Exception 
     * @throws InvalidArgumentException 
     * @throws GlobalException 
     */
    private function getTokenData(?string $keyId): ?SignedKey
    {
        $tokens = $this->securePlaybackTokens();
    
        if(!empty($tokens)) {
            $token = null;
            if(!$keyId) {
                $token = $tokens[0];
            } else {
                $token = array_map(function($tkn) use($keyId) {
                    if($tkn->key_id === $keyId) {
                        return $tkn;
                    }
                }, $tokens);
            }
            
            return is_array($token) ? $token[0] : $token;
        }

        return null;
    }
    

    /**
     * @inheritdoc
     * @since 3.7.0
     */
    public function __set($name, $value)
    {
        parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function getIsFolder(): bool
    {
        return $this->isFolder;
    }

    /**
     * @inheritdoc
     */
    public function getFolderId(): ?int
    {
        return $this->folderId;
    }

    /**
     * @inheritdoc
     */
    public function setFolderId(?int $folderId): void
    {
        $this->folderId = $folderId;
    }

    /**
     * Get Folder
     * @return mixed 
     */
    public function getFolder()
    {
        if ($this->folderId) {
            return Mux::$plugin->folders->getFolderById($this->folderId);
        }
        return null;
    }

    /**
     * Returns the asset's folder name for display
     */
    public function getFolderName(): string
    {
        if (!$this->folderId) {
            return Craft::t('mux', 'Root');
        }
        
        $folder = Mux::$plugin->folders->getFolderById($this->folderId);
        return $folder ? $folder->name : Craft::t('mux', 'Unknown Folder');
    }

    /**
     * Get Volume
     * @return MuxVolume|null
     */
    public function getVolume(): ?MuxVolume
    {
        return Mux::$plugin->volumes->getVolumeById($this->volumeId);
    }
}
