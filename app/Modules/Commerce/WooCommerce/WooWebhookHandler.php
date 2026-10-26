<?php

namespace App\Modules\Commerce\WooCommerce;

use App\Modules\Commerce\CommerceRecorder;
use App\Modules\Commerce\DiscoveredBy;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\StoreStatus;
use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Webhooks\WebhookEvent;
use App\Shared\Webhooks\WebhookHandler;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Turns stored CODEX Connector events (protocol §3) into internal records.
 * The tenant comes from tenant_routes by the store id of the verified URL,
 * never from the body. Orders and products are fetched from the REST API:
 * the plugin sends only their ids.
 */
class WooWebhookHandler implements WebhookHandler
{
    public function __construct(
        private readonly TenantContext $tenants,
        private readonly CommerceRecorder $recorder,
        private readonly WooAdapter $adapter,
        private readonly WooPayloadMapper $mapper,
        private readonly WooCheckoutConsent $checkoutConsent,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(WebhookEvent $event): void
    {
        $storeId = Arr::get($event->payload, 'store_id');
        $tenantId = is_string($storeId) ? TenantRoute::tenantFor(TenantRoute::WOOCOMMERCE, $storeId) : null;

        if ($tenantId === null) {
            Log::info('WooCommerce event for an unknown store ignored.', ['webhook_event_id' => $event->id, 'event' => $event->event_type]);

            return;
        }

        $event->update(['tenant_id' => $tenantId]);

        $this->tenants->run($tenantId, function () use ($event, $storeId) {
            $store = StoreConnection::query()
                ->where('provider', TenantRoute::WOOCOMMERCE)
                ->where('external_store_id', $storeId)
                ->where('status', StoreStatus::Active)
                ->first();

            // Disconnected after the event was accepted: nothing to do.
            if ($store === null) {
                return;
            }

            $store->update(['last_webhook_at' => now()]);

            $this->dispatch($store, $event->event_type, (array) Arr::get($event->payload, 'data', []));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dispatch(StoreConnection $store, string $type, array $data): void
    {
        $id = Arr::get($data, 'id');
        $id = is_scalar($id) && (string) $id !== '' ? (string) $id : null;

        // Cart ids are the plugin's (≤ 64 chars); order/product ids are Woo's numbers.
        if ($id !== null && preg_match(str_starts_with($type, 'cart.') ? WooAdapter::CART_ID : '/^\d{1,20}$/', $id) !== 1) {
            Log::info('WooCommerce event with a malformed id ignored.', ['event' => $type]);

            return;
        }

        match (true) {
            $type === 'cart.updated' && $id !== null => $this->cartUpdated($data),

            $type === 'cart.purchased' && $id !== null => $this->recorder->markCartPurchased($id),

            in_array($type, ['order.created', 'order.updated'], true) && $id !== null => $this->order($store, $id),

            $type === 'product.updated' && $id !== null => $this->product($store, $id),

            $type === 'product.deleted' && $id !== null => $this->recorder->removeProduct($id),

            // Store health: the merchant switched the connector off. Nothing is deleted.
            $type === 'plugin.deactivated' => $this->audit->record('store.connector_deactivated', ActorType::System, subject: $store),

            // connector.ping only refreshes last_webhook_at; unknown events are ignored (forward compatible).
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function cartUpdated(array $data): void
    {
        // Never mix currencies in one workspace (ADR-012): logged and skipped.
        if (! $this->mapper->inWorkspaceCurrency($data, 'cart')) {
            return;
        }

        $this->checkoutConsent->record($data);
        $this->recorder->recordAbandonedCart($this->mapper->cart($data), DiscoveredBy::Webhook);
    }

    private function order(StoreConnection $store, string $id): void
    {
        $order = $this->adapter->order($store, $id);

        if ($order !== null) {
            $this->recorder->recordOrder($order);
        }
    }

    private function product(StoreConnection $store, string $id): void
    {
        $product = $this->adapter->product($store, $id);

        $product === null ? $this->recorder->removeProduct($id) : $this->recorder->recordProduct($product);
    }
}
