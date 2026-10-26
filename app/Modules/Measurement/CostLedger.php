<?php

namespace App\Modules\Measurement;

use App\Modules\Measurement\Models\CostLedgerEntry;
use App\Modules\Measurement\Models\Touch;
use App\Shared\Phone\CallingCode;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Tenancy\WorkspaceLocale;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Every unit of money spent on a customer (US-E06-03): Meta fees on delivery,
 * discounts actually used, LLM calls (P2). Idempotent per reference.
 *
 * Amounts are micros of the workspace currency (ADR-012): 1 major unit =
 * 1,000,000, and Currency::microsPerMinor() links them to minor units.
 * Dollar-priced costs (Meta, AI) are converted at the currency's usd_rate.
 * An entry whose price has no source carries meta.estimated = true.
 */
class CostLedger
{
    /**
     * @param  array<string, array<string, float>|null>  $metaRatesUsd  country code => category => USD; null = no sourced price yet
     */
    public function __construct(
        private readonly array $metaRatesUsd,
        private readonly WorkspaceLocale $locale,
    ) {}

    /**
     * Meta charges per delivered message by category and recipient country.
     * $country is the recipient's ISO country (CallingCode::country); null
     * when unknown, which books the fallback estimate.
     */
    public function recordMessageDelivered(string $reference, string $subjectHash, ?string $pricingCategory, bool $billable, CarbonImmutable $deliveredAt, ?string $country): void
    {
        $this->touch($reference, $subjectHash, $deliveredAt);

        if (! $billable) {
            return;
        }

        $category = strtolower($pricingCategory ?? 'marketing');
        [$usd, $estimated] = $this->metaRateUsd($country, $category);

        $this->append(CostKind::MetaMessage, $this->locale->currency()->microsFromUsd($usd), $reference, $subjectHash, $deliveredAt, array_filter([
            'category' => $category,
            'country' => $country,
            'estimated' => $estimated ?: null,
        ]));
    }

    /**
     * What one message to this phone costs, in micros of the workspace
     * currency, before it is sent (recovery's "worth the cost" check).
     */
    public function messageCostMicros(PhoneNumber|string|null $phone, string $category = 'marketing'): int
    {
        [$usd] = $this->metaRateUsd(CallingCode::country($phone), $category);

        return $this->locale->currency()->microsFromUsd($usd);
    }

    public function recordDiscount(string $reference, string $subjectHash, int $discountMinor, CarbonImmutable $at): void
    {
        $this->append(CostKind::Discount, $discountMinor * $this->locale->currency()->microsPerMinor(), $reference, $subjectHash, $at, []);
    }

    /**
     * One LLM or embeddings call (E10). Booked against the customer when the
     * call served one, so the profit report nets it out per subject.
     */
    public function recordLlm(string $reference, ?string $subjectHash, int $usdMicros, CarbonImmutable $at, string $model): void
    {
        if ($usdMicros <= 0) {
            return;
        }

        $this->append(CostKind::Llm, (int) round($usdMicros * $this->locale->currency()->usdRate), $reference, $subjectHash, $at, ['model' => $model]);
    }

    /** Micros of the workspace currency. */
    public function spentMicros(CostKind $kind, CarbonImmutable $since): int
    {
        return (int) CostLedgerEntry::query()
            ->where('kind', $kind->value)
            ->where('occurred_at', '>=', $since)
            ->sum('amount_micros');
    }

    /**
     * The sourced rate for the country, else the highest sourced rate for the
     * category across countries, marked estimated. Overstating an unknown
     * cost keeps the report and the recovery check on the safe side.
     *
     * @return array{0: float, 1: bool} USD, estimated
     */
    private function metaRateUsd(?string $country, string $category): array
    {
        $rates = $country === null ? null : ($this->metaRatesUsd[$country] ?? null);

        if (is_array($rates)) {
            return [(float) ($rates[$category] ?? $rates['marketing'] ?? 0.0), false];
        }

        $fallback = 0.0;

        foreach ($this->metaRatesUsd as $known) {
            if (is_array($known)) {
                $fallback = max($fallback, (float) ($known[$category] ?? $known['marketing'] ?? 0.0));
            }
        }

        return [$fallback, true];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function append(CostKind $kind, int $amountMicros, string $reference, ?string $subjectHash, CarbonImmutable $at, array $meta): void
    {
        try {
            DB::transaction(fn () => CostLedgerEntry::create([
                'kind' => $kind->value,
                'amount_micros' => $amountMicros,
                'reference' => $reference,
                'subject_hash' => $subjectHash,
                'meta' => $meta,
                // UTC: a local instant would be stored as if it were UTC.
                'occurred_at' => $at->utc(),
            ]));
        } catch (UniqueConstraintViolationException) {
            // Already booked (Meta repeats webhooks for days).
        }
    }

    private function touch(string $reference, string $subjectHash, CarbonImmutable $at): void
    {
        try {
            DB::transaction(fn () => Touch::create(['subject_hash' => $subjectHash, 'reference' => $reference, 'delivered_at' => $at->utc()]));
        } catch (UniqueConstraintViolationException) {
        }
    }
}
