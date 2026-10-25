<?php

namespace App\Modules\SalesAgent\Jobs;

use App\Modules\SalesAgent\SalesAgent;
use App\Shared\Tenancy\TenantAware;
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

    public int $timeout = 90;

    public function __construct(
        public int $tenantId,
        public int $conversationId,
        public int $inboundMessageId,
    ) {}

    public function handle(SalesAgent $agent): void
    {
        $agent->respond($this->conversationId, $this->inboundMessageId);
    }
}
