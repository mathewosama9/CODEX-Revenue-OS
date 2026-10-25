<?php

namespace App\Modules\Commerce\Jobs;

use App\Modules\Commerce\CommerceAdapter;
use App\Modules\Commerce\CommerceRecorder;
use App\Modules\Commerce\Models\Product;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\Salla\SallaRateLimited;
use App\Modules\Commerce\StoreStatus;
use App\Shared\Tenancy\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Full product pass (US-E09-01): webhooks keep prices current between runs,
 * this catches what they missed, and a product the store no longer lists is
 * removed. The agent refuses any price older than
 * `codex.ai.price_max_age_minutes`, so a failing sync means handoffs, never
 * an old price.
 *
 * [افتراض] Salla documents no single-product endpoint (docs.salla.dev, read
 * 2026-10-25), so freshness comes from this pass, not from a live read.
 */
class SyncCatalog implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TenantAware;

    public int $tries = 5;

    public int $timeout = 300;

    /** @var list<int> */
    public array $backoff = [30, 60, 120, 300];

    public function __construct(public int $tenantId) {}

    public function uniqueId(): string
    {
        return (string) $this->tenantId;
    }

    public function handle(CommerceAdapter $adapter, CommerceRecorder $recorder): void
    {
        $store = StoreConnection::query()->where('status', StoreStatus::Active)->first();

        if ($store === null) {
            return;
        }

        $seen = [];

        try {
            foreach ($adapter->products($store) as $product) {
                $recorder->recordProduct($product);
                $seen[$product->externalId] = true;
            }
        } catch (SallaRateLimited $exception) {
            $this->release($exception->retryAfterSeconds);

            return;
        }

        // Only after a complete pass: a partial one must not delete anything.
        Product::query()
            // NULL != 'deleted' is not true in SQL: a product without a status would never leave (review #9).
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'deleted'))
            ->pluck('external_id')
            ->reject(fn (string $id) => isset($seen[$id]))
            ->each(fn (string $id) => $recorder->removeProduct($id));
    }
}
