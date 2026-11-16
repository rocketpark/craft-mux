<?php

namespace rocketpark\mux\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\helpers\App;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\UrlHelper;
use craft\services\ProjectConfig;
use craft\web\Controller;

use rocketpark\mux\helpers\Permission as PermissionHelper;
use rocketpark\mux\models\Settings;
use rocketpark\mux\Mux;
use rocketpark\mux\constants\Languages;
use rocketpark\mux\constants\StaticRenditions;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Settings controller
 */
class SettingsController extends Controller
{


    // Constants
    // =========================================================================

    protected const DOCUMENTATION_URL = 'https://bitbucket.com/rocketpark/craft-mux/';

    // Protected Properties
    // =========================================================================

    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    // Public Methods
    // =========================================================================

    /**
     * Plugin settings
     *
     * @param Settings|bool|null $settings
     *
     * @return Response The rendered result
     * @throws ForbiddenHttpException
     */
    public function actionPluginSettings(Settings|bool|null $settings = null): Response
    {
        $variables = [];
        PermissionHelper::controllerPermissionCheck('mux:settings');
        if ($settings === null) {
            $settings = Mux::$settings;
        }

        /** @var Settings $settings */
        $pluginName = $settings->pluginName;
        $templateTitle = Craft::t('mux', 'Settings');
        $view = Craft::$app->getView();

        // Basic variables
        $variables['fullPageForm'] = true;
        $variables['docsUrl'] = self::DOCUMENTATION_URL;
        $variables['pluginName'] = $pluginName;
        $variables['title'] = $templateTitle;
        $variables['crumbs'] = [
            [
                'label' => $pluginName,
                'url' => UrlHelper::cpUrl('mux'),
            ],
            [
                'label' => $templateTitle,
                'url' => UrlHelper::cpUrl('mux/settings'),
            ],
        ];
        $variables['docTitle'] = "{$pluginName} - {$templateTitle}";
        $variables['selectedSubnavItem'] = 'settings';
        $variables['settings'] = $settings;
        $variables['subtitleLanguages'] = Languages::getSubtitleGenerationOptions();
        $variables['renditionOptions'] = StaticRenditions::getCheckboxOptions();
        $variables['specificResolutions'] = StaticRenditions::SPECIFIC_RESOLUTIONS;

        // Playback restrictions in this Mux environment, offered as suggestions for the default
        $restrictions = Mux::$plugin->playbackRestrictions->getRestrictionSummaries();
        $variables['restrictionSuggestions'] = Mux::$plugin->playbackRestrictions->getRestrictionSuggestions($restrictions ?? []);
        $variables['defaultRestriction'] = Mux::$plugin->signedPlayback->getDefaultPlaybackRestrictionInfo();
        $variables['defaultRestrictionMissing'] = $restrictions !== null
            && $variables['defaultRestriction']['id'] !== null
            && !in_array($variables['defaultRestriction']['id'], array_column($restrictions, 'id'), true);

        // Render the template
        return $this->renderTemplate('mux/settings/index.twig', $variables);
    }

    /**
     * Saves a plugin’s settings.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested plugin cannot be found
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     * @throws ForbiddenHttpException
     */
    public function actionSavePluginSettings(): ?Response
    {
        PermissionHelper::controllerPermissionCheck('retour:settings');
        $this->requirePostRequest();
        $pluginHandle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');
        $settings = Craft::$app->getRequest()->getBodyParam('settings', []);
        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);


        if ($plugin === null) {
            throw new NotFoundHttpException('Plugin not found');
        }

