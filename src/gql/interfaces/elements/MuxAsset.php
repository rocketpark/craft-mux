<?php

namespace rocketpark\mux\gql\interfaces\elements;

use Craft;
use craft\base\Element as BaseElement;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InterfaceType as GqlInterfaceType;
use yii\db\Schema;
use craft\helpers\Gql;

use rocketpark\mux\gql\types\generators\MuxAssetType;
use rocketpark\mux\gql\types\elements\MuxAsset as MuxAssetElement;
use rocketpark\mux\gql\resolvers\elements\MuxAsset as MuxAssetResolver;
use rocketpark\mux\gql\types\PlaybackIdType;
use rocketpark\mux\gql\types\TrackType;
use rocketpark\mux\gql\types\MetaType;

/**
 * Class MuxAssetGqlType
 */
class MuxAsset extends Element
{

    /**
     * @return string
     */
    static public function getName(): string
    {
        return 'MuxAssetInterface';
    
    }

    public static function getTypeGenerator(): string
    {
        return MuxAssetType::class;
    }

    /**
     * @return Type
     */
    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type =  GqlEntityRegistry::createEntity(self::getName(), new GqlInterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all mux assets.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        MuxAssetType::generateTypes();

        //\yii\helpers\VarDumper::dump($type, 5, true);exit;

        return $type;
    }

    /**
     * @return array
     */
    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'id' => [
                'type' => Type::ID(),
                'description' => 'ID'
            ],
            'asset_id' => [
                'type' => Type::string(),
                'description' => 'ID of the mux asset'
            ],
            'volumeId' => [
                'type' => Type::ID(),
                'description' => 'Volume ID'
            ],
            'folderId' => [
                'type' => Type::ID(),
                'description' => 'Folder ID'
            ],
            'created_at' => [
                'type' => Type::string(),
                'description' => 'Creation time of the object'
            ],
            'asset_status' => [
                'type' => Type::string(),
                'description' => 'Status of the mux asset'
            ],
            'duration' => [
                'type' => Type::string(),
                'description' => 'Duration of the object'
            ],
            'max_stored_resolution' => [
                'type' => Type::string(),
                'description' => 'Max stored resolution of the object'
            ],
            'max_stored_frame_rate' => [
                'type' => Type::string(),
                'description' => 'Max stored frame rate of the object'
            ],
            'resolution_tier' => [
                'type' => Type::string(),
                'description' => 'Resolution tier of the object'
            ],
            'max_resolution_tier' => [
                'type' => Type::string(),
                'description' => 'Max resolution tier of the object'
            ],
            'encoding_tier' => [
                'type' => Type::string(),
                'description' => 'Encoding tier of the object'
            ],
            'aspect_ratio' => [
                'type' => Type::string(),
                'description' => 'Aspect ratio of the object'
            ],
            'isAudioOnly' => [
                'type' => Type::boolean(),
                'description' => 'Whether this asset has no video track (audio-only).'
            ],
            'playback_ids' => [
                'type' => Type::listOf(PlaybackIdType::getType()),
                'description' => 'Playback IDs of the object. Each one can return signed tokens and ready-to-use URLs.',
                'args' => [
                    'policy' => [
                        'type' => Type::string(),
                        'description' => 'Only return playback IDs with this policy: `public` or `signed`.',
                    ],
                ],
            ],
            'tracks' => [
                'name' => 'tracks',
                'type' => Type::listOf(TrackType::getType()),
            ],
            'errors' => [
                'type' => Type::string(),
                'description' => 'Errors of the object'
            ],
            'per_title_encode' => [
                'type' => Type::string(),
                'description' => 'Per-title encode of the object'
            ],
            'upload_id' => [
                'type' => Type::string(),
                'description' => 'Upload ID of the object'
            ],
            'is_live' => [
                'type' => Type::string(),
                'description' => 'Indicates whether the object is live or not'
            ],
            'passthrough' => [
                'type' => Type::string(),
                'description' => 'Passthrough of the object'
            ],
            'live_stream_id' => [
                'type' => Type::string(),
                'description' => 'Live stream ID of the object'
            ],
            'master' => [
                'type' => Type::string(),
                'description' => 'Master (source file) access details, as a JSON-encoded string, e.g. `{"status":"ready","url":"…"}`. `"[]"` when empty, `null` when not set.'
            ],
            'master_access' => [
                'type' => Type::string(),
                'description' => 'Master access'
            ],
            'mp4_support' => [
                'type' => Type::string(),
                'description' => 'MP4 support'
            ],
            'source_asset_id' => [
                'type' => Type::string(),
                'description' => 'Source Asset Id'
            ],
            'nomralize_audio' => [
                'type' => Type::string(),
                'description' => 'Whether audio normalization was requested for the asset. (The field name is misspelled; it is kept for backwards compatibility.)'
            ],
            'static_renditions' => [
                'type' => Type::string(),
                'description' => 'Static renditions, as a JSON-encoded string, e.g. `{"status":"ready","files":[{"id":"…","name":"720p.mp4","ext":"mp4","resolution":"720p","width":1280,"height":720,"bitrate":1853080,"filesize":"1849844","status":"ready"}]}`. `"[]"` when empty, `null` when not set.'
            ],
            'recording_times' => [
                'type' => Type::string(),
                'description' => 'Recording times (assets created from a live stream), as a JSON-encoded string: a list of `{"started_at","duration","type"}` objects. `"[]"` when empty, `null` when not set.'
            ],
            'non_standard_input_reasons' => [
                'type' => Type::string(),
                'description' => 'Reasons the input file was non-standard, as a JSON-encoded string, e.g. `{"video_codec":"…","audio_codec":"…"}`. `"[]"` when empty, `null` when not set.'
            ],
            'test' => [
                'type' => Type::string(),
                'description' => 'test'
            ],
            'ingest_type' => [
                'type' => Type::string(),
                'description' => 'Ingest type'
            ],
            'meta' => [
                'type' => MetaType::getType(),
                'description' => 'Meta data of the object'
            ],
        ]), self::getName());
    }

    
}
