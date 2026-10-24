<?php

namespace App\Modules\Measurement;

use App\Modules\Measurement\Models\CategoryMargin;
use Carbon\CarbonImmutable;

/**
 * One answer to "what margin applies here?", used by the discount floor (E05)
 * and the profit report (E07). Category → store default → assumed default.
 */
class MarginLookup
{
    /** @var list<CategoryMargin>|null newest first, loaded once per instance */
    private ?array $rows = null;

    public function __construct(private readonly int $assumedDefaultBps) {}

    public function marginBps(?string $categoryExternalId, ?CarbonImmutable $at = null): int
    {
        return $this->resolve($categoryExternalId, $at)['bps'];
    }

    /**
     * @return array{bps: int, source: 'category'|'store_default'|'assumed'}
     */
    public function resolve(?string $categoryExternalId, ?CarbonImmutable $at = null): array
    {
        $at ??= CarbonImmutable::now();

        if ($categoryExternalId !== null) {
            $bps = $this->effective($categoryExternalId, $at);

            if ($bps !== null) {
                return ['bps' => $bps, 'source' => 'category'];
            }
        }

        $default = $this->effective(null, $at);

        return $default !== null
            ? ['bps' => $default, 'source' => 'store_default']
            : ['bps' => $this->assumedDefaultBps, 'source' => 'assumed'];
    }

    /**
     * @param  array<string, int>  $categoryBps  category external id => basis points
     */
    public function update(?int $defaultBps, array $categoryBps, ?int $userId): void
    {
        $now = CarbonImmutable::now();

        if ($defaultBps !== null) {
            CategoryMargin::create(['category_external_id' => null, 'margin_bps' => $defaultBps, 'effective_from' => $now, 'created_by' => $userId]);
        }

        foreach ($categoryBps as $category => $bps) {
            CategoryMargin::create(['category_external_id' => (string) $category, 'margin_bps' => $bps, 'effective_from' => $now, 'created_by' => $userId]);
        }

        $this->rows = null;
    }

    /**
     * @return array<string, int>
     */
    public function currentByCategory(): array
    {
        $current = [];

        foreach (CategoryMargin::query()->whereNotNull('category_external_id')->orderBy('effective_from')->orderBy('id')->get() as $row) {
            $current[(string) $row->category_external_id] = $row->margin_bps;
        }

        return $current;
    }

    private function effective(?string $categoryExternalId, CarbonImmutable $at): ?int
    {
        $this->rows ??= array_values(CategoryMargin::query()->orderByDesc('effective_from')->orderByDesc('id')->get()->all());

        foreach ($this->rows as $row) {
            if ($row->category_external_id === $categoryExternalId && $row->effective_from->lessThanOrEqualTo($at)) {
                return $row->margin_bps;
            }
        }

        return null;
    }
}
