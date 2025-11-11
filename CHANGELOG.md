# MUX Changelog

All notable changes to this project will be documented in this file.

## 2.4.0.1 - 2025-10-20

### Fixed
- **Install Migration**: Fixed missing volumeId param for table setup

### Updated
- **Sync All Mux Assets**: Updated syncAllMuxAssets to include volumes and folders. 

## 2.4.0 - 2025-10-17

### Added
- **Analytics Dashboard**: Added comprehensive analytics and data visualization system with Mux Data API integration
- **Data Service**: New `Data` service for fetching and caching analytics data from Mux API
- **Analytics Field Layout**: New "Data" tab in Mux Asset field layout with interactive charts and metrics
- **Cache Management**: Console commands for managing analytics data cache (`mux/cache/clear`, `mux/cache/clear-all`, `mux/cache/stats`)
- **Data Controller**: New controller for handling analytics data requests via AJAX
- **Interactive Charts**: Built-in chart functionality using Craft's internal chart system (replacing Chart.js dependency)
- **Multiple Metrics Support**: Views, unique viewers, playing time, average playing time, and completion percentage
- **Timespan Selection**: Support for 1 week, 1 month, 3 months, 6 months, and 1 year data ranges
- **Real-time Data Updates**: Force refresh capability for getting latest analytics data
- **Console Sync Commands**: New sync controller for managing Mux asset synchronization
- **Watermarks**: New settings to add watermark to videos

### Updated
- **Schema Version**: Updated to 1.0.3 to support new analytics features
- **Field Layout System**: Enhanced Mux Asset field layout with dedicated Data and Tracks tabs
- **Service Architecture**: Added Data service to plugin configuration alongside existing services
- **Cache Integration**: Automatic cache clearing when assets are deleted or updated
- **Translation Support**: Added analytics-related translations for the new Data service

### Technical Improvements
- **Performance Optimization**: Intelligent caching reduces API calls while maintaining data freshness
- **Error Handling**: Comprehensive error handling for analytics API failures
- **Memory Management**: Efficient data processing and caching strategies
- **API Integration**: Full integration with Mux Data API for real-time analytics
- **User Experience**: Seamless analytics integration within existing Mux Asset editing workflow

## 2.1.2 - 2025-07-09
### Fixed
- Fixed setting of static_renditions param when uploading mux asset

## 2.1.1 - 2025-07-08
### Updated
- Settings will now warn when mp4_spport or static_rendition inputs are being updated when the other is set
### Fixed
- Static renditions settings param name
- Upload Mux Asset service method static_renditions support

## 2.1.0 - 2025-07-08
### Added
- Added ability to upload via Mux Asset input
- Added translations file for en
### Updated
- Mux Asset input settings for upload functionality
- Mux entry to allow for static rendition while depreciating mp4 support

## 2.0.20 - 2025-04-18
### Added
- Added a button to download text track files from Mux.
- Added new MUX asset model params, meta, and ingest_type. MUX via meta param now includes a title for video viewable in the MUX dashboard.
- Added new query params for tracks and meta column's JSON params.
### Updated
- Increased schema version 1.0.2
- mux-php to version 5.0.1
- php-jwt to version 6.11.1
- front-end packages to latest versions

## 2.0.12 - 2024-12-05
### Added
- Support for Mux MP4 Support
- Job Queue for handling Mux Webhooks
### Updated
- mux-php to version 3.21.0
- php-jwt to version 6.10.2

## 2.0.9 - 2024-10-15
### Fixed
- Fixed permission to work properly with Craft 5 

## 2.0.6 - 2024-10-01
### Updated
- Update Mux PHP SDK to 3.19.0
- Update Javascript compilation to use Vite
- Simplified asset bundles and remove unused code
### Fixed
- Fixed a bug where the asset element thumbnail was not displaying correctly

## 2.0.2 - 2024-08-23
### Updated
- Added encoding_tier, resolution_tier, and max_resolution_tier columns to install migration

## 2.0.1 - 2024-08-12
### Updated
- Updated the Mux PHP SDK to 3.18.0
- Update mux to work in Craft 5
### Added
- Added animated gif thumbnail support

## 1.2.4 - 2023-11-07
### Added
- Added a new plugin variable `craft.signedKeys` to return all the generated signed key for secure playback.
This will help with efficiency instead of grabbing the key on every video iteration. 

## 1.2.3 - 2023-11-06
### Updated
- Updated the php mux api framework to 3.12.1 to fix auto generated closed captions using direct upload.
- Updated MUX entry player to not use cookies for MUX data so as not to skew player data.
### Fixed
- Fixed direct upload api to use array for CreateAssetRequest model "input" param. 

## 1.2.0 - 2023-10-31
### Added
- Added new asset model params, resolution_tier, max_resolution_tier, and ecoding_tier
- Added radio group setting to change max_resolution_tier
- Added default automated encoding for English Closed Caption tracks
### Updated
- Updated muxinc/mux-php version to 3.12.0 

## 1.1.7 - 2023-10-30
### Fixed
- Fixed asset upload element title assignment from filename

## 1.1.5 - 2023-10-27
### Added
- Added a copytext input for the webhook url in the settings page
- Added a element index action to sync selected assets from mux if they don't have an asset status of "ready"
### Fixed
- Fixed video upload by removing looped check for mux asset status through the mux api causing php to timeout
### Updated
- Update Javascript packages and recompiled assets

## 1.1.1 - 2023-09-07
### Fixed
- Fixed Migrations

## 1.1.0 - 2023-09-07
### Added
- Signed Keys setting interface
- Secure Playback
- JWT token for secure playback from Signed Keys

## 1.0.11 - 2023-06-26
### Fixed
- Fix controller action create, inproper variable type default

## 1.0.10 - 2023-06-24
### Fixes
- Recompiled assets for mux element-edit
- Various fixes…

## 1.0.9 - 2023-06-24
### Fixed
- Track output was expecting a variable however it wasn’t included in sync.

## 1.0.8 - 2023-06-24
### Fixed
- Fixed a type error for non_standard_input_reasons element variable
- Updated readme to include sync instructions
- Updated version to 1.0.8

## 1.0.7 - 2023-06-24
- Updated composer version

## 1.0.6 - 2023-06-24
### Added
- Added sync support & webhook delete, create, integration.
### Fixed
- Fixed GraphQL Sub Types for PlaybackIds & Tracks

## 1.0.4 - 2023-05-24
### Added
- When Mux returns a status of video.asset.updated included updateAssetElementWithMuxAsset to update the asset element. 
### Fixed
- Fixed a bug where an update loop would occur from the Element After Save event by preventing update if passthrough param is already set and has not changed.

## 1.0.3 - 2023-05-24
### Added
- Added Vite as a compliler for the mux-dashboard.js app.  Webpack was causing issues to code once compiled for production, not allowing UpChunk to full work.

## 1.0.2 - 2023-05-23
### Added
- Updated webhook code record all endpoints to MUX log
- Updated MUX table column status to asset_status since the element variable status is reserved for the parent element. No migration needed since plugin is not fully in use.
- Updated Readme with webhook info

## 1.0.0 - 2023-05-19
- Initial release
