<?php

namespace App\Modules\Commerce\Data;

use Carbon\CarbonImmutable;

final readonly class TokenSet
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public CarbonImmutable $expiresAt,
        public ?string $scope = null,
    ) {}
}
