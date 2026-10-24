<?php

namespace App\Modules\Compliance\Consent;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Listened to by Recovery (M08) to cancel every pending send for the subject.
 */
final class ConsentRevoked
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $subjectHash,
        public readonly ConsentChannel $channel,
        public readonly ConsentPurpose $purpose,
    ) {}
}
