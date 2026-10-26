<?php

namespace App\Modules\Commerce\WooCommerce;

use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\StoreStatus;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Webhooks\WebhookInbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * CODEX Connector ingress (protocol §3): the store comes from the URL, its
 * secret from its own connection (inside its tenant), then the signature over
 * `"{timestamp}.{raw_body}"` within ±5 minutes. Stored once per event id and
 * answered fast; the plugin retries 5xx and network errors, never 4xx.
 */
class WooWebhookController
{
    private const MAX_BODY_BYTES = 1024 * 1024;

    private const EVENT_ID = '/^[A-Za-z0-9-]{8,64}$/';

    /** Event names like `cart.updated`; anything longer would not fit the inbox column. */
    private const EVENT_TYPE = '/^[a-z0-9_.]{1,64}$/';

    public function __invoke(Request $request, string $storeId, WebhookInbox $inbox, TenantContext $tenants): Response
    {
        $body = $request->getContent();

        if (strlen($body) > self::MAX_BODY_BYTES) {
            return response('', 413);
        }

        $tenantId = TenantRoute::tenantFor(TenantRoute::WOOCOMMERCE, $storeId);

        $secret = $tenantId === null ? null : $tenants->run($tenantId, fn () => StoreConnection::query()
            ->where('provider', TenantRoute::WOOCOMMERCE)
            ->where('external_store_id', $storeId)
            ->where('status', StoreStatus::Active)
            ->first()?->credentials['connector_secret'] ?? null);

        // A disconnected store is unknown: the plugin stops retrying on a 4xx.
        if (! is_string($secret) || $secret === '') {
            return response('', 404);
        }

        $valid = WooSignature::verifyBody(
            $secret,
            (string) $request->header('X-Codex-Timestamp'),
            (string) $request->header('X-Codex-Signature'),
            $body,
            now()->getTimestamp(),
        );

        if (! $valid) {
            Log::warning('Rejected WooCommerce webhook with an invalid signature or timestamp.', ['store_id' => $storeId, 'ip' => $request->ip()]);

            return response('', 401);
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($body, true);
        $eventId = (string) $request->header('X-Codex-Event-Id');

        if (! is_array($payload) || ! is_string($payload['event'] ?? null) || preg_match(self::EVENT_TYPE, $payload['event']) !== 1
            || preg_match(self::EVENT_ID, $eventId) !== 1) {
            return response('', 422);
        }

        // The store id is ours, from the verified URL; the event id is unique per store.
        $inbox->receive(TenantRoute::WOOCOMMERCE, "{$storeId}:{$eventId}", $payload['event'], ['store_id' => $storeId] + $payload);

        return response('', 200);
    }
}
