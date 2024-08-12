<?php 

namespace rocketpark\mux\gql\arguments\elements;

use Craft;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\gql\base\ElementArguments;
use craft\gql\types\Query;
use craft\gql\types\QueryArgument;
use GraphQL\Type\Definition\Type;
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;

/**
 * Class MuxAsset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class MuxAsset extends ElementArguments
{

    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'id' => [
                'name' => 'id',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the mux asset’ id.',
            ],
            'asset_id' => [
                'name' => 'asset_id',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the mux asset’ asset_id.',
            ],
            'asset_status' => [
                'name' => 'asset_status',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the mux asset’ asset_status.',
            ],
            'aspect_ratio' => [
                'name' => 'aspect_ratio',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the mux asset’ aspect_ratio.',
            ],
            'duration' => [
                'name' => 'duration',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the mux asset’ duration.',
            ],
            'is_live' => [
                'name' => 'is_live',
                'type' => Type::listOf(Type::boolean()),
                'description' => 'Narrows the query results based on the mux asset’ is_live.',
            ],
            'upload_id' => [
                'name' => 'upload_id',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the mux asset’ upload_id.',
            ],
            'playback_ids' => [
                'name' => 'playback_ids',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the mux asset’ playback_ids.',
            ],
            'tracks' => [
                'name' => 'tracks',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the mux asset’ tracks.',
            ],
            'passthrough' => [
                'name' => 'passthrough',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the mux asset’ passthrough.',
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getContentArguments(): array
    {
        $contentArguments = [];

        $contentFields = Craft::$app->getFields()->getLayoutByType(MuxAssetElement::class)->getCustomFields();

        foreach ($contentFields as $contentField) {
            if (!$contentField instanceof GqlInlineFragmentFieldInterface) {
                $contentArguments[$contentField->handle] = $contentField->getContentGqlQueryArgumentType();
            }
        }

        return array_merge(parent::getContentArguments(), $contentArguments);
    }
}