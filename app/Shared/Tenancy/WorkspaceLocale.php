<?php

namespace App\Shared\Tenancy;

use App\Models\Team;
use App\Shared\Money\Currencies;
use App\Shared\Money\Currency;

/**
 * The current workspace's currency and timezone (ADR-012), set when its
 * store is connected. Read once per tenant per request or job.
 */
final class WorkspaceLocale
{
    /** @var array<int, array{currency: string, timezone: string}> */
    private array $cache = [];

    public function __construct(private readonly TenantContext $tenants) {}

    public function currency(?int $tenantId = null): Currency
    {
        return Currencies::get($this->row($tenantId)['currency']);
    }

    public function timezone(?int $tenantId = null): string
    {
        return $this->row($tenantId)['timezone'];
    }

    /** After a store connect changes them. */
    public function forget(?int $tenantId = null): void
    {
        unset($this->cache[$tenantId ?? $this->tenants->require()]);
    }

    /**
     * @return array{currency: string, timezone: string}
     */
    private function row(?int $tenantId): array
    {
        $id = $tenantId ?? $this->tenants->require();

        if (! isset($this->cache[$id])) {
            $team = Team::query()->whereKey($id)->first(['currency', 'timezone']);
            $this->cache[$id] = [
                'currency' => (string) ($team->currency ?? config('currencies.default')),
                'timezone' => (string) ($team->timezone ?? config('currencies.default_timezone')),
            ];
        }

        return $this->cache[$id];
    }
}
