# MUX Changelog

All notable changes to this project will be documented in this file.

## 2.0.1 - August 12, 2024
### Updated
 - Updated the Mux PHP SDK to 3.18.0
 - Update mux to work in Craft 5
### Added
 - Added animated gif thumbnail support

## 1.2.4 - November 7, 2023
### Added
- Added a new plugin variable `craft.signedKeys` to return all the generated signed key for secure playback.
This will help with efficiency instead of grabbing the key on every video iteration. 

## 1.2.3 - November 6, 2023
### Updated
- Updated the php mux api framework to 3.12.1 to fix auto generated closed captions using direct upload.
- Updated MUX entry player to not use cookies for MUX data so as not to skew player data.
### Fixed
- Fixed direct upload api to use array for CreateAssetRequest model "input" param. 

## 1.2.0 - October 31, 2023
### Added
- Added new asset model params, resolution_tier, max_resolution_tier, and ecoding_tier
- Added radio group setting to change max_resolution_tier
- Added default automated encoding for English Closed Caption tracks
### Updated
- Updated muxinc/mux-php version to 3.12.0 

## 1.1.7 - October 30, 2023
### Fixed
- Fixed asset upload element title assignment from filename

## 1.1.5 - October 27, 2023
### Added
- Added a copytext input for the webhook url in the settings page
- Added a element index action to sync selected assets from mux if they don't have an asset status of "ready"
### Fixed
- Fixed video upload by removing looped check for mux asset status through the mux api causing php to timeout
### Updated
- Update Javascript packages and recompiled assets

## 1.1.1 - September 7, 2023
### Fixed
- Fixed Migrations

## 1.1.0 - September 7, 2023
### Added
- Signed Keys setting interface
- Secure Playback
- JWT token for secure playback from Signed Keys

## 1.0.11 - June 26, 2023
### Fixed
- Fix controller action create, inproper variable type default

## 1.0.10 - June 24, 2023
### Fixes
- Recompiled assets for mux element-edit
- Various fixes…

## 1.0.9 - June 24, 2023
### Fixed
- Track output was expecting a variable however it wasn’t included in sync.

## 1.0.8 - June 24, 2023
### Fixed
- Fixed a type error for non_standard_input_reasons element variable
- Updated readme to include sync instructions
- Updated version to 1.0.8

## 1.0.7 - June 24, 2023
- Updated composer version

## 1.0.6 - June 24, 2023
### Added
- Added sync support & webhook delete, create, integration.
### Fixed
- Fixed GraphQL Sub Types for PlaybackIds & Tracks

## 1.0.4 - May 24, 2023
### Added
- When Mux returns a status of video.asset.updated included updateAssetElementWithMuxAsset to update the asset element. 
### Fixed
- Fixed a bug where an update loop would occur from the Element After Save event by preventing update if passthrough param is already set and has not changed.

## 1.0.3 - May 24, 2023
### Added
- Added Vite as a compliler for the mux-dashboard.js app.  Webpack was causing issues to code once compiled for production, not allowing UpChunk to full work.

## 1.0.2 - May 23, 2023
### Added
- Updated webhook code record all endpoints to MUX log
- Updated MUX table column status to asset_status since the element variable status is reserved for the parent element. No migration needed since plugin is not fully in use.
- Updated Readme with webhook info

## 1.0.0 - May 19, 2023
- Initial release
