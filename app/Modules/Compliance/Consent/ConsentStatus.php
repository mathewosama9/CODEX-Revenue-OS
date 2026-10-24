<?php

namespace App\Modules\Compliance\Consent;

enum ConsentStatus: string
{
    case Granted = 'granted';
    case Revoked = 'revoked';
}
