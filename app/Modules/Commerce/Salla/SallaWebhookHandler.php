<?php

namespace App\Modules\Commerce\Salla;

use App\Modules\Commerce\CommerceRecorder;
use App\Modules\Commerce\DiscoveredBy;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\StoreConnections;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Webhooks\WebhookEvent;
use App\Shared\Webhooks\WebhookHandler;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Turns stored Salla webhooks into internal records and domain events.
 * The tenant comes from tenant_routes by merchant id, never from the payload.
 */
class SallaWebhookHandler implements WebhookHandler
{
    public function __construct(
        private readonly TenantContext $tenants,
        private readonly StoreConnections $connections,
        private readonly CommerceRecorder $recorder,
        private readonly SallaPayloadMapper $mapper,
    ) {}

    public function handle(WebhookEvent $event): void
    {
        $merchant = Arr::get($event->payload, 'merchant');
        $data = (array) Arr::get($event->payload, 'data', []);

        if (! is_scalar($merchant) || (string) $merchant === '') {
            Log::warning('Salla webhook without merchant id.', ['webhook_event_id' => $event->id]);

            return;
        }

        if ($event->event_type === 'app.store.authorize') {
            $tenantId = $this->connections->authorize(TenantRoute::SALLA, (string) $merchant, SallaTokens::fromResponse($data));
            $event->update(['tenant_id' => $tenantId]);

            return;
        }

        $tenantId = TenantRoute::tenantFor(TenantRoute::SALLA, (string) $merchant);

        if ($tenantId === null) {
            Log::info('Salla webhook for an unknown store ignored.', ['webhook_event_id' => $event->id, 'event' => $event->event_type]);

            return;
        }

        $event->update(['tenant_id' => $tenantId]);

        $this->tenants->run($tenantId, function () use ($event, $data) {
            StoreConnection::query()->update(['last_webhook_at' => now()]);

            $this->dispatch($event->event_type, $data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dispatch(string $type, array $data): void
    {
        match (true) {
            $type === 'app.uninstalled' => $this->connections->uninstall(TenantRoute::SALLA),

            in_array($type, ['abandoned.cart', 'abandoned.cart.updated'], true) => $this->recorder->recordAbandonedCart($this->mapper->cart($data), DiscoveredBy::Webhook),

            $type === 'abandoned.cart.purchased' => $this->recorder->markCartPurchased((string) Arr::get($data, 'id')),

            $type === 'abandoned.cart.status.changed' && Arr::get($data, 'status') === 'purchased' => $this->recorder->markCartPurchased((string) Arr::get($data, 'id')),

            str_starts_with($type, 'order.') && ! in_array($type, ['order.deleted'], true) => $this->recorder->recordOrder($this->mapper->order($data)),

            str_starts_with($type, 'product.') && $type !== 'product.deleted' => $this->recorder->recordProduct($this->mapper->product($data)),

            default => null,
        };
    }
}
