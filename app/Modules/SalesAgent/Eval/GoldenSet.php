<?php

namespace App\Modules\SalesAgent\Eval;

use App\Modules\AiGateway\Data\ChatTurn;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads `evals/golden/cases/*.yaml` and the fixture store `evals/golden/store.yaml`.
 */
final class GoldenSet
{
    public const CATEGORIES = [
        'search', 'recommendation', 'price', 'stock', 'policy', 'discount',
        'out_of_scope', 'complaint', 'injection', 'mixed', 'human',
    ];

    public function __construct(private readonly string $directory) {}

    /**
     * @return array<string, mixed>
     */
    public function store(): array
    {
        return (array) Yaml::parseFile($this->directory.'/store.yaml');
    }

    /**
     * @return list<GoldenCase>
     */
    public function cases(): array
    {
        $cases = [];
        $files = glob($this->directory.'/cases/*.yaml') ?: [];
        sort($files);

        foreach ($files as $file) {
            foreach ((array) Yaml::parseFile($file) as $raw) {
                $cases[] = self::parse((array) $raw, basename($file));
            }
        }

        $ids = array_map(fn (GoldenCase $case) => $case->id, $cases);

        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Duplicate golden case ids.');
        }

        return $cases;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private static function parse(array $raw, string $file): GoldenCase
    {
        $id = (string) ($raw['id'] ?? throw new InvalidArgumentException("A case in {$file} has no id."));
        $expect = (array) ($raw['expect'] ?? []);
        $category = (string) ($raw['category'] ?? '');

        if (! in_array($category, self::CATEGORIES, true)) {
            throw new InvalidArgumentException("{$id}: unknown category '{$category}'.");
        }

        $turns = isset($raw['say'])
            ? [ChatTurn::customer((string) $raw['say'])]
            : array_map(fn ($turn) => isset($turn['assistant'])
                ? ChatTurn::assistant((string) $turn['assistant'])
                : ChatTurn::customer((string) ($turn['customer'] ?? '')), (array) ($raw['turns'] ?? []));

        if ($turns === [] || end($turns)->role !== ChatTurn::CUSTOMER) {
            throw new InvalidArgumentException("{$id}: the last turn must be the customer's.");
        }

        $outcome = (string) ($expect['outcome'] ?? '');

        if (! in_array($outcome, ['draft', 'handoff'], true)) {
            throw new InvalidArgumentException("{$id}: expect.outcome must be draft or handoff.");
        }

        return new GoldenCase(
            id: $id,
            category: $category,
            dialect: (string) ($raw['dialect'] ?? 'saudi'),
            turns: array_values($turns),
            cartSar: isset($raw['cart_sar']) ? (int) $raw['cart_sar'] : null,
            cartItems: array_values(array_map('strval', (array) ($raw['cart_items'] ?? []))),
            outcome: $outcome,
            reason: isset($expect['reason']) ? (string) $expect['reason'] : null,
            tools: array_values(array_map('strval', (array) ($expect['tools'] ?? []))),
            contains: array_values(array_map('strval', (array) ($expect['contains'] ?? []))),
            notContains: array_values(array_map('strval', (array) ($expect['not_contains'] ?? []))),
            price: (bool) ($expect['price'] ?? false),
            offline: isset($raw['offline']) ? array_values(array_map(fn ($step) => (array) $step, (array) $raw['offline'])) : null,
        );
    }
}
