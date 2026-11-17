<?php

/**
 * Mux plugin for Craft CMS
 *
 *
 * @link      https://rocketpark.com/
 * @copyright Copyright (c) 2023 rocketpark
 */

 namespace rocketpark\mux\helpers;

 use Craft;
 use yii\web\ForbiddenHttpException;
 use craft\helpers\Gql as GqlHelper;
 
 /**
  * @author    rocketpark
  * @package   Mux
  * @since     1.0.0
  */
 class Gql extends GqlHelper {

    // Constants
    // =========================================================================

    /**
     * Schema component that allows a schema to receive signed playback tokens and signed URLs.
     */
    public const SIGNED_PLAYBACK_COMPONENT = 'muxSignedPlayback';


    // Public Methods
    // =========================================================================
    public static function canQueryMuxAssets(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();
        return true; //isset($allowedEntities['muxAssets']);
    }


    /**
     * Whether the active schema may receive signed playback tokens and signed URLs.
     */
    public static function canGenerateSignedPlayback(): bool
    {
        return self::canSchema(self::SIGNED_PLAYBACK_COMPONENT, 'read');
    }

    // Private Methods
    // =========================================================================


 }