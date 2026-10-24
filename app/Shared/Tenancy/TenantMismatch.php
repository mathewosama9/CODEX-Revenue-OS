<?php

namespace App\Shared\Tenancy;

use LogicException;

class TenantMismatch extends LogicException
{
    public function __construct(string $message = 'A tenant-owned record cannot be written for, or moved to, another tenant.')
    {
        parent::__construct($message);
    }
}
