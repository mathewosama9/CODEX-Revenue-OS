<?php

namespace App\Modules\Recovery;

use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Recovery\Models\RecoverySettings;

/**
 * Public kill switch of M08, used by the merchant API and the CODEX admin
 * (US-E14-04). Runs inside the tenant context.
 */
class RecoveryControls
{
    public function __construct(
        private readonly RecoveryLifecycle $lifecycle,
        private readonly AuditLogger $audit,
    ) {}

    public function pause(ActorType $actor, ?int $actorId, string $reason): int
    {
        RecoverySettings::current()->update(['enabled' => false]);

        $cancelled = $this->lifecycle->cancelWhere(fn () => null, 'paused');

        $this->audit->record('recovery.paused', $actor, $actorId, context: ['reason' => $reason, 'sequences_cancelled' => $cancelled]);

        return $cancelled;
    }

    public function isEnabled(): bool
    {
        return RecoverySettings::current()->enabled;
    }
}
