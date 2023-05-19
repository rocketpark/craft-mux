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


    // Public Methods
    // =========================================================================
    public static function canQueryMuxAssets(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();
        return true; //isset($allowedEntities['muxAssets']);
    }


    // Private Methods
    // =========================================================================


 }