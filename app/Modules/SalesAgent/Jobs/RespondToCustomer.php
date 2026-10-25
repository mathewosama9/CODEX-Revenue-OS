<?php

namespace App\Modules\SalesAgent\Jobs;

use App\Modules\SalesAgent\SalesAgent;
use App\Shared\Tenancy\TenantAware;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Carries ids only, never the message or the phone. One run per inbound
 * message (unique in `agent_runs`), so a retry never drafts twice.
 */
class RespondToCustomer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TenantAware;

    /** A provider outage is already a handoff; a crash is not worth a second model bill. */
    public int $tries = 1;

    /** Below the queue's retry_after (90s); the run itself stops retrying after 30s. */
    public int $timeout = 85;

    public function __construct(
        public int $tenantId,
        public int $conversationId,
        public int $inboundMessageId,
    ) {}

    public function handle(SalesAgent $agent): void
    {
        $agent->respond($this->conversationId, $this->inboundMessageId);
    }

    public function failed(): void
    {
        app(TenantContext::class)->run($this->tenantId, fn () => app(SalesAgent::class)->abandon($this->inboundMessageId));
    }
}
