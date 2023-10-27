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
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;
use rocketpark\mux\fields\MuxAsset as MuxAssetField;
use rocketpark\mux\gql\interfaces\elements\MuxAsset as MuxAssetInterface;
use rocketpark\mux\gql\queries\MuxAsset as MuxAssetGqlQuery;
use rocketpark\mux\models\MuxAsset;
use rocketpark\mux\models\Settings;
use rocketpark\mux\services\Assets;
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
 */
class Mux extends Plugin
{

    /**
     * @var Retour
     */
    public static ?Plugin $plugin = null;

    /**
     * @var ?Settings
     */
    public static ?Settings $settings = null;

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.1';

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
                'playbackRestrictions' => PlaybackRestrictions::class,
                'signedKeys' => SignedKeys::class
            ],
        ];
    }

    public function init()
    {

        self::$plugin = $this;
        self::$settings = $this->getSettings();
        $this->name = self::$settings->pluginName;


        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
        }

        if (Craft::$app->getEdition() === Craft::Pro) {
            $this->_registerPermissions();
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

        Craft::$app->elements->on(
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            function (ElementEvent $e) {
                // Update the asset passthrough attribute (which holds the title) on Mux.
                if ($e->element instanceof MuxAssetElement) {
                    $element = $e->element;
                    $attributes = $element->getAttributes();

                    $muxAsset = Mux::$plugin->assets->getMuxAsset($attributes['asset_id']);

                    $asset = new MuxAsset();

                    // Prevent an update loop from webhook.
                    if (array_key_exists('passthrough', $muxAsset)) {
                        if ($muxAsset['passthrough'] != $attributes['title']) {
                            $asset->asset_id = $attributes['asset_id'];
                            $asset->passthrough = $attributes['title'];

                            Mux::$plugin->assets->updateMuxAsset($asset);
                        }
                    } else {
                        $asset->asset_id = $attributes['asset_id'];
                        $asset->passthrough = $attributes['title'];

                        Mux::$plugin->assets->updateMuxAsset($asset);
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
                    if ($attributes['trashed'] == 'true') {
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

        $this->_registerLogTarget();

        parent::init();
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
        // Only show sub-navs the user has permission to view
        // if ($currentUser->can('mux:dashboard') && $currentUser->can('mux:assets')) {
        //     $subNavs['dashboard'] = [
        //         'label' => 'Dashboard',
        //         'url' => 'mux/dashboard',
        //     ];
        // }

        if ($currentUser->can('mux:assets')) {
            $subNavs['assets'] = [
                'label' => 'List',
                'url' => 'mux/assets',
            ];
        }

        $editableSettings = true;
        $general = Craft::$app->getConfig()->getGeneral();
        if (!$general->allowAdminChanges) {
            $editableSettings = false;
        }
        if ($currentUser->can('mux:settings') && $editableSettings) {
            $subNavs['settings'] = [
                'label' => 'Settings',
                'url' => 'mux/settings',
            ];
        }

        if ($currentUser->can('mux:settings') && $editableSettings) {
            $subNavs['restrictions'] = [
                'label' => 'Playback Restrictions',
                'url' => 'mux/restrictions',
            ];
        }

        if ($currentUser->can('mux:settings') && $editableSettings) {
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
                    'permissions' => [
                        'mux-createAssets' => ['label' => Craft::t('mux', 'Create assets')],
                        'mux-deleteAssets' => ['label' => Craft::t('mux', 'Delete assets')],
                        'mux-editAssets' => ['label' => Craft::t('mux', 'Manage all Assets'), 'info' => Craft::t('mux', 'This user will be able to manage all Mux assets.')]
                    ]
                ];
            }
        );
    }

    protected function _registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['mux'] = ['template' => 'mux/elements/_index.twig'];
                //$event->rules['mux/dashboard'] = 'mux/assets/dashboard';
                $event->rules['mux/settings'] = 'mux/settings/plugin-settings';
                $event->rules['mux/restrictions'] = ['template' => 'mux/settings/restrictions'];
                $event->rules['mux/signed-keys'] = ['template' => 'mux/settings/signedKeys'];
                $event->rules['mux/assets'] = ['template' => 'mux/elements/_index.twig'];
                $event->rules['mux/assets/<elementId:\d+>'] =  'elements/edit'; //['template' => 'mux/elements/_edit.twig'];
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
        return [
            'mux:assets' => [
                'label' => Craft::t('mux', 'Assets'),
            ],
            'mux:dashboard' => [
                'label' => Craft::t('mux', 'Dashboard'),
            ],
            'mux:assets-edit' => [
                'label' => Craft::t('mux', 'Edit Assets'),
            ],
            'mux:settings' => [
                'label' => Craft::t('mux', 'Settings'),
            ],
        ];
    }
}
