# MUX plugin for Craft CMS 4.x

MUX is a Craft CMS plugin used to 

## Requirements

This plugin requires Craft CMS 4.0.0 or later.

## Installation

To install MUX, follow these steps:

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require rocket-park/mux

3. Install the plugin via `./craft install/plugin mux` via the CLI, or in the Control Panel, go to Settings → Plugins and click the “Install” button for MUX.

MUX works on Craft 4.x.

## MUX configuration

Signup For [MUX Account](https://mux.com/)

You must obtain a MUX Token ID & Token Secret from your MUX environment access tokens page.
 
"An environment represents the highest grouping of data you want to combine and compare within. Multiple websites/apps or video platforms can use the same environment, but we suggest not combining staging and production data." - MUX

## SYNC
The plugin has a console command to sync data from MUX to CRAFT.

`php craft mux/sync/all`

### Webhooks
In the MUX settings a webhook can be added to inform the plugin of asset updates. 
Include the following endbpoint in the MUX webhook control pannel.

`https://site.com/actions/mux/webhooks/mux-webhooks`

## MUX Roadmap

Some things to do, and ideas for potential features:

* **Organization of video** - Allow for folders or sections to organize videos.
* **Webhook verification** - Verify the webhook is coming from MUX via their Webhook Signing Secret.

Brought to you by [Rocket Park](https://rocketpark.com/)