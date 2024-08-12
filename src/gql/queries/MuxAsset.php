<?php

namespace rocketpark\mux\gql\queries;

use GraphQL\Type\Definition\Type;
use rocketpark\mux\helpers\Gql as GqlHelper;
use rocketpark\mux\gql\interfaces\elements\MuxAsset as MuxAssetInterface;
use rocketpark\mux\gql\arguments\elements\MuxAsset as MuxAssetArguments;
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
                'args' => MuxAssetArguments::getArguments(),
                'resolve' => MuxAssetResolver::class . '::resolve',
                'description' => 'This query is used to query for custom MUX assets.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'muxAssetCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => MuxAssetArguments::getArguments(),
                'resolve' => MuxAssetResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of mux assets.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
            'muxAsset' => [
                'type' => MuxAssetInterface::getType(),
                'args' => MuxAssetArguments::getArguments(),
                'resolve' => MuxAssetResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single mux asset.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
        ];
    }
}