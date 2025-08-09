<?php

namespace rocketpark\mux\jobs;

use craft\queue\BaseJob;
use rocketpark\mux\Mux;
use GuzzleHttp\Client;
use MuxPhp\Api\AssetsApi;

class CleanupMuxAssetsJob extends BaseJob
{
    /** @var string[] */
    public array $assetIds = [];

    /** Optional human-readable description shown in CP queue */
    public ?string $description = null;

    public function execute($queue): void
    {
        $ids = array_values(array_unique($this->assetIds));
        $count = count($ids);

        Mux::info("CleanupMuxAssetsJob started for {$count} asset(s).", 'mux');

        $success = 0;
        $failed  = [];

        // Reuse a single API client for the whole job
        $api = new AssetsApi(new Client(), Mux::$plugin->assets->muxConf());

        foreach ($ids as $id) {
            $attempt = 0;
            $maxAttempts = 3;

            while (true) {
                $attempt++;
                try {
                    // Use your service method (refactor it to accept $api if you haven’t yet)
                    $ok = Mux::$plugin->assets->deleteAssetById($id, $api);
                    if ($ok) {
                        $success++;
                    } else {
                        $failed[] = $id;
                    }
                    break;

                } catch (\MuxPhp\ApiException $e) {
                    $code = $e->getCode();

                    // 404 = already deleted
                    if ($code === 404) {
                        Mux::info("Mux asset {$id} not found (404); treating as already deleted.", 'mux');
                        $success++;
                        break;
                    }

                    // Retry on 429/5xx with small backoff (BaseJob doesn't auto-retry)
                    if ($code === 429 || $code >= 500) {
                        $retryAfter = 0;
                        $headers = $e->getResponseHeaders() ?? [];
                        if (isset($headers['Retry-After'][0])) {
                            $retryAfter = (int) $headers['Retry-After'][0];
                        }
                        if ($retryAfter <= 0) {
                            $retryAfter = min(2 ** ($attempt - 1), 8); // 1s,2s,4s,8s
                        }

                        if ($attempt < $maxAttempts) {
                            Mux::warning("Retryable error deleting {$id} (HTTP {$code}); retrying in {$retryAfter}s [attempt {$attempt}/{$maxAttempts}].", 'mux');
                            sleep($retryAfter);
                            continue;
                        }

                        Mux::error("Failed to delete {$id} after {$maxAttempts} attempts (last HTTP {$code}).", 'mux');
                        $failed[] = $id;
                        break;
                    }

                    // Non-retryable client error
                    Mux::error("Non-retryable error deleting {$id}: HTTP {$code} {$e->getMessage()}", 'mux');
                    $failed[] = $id;
                    break;

                } catch (\Throwable $e) {
                    // Unknown error: try a couple times
                    if ($attempt < $maxAttempts) {
                        $delay = min(2 ** ($attempt - 1), 8);
                        Mux::warning("Error deleting {$id}: {$e->getMessage()} — retrying in {$delay}s [attempt {$attempt}/{$maxAttempts}].", 'mux');
                        sleep($delay);
                        continue;
                    }
                    Mux::error("Failed to delete {$id} after {$maxAttempts} attempts: {$e->getMessage()}", 'mux');
                    $failed[] = $id;
                    break;
                }
            }
        }

        $msg = "CleanupMuxAssetsJob finished. Success: {$success}, Failed: " . count($failed) . ".";
        if ($failed) {
            $msg .= " Failed IDs: " . implode(', ', $failed);
        }
        Mux::info($msg, 'mux');
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('mux', 'Cleanup Mux assets');
    }

    public function getTtr(): int
    {
        return 300;
    }
}