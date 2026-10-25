<?php

namespace App\Modules\Commerce\Data;

/**
 * One SKU of a product, e.g. 50ml or 100ml with its own price and stock.
 */
final readonly class VariantData
{
    public function __construct(
        public string $externalId,
        public string $name,
        public ?int $priceHalalas,
        public ?int $quantity,
        public bool $unlimitedQuantity = false,
    ) {}

    /**
     * @return array{external_id: string, name: string, price_halalas: int|null, quantity: int|null, unlimited_quantity: bool}
     */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'name' => $this->name,
            'price_halalas' => $this->priceHalalas,
            'quantity' => $this->quantity,
            'unlimited_quantity' => $this->unlimitedQuantity,
        ];
    }
}
