<?php

namespace rocketpark\mux\gql\queries;

use GraphQL\Type\Definition\Type;
use rocketpark\mux\helpers\Gql as GqlHelper;
use rocketpark\mux\gql\interfaces\elements\MuxAsset as MuxAssetInterface;
//use rocketpark\mux\gql\arguments\elements\MuxAsset as MuxAssetArguments;
use rocketpark\mux\gql\resolvers\elements\MuxAsset as MuxAssetResolver;

class MuxAsset extends \craft\gql\base\Query
{
    public static function getQueries($checkToken = true): array
    {
        // Make sure the current token’s schema allows querying muxAssets
        if ($checkToken && !GqlHelper::canQueryMuxAssets()) {
            return [];
        }

        // Provide one or more query definitions
        return [
            'muxAssets' => [
                'type' => Type::listOf(MuxAssetInterface::getType()),
                //'args' => MuxAssetArguments::getArguments(),
                'resolve' => MuxAssetResolver::class . '::resolve',
                'description' => 'This query is used to query for custom MUX assets.'
            ],
        ];
    }
}