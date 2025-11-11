<?php
/**
 * @link https://rocketpark.com/
 * @copyright Copyright (c) RocketPark LLC.
 * @license https://rocketpark.com/license/
 */

namespace rocketpark\mux\fields;

use Craft;
use craft\base\ElementInterface;
use rocketpark\mux\Mux;
use rocketpark\mux\elements\MuxAsset as MuxAssetElement;
use rocketpark\mux\elements\db\MuxAssetQuery;
use rocketpark\mux\helpers\Gql as GqlHelper;
use rocketpark\mux\gql\interfaces\elements\MuxAsset as MuxAssetInterface;
use rocketpark\mux\gql\resolvers\elements\MuxAsset as MuxAssetResolver;
use rocketpark\mux\gql\types\elements\MuxAsset as MuxAssetType;
use rocketpark\mux\models\MuxVolume;
use rocketpark\mux\models\MuxFolder;

use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\errors\InvalidFsException;
use craft\errors\InvalidSubpathException;
use craft\fields\BaseRelationField;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
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
     * @inheritdoc
     */
    public static function icon(): string
    {
        return '@mux/icon';
    }

    /**
     * @var bool Whether assets should be restricted to a single location.
     */
    public bool $restrictLocation = false;

    /**
     * @var string|null The source key where assets can be selected from, if assets are restricted.
     */
    public ?string $restrictedLocationSource = null;

    /**
     * @var string|null The subpath where assets can be selected from, if assets are restricted.
     */
    public ?string $restrictedLocationSubpath = null;

    /**
     * @var bool Whether assets can be selected from subfolders, if assets are restricted.
     */
    public bool $allowSubfolders = false;

     /**
     * @var string|null The subpath where assets should be uploaded to by default, if assets are restricted and subfolders are allowed.
     */
    public ?string $restrictedDefaultUploadSubpath = null;

    /**
     * @var string|null The source where assets should be uploaded by default, if assets aren’t restricted.
     */
    public ?string $defaultUploadLocationSource = null;

    /**
     * @var string|null The subpath where assets should be uploaded by default, if assets aren’t restricted.
     */
    public ?string $defaultUploadLocationSubpath = null;

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

    
    /**
     * @inheritdoc
     */
    public function getSourceOptions(): array
    {
        $sourceOptions = [];

        foreach (MuxAssetElement::sources('settings') as $volume) {
            if (!isset($volume['heading'])) {
                $sourceOptions[] = [
                    'label' => $volume['label'],
                    'value' => $volume['key'],
                ];
            }
        }

        return $sourceOptions;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, mixed $value): void
    {
       return;
    }

    /**
     * Resolve source path for uploading for this field.
     *
     * @param ElementInterface|null $element
     * @return int
     */
    public function resolveDynamicPathToFolderId(?ElementInterface $element = null): int
    {
        return $this->_uploadFolder($element)->id;
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

        // Ensure element is valid before using it
        if ($element === null) {
            // Return early with safe defaults if element is null
            $variables['canUpload'] = false;
            $variables['defaultFolderId'] = null;
            $variables['defaultSource'] = null;
            $variables['defaultSourcePath'] = null;
            return $variables;
        }
        
        $uploadVolume = $this->_uploadVolume();
        $variables['showFolders'] = !$this->restrictLocation || $this->allowSubfolders;
        $variables['canUpload'] = (
            $this->allowUploads &&
            Craft::$app->getUser()->checkPermission("mux:assets-create")
        );

        // Add the field properties that the template needs
        $variables['defaultUploadLocationSource'] = $this->defaultUploadLocationSource;
        $variables['defaultUploadLocationSubpath'] = $this->defaultUploadLocationSubpath;

        // Resolve the folder ID
        $variables['defaultFolderId'] = null;
        if ($this->defaultUploadLocationSource) {
            $volume = $this->_volumeBySourceKey($this->defaultUploadLocationSource);
            if ($volume) {
                if ($this->defaultUploadLocationSubpath) {
                    try {
                        $folder = $this->_findFolder($this->defaultUploadLocationSource, $this->defaultUploadLocationSubpath, false);
                        $variables['defaultFolderId'] = $folder->id;
                    } catch (\Exception $e) {
                        // Use root folder as fallback
                        $variables['defaultFolderId'] = Mux::$plugin->folders->getRootFolderByVolumeId($volume->id)->id;
                    }
                } else {
                    // Use root folder
                    $variables['defaultFolderId'] = Mux::$plugin->folders->getRootFolderByVolumeId($volume->id)->id;
                }
            }
        }

        if ($this->restrictLocation && !$this->allowSubfolders) {
            $variables['showSourcePath'] = false;
        }

        if (!$this->restrictLocation || $this->allowSubfolders) {
            try {
                $uploadFolder = $this->_uploadFolder($element, false);
                if ($uploadFolder->volumeId) {
                    // If the location is restricted, don't go passed the base source folder
                    $baseUploadFolder = $this->restrictLocation ? $this->_uploadFolder($element, false, false) : null;
                    $folders = $this->_folderWithAncestors($uploadFolder, $baseUploadFolder);
                    $variables['defaultSource'] = $this->_sourceKeyByFolder($folders[0]);
                    $variables['defaultSourcePath'] = array_map(function(MuxFolder $folder) {
                        return $folder->getSourcePathInfo();
                    }, $folders);
                }
            } catch (\Exception $e) {
                // If folder resolution fails (e.g., during draft operations), set safe defaults
                $variables['defaultSource'] = null;
                $variables['defaultSourcePath'] = null;
            }
        }

        return $variables;
    }

    /**
     * Returns the upload folder that should be used for an element.
     *
     * @param ElementInterface|null $element
     * @param bool $createDynamicFolders whether missing folders should be created in the process
     * @param bool $resolveSubtreeDefaultLocation Whether the folder should resolve to the default upload location for subtree fields.
     * @return MuxFolder
     * @throws InvalidSubpathException if the folder subpath is not valid
     * @throws InvalidFsException if there's a problem with the field's volume configuration
     */
    private function _uploadFolder(
        ?ElementInterface $element = null,
        bool $createDynamicFolders = true,
        bool $resolveSubtreeDefaultLocation = true,
    ): MuxFolder {
        if ($this->restrictLocation) {
            $uploadVolume = $this->restrictedLocationSource;
            $subpath = $this->restrictedLocationSubpath;

            if ($this->allowSubfolders && $resolveSubtreeDefaultLocation) {
                $subpath = implode('/', ArrayHelper::filterEmptyStringsFromArray(array_map(fn($segment) => trim($segment, '/'), [
                    $subpath ?? '',
                    $this->restrictedDefaultUploadSubpath ?? '',
                ])));
                $settingName = fn() => Craft::t('app', 'Default Upload Location');
            } else {
                $settingName = fn() => Craft::t('app', 'Asset Location');
            }
        } else {
            $uploadVolume = $this->defaultUploadLocationSource;
            $subpath = $this->defaultUploadLocationSubpath;
            $settingName = fn() => Craft::t('app', 'Default Upload Location');
        }

        $assetsService = Mux::$plugin->folders;

        try {
            if (!$uploadVolume) {
                throw new InvalidFsException();
            }

            return $this->_findFolder($uploadVolume, $subpath, $element, $createDynamicFolders);
        } catch (InvalidFsException $e) {
            throw new InvalidFsException(Craft::t('app', 'The {field} field’s {setting} setting is set to an invalid volume.', [
                'field' => $this->name,
                'setting' => $settingName(),
            ]), 0, $e);
        } catch (InvalidSubpathException $e) {
            // If this is a new/disabled/draft element, the subpath probably just contained a token that returned null, like {id}
            // so use the volume's root folder instead
            if (
                $element === null ||
                !$element->id ||
                !$element->enabled ||
                !$createDynamicFolders ||
                ElementHelper::isDraft($element)
            ) {
                // Get the volume and return its root folder instead of a temp folder
                $volume = $this->_volumeBySourceKey($uploadVolume);
                if ($volume) {
                    return $assetsService->getRootFolderByVolumeId($volume->id);
                }
                // Fallback: return null or throw an exception
                throw new InvalidSubpathException($e->subpath, Craft::t('app', 'Could not determine upload folder for the {field} field.', [
                    'field' => $this->name,
                ]), 0, $e);
            }

            // Existing element, so this is just a bad subpath
            throw new InvalidSubpathException($e->subpath, Craft::t('app', 'The {field} field\'s {setting} setting has an invalid subpath ("{subpath}").', [
                'field' => $this->name,
                'setting' => $settingName(),
                'subpath' => $e->subpath,
            ]), 0, $e);
        }
    }

    /**
     * Finds a volume folder by a source key and (dynamic?) subpath.
     *
     * @param string $sourceKey
     * @param string|null $subpath
     * @param ElementInterface|null $element
     * @return MuxFolder
     * @throws InvalidSubpathException if the subpath cannot be parsed in full
     * @throws InvalidFsException if the volume root folder doesn’t exist
     */
    private function _findFolder(string $sourceKey, ?string $subpath): MuxFolder
    {
        // Make sure the volume and root folder actually exist
        $volume = $this->_volumeBySourceKey($sourceKey);
        if (!$volume) {
            throw new InvalidFsException("Invalid source key: $sourceKey");
        }

        $foldersService = Mux::$plugin->folders;
        $rootFolder = $foldersService->getRootFolderByVolumeId($volume->id);

        // Are we looking for the root folder?
        $subpath = trim($subpath ?? '', '/');
        if ($subpath === '') {
            return $rootFolder;
        }

        // No need for dynamic token parsing - just use the subpath as-is
        $folder = $foldersService->findFolder([
            'volumeId' => $volume->id,
            'path' => $subpath . '/',
        ]);

        // Ensure that the folder exists
        if (!$folder) {
            $folder = $foldersService->ensureFolderByFullPathAndVolume($subpath, $volume);
        }

        return $folder;
    }

     /**
     * Returns a volume via its source key.
     */
    public function _volumeBySourceKey(?string $sourceKey): ?MuxVolume
    {
        if (!$sourceKey) {
            return null;
        }

        $parts = explode(':', $sourceKey, 2);

        if (count($parts) !== 2) {
            return null;
        }

        return Mux::$plugin->volumes->getVolumeByUid($parts[1]);
    }

    /**
     * Returns the target upload volume for the field.
     */
    private function _uploadVolume(): ?MuxVolume
    {
        if ($this->restrictLocation) {
            return $this->_volumeBySourceKey($this->restrictedLocationSource);
        }

        return $this->_volumeBySourceKey($this->defaultUploadLocationSource);
    }

    /**
     * Returns the full source key for a folder, in the form of `volume:UID/folder:UID/...`.
     */
    private function _sourceKeyByFolder(MuxFolder $folder): string
    {
        if (!$folder->volumeId) {
            // Probably the user's temp folder
            return "temp";
        }

        $segments = array_map(function(MuxFolder $folder) {
            if ($folder->parentId) {
                return "folder:$folder->uid";
            }
            return sprintf('volume:%s', $folder->getVolume()->uid);
        }, $this->_folderWithAncestors($folder));

        return implode('/', $segments);
    }

    /**
     * Returns the given folder along with each of its ancestors.
     *
     * @return MuxFolder[]  
     */
    private function _folderWithAncestors(MuxFolder $folder, ?MuxFolder $untilFolder = null): array
    {
        $folders = [$folder];

        while ($folder->parentId && $folder->volumeId !== null && (!$untilFolder || $folder->id !== $untilFolder->id)) {
            $folder = $folder->getParent();
            array_unshift($folders, $folder);
        }

        return $folders;
    }

}
