<?php

namespace App\Modules\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A customer wrote to the merchant. Opt-out was already handled before this fires (09 §9.8).
 *
 * Listeners run in the webhook's transaction and must stay synchronous: the
 * phone is plain here and must never be serialized into a queued payload.
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
        public readonly ?string $phoneE164 = null,
    ) {}
}
