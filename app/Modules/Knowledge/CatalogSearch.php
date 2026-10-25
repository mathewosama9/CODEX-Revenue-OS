<?php

namespace App\Modules\Knowledge;

use App\Modules\Knowledge\Data\CatalogHit;

/**
 * Finds listed products of the current tenant, however the customer spells
 * them (US-E09-03). Used by the agent's `search_products` tool.
 */
interface CatalogSearch
{
    /**
     * @return list<CatalogHit> best first
     */
    public function search(string $query, int $limit = 5): array;
}
