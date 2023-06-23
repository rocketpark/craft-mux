<?php

namespace rocketpark\mux\gql\interfaces\elements;

use Craft;
use craft\base\Element as BaseElement;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InterfaceType as GqlInterfaceType;
use yii\db\Schema;
use craft\helpers\Gql;

use rocketpark\mux\gql\types\generators\MuxAssetType;
use rocketpark\mux\gql\types\elements\MuxAsset as MuxAssetElement;
use rocketpark\mux\gql\resolvers\elements\MuxAsset as MuxAssetResolver;
use rocketpark\mux\gql\types\PlaybackIdType;
use rocketpark\mux\gql\types\TrackType;

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
        $PlaybackIdType = new ObjectType([
            'name' => 'PlaybackID',
            'fields' => [
                'id' => [
                    'type' => Type::string(),
                    'description' => 'ID of the playback'
                ],
                'policy' => [
                    'type' => Type::string(),
                    'description' => 'Policy of the playback'
                ]
            ]
        ]);

        $TracksType = new ObjectType([
            'name' => 'Track',
            'description' => 'Mux Asset Track',
            'fields' => [
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
                    'type' => Type::int(),
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
                'max_video_bitrate' => [
                    'type' => Type::int(),
                    'description' => 'The maximum video bitrate of the track'
                ],
                'max_audio_bitrate' => [
                    'type' => Type::int(),
                    'description' => 'The maximum audio bitrate of the track'
                ],
                'created_at' => [
                    'type' => Type::string(),
                    'description' => 'The creation time of the track'
                ],
                'updated_at' => [
                    'type' => Type::string(),
                    'description' => 'The last update time of the track'
                ],
            ]
        ]);

        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'id' => [
                'type' => Type::ID(),
                'description' => 'ID'
            ],
            'asset_id' => [
                'type' => Type::string(),
                'description' => 'ID of the mux asset'
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
            'aspect_ratio' => [
                'type' => Type::string(),
                'description' => 'Aspect ratio of the object'
            ],
            'playback_ids' => [
                'type' => Type::listOf(PlaybackIdType::getType()),
                'description' => 'Playback IDs of the object',
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
                'description' => 'Master of the object'
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
                'description' => 'Normalize Audio'
            ],
            'static_renditions' => [
                'type' => Type::string(),
                'description' => 'Static Renditions'
            ],
            'recording_times' => [
                'type' => Type::string(),
                'description' => 'Recording Times'
            ],
            'non_standard_input_reasons' => [
                'type' => Type::string(),
                'description' => 'Non Standard Input Reasons'
            ],
            'test' => [
                'type' => Type::string(),
                'description' => 'test'
            ],
        ]), self::getName());
    }

    
}
