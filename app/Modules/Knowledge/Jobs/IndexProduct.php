<?php

namespace App\Modules\Knowledge\Jobs;

use App\Modules\Knowledge\CatalogIndexer;
use App\Shared\Tenancy\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class IndexProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TenantAware;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public function __construct(public int $tenantId, public string $externalId) {}

    public function handle(CatalogIndexer $indexer): void
    {
        $indexer->index($this->externalId);
    }
}
