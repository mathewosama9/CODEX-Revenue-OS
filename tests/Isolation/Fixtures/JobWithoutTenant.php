<?php

namespace Tests\Isolation\Fixtures;

use App\Shared\Tenancy\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/** Deliberately broken: a tenant job with no tenantId must fail. */
class JobWithoutTenant implements ShouldQueue
{
    use Dispatchable, Queueable, TenantAware;

    public function handle(): void {}
}
