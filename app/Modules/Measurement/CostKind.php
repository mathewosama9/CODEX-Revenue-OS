<?php

namespace App\Modules\Measurement;

enum CostKind: string
{
    case MetaMessage = 'meta_message';
    case Discount = 'discount';
    case Llm = 'llm';
}
