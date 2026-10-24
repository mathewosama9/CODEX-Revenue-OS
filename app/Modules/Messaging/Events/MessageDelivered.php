<?php

namespace App\Modules\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Meta charges on delivery (spine §7.2): Measurement books the cost here.
 */
final class MessageDelivered
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $messageId,
        public readonly ?string $pricingCategory,
        public readonly bool $billable,
    ) {}
}
