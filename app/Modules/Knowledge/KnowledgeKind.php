<?php

namespace App\Modules\Knowledge;

enum KnowledgeKind: string
{
    case Faq = 'faq';
    case Shipping = 'shipping';
    case Returns = 'returns';
}
