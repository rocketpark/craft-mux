<?php
namespace rocketpark\mux\gql\types\elements;

use craft\gql\types\elements\Element;
use craft\helpers\Json;
use GraphQL\Type\Definition\ResolveInfo;
use rocketpark\mux\gql\interfaces\elements\MuxAsset as MuxAssetInterface;

class MuxAsset extends Element
{
    /**
     * Fields declared as `String` in the schema whose element properties are arrays (decoded
     * JSON columns). graphql-php's String type can't serialize an array, so these are returned
     * as JSON-encoded strings. The element properties stay arrays for Twig.
     */
    public const JSON_STRING_FIELDS = [
        'static_renditions',
        'master',
        'recording_times',
        'non_standard_input_reasons',
    ];

    public function __construct(array $config)
    {
        $config['interfaces'] = [
           MuxAssetInterface::getType(),
        ];

        parent::__construct($config);
    }

    /**
     * Returns a value as a JSON-encoded string when it is an array, leaving strings and `null` as they are.
     */
    public static function toJsonString(mixed $value): mixed
    {
        return is_array($value) ? Json::encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $value;
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

        if (in_array($resolveInfo->fieldName, self::JSON_STRING_FIELDS, true)) {
            return self::toJsonString($source->{$resolveInfo->fieldName} ?? null);
        }

        // The schema field name is misspelled; the element property is `normalize_audio`.
        // Without this mapping the field throws "Getting unknown property" on every asset.
        if ($resolveInfo->fieldName === 'nomralize_audio') {
            return $source->normalize_audio ?? null;
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
