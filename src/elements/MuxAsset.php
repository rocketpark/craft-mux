<?php

namespace rocketpark\mux\elements;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\elements\db\ElementQueryInterface;
use craft\fieldlayoutelements\TextField;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\CpScreenResponseBehavior;
use craft\web\View;
use rocketpark\mux\elements\db\MuxAssetQuery;
use rocketpark\mux\fieldlayoutelements\MuxAssetFieldContentTab;
use rocketpark\mux\fieldlayoutelements\MuxAssetFieldTracksTab;
use yii\web\Response;

/**
 * Mux Asset element type
 */
class MuxAsset extends Element
{

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('mux', 'MUX asset');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('mux', 'MUX asset');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('mux', 'MUX Assets');
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
    public static function hasContent(): bool
    {
        return true;
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
            'duration',
            'is_live',
            'passthrough',
            'test'
        ];
    }

    public ?string $asset_id = '';
    public ?string $created_at = '';
    public ?string $status = '';
    public ?string $duration = '';
    public ?string $max_stored_resolution = '';
    public ?string $max_stored_frame_rate = '';
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
    public ?array  $static_renditions = [];
    public ?array  $recording_times = [];
    public ?string $non_standard_input_reasons = '';
    public ?bool $test = null;


    public function getPlaybackId(): string
    {
        return $this->playback_ids[0]['id'];
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
        return UrlHelper::urlWithParams("https://image.mux.com/{$this->playback_ids[0]['id']}/thumbnail.{$format}",
            [
                'width' => $width,
                'height' => $height,
                'fit_mode' => $fit_mode,
        ]);
    }

    protected static function defineActions(string $source): array
    {
        // List any bulk element actions here
        return [];
    }

    protected static function includeSetStatusAction(): bool
    {
        return false;
    }

    protected static function defineSources(string $context = null): array
    {
        return [
            [
                'key' => 'allassets',
                'label' => 'All Assets',
                'hasThumbs' => true,
            ]
        ];
    }

    public function getThumbUrl(int $size): ?string
    {
        return UrlHelper::urlWithParams("https://image.mux.com/{$this->playback_ids[0]['id']}/thumbnail.webp",
            [
                'width' => $size,
                'height' => $size,
                'fit_mode' =>'smartcrop',
            ]);
        //return UrlHelper::resourceUrl("product-images/{$this->id}/{$size}");
    }

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
            // ...
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'id' => ['label' => Craft::t('app', 'ID')],
            'asset_id' => ['label' => Craft::t('mux', 'Mux Asset ID')],
            'aspect_ratio' => ['label' => Craft::t('mux', 'Aspect Ratio')],
            'duration' => ['label' => Craft::t('mux', 'Duration')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }
    
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'duration':
                return gmdate("H:i:s", $this->duration);
        }

        return parent::tableAttributeHtml($attribute);
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'id',
            'asset_id',
            'aspect_ratio',
            'duration',
            'dateCreated',
        ];
    }

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }

    protected function route(): array|string|null
    {
        // Define how mux assets should be routed when their URLs are requested
        return [
            'templates/render',
            [
                'template' => 'site/template/path',
                'variables' => ['muxAsset' => $this],
            ]
        ];
    }

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('viewMuxAssets');
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('saveMuxAssets');
    }

    public function canDelete(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('deleteMuxAssets');
    }

    protected function cpEditUrl(): ?string
    {
        return sprintf('mux/assets/%s', $this->getCanonicalId());
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('mux/assets');
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => self::pluralDisplayName(),
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

        $fieldLayout->setTabs([
           $tab, $tab2
        ]);

        return $fieldLayout;
    }

    /**
     * @inheritdoc
     * @since 3.7.0
     */
    public function getSidebarHtml(bool $static): string
    {
        $html = [];
        $html[] = Craft::$app->getView()->renderTemplate('mux/_includes/sidebar', ['muxAsset' => $this]);
        $html[] = parent::getSidebarHtml(false);
        return implode('', $html);
    }

    public function afterSave(bool $isNew): void
    {
      
        if ($isNew) {
            Db::insert('{{%mux_assets}}', [
                'id' => $this->id,
                'asset_id' => $this->asset_id,
                'created_at' => $this->created_at,
                'status' => $this->status,
                'duration' => $this->duration,
                'max_stored_resolution' => $this->max_stored_resolution,
                'max_stored_frame_rate' => $this->max_stored_frame_rate,
                'aspect_ratio' => $this->aspect_ratio,
                'playback_ids' => $this->playback_ids,
                'tracks' => $this->tracks,
                'errors' => $this->errors,
                'per_title_encode' => $this->per_title_encode,
                'upload_id' => $this->upload_id,
                'is_live' => $this->is_live,
                'passthrough' => $this->passthrough,
                'live_stream_id' => $this->live_stream_id,
                'master' => $this->master,
                'master_access' => $this->master_access,
                'mp4_support' => $this->mp4_support,
                'source_asset_id' => $this->source_asset_id,
                'normalize_audio' => $this->normalize_audio,
                'static_renditions' => $this->static_renditions,
                'recording_times' => $this->recording_times,
                'non_standard_input_reasons' => $this->non_standard_input_reasons,
                'test' => $this->test,
            ]);
        } else {
            Db::update('{{%mux_assets}}', [
                'asset_id' => $this->asset_id,
                'created_at' => $this->created_at,
                'status' => $this->status,
                'duration' => $this->duration,
                'max_stored_resolution' => $this->max_stored_resolution,
                'max_stored_frame_rate' => $this->max_stored_frame_rate,
                'aspect_ratio' => $this->aspect_ratio,
                'playback_ids' => $this->playback_ids,
                'tracks' => $this->tracks,
                'errors' => $this->errors,
                'per_title_encode' => $this->per_title_encode,
                'upload_id' => $this->upload_id,
                'is_live' => $this->is_live,
                'passthrough' => $this->passthrough,
                'live_stream_id' => $this->live_stream_id,
                'master' => $this->master,
                'master_access' => $this->master_access,
                'mp4_support' => $this->mp4_support,
                'source_asset_id' => $this->source_asset_id,
                'normalize_audio' => $this->normalize_audio,
                'static_renditions' => $this->static_renditions,
                'recording_times' => $this->recording_times,
                'non_standard_input_reasons' => $this->non_standard_input_reasons,
                'test' => $this->test,
            ], ['id' => $this->id]);
        }
    
        parent::afterSave($isNew);
    }
}
