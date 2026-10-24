<?php

namespace App\Shared\Stats;

/**
 * Standard normal helpers for the profit report (ADR-013). Accurate to ~1e-7,
 * enough for intervals and win probabilities.
 */
final class Normal
{
    public const Z_95 = 1.959964;

    public const Z_80_POWER = 0.841621;

    public static function cdf(float $x): float
    {
        return 0.5 * (1.0 + self::erf($x / M_SQRT2));
    }

    /** Abramowitz & Stegun 7.1.26. */
    public static function erf(float $x): float
    {
        $sign = $x < 0 ? -1.0 : 1.0;
        $x = abs($x);
        $t = 1.0 / (1.0 + 0.3275911 * $x);
        $y = 1.0 - (((((1.061405429 * $t - 1.453152027) * $t) + 1.421413741) * $t - 0.284496736) * $t + 0.254829592) * $t * exp(-$x * $x);

        return $sign * $y;
    }

    /**
     * p-value of a chi-square statistic with one degree of freedom.
     */
    public static function chiSquare1P(float $statistic): float
    {
        return max(0.0, 2.0 * (1.0 - self::cdf(sqrt(max(0.0, $statistic)))));
    }

    /**
     * @param  list<float|int>  $values
     * @return array{n: int, mean: float, variance: float}
     */
    public static function describe(array $values): array
    {
        $n = count($values);

        if ($n === 0) {
            return ['n' => 0, 'mean' => 0.0, 'variance' => 0.0];
        }

        $mean = array_sum($values) / $n;
        $squares = 0.0;

        foreach ($values as $value) {
            $squares += ($value - $mean) ** 2;
        }

        return ['n' => $n, 'mean' => $mean, 'variance' => $n > 1 ? $squares / ($n - 1) : 0.0];
    }
}
