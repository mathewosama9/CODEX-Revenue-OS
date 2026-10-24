<?php

namespace App\Modules\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Quality changed; RED pauses recovery for the number.
 */
final class NumberQualityChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $whatsappNumberId,
        public readonly string $quality,
    ) {}
}
