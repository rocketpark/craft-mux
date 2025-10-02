<?php

namespace rocketpark\mux\twigextensions;

use rocketpark\mux\Mux;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class MuxTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return [
            'mux' => Mux::$plugin,
        ];
    }
}
