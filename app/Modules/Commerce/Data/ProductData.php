<?php

namespace App\Modules\Commerce\Data;

final readonly class ProductData
{
    /**
     * @param  int|null  $priceHalalas  what the customer pays now (the sale price while on sale)
     * @param  list<VariantData>  $variants
     */
    public function __construct(
        public string $externalId,
        public string $name,
        public ?string $sku,
        public ?string $categoryExternalId,
        public ?int $priceHalalas,
        public ?int $quantity,
        public ?string $status,
        public ?string $description = null,
        public ?string $url = null,
        public ?int $regularPriceHalalas = null,
        public ?bool $isAvailable = null,
        public bool $unlimitedQuantity = false,
        public array $variants = [],
    ) {}
}
