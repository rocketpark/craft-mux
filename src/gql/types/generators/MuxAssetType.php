<?php
namespace rocketpark\mux\gql\types\generators;

use Craft;
use craft\gql\base\Generator;
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;
use rocketpark\mux\gql\types\elements\MuxAsset;
use rocketpark\mux\gql\interfaces\elements\MuxAsset as MuxAssetInterface;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeManager;

class MuxAssetType implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        // MuxAssets have no context
        $type = static::generateType(null);
        return [$type->name => $type];
    }

    public static function generateType(mixed $context): ObjectType
    {
        //$pluginType = new MuxAssetElement();
        $typeName = MuxAssetElement::gqlTypeNameByContext(null);
        $muxAssetFields = MuxAssetInterface::getFieldDefinitions();

        // Return the type if it exists, otherwise create and return it
        return GqlEntityRegistry::getEntity($typeName) ?:
            GqlEntityRegistry::createEntity(
                $typeName,
                new MuxAsset([
                    'name' => $typeName,
                    'fields' => function() use ($muxAssetFields, $typeName) {
                        return Craft::$app->getGql()->prepareFieldDefinitions($muxAssetFields, $typeName);
                    },
                ])
            );
    }
}