<?php

namespace App\Modules\Commerce\Salla;

use RuntimeException;

class SallaRateLimited extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct("Salla rate limit hit; retry after {$retryAfterSeconds}s.");
    }
}
