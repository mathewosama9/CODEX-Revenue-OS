<?php

namespace App\Shared\Tenancy;

/**
 * Use on every queued job that touches tenant-owned data. The job must declare
 * a public int $tenantId (usually via constructor promotion).
 */
trait TenantAware
{
    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new SetTenantForJob];
    }
}
