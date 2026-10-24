<?php

namespace App\Modules\Commerce\Data;

use Carbon\CarbonImmutable;

final readonly class OrderSnapshot
{
    /**
     * @param  list<LineItemData>  $items
     */
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?string $phoneHash,
        public int $totalHalalas,
        public int $discountHalalas,
        public int $shippingHalalas,
        public int $refundedHalalas,
        public ?string $couponCode,
        public CarbonImmutable $placedAt,
        public bool $cancelled,
        public array $items,
    ) {}
}
