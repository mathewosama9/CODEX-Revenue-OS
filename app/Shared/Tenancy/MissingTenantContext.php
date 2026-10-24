<?php

namespace App\Shared\Tenancy;

use LogicException;

class MissingTenantContext extends LogicException
{
    public function __construct(string $message = 'No tenant context is set; tenant-owned data cannot be read or written.')
    {
        parent::__construct($message);
    }
}
