<?php

namespace App\Modules\Messaging\Meta;

use App\Shared\Webhooks\WebhookInbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * One endpoint for every WABA of every merchant. Meta retries for up to 7 days
 * and sends duplicates; payloads up to 3 MB (09 §9.7.2).
 */
class MetaWebhookController
{
    private const MAX_BODY_BYTES = 3 * 1024 * 1024;

    /** Subscription handshake: echo the challenge only for our verify token. */
    public function verify(Request $request): Response
    {
        $token = (string) config('services.meta.webhook_verify_token');

        if ($token !== '' && $request->query('hub_mode') === 'subscribe' && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200);
        }

        return response('', 403);
    }

    public function receive(Request $request, MetaGraphClient $meta, WebhookInbox $inbox): Response
    {
        $body = $request->getContent();

        if (strlen($body) > self::MAX_BODY_BYTES) {
            return response('', 413);
        }

        if (! $meta->verifySignature($body, (string) $request->header('X-Hub-Signature-256'))) {
            Log::warning('Rejected Meta webhook with an invalid signature.', ['ip' => $request->ip()]);

            return response('', 401);
        }

        $payload = json_decode($body, true);

        if (! is_array($payload)) {
            return response('', 422);
        }

        $inbox->receive('meta', hash('sha256', $body), (string) ($payload['object'] ?? 'unknown'), $payload);

        return response('', 200);
    }
}
