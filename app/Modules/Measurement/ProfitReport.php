<?php

namespace App\Modules\Measurement;

use App\Modules\Commerce\Commerce;
use App\Modules\Commerce\Data\OrderSnapshot;
use App\Modules\Measurement\Models\CostLedgerEntry;
use App\Modules\Measurement\Models\Exposure;
use App\Modules\Measurement\Models\Touch;
use App\Shared\Money\Currency;
use App\Shared\Stats\Normal;
use App\Shared\Tenancy\WorkspaceLocale;
use Carbon\CarbonImmutable;

/**
 * Incremental Profit Report (E07, ADR-013).
 *
 * Unit: a customer assigned to the experiment who had an eligible cart in the
 * period (intent-to-treat), counted once, including customers who did not buy.
 * Outcome per customer over a fixed window after their first eligible cart:
 *
 *   contribution = Σ orders (net sale × category margin) − message/LLM costs
 *   net sale     = order total − shipping − refunds; cancelled orders = 0
 *
 * Discounts are already inside the net sale, so they are shown in the cost
 * line but never subtracted twice (independent review #1). Only customers
 * whose window has closed are counted (uniform maturity).
 *
 * The headline is the difference in mean contribution per customer, as an
 * interval with the probability that it is positive — never a single number.
 *
 * Every amount is minor units of the workspace currency (ADR-012; the
 * *_halalas keys keep their historic names). The ledger already holds
 * micros of that currency, so nothing is mixed or converted here.
 */
