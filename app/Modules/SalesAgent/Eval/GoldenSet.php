<?php

namespace App\Modules\SalesAgent\Eval;

use App\Modules\AiGateway\Data\ChatTurn;
use App\Shared\Money\Currencies;
use App\Shared\Money\JsonAmount;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads `evals/golden/cases/*.yaml` and the fixture stores: `store.yaml` (the
 * `default` store) and `store-<key>.yaml` (e.g. `store-eg.yaml`, key `eg`).
 * A case runs against `store: <key>`, default `default`. Each store has its
 * own `currency` (default SAR) and `timezone` (ADR-012).
 */
final class GoldenSet
{
    public const DEFAULT_STORE = 'default';

    public const CATEGORIES = [
        'search', 'recommendation', 'price', 'stock', 'policy', 'discount',
        'out_of_scope', 'complaint', 'injection', 'mixed', 'human',
    ];

    public function __construct(private readonly string $directory) {}

    /**
     * @return array<string, mixed>
     */
    public function store(string $key = self::DEFAULT_STORE): array
    {
        return $this->stores()[$key] ?? throw new InvalidArgumentException("No golden store '{$key}'.");
    }

    /**
     * Every fixture store by key, with `currency` and `timezone` filled in.
     *
     * @return array<string, array<string, mixed>>
     */
    public function stores(): array
    {
        $stores = [self::DEFAULT_STORE => $this->directory.'/store.yaml'];
        $files = glob($this->directory.'/store-*.yaml') ?: [];
        sort($files);

        foreach ($files as $file) {
            $key = substr(basename($file, '.yaml'), strlen('store-'));

            if (preg_match('/^[a-z0-9-]+$/', $key) !== 1 || $key === self::DEFAULT_STORE) {
                throw new InvalidArgumentException(basename($file).': store keys are lower-case letters, digits and dashes.');
            }

            $stores[$key] = $file;
        }

        return array_map(function (string $file) {
            $store = (array) Yaml::parseFile($file);
            $store['currency'] = strtoupper((string) ($store['currency'] ?? 'SAR'));
            $store['timezone'] = (string) ($store['timezone'] ?? config('currencies.default_timezone'));

            if (! Currencies::supports($store['currency'])) {
                throw new InvalidArgumentException(basename($file).": unsupported currency '{$store['currency']}'.");
            }

            if (! in_array($store['timezone'], timezone_identifiers_list(), true)) {
                throw new InvalidArgumentException(basename($file).": unknown timezone '{$store['timezone']}'.");
            }

            return $store;
        }, $stores);
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

        $stores = $this->stores();

        foreach ($cases as $case) {
            if (! isset($stores[$case->store])) {
                throw new InvalidArgumentException("{$case->id}: no golden store '{$case->store}' (evals/golden/store-{$case->store}.yaml).");
            }

            // `cart_sar` reads as riyals: only for a SAR store; others say `cart`.
            if ($case->cartInSar && $stores[$case->store]['currency'] !== 'SAR') {
                throw new InvalidArgumentException("{$case->id}: use cart, not cart_sar, for a {$stores[$case->store]['currency']} store.");
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
    private static function parse(array $raw, string $file, bool $live = false): GoldenCase
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

        // "any" (live only): a reply or a handoff are both safe; the reply checks apply only to a reply.
        if (! in_array($outcome, $live ? ['draft', 'handoff', 'any'] : ['draft', 'handoff'], true)) {
            throw new InvalidArgumentException("{$id}: expect.outcome must be draft or handoff".($live ? ' or any' : '').'.');
        }

        return new GoldenCase(
            id: $id,
            category: $category,
            dialect: (string) ($raw['dialect'] ?? 'saudi'),
            turns: array_values($turns),
            cart: JsonAmount::decimal($raw['cart'] ?? $raw['cart_sar'] ?? null),
            cartItems: array_values(array_map('strval', (array) ($raw['cart_items'] ?? []))),
            outcome: $outcome,
            reason: isset($expect['reason']) ? (string) $expect['reason'] : null,
            tools: array_values(array_map('strval', (array) ($expect['tools'] ?? []))),
            contains: array_values(array_map('strval', (array) ($expect['contains'] ?? []))),
            notContains: array_values(array_map('strval', (array) ($expect['not_contains'] ?? []))),
            price: (bool) ($expect['price'] ?? false),
            offline: isset($raw['offline']) ? array_values(array_map(fn ($step) => (array) $step, (array) $raw['offline'])) : null,
            store: (string) ($raw['store'] ?? self::DEFAULT_STORE),
            cartInSar: ! isset($raw['cart']) && isset($raw['cart_sar']),
            live: ! $live && isset($raw['live']) ? self::parse(['expect' => ['price' => $expect['price'] ?? false] + (array) $raw['live'], 'live' => null] + $raw, $file, true) : null,
        );
    }
}