        if (!Mux::$plugin->settings->saveSettings($plugin, $settings)) {
            Craft::$app->getSession()->setError(Craft::t('mux', "Couldn't save plugin settings."));

            // Send the plugin back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'plugin' => $plugin,
            ]);
            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('mux', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl();
    }


    /**
     * Use Playback Restrictions List
     * @param int|null $page
     * @param int|null $limit
     * @return array
     */
    public function actionUsePlaybackRestrictionsList(?int $page = 1, ?int $limit = 100)
    {
        PermissionHelper::controllerPermissionCheck('mux:settings');
        return $this->muxJson(fn() => Mux::$plugin->playbackRestrictions->listPlaybackRestrictions($page, $limit));
    }

    public function actionCreatePlaybackRestriction()
    {
        PermissionHelper::controllerPermissionCheck('mux:settings');
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();
       //\yii\helpers\VarDumper::dump(json_encode($params['request']), 5, true);exit;
        return $this->muxJson(fn() => Mux::$plugin->playbackRestrictions->createPlaybackRestriction($params['request'] ?? []));
    }

    public function actionDeletePlaybackRestriction()
    {
        PermissionHelper::controllerPermissionCheck('mux:settings');
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();
        return $this->muxJson(fn() => Mux::$plugin->playbackRestrictions->deletePlaybackRestriction($params['id'] ?? null));
    }

    public function actionUpdateReferrerDomainRestriction()
    {
        PermissionHelper::controllerPermissionCheck('mux:settings');
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();
        return $this->muxJson(fn() => Mux::$plugin->playbackRestrictions->updateReferrerDomainRestriction($params['id'] ?? null, $params['referrer'] ?? []));
    }

    /**
     * Returns where the default playback restriction comes from (for the Playback Restrictions page).
     */
    public function actionDefaultPlaybackRestriction(): Response
    {
        PermissionHelper::controllerPermissionCheck('mux:settings');

        return $this->asJson([
            'success' => true,
            'default' => Mux::$plugin->signedPlayback->getDefaultPlaybackRestrictionInfo(),
        ]);
    }

    /**
     * Makes a playback restriction the default for signed playback tokens, or clears the default (`id` = '').
     *
     * Refused when the default comes from `config/mux.php` or an environment variable, which must be changed there.
     */
    public function actionSetDefaultPlaybackRestriction(): Response
    {
        PermissionHelper::controllerPermissionCheck('mux:settings');
        $this->requirePostRequest();

        $id = trim((string) Craft::$app->getRequest()->getBodyParam('id', ''));
        $info = Mux::$plugin->signedPlayback->getDefaultPlaybackRestrictionInfo();

        if (in_array($info['source'], ['config', 'env'], true)) {
            return $this->asJson([
                'success' => false,
                'error' => $info['source'] === 'config'
                    ? Craft::t('mux', 'The default playback restriction is set in config/mux.php. Change it there.')
                    : Craft::t('mux', 'The default playback restriction comes from {var}. Change that environment variable instead.', ['var' => $info['raw']]),
                'default' => $info,
            ]);
        }

        if ($id !== '' && !preg_match('/^[A-Za-z0-9]+$/', $id)) {
            return $this->asJson(['success' => false, 'error' => Craft::t('mux', 'Invalid playback restriction ID.'), 'default' => $info]);
        }

        $plugin = Craft::$app->getPlugins()->getPlugin('mux');

        // savePluginSettings() writes only the keys it is given to project config, replacing the stored
        // settings. Start from everything that is stored, so only playbackRestrictionId changes.
        $stored = ProjectConfigHelper::unpackAssociativeArrays(
            Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_PLUGINS . '.mux.settings') ?? []
        );
        $settings = array_merge($stored, ['playbackRestrictionId' => $id]);

        if (!$plugin || !Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            return $this->asJson(['success' => false, 'error' => Craft::t('mux', 'Couldn’t save plugin settings.'), 'default' => $info]);
        }

        return $this->asJson([
            'success' => true,
            'default' => Mux::$plugin->signedPlayback->getDefaultPlaybackRestrictionInfo(),
        ]);
    }

    /**
     * Runs a Mux API call and returns its result as JSON. Failures return `{success: false, error}` with
     * a 502 status instead of an HTML error page, so the control panel can show what went wrong.
     */
    private function muxJson(callable $call): Response
    {
        try {
            return $this->asJson($call());
        } catch (\Throwable $e) {
            Mux::error('Mux playback restriction request failed: ' . $e->getMessage());

            $response = $this->asJson([
                'success' => false,
                'error' => $this->describeMuxError($e),
            ]);
            $response->setStatusCode(502);

            return $response;
        }
    }

    /**
     * Turns a Mux API exception into a message for the control panel.
     */
    private function describeMuxError(\Throwable $e): string
    {
        if (!$e instanceof \MuxPhp\ApiException) {
            return $e->getMessage();
        }

        $body = json_decode((string) $e->getResponseBody(), true);
        $muxMessage = implode(' ', (array) ($body['error']['messages'] ?? [])) ?: $e->getMessage();

        if (in_array($e->getCode(), [401, 403], true)) {
            $tokenId = trim((string) App::parseEnv(Mux::$settings->muxTokenId ?? ''));
            $tokenSecret = trim((string) App::parseEnv(Mux::$settings->muxTokenSecret ?? ''));

            if ($tokenId === '' || $tokenSecret === '') {
                return Craft::t('mux', 'The Mux Token ID or Token Secret is empty. Check Mux → Settings and the environment variables they reference.');
            }

            return Craft::t('mux', 'Mux refused the API credentials ({code}: {message}). Check the access token for this Mux environment in the Mux dashboard under Settings → Access Tokens.', [
                'code' => $e->getCode(),
                'message' => $muxMessage,
            ]);
        }

        return Craft::t('mux', 'Mux returned an error ({code}): {message}', [
            'code' => $e->getCode(),
            'message' => $muxMessage,
        ]);
    }
}
