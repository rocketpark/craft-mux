<p align="center"><img src="https://rocketpark.com/assets/plugins/mux/mux-icon.svg" width="100" height="100" alt="Mux icon"></p>
<h1 align="center">Mux for Craft CMS</h1>

MUX is a Craft CMS plugin used to upload and manage videos using the [MUX](https://www.mux.com/) service.

## Features

### Video Management
- Upload and manage videos directly from Craft CMS
- Organize videos in custom volumes and folders
- Sync assets between Mux and Craft CMS

### Analytics Dashboard
- Real-time video performance metrics
- Interactive charts and data visualization
- Track views, unique viewers, and engagement
- Multiple timespan options (1 week to 1 year)

### Security & Access Control
- Secure video playback with JWT tokens
- Domain and geographic restrictions
- Granular user permissions
- Signed keys management

### Video Processing
- Static renditions for offline viewing
- Automatic closed caption generation
- Multiple resolution and encoding options
- Track management (subtitles, audio)

### Developer Integration
- GraphQL API support
- Custom Mux Asset field type
- Webhook integration for real-time updates
- Template variables for frontend use

### Console Commands
- Asset synchronization: `php craft mux/sync/all`
- Cache management: `php craft mux/cache/clear`
- Background job processing

### Frontend Ready
- Template variables for video display
- Player integration support

## MUX configuration

Signup For [MUX Account](https://mux.com/)

You must obtain a MUX Token ID & Token Secret from your MUX environment access tokens page.

### Webhooks

The plugin relies on Mux webhooks to keep assets up to date. For each Mux environment (e.g. development, staging, production):

1. On the [Mux webhooks settings page](https://dashboard.mux.com/settings/webhooks), add a webhook pointing to `https://your-site.com/actions/mux/webhooks/mux-webhooks` (the URL is also shown in **Mux → Settings**).
2. Copy the webhook's **signing secret** into the site's `.env`, e.g. `MUX_WEBHOOK_SECRET="..."`.
3. In **Mux → Settings → Webhook Signing Secret**, select `$MUX_WEBHOOK_SECRET`.

Every webhook is verified against the `Mux-Signature` header. Requests with a missing, invalid or expired signature are rejected with a `401`, and **all webhooks are rejected while no signing secret is configured**. Signatures older than 5 minutes are rejected; adjust this with `webhookTimestampTolerance` (seconds) in `config/mux.php` if needed.
 

## Documentation
Visit the [Mux Plugin page](https://rocketpark.com/mux-plugin) for all documentation, pricing and developer resources.

## Support
Get in touch with us via the [Mux Support page](https://rocketpark.com/mux-plugin/support/) or by [creating a Github issue](https://github.com/rocketpark/craft-mux/issues)

Brought to you by [Rocket Park](https://rocketpark.com/)