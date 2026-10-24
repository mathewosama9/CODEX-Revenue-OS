<?php

namespace App\Modules\Commerce\Jobs;

use App\Modules\Commerce\CommerceAdapter;
use App\Modules\Commerce\CommerceRecorder;
use App\Modules\Commerce\DiscoveredBy;
use App\Modules\Commerce\Models\Category;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\Salla\SallaRateLimited;
use App\Modules\Commerce\StoreStatus;
use App\Shared\Tenancy\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * First pull after install, in priority order (09 §9.7.1): catalog, abandoned
 * carts of the last 30 days, orders of the last 90 days. Backfilled carts are
 * history only — they never start a recovery sequence (see DiscoveredBy).
 */
class BackfillStore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TenantAware;

    public int $tries = 10;

    public function __construct(public int $tenantId) {}

    public function handle(CommerceAdapter $adapter, CommerceRecorder $recorder): void
    {
        $store = StoreConnection::query()->where('status', StoreStatus::Active)->first();

        if ($store === null) {
            return;
        }

        try {
            foreach ($adapter->categories($store) as $category) {
                Category::query()->updateOrCreate(['external_id' => $category['external_id']], ['name' => $category['name']]);
            }

            foreach ($adapter->products($store) as $product) {
                $recorder->recordProduct($product);
            }

            foreach ($adapter->abandonedCarts($store, 30 * 24 * 60) as $cart) {
                $recorder->recordAbandonedCart($cart, DiscoveredBy::Backfill);
            }

            foreach ($adapter->ordersSince($store, now()->subDays(90)) as $order) {
                $recorder->recordOrder($order);
            }
        } catch (SallaRateLimited $exception) {
            $this->release($exception->retryAfterSeconds);

            return;
        }

        $store->update(['last_reconciled_at' => now()]);
    }
}
