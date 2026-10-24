<?php

namespace App\Modules\Commerce\Salla;

use App\Shared\Webhooks\WebhookInbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Salla webhook ingress (E02, US-E02-03): verify the HMAC-SHA256 signature over
 * the raw body, record the event once, answer 200 fast (Salla times out at ~30s
 * and retries only 3 times).
 */
class SallaWebhookController
{
    private const MAX_BODY_BYTES = 3 * 1024 * 1024;

    public function __invoke(Request $request, WebhookInbox $inbox): Response
    {
        $body = $request->getContent();

        if (strlen($body) > self::MAX_BODY_BYTES) {
            return response('', 413);
        }

        if (! $this->hasValidSignature($body, (string) $request->header('X-Salla-Signature'))) {
            Log::warning('Rejected Salla webhook with an invalid signature.', ['ip' => $request->ip()]);

            return response('', 401);
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($body, true);

        if (! is_array($payload) || ! is_string($payload['event'] ?? null)) {
            return response('', 422);
        }

        // Salla sends no stable event id; identical retries have identical bodies (09 §9.9.3).
        $inbox->receive('salla', hash('sha256', $body), $payload['event'], $payload);

        return response('', 200);
    }

    private function hasValidSignature(string $body, string $signature): bool
    {
        $secret = (string) config('services.salla.webhook_secret');

        // Fail closed: without a configured secret nothing is accepted.
        if ($secret === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $body, $secret), $signature);
    }
}
