<?php

namespace App\Shared\Tenancy;

use Closure;

/**
 * Job middleware: every tenant job carries an explicit `tenantId` and runs
 * inside that tenant only. A job without one fails instead of inheriting
 * whatever tenant the worker last saw.
 */
class SetTenantForJob
{
    public function handle(object $job, Closure $next): mixed
    {
        $tenantId = property_exists($job, 'tenantId') ? $job->tenantId : null;

        if (! is_int($tenantId)) {
            throw new MissingTenantContext('Tenant job '.$job::class.' has no tenantId.');
        }

        return app(TenantContext::class)->run($tenantId, fn () => $next($job));
    }
}
