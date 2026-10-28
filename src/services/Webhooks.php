<?php

namespace rocketpark\mux\services;

use Craft;
use craft\helpers\App;
use rocketpark\mux\Mux;
use yii\base\Component;

/**
 * Webhooks service
 *
 * Verifies the `Mux-Signature` header on incoming webhooks and de-duplicates
 * repeated deliveries of the same event.
 *
 * @see https://www.mux.com/docs/core/verify-webhook-signatures
 */
class Webhooks extends Component
{
    // Constants
    // =========================================================================

    public const SIGNATURE_HEADER = 'Mux-Signature';

    /**
     * Allowed clock difference between the signature timestamp and this server, in seconds.
     * Matches the default tolerance used by Mux's SDKs.
     */
    public const DEFAULT_TIMESTAMP_TOLERANCE = 300;

    /**
     * How long a processed event id is remembered. Mux retries failed deliveries for up to 24 hours.
     */
    public const PROCESSED_EVENT_TTL = 86400;

    private const PROCESSED_EVENT_CACHE_PREFIX = 'mux-webhook-event-';

    // Public Methods
    // =========================================================================

    /**
     * Get the resolved webhook signing secret ('' when not configured).
     * @return string
     */
    public function getSigningSecret(): string
    {
        return trim((string) App::parseEnv(Mux::$settings->muxWebhookSecret ?? ''));
    }

    /**
     * Whether a webhook signing secret is configured.
     * @return bool
     */
    public function hasSigningSecret(): bool
    {
        return $this->getSigningSecret() !== '';
    }

    /**
     * Get the allowed timestamp tolerance in seconds.
     * @return int
     */
    public function getTimestampTolerance(): int
    {
        $tolerance = (int) (Mux::$settings->webhookTimestampTolerance ?? self::DEFAULT_TIMESTAMP_TOLERANCE);

        return $tolerance > 0 ? $tolerance : self::DEFAULT_TIMESTAMP_TOLERANCE;
    }

    /**
     * Verify a `Mux-Signature` header against the raw request body.
     *
     * The header has the form `t=<unix timestamp>,v1=<hex signature>`. The expected
     * signature is HMAC-SHA256 of "{t}.{raw body}" keyed with the endpoint's signing secret.
     *
     * @param string $rawBody The unparsed request body
     * @param string|null $header The `Mux-Signature` header value
     * @param string $secret The webhook signing secret
     * @param int|null $tolerance Allowed clock difference in seconds (defaults to the configured tolerance)
     * @param int|null $now Current unix timestamp (for testing)
     * @return bool
     */
    public function verifySignature(string $rawBody, ?string $header, string $secret, ?int $tolerance = null, ?int $now = null): bool
    {
        if ($secret === '' || $header === null || trim($header) === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) !== 2) {
                continue;
            }

            [$key, $value] = $pair;

            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1' && $value !== '') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || !ctype_digit($timestamp) || empty($signatures)) {
            return false;
        }

        $tolerance ??= $this->getTimestampTolerance();
        $now ??= time();

        if (abs($now - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, strtolower($signature))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Record an event id as processed. Returns false when the event was already processed.
     *
     * @param string|null $eventId The Mux event id (top-level `id` of the payload)
     * @return bool
     */
    public function markEventProcessed(?string $eventId): bool
    {
        if ($eventId === null || $eventId === '') {
            return true;
        }

        return Craft::$app->getCache()->add(
            self::PROCESSED_EVENT_CACHE_PREFIX . $eventId,
            true,
            self::PROCESSED_EVENT_TTL
        );
    }

    /**
     * Forget a processed event id so a retried delivery is accepted again.
     *
     * @param string|null $eventId The Mux event id
     * @return void
     */
    public function forgetProcessedEvent(?string $eventId): void
    {
        if ($eventId === null || $eventId === '') {
            return;
        }

        Craft::$app->getCache()->delete(self::PROCESSED_EVENT_CACHE_PREFIX . $eventId);
    }
}
