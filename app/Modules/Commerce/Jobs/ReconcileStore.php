<?php

namespace App\Modules\Commerce\Jobs;

use App\Modules\Commerce\CommerceAdapters;
use App\Modules\Commerce\CommerceRecorder;
use App\Modules\Commerce\DiscoveredBy;
use App\Modules\Commerce\Models\Category;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\PlatformRateLimited;
use App\Modules\Commerce\StoreStatus;
use App\Shared\Tenancy\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

/**
 * Salla retries a webhook only 3 times (~15 minutes). Polling catches what was
 * lost; anything found here that never came as a webhook is a gap (P1 exit #8).
 */
class ReconcileStore implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TenantAware;

    private const MAX_LOOKBACK_MINUTES = 180;

    /**
     * Each 429 releases the job, and every release counts as an attempt: one
     * attempt meant the first rate limit failed the whole cycle (review H4).
     */
    public int $tries = 5;

    public int $timeout = 75;

    /** @var list<int> */
    public array $backoff = [30, 60, 120, 300];

    private const OVERLAP_MINUTES = 15;

    public function __construct(public int $tenantId) {}

    public function uniqueId(): string
    {
        return (string) $this->tenantId;
    }

    public function handle(CommerceAdapters $adapters, CommerceRecorder $recorder): void
    {
        $store = StoreConnection::query()->where('status', StoreStatus::Active)->first();

        if ($store === null) {
            return;
        }

        $adapter = $adapters->for($store);

        $since = $store->last_reconciled_at ?? now()->subMinutes(self::MAX_LOOKBACK_MINUTES);
        $minutes = (int) min(self::MAX_LOOKBACK_MINUTES, $since->diffInMinutes(now()) + self::OVERLAP_MINUTES);
        $startedAt = now();
        $gaps = 0;

        try {
            // Categories change rarely; once a day keeps the margins screen complete (review H3).
            if (Cache::add("commerce:categories-synced:{$this->tenantId}", true, now()->addDay())) {
                foreach ($adapter->categories($store) as $category) {
                    Category::query()->updateOrCreate(['external_id' => $category['external_id']], ['name' => $category['name']]);
                }
            }

            foreach ($adapter->abandonedCarts($store, $minutes) as $cart) {
                $gaps += $recorder->recordAbandonedCart($cart, DiscoveredBy::Reconciliation) ? 1 : 0;
            }

            foreach ($adapter->ordersSince($store, $startedAt->subMinutes($minutes)) as $order) {
                $gaps += $recorder->recordOrder($order) ? 1 : 0;
            }
        } catch (PlatformRateLimited $exception) {
            $this->release($exception->retryAfterSeconds);

            return;
        }

        $store->update(['last_reconciled_at' => $startedAt]);

        if ($gaps > 0) {
            $store->increment('reconciliation_gap_count', $gaps);
        }
    }
}
