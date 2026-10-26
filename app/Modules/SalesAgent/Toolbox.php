<?php

namespace App\Modules\SalesAgent;

use App\Modules\AiGateway\AgentTool;
use App\Modules\Commerce\Commerce;
use App\Modules\Knowledge\CatalogSearch;
use App\Modules\Knowledge\KnowledgeRetriever;
use App\Modules\SalesAgent\Tools\GetCartLink;
use App\Modules\SalesAgent\Tools\GetPolicy;
use App\Modules\SalesAgent\Tools\GetProduct;
use App\Modules\SalesAgent\Tools\Handoff;
use App\Modules\SalesAgent\Tools\RunContext;
use App\Modules\SalesAgent\Tools\SearchProducts;
use App\Shared\Tenancy\WorkspaceLocale;

/**
 * Every tool the agent may call, by name. Rule 9: no discount, coupon,
 * payment link, cart creation or order change. A test fails if this list
 * grows without the founder's say (enforced in code, not in the prompt).
 */
final class Toolbox
{
    public const ALLOWED = ['search_products', 'get_product', 'get_policy', 'get_cart_link', 'handoff'];

    public function __construct(
        private readonly CatalogSearch $search,
        private readonly KnowledgeRetriever $knowledge,
        private readonly Commerce $commerce,
        private readonly int $priceMaxAgeMinutes,
        private readonly WorkspaceLocale $locale,
    ) {}

    /**
     * @return list<AgentTool>
     */
    public function forRun(RunContext $context): array
    {
        $tools = [
            new SearchProducts($this->search, $this->commerce),
            new GetProduct($this->commerce, $this->priceMaxAgeMinutes, $this->locale->currency()),
            new GetPolicy($this->knowledge, $context),
            new GetCartLink($this->commerce, $context),
            new Handoff($context),
        ];

        $names = array_map(fn (AgentTool $tool) => $tool->name(), $tools);

        if ($names !== self::ALLOWED) {
            throw new \LogicException('The agent toolbox differs from the allowed list.');
        }

        return $tools;
    }
}
