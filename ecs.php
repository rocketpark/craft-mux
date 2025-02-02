<?php

declare(strict_types=1);

use craftecs\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __FILE__,
    ]);

    $ecsConfig->sets([
        SetList::CRAFT_CMS_4,
    ]);
};
