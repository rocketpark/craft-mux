<?php
namespace rocketpark\mux\gql\types\elements;

use craft\gql\types\elements\Element;
use GraphQL\Type\Definition\ResolveInfo;
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

    /**
     * @inheritdoc
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if ($resolveInfo->fieldName === 'playback_ids') {
            $playbackIds = array_values(array_filter((array) ($source->playback_ids ?? []), 'is_array'));
            $policy = $arguments['policy'] ?? null;

            if ($policy === null || $policy === '') {
                return $playbackIds;
            }

            return array_values(array_filter($playbackIds, fn(array $entry) => ($entry['policy'] ?? null) === $policy));
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
