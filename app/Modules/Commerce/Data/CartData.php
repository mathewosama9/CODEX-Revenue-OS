<?php

namespace App\Modules\Commerce\Data;

use Carbon\CarbonImmutable;

final readonly class CartData
{
    /**
     * @param  list<LineItemData>  $items
     */
    public function __construct(
        public string $externalId,
        public ?CustomerData $customer,
        public int $totalHalalas,
        public string $currency,
        public ?string $checkoutUrl,
        public array $items,
        public ?CarbonImmutable $abandonedAt,
        public bool $purchased = false,
    ) {}
}
