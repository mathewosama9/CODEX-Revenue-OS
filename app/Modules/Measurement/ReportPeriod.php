<?php

namespace App\Modules\Measurement;

use Carbon\CarbonImmutable;

/**
 * Report periods are whole merchant days in Asia/Riyadh, with an inclusive
 * "to" date (review H6). Queries take UTC instants: [start, end).
 */
final class ReportPeriod
{
    public const TIMEZONE = 'Asia/Riyadh';

    private function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    /** "2026-11-01" to "2026-11-30" → 2026-10-31 21:00 UTC to 2026-11-30 21:00 UTC. */
    public static function days(string $from, string $to): self
    {
        return new self(
            CarbonImmutable::parse($from, self::TIMEZONE)->startOfDay()->utc(),
            CarbonImmutable::parse($to, self::TIMEZONE)->addDay()->startOfDay()->utc(),
        );
    }

    /** The last N whole days, today (Riyadh) included. */
    public static function lastDays(int $days): self
    {
        $today = CarbonImmutable::now(self::TIMEZONE)->toDateString();

        return self::days(CarbonImmutable::parse($today, self::TIMEZONE)->subDays($days - 1)->toDateString(), $today);
    }

    /** The first Riyadh day of a [start, end) range. */
    public static function firstDay(CarbonImmutable $start): string
    {
        return $start->setTimezone(self::TIMEZONE)->toDateString();
    }

    /** The last Riyadh day of a [start, end) range (the end itself is excluded). */
    public static function lastDay(CarbonImmutable $end): string
    {
        return $end->subSecond()->setTimezone(self::TIMEZONE)->toDateString();
    }
}
