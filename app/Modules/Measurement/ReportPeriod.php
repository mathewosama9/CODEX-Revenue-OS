<?php

namespace App\Modules\Measurement;

use App\Shared\Tenancy\TenantContext;
use App\Shared\Tenancy\WorkspaceLocale;
use Carbon\CarbonImmutable;

/**
 * Report periods are whole merchant days in the workspace timezone
 * (ADR-012), with an inclusive "to" date (review H6). Queries take UTC
 * instants: [start, end). Outside a tenant the default timezone applies.
 */
final class ReportPeriod
{
    private function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    /** "2026-11-01" to "2026-11-30" in Riyadh → 2026-10-31 21:00 UTC to 2026-11-30 21:00 UTC. */
    public static function days(string $from, string $to, ?string $timezone = null): self
    {
        $timezone ??= self::timezone();

        return new self(
            CarbonImmutable::parse($from, $timezone)->startOfDay()->utc(),
            CarbonImmutable::parse($to, $timezone)->addDay()->startOfDay()->utc(),
        );
    }

    /** The last N whole local days, today included. */
    public static function lastDays(int $days, ?string $timezone = null): self
    {
        $timezone ??= self::timezone();
        $today = CarbonImmutable::now($timezone)->toDateString();

        return self::days(CarbonImmutable::parse($today, $timezone)->subDays($days - 1)->toDateString(), $today, $timezone);
    }

    /** The N whole local days before today (the weekly snapshot). */
    public static function daysBeforeToday(int $days, ?string $timezone = null): self
    {
        $timezone ??= self::timezone();
        $yesterday = CarbonImmutable::now($timezone)->subDay()->toDateString();

        return self::days(CarbonImmutable::parse($yesterday)->subDays($days - 1)->toDateString(), $yesterday, $timezone);
    }

    /** The first local day of a [start, end) range. */
    public static function firstDay(CarbonImmutable $start, ?string $timezone = null): string
    {
        return $start->setTimezone($timezone ?? self::timezone())->toDateString();
    }

    /** The last local day of a [start, end) range (the end itself is excluded). */
    public static function lastDay(CarbonImmutable $end, ?string $timezone = null): string
    {
        return $end->subSecond()->setTimezone($timezone ?? self::timezone())->toDateString();
    }

    public static function timezone(): string
    {
        return app(TenantContext::class)->id() === null
            ? (string) config('currencies.default_timezone')
            : app(WorkspaceLocale::class)->timezone();
    }
}
