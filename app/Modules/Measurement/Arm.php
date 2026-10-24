<?php

namespace App\Modules\Measurement;

enum Arm: string
{
    case Treatment = 'treatment';
    case Control = 'control';
}
