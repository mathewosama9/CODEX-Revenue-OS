<?php

namespace App\Modules\Knowledge\Data;

use App\Modules\Knowledge\KnowledgeKind;

final readonly class PolicyPassage
{
    public function __construct(
        public int $documentId,
        public KnowledgeKind $kind,
        public string $title,
        public string $text,
    ) {}
}
