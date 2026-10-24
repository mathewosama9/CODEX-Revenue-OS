<?php

namespace App\Modules\Platform;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * One row per merchant with everything that can silently stop recovery
 * (US-E14-01). Platform is allowed to read across tenants (09 §9.4.2); it
 * reads ids, statuses and counts only — never customer data.
 */
class PlatformHealth
{
    /**
     * @return array<string, mixed>
     */
    public function forTenant(int $tenantId): array
    {
        $store = DB::table('store_connections')->where('tenant_id', $tenantId)->first();
        $number = DB::table('whatsapp_numbers')->where('tenant_id', $tenantId)->first();
        $since = CarbonImmutable::now()->subDay();

        $health = [
            'store_status' => $store->status ?? 'not_connected',
            'token_expires_at' => $store->access_expires_at ?? null,
            'last_webhook_at' => $store->last_webhook_at ?? null,
            'reconciliation_gaps' => (int) ($store->reconciliation_gap_count ?? 0),
            'whatsapp_quality' => $number->quality_rating ?? 'not_connected',
            'templates_approved' => DB::table('message_templates')->where('tenant_id', $tenantId)->where('status', 'APPROVED')->count(),
            'recovery_enabled' => (bool) DB::table('recovery_settings')->where('tenant_id', $tenantId)->value('enabled'),
            'failed_actions' => DB::table('scheduled_actions')->where('tenant_id', $tenantId)->where('status', 'failed')->where('updated_at', '>=', $since)->count(),
            'failed_webhooks' => DB::table('webhook_events')->where('tenant_id', $tenantId)->where('status', 'failed')->where('received_at', '>=', $since)->count(),
            'srm_flagged' => DB::table('experiments')->where('tenant_id', $tenantId)->whereNotNull('srm_flagged_at')->exists(),
        ];

        return $health + ['level' => $this->level($health)];
    }

    /**
     * @param  array<string, mixed>  $health
     */
    private function level(array $health): string
    {
        if ($health['store_status'] === 'needs_reauth' || $health['whatsapp_quality'] === 'RED' || $health['srm_flagged'] || $health['failed_webhooks'] > 0) {
            return 'red';
        }

        $expires = $health['token_expires_at'] === null ? null : CarbonImmutable::parse($health['token_expires_at']);

        if ($health['whatsapp_quality'] === 'YELLOW' || $health['failed_actions'] > 0 || $health['reconciliation_gaps'] > 0 || ($expires !== null && $expires->isBefore(now()->addDays(3)))) {
            return 'yellow';
        }

        return 'green';
    }
}
