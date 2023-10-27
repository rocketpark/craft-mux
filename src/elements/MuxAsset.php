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
use Exception as GlobalException;
use phpDocumentor\Reflection\Types\Boolean;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\db\MuxAssetQuery;
use rocketpark\mux\elements\actions\SyncAssets;
use rocketpark\mux\fieldlayoutelements\MuxAssetFieldContentTab;
use rocketpark\mux\fieldlayoutelements\MuxAssetFieldTracksTab;
use rocketpark\mux\records\SignedKeys;
use rocketpark\mux\models\SignedKey;
use rocketpark\mux\helpers\JWT as JWTHelper;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\InvalidArgumentException;
use yii\behaviors\AttributeTypecastBehavior;
use yii\db\Exception;
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
            'asset_status',
            'duration',
            'is_live',
            'passthrough',
            'test'
        ];
    }

    public ?string $asset_id = '';
    public ?string $created_at = '';
    public ?string $asset_status = '';
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
    public ?array $non_standard_input_reasons = [];
    public ?bool $test = null;

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
     * Get Thumb Url
     * @param int $size 
     * @return string|null 
     */
    public function getThumbUrl(int $size): ?string
    {
        $options = [
            'width' => $size,
            'height' => $size,
            'fit_mode' => 'smartcrop',
        ];

        if ($jwt = $this->getSecurePlaybackJWT(null,'t', $options)) {
            $options = [ 'token' => $jwt ];
        }

        return UrlHelper::urlWithParams("https://image.mux.com/{$this->playback_ids[0]['id']}/thumbnail.webp", $options);
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source): array
    {
        // List any bulk element actions here
        $actions = [];
        $actions[] = SyncAssets::class;
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
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'duration':
                return gmdate("H:i:s", $this->duration);
            case 'securePlayback':
                return $this->securePlayback 
                ? '<svg style="fill:currentColor;height:16px; width:auto;" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 448 512"><!--! Font Awesome Pro 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M224 64c44.2 0 80 35.8 80 80v48H144V144c0-44.2 35.8-80 80-80zM80 144v48H64c-35.3 0-64 28.7-64 64V448c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V256c0-35.3-28.7-64-64-64H368V144C368 64.5 303.5 0 224 0S80 64.5 80 144zM256 320v64c0 17.7-14.3 32-32 32s-32-14.3-32-32V320c0-17.7 14.3-32 32-32s32 14.3 32 32z"/></svg>' 
                : '<svg style="fill:currentColor;height:16px; width:auto;" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 576 512"><!--! Font Awesome Pro 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M432 48c-44.2 0-80 35.8-80 80v64h32c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H304V128C304 57.3 361.3 0 432 0s128 57.3 128 128v72c0 13.3-10.7 24-24 24s-24-10.7-24-24V128c0-44.2-35.8-80-80-80zM384 240H64c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H384c8.8 0 16-7.2 16-16V256c0-8.8-7.2-16-16-16zM256 376H192c-13.3 0-24-10.7-24-24s10.7-24 24-24h64c13.3 0 24 10.7 24 24s-10.7 24-24 24z"/></svg>';
        }

        return parent::tableAttributeHtml($attribute);
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
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('viewMuxAssets');
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('saveMuxAssets');
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('deleteMuxAssets');
    }

    /**
     * @inheritdoc
     */
    protected function cpEditUrl(): ?string
    {
        return sprintf('mux/assets/%s', $this->getCanonicalId());
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

    /**
     * @inheritdoc
     * @since 3.7.0
     */
    public function afterSave(bool $isNew): void
    {
      
        if ($isNew) {
            Db::insert('{{%mux_assets}}', [
                'id' => $this->id,
                'asset_id' => $this->asset_id,
                'created_at' => $this->created_at,
                'asset_status' => $this->asset_status,
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
                'asset_status' => $this->asset_status,
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
        if ($name === 'passthrough') {
            if (isset($this->asset_id) && $value == $this->asset_id) {
                $this->passthrough = $this->title;
            } else {
                $this->passthrough = $value; 
            }
        } else {
            parent::__set($name, $value); // default behavior 
        }
    }
}
