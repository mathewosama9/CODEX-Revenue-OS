<?php

namespace App\Modules\SalesAgent;

/**
 * 09 §9.11.3. Assisted is the only mode a tenant can switch on by itself:
 * every AI reply is a draft a member approves.
 */
enum AgentMode: string
{
    case Off = 'off';
    case Assisted = 'assisted';
    /** Built, but locked: admin flag + owner's written approval + 4 weeks of Assisted. */
    case ScopedAutonomous = 'scoped_autonomous';
}
