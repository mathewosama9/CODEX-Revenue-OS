<?php

namespace App\Modules\AiGateway;

enum AiStatus: string
{
    case Ok = 'ok';
    /** The tenant's daily AI budget is spent (US-E10-06): hand off, never reply unchecked. */
    case CapReached = 'cap_reached';
    /** Provider down, timed out or rate limited (S8): hand off. */
    case ProviderUnavailable = 'provider_unavailable';
    case Failed = 'failed';
}
