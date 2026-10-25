<?php

namespace App\Modules\Knowledge\Data;

final readonly class CatalogHit
{
    public function __construct(
        public string $productExternalId,
        public float $score,
    ) {}
}
