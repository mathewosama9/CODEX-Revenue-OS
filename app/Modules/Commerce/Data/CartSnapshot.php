<?php

namespace App\Modules\Commerce\Data;

use App\Modules\Commerce\CartStatus;
use App\Shared\Phone\PhoneNumber;
use Carbon\CarbonImmutable;

/**
 * What other modules may know about a cart (they never touch Commerce models).
 */
final readonly class CartSnapshot
{
    /**
     * @param  list<LineItemData>  $items
     */
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $externalId,
        public ?string $phoneHash,
        public ?PhoneNumber $phone,
        public int $totalHalalas,
        public string $currency,
        public ?string $checkoutUrl,
        public CartStatus $status,
        public ?CarbonImmutable $abandonedAt,
        public array $items,
    ) {}
}
