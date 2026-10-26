<?php

namespace App\Modules\Commerce\Jobs;

use App\Modules\Commerce\CommerceAdapters;
use App\Modules\Commerce\CommerceRecorder;
use App\Modules\Commerce\Data\PageWindow;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\PlatformRateLimited;
use App\Modules\Commerce\StoreStatus;
use App\Shared\Tenancy\TenantAware;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Refunds and cancellations reach us only as updates to an order. Reconciliation
 * polls by placement time over the last few hours, so when the update to an
 * older order is lost (Salla retries a webhook only 3 times), the profit report
 * keeps counting money the merchant gave back. Once a day this re-reads every
 * order placed inside the refresh window. recordOrder is idempotent, and a
 * refund only ever grows and a cancellation never clears, so re-reading is safe.
 *
 * WooCommerce lists full orders, refunds included. Salla's `GET /orders` has the
 * status but no refund amount we can trust yet (`payment_actions.refund_action`
 * is undocumented [افتراض]), so there it catches cancellations and lost orders;
 * Salla refunds still come from the `order.refunded` webhook until the spike.
 *
 * Runs as a chain of short jobs that resume from their page, like BackfillStore
 * (review H3). Not unique: the next page is dispatched while this job still runs.
 */
class RefreshRecentOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TenantAware;

    /** Pages per job: about 300 orders, a few seconds of platform calls. */
    public const PAGES_PER_JOB = 5;

    /**
     * Hard stop for the whole chain, so a platform that keeps reporting more
     * pages cannot queue jobs forever: 400 pages is 24,000 Salla orders or
     * 40,000 WooCommerce orders, far above any ICP store's month.
     */
    public const MAX_PAGES = 400;

    public int $tries = 10;

    /** Below the queue's retry_after (90s), so a slow job is never run twice at once. */
    public int $timeout = 75;

    /**
     * @param  string|null  $since  ISO time fixed by the first job, so every page reads the same window
     */
    public function __construct(
        public int $tenantId,
        public int $page = 1,
        public ?string $since = null,
    ) {}

    public function handle(CommerceAdapters $adapters, CommerceRecorder $recorder): void
    {
        $store = StoreConnection::query()->where('status', StoreStatus::Active)->first();

        if ($store === null) {
            return;
        }

        $since = $this->since ?? CarbonImmutable::now()->subDays((int) config('codex.measurement.order_refresh_days'))->toIso8601String();
        $window = new PageWindow($this->page, self::PAGES_PER_JOB);
        $gaps = 0;
        $rateLimited = null;

        try {
            foreach ($adapters->for($store)->ordersSince($store, CarbonImmutable::parse($since), $window) as $order) {
                // An order we never saw at all is a lost webhook too (P1 exit #8).
                $gaps += $recorder->recordOrder($order) ? 1 : 0;
            }
        } catch (PlatformRateLimited $exception) {
            $rateLimited = $exception;
        }

        // Counted before a release too: the retry sees these orders as known.
        if ($gaps > 0) {
            $store->increment('reconciliation_gap_count', $gaps);
        }

        if ($rateLimited !== null) {
            $this->release($rateLimited->retryAfterSeconds);

            return;
        }

        if ($window->next === null) {
            return;
        }

        if ($window->next > self::MAX_PAGES) {
            Log::warning('Order refresh stopped at its page limit.', ['tenant_id' => $this->tenantId, 'pages' => self::MAX_PAGES]);

            return;
        }

        self::dispatch($this->tenantId, $window->next, $since);
    }
}
