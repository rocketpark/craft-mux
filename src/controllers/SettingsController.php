<?php

namespace rocketpark\mux\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use rocketpark\mux\helpers\Permission as PermissionHelper;
use rocketpark\mux\models\Settings;
use rocketpark\mux\Mux;
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

    protected const DOCUMENTATION_URL = 'https://github.com/rocketpark/craft-mux/';

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
        return $this->asJson(Mux::$plugin->playbackRestrictions->listPlaybackRestrictions($page, $limit));
    }

    public function actionCreatePlaybackRestriction()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();
       //\yii\helpers\VarDumper::dump(json_encode($params['request']), 5, true);exit;
        return $this->asJson(Mux::$plugin->playbackRestrictions->createPlaybackRestriction($params['request']));
    }

    public function actionDeletePlaybackRestriction()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();
        return $this->asJson(Mux::$plugin->playbackRestrictions->deletePlaybackRestriction($params['id']));
    }

    public function actionUpdateReferrerDomainRestriction()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();
        return $this->asJson(Mux::$plugin->playbackRestrictions->updateReferrerDomainRestriction($params['id'], $params['referrer']));
    }
}
