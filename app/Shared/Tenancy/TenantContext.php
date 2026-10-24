<?php

namespace App\Shared\Tenancy;

/**
 * The tenant (Team) the current request or job acts for (ADR-004).
 *
 * The tenant always comes from the server side — the route's team after the
 * membership check, or the job's explicit tenant id — never from a webhook
 * payload or an LLM argument.
 */
final class TenantContext
{
    private ?int $tenantId = null;

    public function set(int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function id(): ?int
    {
        return $this->tenantId;
    }

    /**
     * Get the current tenant id, failing closed when none is set.
     *
     * @throws MissingTenantContext
     */
    public function require(): int
    {
        return $this->tenantId ?? throw new MissingTenantContext;
    }

    public function forget(): void
    {
        $this->tenantId = null;
    }

    /**
     * Run the callback as the given tenant, restoring the previous one afterwards.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function run(int $tenantId, callable $callback): mixed
    {
        $previous = $this->tenantId;
        $this->tenantId = $tenantId;

        try {
            return $callback();
        } finally {
            $this->tenantId = $previous;
        }
    }
}
