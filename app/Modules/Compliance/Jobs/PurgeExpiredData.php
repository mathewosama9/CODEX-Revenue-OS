<?php

namespace App\Modules\Compliance\Jobs;

use App\Modules\Inbox\Inbox;
use App\Modules\Messaging\Messaging;
use App\Modules\SalesAgent\Drafts;
use App\Shared\Tenancy\TenantAware;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Daily retention per tenant (M10). Windows are config defaults until the
 * lawyer's opinion sets them (09 §9.21 #9). Audit and consent are kept.
 */
class PurgeExpiredData implements ShouldQueue
{
    use Dispatchable, Queueable, TenantAware;

    public function __construct(public int $tenantId) {}

    public function handle(Messaging $messaging, Inbox $inbox, Drafts $drafts): void
    {
        $now = CarbonImmutable::now();

        $messaging->purgeBodiesBefore($now->subDays((int) config('codex.retention.message_body_days')));
        $drafts->purgeBefore($now->subDays((int) config('codex.retention.message_body_days')));
        $inbox->purgeNotesBefore($now->subDays((int) config('codex.retention.conversation_note_days')));
    }
}
