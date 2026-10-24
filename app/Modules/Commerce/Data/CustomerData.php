<?php

namespace App\Modules\Commerce\Data;

use App\Shared\Phone\PhoneNumber;

final readonly class CustomerData
{
    public function __construct(
        public ?string $externalId,
        public ?PhoneNumber $phone,
    ) {}
}
