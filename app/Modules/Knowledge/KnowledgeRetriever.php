<?php

namespace App\Modules\Knowledge;

use App\Modules\Knowledge\Data\PolicyPassage;

/**
 * The merchant's own FAQ, shipping and returns text. Empty means the store
 * has not written it, and the agent must hand off, never invent (US-E09-02).
 */
interface KnowledgeRetriever
{
    /**
     * @return list<PolicyPassage>
     */
    public function retrieve(KnowledgeKind $kind, ?string $question = null, int $limit = 3): array;
}
