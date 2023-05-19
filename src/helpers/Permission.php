<?php

/**
 * Mux plugin for Craft CMS
 *
 *
 * @link      https://rocketpark.com/
 * @copyright Copyright (c) 2022 rocketpark
 */

namespace rocketpark\mux\helpers;

use Craft;
use yii\web\ForbiddenHttpException;

/**
 * @author    rocketpark
 * @package   Mux
 * @since     1.0.0
 */
class Permission
{
    // Constants
    // =========================================================================

    // Public Methods
    // =========================================================================

    /**
     * @param string $permission
     *
     * @throws ForbiddenHttpException
     */
    public static function controllerPermissionCheck(string $permission): void
    {
        if (($currentUser = Craft::$app->getUser()->getIdentity()) === null) {
            throw new ForbiddenHttpException('Your account has no identity.');
        }

        if (!$currentUser->can($permission)) {
            throw new ForbiddenHttpException("Your account doesn't have permission to assign access this resource.");
        }
    }

    // Protected Static Methods
    // =========================================================================
}
