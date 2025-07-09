<?php
/**
 * @link https://rocketpark.com/
 * @copyright Copyright (c) RocketPark LLC.
 * @license https://rocketpark.com/license/
 */

namespace rocketpark\mux\fields;

use Craft;
use craft\base\ElementInterface;
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;
use rocketpark\mux\elements\db\MuxAssetQuery;
use rocketpark\mux\helpers\Gql as GqlHelper;
use rocketpark\mux\gql\interfaces\elements\MuxAsset as MuxAssetInterface;
use rocketpark\mux\gql\resolvers\elements\MuxAsset as MuxAssetResolver;
use rocketpark\mux\gql\types\elements\MuxAsset as MuxAssetType;

use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\errors\InvalidFsException;
use craft\errors\InvalidSubpathException;
use craft\fields\BaseRelationField;

use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\models\GqlSchema;
use craft\helpers\Gql;
use craft\services\Gql as GqlService;

use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Twig\Error\RuntimeError;
use yii\base\InvalidConfigException;

/**
 * MuxAssets represents an MuxAssets field.
 *
 * @author RocketPark LLC. <support@rocketpark.com>
 * @since 3.0.0
 */
class MuxAsset extends BaseRelationField
{

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('mux', 'Mux Asset');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('mux', 'mux asset');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('mux', 'mux assets');
    }

    /**
     * @inheritdoc
     */
    public static function elementType(): string
    {
        return MuxAssetElement::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('mux', 'Add an mux asset');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', MuxAssetQuery::class, ElementCollection::class, MuxAssetElement::class);
    }

    /**
     * @var bool Whether it should be possible to upload files directly to the field.
     * @since 2.1.0
     */
    public bool $allowUploads = true;

    /**
     * @inheritdoc
     */
    protected string $settingsTemplate = 'mux/fields/_settings.twig';

    /**
     * @inheritdoc
     */
    protected string $inputTemplate = 'mux/fields/_input.twig';

    
    public function getSourceOptions(): array
    {
        return [];
    }

    public function modifyElementsQuery(ElementQueryInterface $query, mixed $value): void
    {
       return;
    }

    /**
     * @inheritdoc
     */
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return GqlHelper::canQueryMuxAssets($schema);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(MuxAssetInterface::getType())),
            'resolve' => MuxAssetResolver::class . '::resolve',
            'complexity' => Gql::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function inputTemplateVariables(null|array|ElementQueryInterface $value = null, ?ElementInterface $element = null): array
    {
        $variables = parent::inputTemplateVariables($value, $element);
        $variables['canUpload'] = (
            $this->allowUploads &&
            Craft::$app->getUser()->checkPermission("mux:assets-create")
        );

        return $variables;
    }

    // Events
    // -------------------------------------------------------------------------


}
