<?php

namespace App\Modules\Recovery\Listeners;

use App\Modules\Billing\UsageMeter;
use App\Modules\Commerce\Commerce;
use App\Modules\Commerce\Events\CartAbandoned;
use App\Modules\Commerce\Events\CartPurchased;
use App\Modules\Commerce\Events\OrderPlaced;
use App\Modules\Commerce\Events\StoreDisconnected;
use App\Modules\Compliance\Consent\ConsentRevoked;
use App\Modules\Measurement\CostLedger;
use App\Modules\Messaging\Events\InboundMessageReceived;
use App\Modules\Messaging\Events\NumberQualityChanged;
use App\Modules\Recovery\Jobs\StartRecovery;
use App\Modules\Recovery\Models\RecoverySequence;
use App\Modules\Recovery\Models\RecoverySettings;
use App\Modules\Recovery\Models\ScheduledAction;
use App\Modules\Recovery\RecoveryLifecycle;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Events\Dispatcher;

/**
 * Everything that must stop a pending send, as soon as it happens (06 S2/S3/S8).
 */
class RecoveryListeners
{
    public function __construct(
        private readonly TenantContext $tenants,
        private readonly RecoveryLifecycle $lifecycle,
        private readonly Commerce $commerce,
        private readonly CostLedger $ledger,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(CartAbandoned::class, function (CartAbandoned $event) {
            $this->in($event->tenantId, fn () => app(UsageMeter::class)->increment(UsageMeter::CARTS_ABANDONED));
            StartRecovery::dispatch($event->tenantId, $event->cartId);
        });

        $events->listen(CartPurchased::class, fn (CartPurchased $event) => $this->in($event->tenantId, fn () => $this->lifecycle->cancelWhere(
            fn (Builder $query) => $query->where('cart_id', $event->cartId),
            'purchased',
        )));

        $events->listen(OrderPlaced::class, fn (OrderPlaced $event) => $this->in($event->tenantId, fn () => $this->orderPlaced($event->orderId)));

        $events->listen(ConsentRevoked::class, fn (ConsentRevoked $event) => $this->in($event->tenantId, fn () => $this->lifecycle->cancelWhere(
            fn (Builder $query) => $query->where('subject_hash', $event->subjectHash),
            'opt_out',
        )));

        $events->listen(StoreDisconnected::class, fn (StoreDisconnected $event) => $this->in($event->tenantId, fn () => $this->lifecycle->cancelWhere(
            fn () => null,
            'store_disconnected',
        )));

        $events->listen(NumberQualityChanged::class, fn (NumberQualityChanged $event) => $event->quality === 'RED'
            ? $this->in($event->tenantId, fn () => $this->lifecycle->cancelWhere(fn () => null, 'quality_red'))
            : null);

        // Any reply hands the conversation to a human: no more automated steps.
        $events->listen(InboundMessageReceived::class, fn (InboundMessageReceived $event) => $event->subjectHash === null || $event->isOptOut
            ? null
            : $this->in($event->tenantId, fn () => $this->lifecycle->cancelWhere(
                fn (Builder $query) => $query->where('subject_hash', $event->subjectHash),
                $event->isAmbiguous ? 'customer_not_now' : 'customer_replied',
            )));
    }

    private function orderPlaced(int $orderId): void
    {
        $order = $this->commerce->order($orderId);

        if ($order === null || $order->phoneHash === null) {
            return;
        }

        $this->lifecycle->cancelWhere(fn (Builder $query) => $query->where('subject_hash', $order->phoneHash), 'ordered');

        // Book the discount only when the order used our recovery code after we sent it.
        $settings = RecoverySettings::current();

        if ($order->couponCode === null || $settings->discount_code === null || strcasecmp($order->couponCode, $settings->discount_code) !== 0 || $order->discountHalalas <= 0) {
            return;
        }

        $sentDiscount = ScheduledAction::query()
            ->whereIn('recovery_sequence_id', RecoverySequence::query()->where('subject_hash', $order->phoneHash)->select('id'))
            ->where('result->discount_percent', '>', 0)
            ->exists();

        if ($sentDiscount) {
            $this->ledger->recordDiscount("order:{$order->id}", $order->phoneHash, $order->discountHalalas, $order->placedAt);
        }
    }

    private function in(int $tenantId, callable $callback): mixed
    {
        return $this->tenants->run($tenantId, $callback);
    }
}
