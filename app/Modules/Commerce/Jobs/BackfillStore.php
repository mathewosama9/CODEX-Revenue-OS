<?php

namespace App\Modules\Commerce\Jobs;

use App\Modules\Commerce\CommerceAdapters;
use App\Modules\Commerce\CommerceRecorder;
use App\Modules\Commerce\Data\PageWindow;
use App\Modules\Commerce\DiscoveredBy;
use App\Modules\Commerce\Models\Category;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\PlatformRateLimited;
use App\Modules\Commerce\StoreStatus;
use App\Shared\Tenancy\TenantAware;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * First pull after install, in priority order (09 §9.7.1): catalog, abandoned
 * carts of the last 30 days, orders of the last 90 days. Backfilled carts are
 * history only — they never start a recovery sequence (see DiscoveredBy).
 *
 * Runs as a chain of short jobs, a few pages each, so a large store finishes
 * well inside the worker timeout and a retry resumes from its own page
 * instead of starting over from the catalog (review H3).
 */
class BackfillStore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TenantAware;

    public const STEPS = ['categories', 'products', 'carts', 'orders'];

    /** Pages per job: about 300 rows, a few seconds of Salla calls. */
    public const PAGES_PER_JOB = 5;

    public int $tries = 10;

    /** Below the queue's retry_after (90s), so a slow job is never run twice at once. */
    public int $timeout = 75;

    /**
     * @param  string|null  $ordersSince  ISO time fixed by the first job, so every orders page uses the same window
     */
    public function __construct(
        public int $tenantId,
        public string $step = 'categories',
        public int $page = 1,
        public ?string $ordersSince = null,
    ) {}

    public function handle(CommerceAdapters $adapters, CommerceRecorder $recorder): void
    {
        $store = StoreConnection::query()->where('status', StoreStatus::Active)->first();

        if ($store === null) {
            return;
        }

        $adapter = $adapters->for($store);

        $ordersSince = $this->ordersSince ?? CarbonImmutable::now()->subDays(90)->toIso8601String();
        $window = new PageWindow($this->page, self::PAGES_PER_JOB);

        try {
            match ($this->step) {
                'categories' => $this->each($adapter->categories($store, $window), fn (array $category) => Category::query()->updateOrCreate(
                    ['external_id' => $category['external_id']],
                    ['name' => $category['name']],
                )),
                'products' => $this->each($adapter->products($store, $window), fn ($product) => $recorder->recordProduct($product)),
                'carts' => $this->each($adapter->abandonedCarts($store, 30 * 24 * 60, $window), fn ($cart) => $recorder->recordAbandonedCart($cart, DiscoveredBy::Backfill)),
                'orders' => $this->each($adapter->ordersSince($store, CarbonImmutable::parse($ordersSince), $window), fn ($order) => $recorder->recordOrder($order)),
                default => null,
            };
        } catch (PlatformRateLimited $exception) {
            $this->release($exception->retryAfterSeconds);

            return;
        }

        if ($window->next !== null) {
            self::dispatch($this->tenantId, $this->step, $window->next, $ordersSince);

            return;
        }

        $position = array_search($this->step, self::STEPS, true);
        $next = $position === false ? null : (self::STEPS[$position + 1] ?? null);

        if ($next !== null) {
            self::dispatch($this->tenantId, $next, 1, $ordersSince);

            return;
        }

        $store->update(['last_reconciled_at' => now()]);
    }

    /**
     * @template T
     *
     * @param  iterable<T>  $rows
     * @param  callable(T): mixed  $record
     */
    private function each(iterable $rows, callable $record): void
    {
        foreach ($rows as $row) {
            $record($row);
        }
    }
}