class ProfitReport
{
    public function __construct(
        private readonly ExperimentAssigner $assigner,
        private readonly Commerce $commerce,
        private readonly MarginLookup $margins,
        private readonly int $windowDays,
        private readonly int $attributionHours,
        private readonly float $mdeUsdPerSubject,
        private readonly string $methodVersion,
        private readonly WorkspaceLocale $locale,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function compute(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $experiment = $this->assigner->experiment();
        $now = CarbonImmutable::now();
        $currency = $this->locale->currency();
        $microsPerMinor = $currency->microsPerMinor();

        $base = [
            // Merchant days, "to" inclusive (the query end is exclusive).
            'period' => ['from' => ReportPeriod::firstDay($from), 'to' => ReportPeriod::lastDay($to)],
            'method_version' => $this->methodVersion,
            'outcome_window_days' => $this->windowDays,
            'holdout_bps' => $experiment->holdout_bps,
            'currency' => $currency->toArray(),
        ];

        if ($experiment->srm_flagged_at !== null) {
            return $base + ['status' => 'frozen', 'reason' => 'sample_ratio_mismatch'];
        }

        $subjects = $this->subjects($from, $to);
        $matured = array_filter($subjects, fn (array $subject) => $subject['first_eligible_at']->addDays($this->windowDays)->lessThanOrEqualTo($now));

        $outcomes = ['treatment' => [], 'control' => []];
        $revenue = ['treatment' => [], 'control' => []];
        $converted = ['treatment' => 0, 'control' => 0];
        $assumedMarginRevenue = 0;
        $totalRevenue = 0;
        $attributed = 0;

        // Bulk loads: one pass per source instead of one query per customer.
        $hashes = array_map('strval', array_keys($matured));
        $windowStart = $from;
        $windowLimit = $to->addDays($this->windowDays);
        $ordersBySubject = $this->commerce->ordersForSubjects($hashes, $windowStart, $windowLimit);
        $touchesBySubject = $this->touchesBySubject($hashes, $windowStart, $windowLimit);
        $costsBySubject = $this->costsBySubject($hashes, $windowStart, $windowLimit);

        foreach ($matured as $hash => $subject) {
            $windowEnd = $subject['first_eligible_at']->addDays($this->windowDays);
            $inWindow = fn (CarbonImmutable $at) => $at->greaterThanOrEqualTo($subject['first_eligible_at']) && $at->lessThanOrEqualTo($windowEnd);

            $orders = array_filter($ordersBySubject[$hash] ?? [], fn (OrderSnapshot $order) => $inWindow($order->placedAt));
            $touches = $subject['arm'] === 'treatment' ? array_values(array_filter($touchesBySubject[$hash] ?? [], $inWindow)) : [];

            $contribution = 0.0;
            $netSales = 0;

            foreach ($orders as $order) {
                $net = $this->netSale($order);
                [$margin, $assumedShare] = $this->orderMargin($order);

                $contribution += $net * $margin;
                $netSales += $net;
                $totalRevenue += $net;
                $assumedMarginRevenue += (int) round($net * $assumedShare);

                if ($this->isAttributed($order, $touches)) {
                    $attributed += $net;
                }
            }

            $costs = 0;

            foreach ($costsBySubject[$hash] ?? [] as [$at, $micros]) {
                $costs += $inWindow($at) ? $micros : 0;
            }

            $outcomes[$subject['arm']][] = $contribution - $costs / $microsPerMinor;
            $revenue[$subject['arm']][] = $netSales;
            $converted[$subject['arm']] += $netSales > 0 ? 1 : 0;
        }

        $t = Normal::describe($outcomes['treatment']);
        $c = Normal::describe($outcomes['control']);
        $rt = Normal::describe($revenue['treatment']);
        $rc = Normal::describe($revenue['control']);

        $periodCosts = $this->periodCosts($from, $to, $microsPerMinor);

        $report = $base + [
            'status' => 'ok',
            'sample' => [
                'treatment' => $t['n'],
                'control' => $c['n'],
                'not_yet_matured' => count($subjects) - count($matured),
            ],
            'conversion' => [
                'treatment' => $t['n'] > 0 ? $converted['treatment'] / $t['n'] : null,
                'control' => $c['n'] > 0 ? $converted['control'] / $c['n'] : null,
            ],
            // 1. What other tools would claim: orders within 72h of a delivered message.
            'attributed_revenue_halalas' => $attributed,
            // 2 & 4. Our honest numbers, as ranges for the whole treated group.
            'incremental_revenue_halalas' => $this->scaledInterval($rt, $rc, $t['n']),
            'incremental_profit_halalas' => $this->scaledInterval($t, $c, $t['n']),
            // 3. What it cost.
            'costs_halalas' => $periodCosts,
            // Some cost has no sourced price (a Meta rate for a country we have no price for).
            'costs_estimated' => $this->costsEstimated($from, $to),
            'margin_quality' => [
                'revenue_on_assumed_margin_share' => $totalRevenue > 0 ? $assumedMarginRevenue / $totalRevenue : 0.0,
                'warning' => $totalRevenue > 0 && $assumedMarginRevenue / $totalRevenue > 0.3,
            ],
        ];

        $report['verdict'] = $report['incremental_profit_halalas']['verdict'];
        $report['additional_customers_needed'] = $report['verdict'] === 'inconclusive'
            ? $this->additionalNeeded($t, $c, $experiment->holdout_bps, $currency)
            : 0;

        return $report;
    }

    /**
     * @return array<string, array{arm: string, first_eligible_at: CarbonImmutable}>
     */
    private function subjects(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $subjects = [];

        $exposures = Exposure::query()
            ->where('experiment_id', $this->assigner->experiment()->id)
            ->where('eligible_at', '>=', $from)
            ->where('eligible_at', '<', $to)
            ->orderBy('eligible_at')
            ->get(['subject_hash', 'arm', 'eligible_at']);

        foreach ($exposures as $exposure) {
            $subjects[$exposure->subject_hash] ??= ['arm' => $exposure->arm, 'first_eligible_at' => $exposure->eligible_at];
        }

        return $subjects;
    }

    private function netSale(OrderSnapshot $order): int
    {
        if ($order->cancelled) {
            return 0;
        }

        return max(0, $order->totalHalalas - $order->shippingHalalas - $order->refundedHalalas);
    }

    /**
     * Value-weighted margin of the order's items, and the share of its value
     * that relied on the assumed default margin.
     *
     * @return array{0: float, 1: float}
     */
    private function orderMargin(OrderSnapshot $order): array
    {
        $value = 0;
        $weighted = 0.0;
        $assumed = 0;

        foreach ($order->items as $item) {
            $line = $item->unitPriceHalalas * $item->quantity;
            $resolved = $this->margins->resolve($item->categoryExternalId, $order->placedAt);

            $value += $line;
            $weighted += $line * $resolved['bps'] / 10_000;
            $assumed += $resolved['source'] === 'assumed' ? $line : 0;
        }

        if ($value === 0) {
            $resolved = $this->margins->resolve(null, $order->placedAt);

            return [$resolved['bps'] / 10_000, $resolved['source'] === 'assumed' ? 1.0 : 0.0];
        }

        return [$weighted / $value, $assumed / $value];
    }

    /**
     * @param  list<string>  $hashes
     * @return array<string, list<CarbonImmutable>>
     */
    private function touchesBySubject(array $hashes, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $result = [];

        foreach (array_chunk($hashes, 1000) as $chunk) {
            foreach (Touch::query()->whereIn('subject_hash', $chunk)->whereBetween('delivered_at', [$from, $to])->get(['subject_hash', 'delivered_at']) as $touch) {
                $result[$touch->subject_hash][] = $touch->delivered_at;
            }
        }

        return $result;
    }

    /**
     * @param  list<CarbonImmutable>  $touches
     */
    private function isAttributed(OrderSnapshot $order, array $touches): bool
    {
        foreach ($touches as $touch) {
            if ($order->placedAt->greaterThanOrEqualTo($touch) && $order->placedAt->lessThanOrEqualTo($touch->addHours($this->attributionHours))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Message and LLM costs per customer. Discounts are excluded on purpose:
     * they are already inside the net sale (independent review #1).
     *
     * @param  list<string>  $hashes
     * @return array<string, list<array{0: CarbonImmutable, 1: int}>>
     */
    private function costsBySubject(array $hashes, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $result = [];

        foreach (array_chunk($hashes, 1000) as $chunk) {
            $entries = CostLedgerEntry::query()
                ->whereIn('subject_hash', $chunk)
                ->whereIn('kind', [CostKind::MetaMessage->value, CostKind::Llm->value])
                ->whereBetween('occurred_at', [$from, $to])
                ->get(['subject_hash', 'occurred_at', 'amount_micros']);

            foreach ($entries as $entry) {
                $result[(string) $entry->subject_hash][] = [$entry->occurred_at, $entry->amount_micros];
            }
        }

        return $result;
    }

    /**
     * @return array<string, int>
     */
    private function periodCosts(CarbonImmutable $from, CarbonImmutable $to, int $microsPerMinor): array
    {
        $costs = [];

        foreach (CostKind::cases() as $kind) {
            $costs[$kind->value] = (int) round(CostLedgerEntry::query()
                ->where('kind', $kind->value)
                ->where('occurred_at', '>=', $from)
                ->where('occurred_at', '<', $to)
                ->sum('amount_micros') / $microsPerMinor);
        }

        return $costs + ['total' => array_sum($costs)];
    }

    private function costsEstimated(CarbonImmutable $from, CarbonImmutable $to): bool
    {
        return CostLedgerEntry::query()
            ->where('occurred_at', '>=', $from)
            ->where('occurred_at', '<', $to)
            ->where('meta->estimated', true)
            ->exists();
    }

    /**
     * Difference in means per customer, scaled to the treated group, as a 95%
     * interval plus the probability that the true effect is positive.
     *
     * @param  array{n: int, mean: float, variance: float}  $t
     * @param  array{n: int, mean: float, variance: float}  $c
     * @return array<string, mixed>
     */
    private function scaledInterval(array $t, array $c, int $treated): array
    {
        if ($t['n'] < 2 || $c['n'] < 2) {
            return ['low' => null, 'point' => null, 'high' => null, 'win_probability' => null, 'verdict' => 'inconclusive'];
        }

        $difference = $t['mean'] - $c['mean'];
        $standardError = sqrt($t['variance'] / $t['n'] + $c['variance'] / $c['n']);

        $low = $difference - Normal::Z_95 * $standardError;
        $high = $difference + Normal::Z_95 * $standardError;
        $win = $standardError > 0 ? Normal::cdf($difference / $standardError) : ($difference > 0 ? 1.0 : 0.0);

        return [
            'low' => (int) round($low * $treated),
            'point' => (int) round($difference * $treated),
            'high' => (int) round($high * $treated),
            'per_customer' => ['low' => round($low, 2), 'high' => round($high, 2)],
            'win_probability' => round($win, 3),
            'verdict' => $low > 0 ? 'helpful' : ($high < 0 ? 'harmful' : 'inconclusive'),
        ];
    }

    /**
     * Customers still needed to detect the minimum effect worth paying for, at
     * 95% confidence and 80% power, with the current split (independent review #2).
     *
     * @param  array{n: int, mean: float, variance: float}  $t
     * @param  array{n: int, mean: float, variance: float}  $c
     */
    private function additionalNeeded(array $t, array $c, int $holdoutBps, Currency $currency): int
    {
        $n = $t['n'] + $c['n'];
        $mde = $currency->minorFromUsd($this->mdeUsdPerSubject);

        if ($n < 2 || $mde <= 0) {
            return 0;
        }

        $pooledVariance = (($t['n'] - 1) * $t['variance'] + ($c['n'] - 1) * $c['variance']) / max(1, $n - 2);
        $controlShare = max(0.01, $holdoutBps / 10_000);
        $treatmentShare = 1 - $controlShare;

        $required = (Normal::Z_95 + Normal::Z_80_POWER) ** 2 * $pooledVariance
            * (1 / $treatmentShare + 1 / $controlShare) / ($mde ** 2);

        return max(0, (int) ceil($required) - $n);
    }
}
