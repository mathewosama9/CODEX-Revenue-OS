<?php

namespace App\Modules\Recovery;

use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Recovery\Models\RecoverySequence;
use App\Modules\Recovery\Models\ScheduledAction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ending sequences and actions, with the reason recorded and audited.
 * Cancelled actions are kept, never deleted (US-E14-04).
 */
class RecoveryLifecycle
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function cancelSequence(RecoverySequence $sequence, string $reason): void
    {
        $cancelled = $sequence->actions()
            ->whereIn('status', ActionStatus::open())
            ->update(['status' => ActionStatus::Cancelled, 'last_error' => $reason]);

        if ($sequence->status === SequenceStatus::Active) {
            $sequence->update(['status' => SequenceStatus::Cancelled, 'reason' => $reason, 'ended_at' => CarbonImmutable::now()]);
        }

        if ($cancelled > 0) {
            $this->audit->record('recovery.cancelled', ActorType::System, subject: $sequence, context: [
                'reason' => $reason,
                'actions_cancelled' => $cancelled,
            ]);
        }
    }

    public function completeSequence(RecoverySequence $sequence, string $reason): void
    {
        $sequence->update(['status' => SequenceStatus::Completed, 'reason' => $reason, 'ended_at' => CarbonImmutable::now()]);
    }

    /**
     * Failed actions stay visible as the dead-letter view (09 §9.9.4). A
     * marketing message is never replayed automatically after its moment.
     */
    public function failAction(ScheduledAction $action, string $reason): void
    {
        $action->update(['status' => ActionStatus::Failed, 'last_error' => $reason]);

        $this->audit->record('recovery.action_failed', ActorType::System, subject: $action, context: ['reason' => $reason]);

        if (! $action->sequence->actions()->whereIn('status', ActionStatus::open())->exists()) {
            $this->completeSequence($action->sequence, 'failed_'.$reason);
        }
    }

    /**
     * @param  callable(Builder<RecoverySequence>): mixed  $filter
     */
    public function cancelWhere(callable $filter, string $reason): int
    {
        $query = RecoverySequence::query()->where('status', SequenceStatus::Active);
        $filter($query);

        $count = 0;

        foreach ($query->get() as $sequence) {
            $this->cancelSequence($sequence, $reason);
            $count++;
        }

        return $count;
    }
}
