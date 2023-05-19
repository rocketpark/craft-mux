<?php
namespace rocketpark\mux\gql\types\elements;

use craft\gql\types\elements\Element;
use rocketpark\mux\gql\interfaces\elements\MuxAsset as MuxAssetInterface;

class MuxAsset extends Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
           MuxAssetInterface::getType(),
        ];

        parent::__construct($config);
    }
}