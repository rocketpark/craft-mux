<?php

namespace rocketpark\mux;

use Craft;
use GuzzleHttp\Client;
use Monolog\Formatter\LineFormatter;
use MuxPhp;
use Psr\Log\LogLevel;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\DefineBehaviorsEvent;
use craft\events\ElementEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\log\MonologTarget;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Gql;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use rocketpark\mux\assetbundles\mux\MuxAsset as MuxAssetAsset;
use rocketpark\mux\assetbundles\mux\MuxAssetIndexAsset;
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;
use rocketpark\mux\fields\MuxAsset as MuxAssetField;
use rocketpark\mux\gql\interfaces\elements\MuxAsset as MuxAssetInterface;
use rocketpark\mux\gql\queries\MuxAsset as MuxAssetGqlQuery;
use rocketpark\mux\models\MuxAsset;
use rocketpark\mux\models\Settings;
use rocketpark\mux\services\Assets;
use rocketpark\mux\services\Data;
use rocketpark\mux\services\Folders;
use rocketpark\mux\services\Volumes;
use rocketpark\mux\services\PlaybackRestrictions;
use rocketpark\mux\services\SettingsService;
use rocketpark\mux\services\SignedKeys;
use rocketpark\mux\variables\MuxAssetBehavior;
use yii\base\Event;
use yii\log\Logger;

/**
 * Mux plugin
 *
 * @method static Mux getInstance()
 * @method Settings getSettings()
 * @author Rocket Park <support@rocketpark.com>
 * @copyright Rocket Park
 * @license https://craftcms.github.io/license/ Craft License
 * @property-read PlaybackRestrictions $playbackRestrictions
 * @property-read SignedKeys $signedKeys
 * @property-read Assets $assets
 * @property-read Folders $folders
 * @property-read Volumes $volumes
 * @property-read SettingsService $settings
 * @property-read Data $data
 */
class Mux extends Plugin
{

    /**
     * @var Plugin|null
     * @property-read Plugin $plugin
     */
    public static ?Plugin $plugin = null;

    /**
     * @var Settings|null
     * @property-read Settings $settings
     */
    public static ?Settings $settings = null;

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.3';

    /**
     * @var bool
     */
    public bool $hasCpSection = true;

    /**
     * @var bool
     */
    public bool $hasCpSettings = true;


