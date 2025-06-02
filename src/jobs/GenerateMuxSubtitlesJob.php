<?php 
namespace rocketpark\mux\jobs;

use Craft;
use craft\queue\BaseJob;
use rocketpark\mux\Mux;

class GenerateMuxSubtitlesJob extends BaseJob
{
    public string $assetId;
    public string $trackId;

    public function execute($queue): void
    {
        $this->throttleMuxRequests(1); // Throttle requests to avoid hitting Mux API limits
        Craft::info("Starting subtitle generation for asset: {$this->assetId}, track: {$this->trackId}", __METHOD__);
        if (empty($this->assetId) || empty($this->trackId)) {
            Craft::error("Asset ID or Track ID is empty. Cannot generate subtitles.", __METHOD__);
            return;
        }
        // Ensure the asset and track IDs are valid
        try {
            Mux::$plugin->assets->generateAssetTrackSubtitles($this->assetId, $this->trackId);
            Craft::info("Subtitles generated for asset: {$this->assetId}", __METHOD__);
        } catch (\Throwable $e) {
            Craft::error("Failed to generate subtitles for asset {$this->assetId}: " . $e->getMessage(), __METHOD__);
            throw $e; // So it can retry if retries are enabled
        }
    }

    private function throttleMuxRequests(int $secondsBetweenRequests = 1): void
    {
        $cache = Craft::$app->getCache();
        $cacheKey = 'mux:lastRequestTime';

        $now = time();
        $lastRequestTime = (int) $cache->get($cacheKey);

        $elapsed = $now - $lastRequestTime;

        if ($elapsed < $secondsBetweenRequests) {
            $sleep = $secondsBetweenRequests - $elapsed;
            sleep($sleep);
        }

        $cache->set($cacheKey, time());
    }

    protected function defaultDescription(): string
    {
        return "Generating subtitles for MUX asset {$this->assetId}";
    }
}