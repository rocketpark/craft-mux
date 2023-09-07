<?php

namespace rocketpark\mux\services;

use Craft;
use yii\base\Component;

use rocketpark\mux\Mux;
use GuzzleHttp\Client;
use craft\helpers\UrlHelper;
use craft\helpers\App;
use InvalidArgumentException;
use rocketpark\mux\records\SignedKeys as SignedKeysRecord;
use rocketpark\mux\models\SignedKey;
use MuxPhp;
use MuxPhp\ApiException;
use MuxPhp\Configuration;
use MuxPhp\Api\SigningKeysApi;
use MuxPhp\Models\Asset;
use MuxPhp\Models\AssetResponse;
use MuxPhp\Models\ListAssetsResponse;
use MuxPhp\Models\ListSigningKeysResponse;
use MuxPhp\Models\Upload;
use MuxPhp\Models\SigningKey;
use MuxPhp\Models\SigningKeyResponse;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Signed Keys service
 */
class SignedKeys extends Component
{

    /**
     * Creates MUX API Configuration
     * @return Configuration 
     * @throws BaseInvalidArgumentException 
    */
    private function muxConf(): MuxPhp\Configuration
    {
        $settings = Mux::$settings;
        // Authentication Setup
        return MuxPhp\Configuration::getDefaultConfiguration()
            ->setUsername(App::parseEnv($settings->muxTokenId))
            ->setPassword(App::parseEnv($settings->muxTokenSecret));
    }

    /**
     * Created Signed Key
     * @return true[]|(false|string)[] 
     * @throws Exception 
     * @throws ApiException 
     * @throws InvalidArgumentException 
     */
    public function createSignedKey(): array
    {
        $config = $this->muxConf();

        $apiInstance = new SigningKeysApi(
            new Client(),
            $config
        );
        
        try {
            $response = $apiInstance->createSigningKey();
            
            $keyData = $response['data'];

            $signedKey = new SignedKeysRecord(); 
            $signedKey->key_id = $keyData['id'];
            $signedKey->private_key = $keyData['private_key']; 
            $signedKey->created_at = $keyData['created_at'];

            if($signedKey->save()) {
                return [
                    'success' => true,
                    'key' => $signedKey->findOne(['key_id' == $keyData['id'] ])
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Could not save Signed Key Record for: '. $keyData['id']
                ];
            }

            
        } catch (Exception $e) {
            throw new Exception("Exception when calling SigningKeysApi->createSigningKey: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete Signed Key
     * @param null|string $signing_key_id 
     * @return true[]|(false|string)[] 
     * @throws Exception 
     * @throws ApiException 
     * @throws InvalidArgumentException 
     */
    public function deleteSignedKey(string $signing_key_id): array
    {
        $config = Mux::$plugin->signedKeys->muxConf(); 

        $apiInstance = new SigningKeysApi(
            new Client(),
            $config
        );

        try {
            $apiInstance->deleteSigningKey($signing_key_id);
            $record = SignedKeysRecord::find()->where(['key_id' => $signing_key_id])->one();
            $record->delete();

            return ['success' => true];
        } catch (Exception $e) {
            throw new Exception("Exception when calling SigningKeysApi->deleteSigningKey: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List Signed Keys
     * @return array 
     * @throws InvalidConfigException 
     */
    public function listSignedKeys(?int $limit, ?int $page): array
    {
        $query = SignedKeysRecord::find();

        if (isset($limit) && isset($page)) {
            $offset = ($page - 1) * $limit;
            $query->limit($limit)->offset($offset);
        }

        return $query->all();
    }

    /**
     * Get Signed Key
     * @param null|string $signed_key_id 
     * @return mixed 
     * @throws InvalidConfigException 
     */
    public function getSignedKey(?string $signed_key_id)
    {
        return SignedKeysRecord::findOne($signed_key_id);
    }


    /**
     * Get Mux Signed Key
     * @param null|string $signing_key_id 
     * @return (true|SigningKeyResponse)[]|(false|string)[] 
     * @throws Exception 
     * @throws ApiException 
     * @throws InvalidArgumentException 
     */
    public function getMuxSignedKey(?string $signing_key_id): array
    {
        $config = Mux::$plugin->signedKeys->muxConf();

        $apiInstance = new SigningKeysApi(
            new Client(),
            $config
        );

        try {
            $result = $apiInstance->getSigningKey($signing_key_id);

            return [
                'success' => true,
                'signedKey' => $result
            ];
        } catch (Exception $e) {
            throw new Exception("Exception when calling SigningKeysApi->getSigningKey: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List Mux Signing Keys
     * @param null|int $page defaults to 1
     * @param null|int $limit defaults to 50
     * @return (true|ListSigningKeysResponse)[]|(false|string)[] 
     * @throws Exception 
     * @throws ApiException 
     * @throws InvalidArgumentException 
     */
    public function listMuxSigningKey(?int $limit = 50, ?int $page = 1): array
    {
        $config = Mux::$plugin->signedKeys->muxConf();

        $apiInstance = new SigningKeysApi(
            new Client(),
            $config
        );

        try {
            $result = $apiInstance->listSigningKeys($limit, $page);
            return [
                'success' => true,
                'signedKeys' => $result
            ];
        } catch (Exception $e) {
            throw new Exception("Exception when calling SigningKeysApi->listSigningKeys: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
