<?php

namespace rocketpark\mux\elements\conditions;

use Craft;
use craft\elements\conditions\ElementCondition;

/**
 * Mux Asset condition
 */
class MuxAssetCondition extends ElementCondition
{
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            // ...
        ]);
    }
}
