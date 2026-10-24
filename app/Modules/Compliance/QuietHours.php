<?php

namespace App\Modules\Compliance;

use Carbon\CarbonImmutable;

/**
 * No promotional sends inside quiet hours (US-E04-03). A send that falls inside
 * the window is postponed to the window's end, never cancelled.
 */
class QuietHours
{
    /**
     * @param  array{start: string, end: string}  $default
     * @param  array{start: string, end: string}  $ramadan
     * @param  list<array{from: string, to: string}>  $ramadanPeriods
     */
    public function __construct(
        private readonly string $timezone,
        private readonly array $default,
        private readonly array $ramadan,
        private readonly array $ramadanPeriods,
    ) {}

    public function isQuiet(CarbonImmutable $at): bool
    {
        return $this->windowContaining($at) !== null;
    }

    /**
     * The earliest moment at or after $at when a promotional send is allowed, in UTC.
     */
    public function nextAllowedAt(CarbonImmutable $at): CarbonImmutable
    {
        $window = $this->windowContaining($at);

        return ($window === null ? $at : $window[1])->utc();
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    private function windowContaining(CarbonImmutable $at): ?array
    {
        $local = $at->setTimezone($this->timezone);

        // A window that crosses midnight may have started the previous day.
        foreach ([$local->subDay(), $local] as $day) {
            [$start, $end] = $this->windowStartingOn($day);

            if ($local->greaterThanOrEqualTo($start) && $local->lessThan($end)) {
                return [$start, $end];
            }
        }

        return null;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function windowStartingOn(CarbonImmutable $day): array
    {
        $hours = $this->isRamadan($day) ? $this->ramadan : $this->default;

        $start = $day->setTimeFromTimeString($hours['start']);
        $end = $day->setTimeFromTimeString($hours['end']);

        return [$start, $end->lessThanOrEqualTo($start) ? $end->addDay() : $end];
    }

    private function isRamadan(CarbonImmutable $day): bool
    {
        $date = $day->toDateString();

        foreach ($this->ramadanPeriods as $period) {
            if ($date >= $period['from'] && $date <= $period['to']) {
                return true;
            }
        }

        return false;
    }
}
