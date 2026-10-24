<?php

namespace App\Modules\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A send failed after Meta accepted it, or was rejected (e.g. 131049 per-user limit).
 */
final class MessageFailed
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $messageId,
        public readonly ?string $errorCode,
    ) {}
}
