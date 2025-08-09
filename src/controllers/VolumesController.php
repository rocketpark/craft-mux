<?php

namespace rocketpark\mux\controllers;

use Craft;
use craft\web\Controller;
use rocketpark\mux\models\MuxVolume;
use rocketpark\mux\Mux;

class VolumesController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;
    
    /**
     * Get all volumes.
     * @return JsonResponse
     */
    public function actionGetAll()
    {
        try {
            $volumes = Mux::$plugin->volumes->getAllVolumes();
            
            return $this->asJson(['success' => true, 'volumes' => $volumes]);
        } catch (\Exception $e) {
            return $this->asJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Create a new volume.
     * @requestParams $params['name']
     * @requestParams $params['handle']
     * @return JsonResponse
     */
    public function actionCreate()
    {
        $this->requirePostRequest();
        
        $request = Craft::$app->getRequest();

        $name = $request->getBodyParam('name');
        $handle = $request->getBodyParam('handle');

        // Create the volume model
        $volume = new MuxVolume([
            'name' => $name,
            'handle' => $handle,
        ]);

        // Try to save the volume with validation enabled
        if (Mux::$plugin->volumes->saveVolume($volume, true)) {
            return $this->asJson(['success' => true, 'volume' => $volume]);
        } else {
            // Validation failed - return the validation errors
            $errors = [];
            foreach ($volume->getErrors() as $attribute => $attributeErrors) {
                $errors[$attribute] = $attributeErrors;
            }
            
            return $this->asJson([
                'success' => false, 
                'message' => Craft::t('mux', 'Could not create volume.'),
                'errors' => $errors
            ]);
        }
    }

    /**
     * Delete a volume by its UID.
     * @requestParams $params['volumeUid']
     * @return JsonResponse
     */
    public function actionDelete()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $volumeUid = $request->getBodyParam('volumeUid');
        
        $volume = Mux::$plugin->volumes->getVolumeByUid($volumeUid);
        if (!$volume) {
            return $this->asJson(['success' => false, 'message' => Craft::t('mux', 'Volume not found.')]);
        }

        if (Mux::$plugin->volumes->deleteVolume($volume)) {
            return $this->asJson(['success' => true, 'message' => Craft::t('mux', 'Volume deleted.')]);
        }
    }
}