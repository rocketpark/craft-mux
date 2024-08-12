<?php

namespace rocketpark\mux\gql\types;

use Craft;
use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;

/**
 * Class TrackType
 *
 * @author    rocketpakr
 * @package   Mux
 */
class TrackType extends ObjectType
{
    /**
     * @var string
     */
    public $name = 'Track';

    /**
     * @var string
     */
    public $description = 'Mux Asset Track Type';

    /**
     * @inheritdoc
     */
    public function __construct($config)
    {
        $config = array_merge($config, [
            'name' => self::getName(),
            'description' => $this->description,
            'fields' => self::getFieldDefinition(),
        ]);
        parent::__construct($config);
    }

    /**
     * Returns a singleton instance to ensure one type per schema.
     *
     * @return TrackType
     */
    public static function getType(): TrackType
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([]));
    }

    /**
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'Track';
    }

    /**
     * Define fields for this type.
     *
     * @return array
     */
    public static function getFieldDefinition(): array
    {
        $fields = [
            'id' => [
                'type' => Type::string(),
                'description' => 'Track ID'
            ],
            'type' => Type::string(),
            'text_source' => Type::string(),
            'text_type' => Type::string(),
            'language_code' => Type::string(),
            'name' => Type::string(),
            'closed_captions' => Type::string(),
            'duration' => [
                'type' => Type::float(),
                'description' => 'The duration of the track in seconds'
            ],
            'max_width' => [
                'type' => Type::int(),
                'description' => 'The maximum video bitrate of the track'
            ],
            'max_height' => [
                'type' => Type::int(),
                'description' => 'The maximum video bitrate of the track'
            ],
            'max_frame_rate' => [
                'type' => Type::int(),
                'description' => 'The maximum video bitrate of the track'
            ]
        ];

        return Craft::$app->getGql()->prepareFieldDefinitions($fields, self::getName());
    }
}
