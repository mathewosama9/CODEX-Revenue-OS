<?php

namespace App\Modules\Commerce\Data;

final readonly class ProductData
{
    public function __construct(
        public string $externalId,
        public string $name,
        public ?string $sku,
        public ?string $categoryExternalId,
        public ?int $priceHalalas,
        public ?int $quantity,
        public ?string $status,
    ) {}
}
