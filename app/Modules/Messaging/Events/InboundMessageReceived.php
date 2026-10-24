<?php

namespace App\Modules\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A customer wrote to the merchant. Opt-out was already handled before this fires (09 §9.8).
 */
final class InboundMessageReceived
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $messageId,
        public readonly ?string $subjectHash,
        public readonly bool $isOptOut,
        public readonly bool $isAmbiguous,
    ) {}
}
