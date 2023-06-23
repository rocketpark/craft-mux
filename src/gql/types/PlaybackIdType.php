<?php

namespace rocketpark\mux\gql\types;

use Craft;
use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;

/**
 * Class PlaybackIdType
 *
 * @author    rocketpakr
 * @package   Mux
 */
class PlaybackIdType extends ObjectType
{
    /**
     * @var string
     */
    public $name = 'PlaybackID';

    /**
     * @var string
     */
    public $description = 'Mux Asset Playback Ids';

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
     * @return PlaybackIdType
     */
    public static function getType(): PlaybackIdType
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([]));
    }

    /**
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'PlaybackID';
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
                'description' => 'ID of the playback'
            ],
            'policy' => [
                'type' => Type::string(),
                'description' => 'Policy of the playback'
            ]
        ];

        return Craft::$app->getGql()->prepareFieldDefinitions($fields, self::getName());
    }
}
