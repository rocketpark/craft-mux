<?php

namespace rocketpark\mux\gql\types;

use Craft;
use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;

/**
 * Class MetaType
 *
 * @author    rocketpakr
 * @package   Mux
 */
class MetaType extends ObjectType
{
    /**
     * @var string
     */
    public $name = 'Meta';

    /**
     * @var string
     */
    public $description = 'Mux Asset Meta data';

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
     * @return MetaType
     */
    public static function getType(): MetaType
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([]));
    }

    /**
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'Meta';
    }

    /**
     * Define fields for this type.
     *
     * @return array
     */
    public static function getFieldDefinition(): array
    {
        $fields = [
            'title' => [
                'type' => Type::string(),
                'description' => 'Title of the asset'
            ],
            'external_id' => [
                'type' => Type::string(),
                'description' => 'External Id of the asset'
            ],
            'creator_id' => [
                'type' => Type::string(),
                'description' => 'Creator Id of the asset'
            ],
        ];

        return Craft::$app->getGql()->prepareFieldDefinitions($fields, self::getName());
    }
}
