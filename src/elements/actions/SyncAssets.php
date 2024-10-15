<?php

namespace rocketpark\mux\elements\actions;

use Craft;
use craft\base\ElementAction;

/**
 * Sync represents a Sync asset action.
 *
 * @author Rocket Park, LLC. <support@rocketpark.com>
 */
class SyncAssets extends ElementAction
{

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('mux', 'Sync MUX Assets');
    }

    public function getMessage(): ?string
    {
        return Craft::t('mux', 'All pending MUX assets have been synced');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(function($actionClass) {
            return <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $actionClass,
        bulk: true,
        requireId: false,
        validateSelection: (\$selectedItems) => {

            const \$elements = \$selectedItems.find('.element');
            for (let i = 0; i < \$elements.length; i++) {
                const \$element = \$elements.eq(i);
                if (!Garnish.hasAttr(\$element, 'data-mux-asset-status') || 
                    \$element.attr('data-mux-asset-status') === 'ready') {
                    return false;
                }
            }

            return true;
        },
        activate: (\$selectedItems) => {
            Craft.elementIndex.setIndexBusy();

            for (let i = 0; i < \$selectedItems.length; i++) {
                const \$element = \$selectedItems.eq(i).find('.element');
                const muxAssetId = \$element.attr('data-mux-asset-id');
                const muxAssetStatus = \$element.data('data-mux-asset-status');

                const data = {
                    assetId: muxAssetId
                };

                Craft.sendActionRequest('POST', 'mux/assets/sync-asset-by-id', {data})
                .then(response => {
                    if (response.data.conflict) {
                        alert(response.data.conflict);
                        this.activate(\$selectedItems);
                        return;
                    }

                    if (response.data.success) {
                        Craft.elementIndex.updateElements();
                    }
                })
                .catch(({response}) => {
                    alert(response.data.message)
                })
                // .finally(() => {
                    Craft.elementIndex.setIndexAvailable();
                // });
            }

            Craft.elementIndex.setIndexAvailable();
        },
    });
})();
JS;
        }, [
            static::class,
        ]);

        return null;
    }
}
