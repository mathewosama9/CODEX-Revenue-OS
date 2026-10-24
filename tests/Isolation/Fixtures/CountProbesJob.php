<?php

namespace Tests\Isolation\Fixtures;

use App\Shared\Tenancy\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CountProbesJob implements ShouldQueue
{
    use Dispatchable, Queueable, TenantAware;

    /** @var list<list<string>> */
    public static array $seen = [];

    public function __construct(public int $tenantId) {}

    public function handle(): void
    {
        /** @var list<string> $labels */
        $labels = TenancyProbe::query()->pluck('label')->all();

        static::$seen[] = $labels;
    }
}
