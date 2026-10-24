<?php

namespace App\Modules\Recovery;

use App\Modules\Commerce\Commerce;
use App\Modules\Commerce\Data\CartSnapshot;
use App\Modules\Measurement\CostKind;
use App\Modules\Measurement\CostLedger;
use App\Modules\Measurement\MarginLookup;
use App\Modules\Recovery\Models\RecoverySettings;
use Carbon\CarbonImmutable;

/**
 * Discount rules in code, never in a model (Rule 9, ADR-014). The order of
 * checks is the policy:
 *
 *  1. Message 1 never carries a discount (product rule, not a setting).
 *  2. Discounts must be switched on, with a registered code.
 *  3. The discount may not push any item's category margin below the floor → no discount.
 *  4. The monthly discount budget must not be exhausted → no discount.
 *  5. Above the cap, or for a customer who already bought before → owner approval.
 */
class DiscountPolicy
{
    public function __construct(
        private readonly MarginLookup $margins,
        private readonly Commerce $commerce,
        private readonly CostLedger $ledger,
    ) {}

    public function decide(RecoverySettings $settings, CartSnapshot $cart, int $step): DiscountDecision
    {
        if ($step <= 1) {
            return DiscountDecision::none('first_message');
        }

        if (! $settings->discount_enabled || $settings->discount_code === null || $settings->discount_percent <= 0) {
            return DiscountDecision::none('disabled');
        }

        $percent = $settings->discount_percent;

        if (! $this->keepsMarginFloor($cart, $percent, $settings->margin_floor_bps)) {
            return DiscountDecision::none('below_margin_floor');
        }

        if ($this->budgetExhausted($settings)) {
            return DiscountDecision::none('budget_exhausted');
        }

        if ($percent > $settings->discount_cap_percent) {
            return DiscountDecision::needsApproval($percent, 'above_cap');
        }

        if ($cart->phoneHash !== null && $this->commerce->hasOrderedBefore($cart->phoneHash, $cart->abandonedAt ?? now()->toImmutable())) {
            return DiscountDecision::needsApproval($percent, 'existing_customer');
        }

        return DiscountDecision::apply($percent, $settings->discount_code);
    }

    private function keepsMarginFloor(CartSnapshot $cart, int $percent, int $floorBps): bool
    {
        $categories = [];

        foreach ($cart->items as $item) {
            $categories[] = $item->categoryExternalId;
        }

        foreach ($categories === [] ? [null] : array_unique($categories) as $category) {
            if ($this->margins->marginBps($category) - ($percent * 100) < $floorBps) {
                return false;
            }
        }

        return true;
    }

    private function budgetExhausted(RecoverySettings $settings): bool
    {
        if ($settings->monthly_discount_budget_halalas <= 0) {
            return false;
        }

        $spentMicros = $this->ledger->spentMicros(CostKind::Discount, CarbonImmutable::now()->startOfMonth());

        return $spentMicros >= $settings->monthly_discount_budget_halalas * 10_000;
    }
}
