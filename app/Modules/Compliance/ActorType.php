<?php

namespace App\Modules\Compliance;

enum ActorType: string
{
    case User = 'user';
    case AiAgent = 'ai_agent';
    case System = 'system';
    case Admin = 'admin';
}
