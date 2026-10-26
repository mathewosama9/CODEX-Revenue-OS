<?php

namespace App\Modules\Recovery;

use App\Modules\Commerce\Commerce;
use App\Modules\Commerce\Data\CartSnapshot;
use App\Modules\Measurement\CostKind;
use App\Modules\Measurement\CostLedger;
use App\Modules\Measurement\MarginLookup;
use App\Modules\Recovery\Models\RecoverySettings;
use App\Modules\Recovery\Models\ScheduledAction;
use App\Shared\Tenancy\WorkspaceLocale;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Discount rules in code, never in a model (Rule 9, ADR-014). The order of
 * checks is the policy:
 *
 *  1. Message 1 never carries a discount (product rule, not a setting).
 *  2. Discounts must be switched on, with a registered code.
 *  3. The discount may not push any item's category margin below the floor → no discount.
 *  4. The monthly budget must cover this discount, counting every discount
 *     already sent this month as spent → no discount. No budget = no discounts.
 *  5. Above the cap, or for a customer who already bought before → owner approval.
 */
class DiscountPolicy
{
    public function __construct(
        private readonly MarginLookup $margins,
        private readonly Commerce $commerce,
        private readonly CostLedger $ledger,
        private readonly WorkspaceLocale $locale,
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

        if (! $this->coversBudget($settings, $cart, $percent)) {
            return DiscountDecision::none($settings->monthly_discount_budget_halalas <= 0 ? 'no_budget' : 'budget_exhausted');
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

    /** What this discount would cost if the customer used it. */
    public static function expectedDiscountHalalas(CartSnapshot $cart, int $percent): int
    {
        return intdiv($cart->totalHalalas * $percent, 100);
    }

    /**
     * A hard monthly cap (Rule 9). Every discount message sent this month
     * reserves its full expected value, because any of them may still be used;
     * booked redemptions count when they are higher. No budget, no discounts.
     * Amounts are minor units of the workspace currency; the month is the
     * workspace's local calendar month (ADR-012).
     */
    public function coversBudget(RecoverySettings $settings, CartSnapshot $cart, int $percent): bool
    {
        $expectedHalalas = self::expectedDiscountHalalas($cart, $percent);
        $budget = $settings->monthly_discount_budget_halalas;

        if ($budget <= 0) {
            return false;
        }

        $monthStart = CarbonImmutable::now($this->locale->timezone())->startOfMonth()->utc();

        $reserved = (int) ScheduledAction::query()
            ->where('status', ActionStatus::Done)
            ->where('updated_at', '>=', $monthStart)
            ->sum(DB::raw("COALESCE((result->>'discount_reserved_halalas')::bigint, 0)"));

        $booked = intdiv($this->ledger->spentMicros(CostKind::Discount, $monthStart), $this->locale->currency()->microsPerMinor());

        return max($reserved, $booked) + $expectedHalalas <= $budget;
    }
}
