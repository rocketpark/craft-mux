<?php

namespace rocketpark\mux\controllers;

use Craft;
use craft\web\Controller;
use rocketpark\mux\Mux;
use yii\web\Response;

/**
 * Signing Keys Controller controller
 */
class SigningKeysController extends Controller
{
    // Protected Properties
    // =========================================================================

    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    // Public Methods
    // =========================================================================

    /**
     * Creeate Signed Key
     * @return Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionCreateSignedKey():response
    {
        $this->requirePostRequest();
        return $this->asJson(Mux::$plugin->signedKeys->createSignedKey());
    }

    /**
     * Delete Signed Key
     * @param string signed_key_id
     * @return Response
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDeleteSignedKey():response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();
        return $this->asJson(Mux::$plugin->signedKeys->deleteSignedKey($params['signed_key_id']));
    }

    /**
     * Get Signed Key
     * @param string signed_key_id
     * @return Response
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetSignedKey():response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();
        return $this->asJson(Mux::$plugin->signedKeys->getSignedKey($params['signed_key_id']));
    }

    /**
     * List Signed Keys
     * @param null|int $limit defaults to 50
     * @param null|int $page defaults to 1
     * @return Response
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionListSignedKeys():response
    {
        $request = Craft::$app->getRequest();
        $params = $request->getBodyParams();
        $limit = isset($params['limit']) ? $params['limit'] : null;
        $page = isset($params['page']) ? $params['page'] : null;
        return $this->asJson(Mux::$plugin->signedKeys->listSignedKeys($limit, $page));
    }
}
