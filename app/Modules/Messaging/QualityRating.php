<?php

namespace App\Modules\Messaging;

enum QualityRating: string
{
    case Green = 'GREEN';
    case Yellow = 'YELLOW';
    case Red = 'RED';
    case Unknown = 'UNKNOWN';

    /** RED stops recovery for the number automatically (09 §9.7.2). */
    public function allowsMarketing(): bool
    {
        return $this !== self::Red;
    }
}
