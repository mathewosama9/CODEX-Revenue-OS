<?php

namespace App\Modules\Commerce\Data;

use Carbon\CarbonImmutable;

final readonly class OrderData
{
    /**
     * @param  list<LineItemData>  $items
     * @param  bool  $breakdownKnown  false when the source carries the total only (Salla's order list has no
     *                                `amounts`): its shipping and discounts read as 0 and must not replace the ones on file
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
        public bool $breakdownKnown = true,
    ) {}
}
