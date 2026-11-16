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
 

### Signed playback

Assets with a `signed` playback ID need a token on every stream, image, storyboard, caption and MP4 URL. Create a signing key under **Mux → Signed Keys**; the plugin then signs URLs for you.

All URL modifiers (thumbnail `width`/`time`, stream `max_resolution`, …) go inside the token for signed playback IDs, so pass them to the helpers below rather than adding them to the URL.

**Twig**

```twig
{% set video = entry.video.one() %}
<mux-player
  playback-id="{{ video.getPlaybackId('signed') }}"
  playback-token="{{ video.getPlaybackToken('v', {}, null, 'signed') }}"
  thumbnail-token="{{ video.getPlaybackToken('t', {time: 2}, null, 'signed') }}"
  storyboard-token="{{ video.getPlaybackToken('s', {}, null, 'signed') }}">
</mux-player>

<img src="{{ video.getThumbnailUrl({width: 640, height: 360, fit_mode: 'smartcrop'}) }}">
<a href="{{ video.getStaticRenditionUrl('highest.mp4', 'my-video.mp4') }}">Download</a>
```

`getStreamUrl()`, `getThumbnailUrl()`, `getAnimatedUrl()`, `getStoryboardUrl()`, `getTextTrackUrl()` and `getStaticRenditionUrl()` return signed URLs for signed playback IDs and plain URLs for public ones. Each takes an optional `expiresIn` (seconds) and `policy` (`public`/`signed`) to choose between playback IDs when an asset has both.

**GraphQL**

Enable **Mux → Generate signed playback tokens and URLs** on the schema your server uses. Only enable it on private schemas: anyone with that schema can play every signed video it can query. Without it, token and signed URL fields return `null`.

```graphql
fragment MuxVideo on MuxAssetInterface {
  playback_ids(policy: "signed") {
    id
    policy
    video: token(type: "v", expiresIn: 31536000)
    poster: token(type: "t", width: 1280, time: 2, fit_mode: "smartcrop")
    storyboard: token(type: "s")
    thumbnailUrl(width: 640, format: "jpg")
    streamUrl(max_resolution: "720p")
  }
}
```

Fields on `PlaybackID`: `token(type, expiresIn, …modifiers)`, `streamUrl`, `thumbnailUrl`, `animatedUrl`, `storyboardUrl`, `textTrackUrl(trackId)` and `staticRenditionUrl(name, download)`.

**Token lifetime and caching**

Every token is valid for at least `expiresIn` seconds (default `signedTokenLifetime`, 24 hours), capped at `maxSignedTokenLifetime` (365 days). Expiry is rounded up to a rotation window (`signedTokenWindow`, 1 hour), so the same request returns the same token for up to an hour and a token may stay valid up to one window longer than requested. Craft's GraphQL response cache and `{% cache %}` blocks that contain tokens expire when the window rotates. If you cache pages or GraphQL responses yourself (SSR, CDN), keep that cache shorter than the token lifetime.

To enforce a [playback restriction](https://www.mux.com/docs/guides/secure-video-playback) (e.g. allowed referrer domains) on signed playback IDs, create it under **Mux → Playback Restrictions**, then tick **Default** on that page or set **Mux → Settings → Default Playback Restriction ID** (preferably to an env var such as `$MUX_PLAYBACK_RESTRICTION_ID`, since each Mux environment has its own IDs). Pass `playback_restriction_id` per token to override it, or `""` for none. Restrictions don't apply to public playback IDs.

```php
// config/mux.php
return [
    'signedTokenLifetime' => 86400,       // default lifetime in seconds
    'maxSignedTokenLifetime' => 31536000, // upper bound for any expiresIn
    'signedTokenWindow' => 3600,          // rotation window in seconds
    'playbackRestrictionId' => App::env('MUX_PLAYBACK_RESTRICTION_ID'),
];
```

## Documentation
Visit the [Mux Plugin page](https://rocketpark.com/mux-plugin) for all documentation, pricing and developer resources.

## Support
Get in touch with us via the [Mux Support page](https://rocketpark.com/mux-plugin/support/) or by [creating a Github issue](https://github.com/rocketpark/craft-mux/issues)

Brought to you by [Rocket Park](https://rocketpark.com/)