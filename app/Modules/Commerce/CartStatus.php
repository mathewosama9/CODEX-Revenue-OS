<?php

namespace App\Modules\Commerce;

enum CartStatus: string
{
    case Abandoned = 'abandoned';
    case Purchased = 'purchased';
}