    public static function config(): array
    {
        return [
            'components' => [
                'assets' => Assets::class,
                'settings' => SettingsService::class,
                'folders' => Folders::class,
                'volumes' => Volumes::class,
                'playbackRestrictions' => PlaybackRestrictions::class,
                'signedKeys' => SignedKeys::class,
                'data' => Data::class
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;
        self::$settings = $this->getSettings();
        $this->name = self::$settings->pluginName;


        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->registerCpRoutes();
        }

        if (Craft::$app->getEdition() === Craft::Pro) {
            $this->_registerPermissions();
        }

        // Register the asset bundle for the Control Panel
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Craft::$app->view->registerAssetBundle(MuxAssetIndexAsset::class);
            Craft::$app->view->registerAssetBundle(MuxAssetAsset::class);
        }

        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = MuxAssetElement::class;
            }
        );

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = MuxAssetField::class;
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_DEFINE_BEHAVIORS,
            function (DefineBehaviorsEvent $e) {
                $e->sender->attachBehaviors([
                    MuxAssetBehavior::class,
                ]);
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_QUERIES,
            function (RegisterGqlQueriesEvent $event) {
                $event->queries = array_merge(
                    $event->queries,
                    MuxAssetGqlQuery::getQueries()
                );
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_TYPES,
            function (RegisterGqlTypesEvent $event) {
                $event->types[] = MuxAssetInterface::class;
            }
        );

        // Craft::$app->getElements()->on(
        //     Elements::EVENT_BEFORE_SAVE_ELEMENT,
        //     function (ElementEvent $e) {
        //         if ($e->element instanceof MuxAssetElement) {
        //             $element = $e->element;
        //             Mux::info('Before saving asset with attributes: ' . json_encode($element->getAttributes()), 'mux');
        //         }
        //     }
        // );

        Craft::$app->getElements()->on(
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            function (ElementEvent $e) {
                if ($e->element instanceof MuxAssetElement) {
                    
                    $element = $e->element;

                    //Mux::info('Saving asset with attributes: ' . json_encode($element->getAttributes()), 'mux');
                    
                    // Skip sync if this was triggered by a webhook
                    if (isset($element->isWebhookUpdate) && !$element->isWebhookUpdate) {

                        $attributes = $element->getAttributes();
                        $params = [
                            'asset_id' => $attributes['asset_id'],
                            'passthrough' => $attributes['passthrough'],
                            'meta' => [
                                'title' => $attributes['title'],
                                'external_id' => $attributes['id'],
                                'creator_id' => $attributes['meta']['creator_id'] ?? ''
                            ]
                        ];

                        Mux::info("Updating Asset in MUX: ". $params['asset_id']);

                        Mux::$plugin->assets->updateMuxAsset($params);
                    }

                }
            }
        );

        Craft::$app->elements->on(
            Elements::EVENT_BEFORE_DELETE_ELEMENT,
            function (ElementEvent $e) {
                if ($e->element instanceof MuxAssetElement) {
                    $element = $e->element;
                    $attributes = $element->getAttributes();
                    /* 
                        If (trashed == true) the element is being hard deleted (removed FOREVER see:https://media.giphy.com/media/hEwkspP1OllJK/giphy.gif)
                          then we remove it from MUX unless it has already been removed from MUX. 
                    */
                    if ($e->hardDelete) {
                        $config = Mux::$plugin->assets->muxConf();
                        $apiInstance = new MuxPhp\Api\AssetsApi(
                            new Client(),
                            $config
                        );

                        try {
                            $response = $apiInstance->getAsset($attributes['asset_id']);
                            if ($response) {
                                if (!Mux::$plugin->assets->deleteAssetById($attributes['asset_id'])) {
                                    return false;
                                }
                            }
                        } catch (\MuxPhp\ApiException $e) {
                            //$this->error($e->getCode());
                            $this->error($e->getMessage());
                            $this->info("Attempting to hard delete a trashed asset element and it's MUX counterpart. However, the asset cannot be found in MUX as it has already been deleted. Therefore, only the MuxAssetElement was deleted.");
                            return true;
                        }
                    }
                }
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_DELETE_ELEMENT,
            function(ElementEvent $event) {
                if ($event->element instanceof MuxAssetElement) {
                    $assetId = $event->element->asset_id;
                    if ($assetId) {
                        Mux::$plugin->data->clearAssetCache($assetId);
                    }
                }
            }
        );

        $this->_registerLogTarget();

        // Register services
        $this->setComponents([
            'assets' => \rocketpark\mux\services\Assets::class,
            'folders' => \rocketpark\mux\services\Folders::class,
            'volumes' => \rocketpark\mux\services\Volumes::class,
            'settings' => \rocketpark\mux\services\SettingsService::class,
            'playbackRestrictions' => \rocketpark\mux\services\PlaybackRestrictions::class,
            'signedKeys' => \rocketpark\mux\services\SignedKeys::class,
            'data' => \rocketpark\mux\services\Data::class
        ]);
    }

    /**
     * Logs an informational message to our custom log target.
     */
    public static function info(string $message): void
    {
        Craft::info($message, 'mux');
    }

    /**
     * Logs an error message to our custom log target.
     */
    public static function error(string $message): void
    {
        Craft::error($message, 'mux');
    }

    /**
     * Logs a warning message to our custom log target.
     */
    public static function warning(string $message): void
    {
        Craft::warning($message, 'mux');
    }

    /**
     * Registers a custom log target, keeping the format as simple as possible.
     */
    private function _registerLogTarget(): void
    {
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'mux',
            'categories' => ['mux'],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => false,
            'formatter' => new LineFormatter(
                format: "%datetime% %message%\n",
                dateFormat: 'Y-m-d H:i:s',
            ),
        ]);

        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'mux',
            'categories' => ['mux'],
            'level' => LogLevel::WARNING,
            'logContext' => false,
            'allowLineBreaks' => false,
        ]);

        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'mux',
            'categories' => ['mux'],
            'level' => LogLevel::ERROR,
            'logContext' => false,
            'allowLineBreaks' => false,
        ]);
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        // Just redirect to the plugin settings page
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('mux/settings'));
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
    {
        $subNavs = [];
        $navItem = parent::getCpNavItem();
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser->can('mux:assets')) {
            $subNavs['assets'] = [
                'label' => 'List',
                'url' => 'mux/assets',
            ];
        }

        if ($currentUser->can('mux:settings')) {
            $subNavs['settings'] = [
                'label' => 'Settings',
                'url' => 'mux/settings',
            ];
        }

        if ($currentUser->can('mux:settings')) {
            $subNavs['restrictions'] = [
                'label' => 'Playback Restrictions',
                'url' => 'mux/restrictions',
            ];
        }

        if ($currentUser->can('mux:settings')) {
            $subNavs['signedKeys'] = [
                'label' => 'Signed Keys',
                'url' => 'mux/signed-keys',
            ];
        }

        if (empty($subNavs)) {
            return null;
        }

        // A single sub nav item is redundant
        if (count($subNavs) === 1) {
            $subNavs = [];
        }
        $navItem = array_merge($navItem, [
            'subnav' => $subNavs,
        ]);

        return $navItem;
    }


    private function _registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => Craft::t('mux', 'Mux'),
                    'permissions' => $this->customAdminCpPermissions(),
                ];
            }
        );
    }

    protected function registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['mux/assets/edit/<elementId:\d+>'] = 'mux/assets/edit';
                $event->rules['mux/assets/<defaultSource:{handle}(\/[^\/]*)?>'] = 'mux/assets/index';
                $event->rules['mux/assets'] = 'mux/assets/index';
                
                $event->rules['mux'] = 'mux/assets/index';
                $event->rules['mux/settings'] = 'mux/settings/plugin-settings';
                $event->rules['mux/restrictions'] = ['template' => 'mux/settings/restrictions'];
                $event->rules['mux/signed-keys'] = ['template' => 'mux/settings/signedKeys'];
            }
        );
    }

    /**
     * Returns the custom Control Panel user permissions.
     *
     * @return array
     */
    protected function customAdminCpPermissions(): array
    {
        $permissions = [];
        $permissions[] = [
            'mux:assets' => [
                'label' => Craft::t('mux', 'View Assets'),
                'info' => Craft::t('mux', 'This user will be able to view Mux assets.'),
                'nested' => [
                    'mux:assets-create' => [
                        'label' => Craft::t('mux', 'Create Assets'),
                        'info' => Craft::t('mux', 'This user will be able edit create Mux asset by uploading.'),
                    ],
                    'mux:assets-edit' => [
                        'label' => Craft::t('mux', 'Edit Assets'),
                        'info' => Craft::t('mux', 'This user will be able edit/save an already created Asset.'),
                    ],
                    'mux:assets-delete' => [
                        'label' => Craft::t('mux', 'Delete Assets'),
                        'info' => Craft::t('mux', 'This user will be able to delete an already created Asset.'),
                    ],
                ]
            ],
            'mux:settings' => [
                'label' => Craft::t('mux', 'Settings'),
            ],
        ];


        return $permissions;
    }
}