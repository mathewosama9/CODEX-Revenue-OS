<?php

namespace App\Modules\Commerce\Data;

final readonly class LineItemData
{
    public function __construct(
        public ?string $productExternalId,
        public ?string $name,
        public int $quantity,
        public int $unitPriceHalalas,
        public ?string $categoryExternalId = null,
    ) {}
}
