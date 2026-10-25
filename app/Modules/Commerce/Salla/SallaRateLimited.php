<?php

namespace App\Modules\Commerce\Salla;

use App\Modules\Commerce\PlatformRateLimited;

class SallaRateLimited extends PlatformRateLimited
{
    public function __construct(int $retryAfterSeconds)
    {
        parent::__construct($retryAfterSeconds, 'Salla');
    }
}
