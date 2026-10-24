<?php

namespace App\Modules\Commerce\Data;

use Carbon\CarbonImmutable;

final readonly class OrderData
{
    /**
     * @param  list<LineItemData>  $items
     */
    public function __construct(
        public string $externalId,
        public ?CustomerData $customer,
        public int $totalHalalas,
        public int $discountHalalas,
        public int $shippingHalalas,
        public string $currency,
        public ?string $status,
        public ?string $paymentMethod,
        public ?string $couponCode,
        public CarbonImmutable $placedAt,
        public array $items,
        public bool $cancelled = false,
        public int $refundedHalalas = 0,
    ) {}
}
