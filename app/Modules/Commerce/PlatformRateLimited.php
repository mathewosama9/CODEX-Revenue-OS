<?php

namespace App\Modules\Commerce;

use RuntimeException;

/** Any commerce platform asked us to slow down; the job releases itself and retries. */
class PlatformRateLimited extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds, string $platform = 'Commerce platform')
    {
        parent::__construct("{$platform} rate limit hit; retry after {$retryAfterSeconds}s.");
    }
}
