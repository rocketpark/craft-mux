<?php
namespace rocketpark\mux\gql\resolvers\elements;

use craft\gql\base\ElementResolver;
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;
use rocketpark\mux\elements\db\MuxAssetQuery;
use rocketpark\mux\helpers\Gql as GqlHelper;
use craft\helpers\Gql;
use GraphQL\Type\Definition\ResolveInfo;

class MuxAsset extends ElementResolver
{
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        if ($source === null) {
            // If this is the beginning of a resolver chain, start fresh
            $query = MuxAssetElement::find();
        } else {
            // If not, get the prepared element query
            $query = $source->$fieldName;
        }

        if (!$query instanceof MuxAssetQuery) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        // Don’t return anything that’s not allowed
        if (!GqlHelper::canQueryMuxAssets()) {
            return [];
        }

        return $query;
    }

    // public static function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    // {
    //     $query = self::prepareElementQuery($source, $arguments, $context, $resolveInfo);
    //     $value = $query instanceof MuxAssetQuery ? $query->one() : $query;

    //     return Gql::applyDirectives($source, $resolveInfo, $value);
    // }
}