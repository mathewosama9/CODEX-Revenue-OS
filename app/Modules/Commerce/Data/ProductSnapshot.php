<?php

namespace App\Modules\Commerce\Data;

use Carbon\CarbonImmutable;

/**
 * What other modules may know about a product. Prices in halalas.
 */
final readonly class ProductSnapshot
{
    /**
     * @param  list<array{external_id: string, name: string, price_halalas: int|null, quantity: int|null, unlimited_quantity: bool}>  $variants
     */
    public function __construct(
        public string $externalId,
        public string $name,
        public ?string $sku,
        public ?string $categoryName,
        public ?string $description,
        public ?string $url,
        public ?int $priceHalalas,
        public ?int $regularPriceHalalas,
        public ?int $quantity,
        public bool $unlimitedQuantity,
        public ?string $status,
        public ?bool $isAvailable,
        public array $variants,
        public ?CarbonImmutable $syncedAt,
    ) {}

    /** Hidden or deleted products are never offered (US-E09-05). */
    public function listed(): bool
    {
        return ! in_array($this->status, ['hidden', 'deleted'], true);
    }

    public function inStock(): bool
    {
        if (! $this->listed() || $this->status === 'out' || $this->isAvailable === false) {
            return false;
        }

        return $this->unlimitedQuantity || $this->quantity === null || $this->quantity > 0;
    }
}
