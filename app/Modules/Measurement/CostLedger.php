<?php

namespace App\Modules\Measurement;

use App\Modules\Measurement\Models\CostLedgerEntry;
use App\Modules\Measurement\Models\Touch;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Every riyal spent on a customer (US-E06-03): Meta fees on delivery,
 * discounts actually used, LLM calls (P2). Idempotent per reference.
 */
class CostLedger
{
    /**
     * @param  array<string, array<string, float>>  $metaRatesUsd  country code => category => USD
     */
    public function __construct(
        private readonly array $metaRatesUsd,
        private readonly float $usdToSar,
    ) {}

    /**
     * Meta charges per delivered message by category and recipient country.
     */
    public function recordMessageDelivered(string $reference, string $subjectHash, ?string $pricingCategory, bool $billable, CarbonImmutable $deliveredAt, string $country = 'SA'): void
    {
        $this->touch($reference, $subjectHash, $deliveredAt);

        if (! $billable) {
            return;
        }

        $category = strtolower($pricingCategory ?? 'marketing');
        $usd = $this->metaRatesUsd[$country][$category] ?? $this->metaRatesUsd[$country]['marketing'] ?? 0.0;

        $this->append(CostKind::MetaMessage, (int) round($usd * $this->usdToSar * 1_000_000), $reference, $subjectHash, $deliveredAt, [
            'category' => $category,
            'country' => $country,
        ]);
    }

    public function recordDiscount(string $reference, string $subjectHash, int $discountHalalas, CarbonImmutable $at): void
    {
        $this->append(CostKind::Discount, $discountHalalas * 10_000, $reference, $subjectHash, $at, []);
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

        $this->append(CostKind::Llm, (int) round($usdMicros * $this->usdToSar), $reference, $subjectHash, $at, ['model' => $model]);
    }

    public function spentMicros(CostKind $kind, CarbonImmutable $since): int
    {
        return (int) CostLedgerEntry::query()
            ->where('kind', $kind->value)
            ->where('occurred_at', '>=', $since)
            ->sum('amount_micros');
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
                'occurred_at' => $at,
            ]));
        } catch (UniqueConstraintViolationException) {
            // Already booked (Meta repeats webhooks for days).
        }
    }

    private function touch(string $reference, string $subjectHash, CarbonImmutable $at): void
    {
        try {
            DB::transaction(fn () => Touch::create(['subject_hash' => $subjectHash, 'reference' => $reference, 'delivered_at' => $at]));
        } catch (UniqueConstraintViolationException) {
        }
    }
}
