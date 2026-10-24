<?php

use App\Modules\Compliance\QuietHours;
use Carbon\CarbonImmutable;

function quietHours(): QuietHours
{
    return new QuietHours(
        'Asia/Riyadh',
        ['start' => '22:00', 'end' => '09:00'],
        ['start' => '01:00', 'end' => '12:00'],
        [['from' => '2027-02-08', 'to' => '2027-03-09']],
    );
}

function riyadh(string $time): CarbonImmutable
{
    return CarbonImmutable::parse($time, 'Asia/Riyadh');
}

it('allows sends outside quiet hours', function (string $time) {
    expect(quietHours()->isQuiet(riyadh($time)))->toBeFalse();
})->with(['2026-11-10 09:00', '2026-11-10 14:30', '2026-11-10 21:59']);

it('blocks sends inside quiet hours', function (string $time) {
    expect(quietHours()->isQuiet(riyadh($time)))->toBeTrue();
})->with(['2026-11-10 22:00', '2026-11-10 23:59', '2026-11-11 03:00', '2026-11-11 08:59']);

it('postpones a send to 09:00 Riyadh, never cancels it', function () {
    $next = quietHours()->nextAllowedAt(riyadh('2026-11-10 23:30'));

    expect($next->setTimezone('Asia/Riyadh')->format('Y-m-d H:i'))->toBe('2026-11-11 09:00')
        ->and($next->getTimezone()->getName())->toBe('UTC');
});

it('leaves allowed times unchanged', function () {
    $at = riyadh('2026-11-10 15:00');

    expect(quietHours()->nextAllowedAt($at)->equalTo($at))->toBeTrue();
});

it('uses the Ramadan window during Ramadan', function () {
    $hours = quietHours();

    expect($hours->isQuiet(riyadh('2027-02-15 23:00')))->toBeFalse()
        ->and($hours->isQuiet(riyadh('2027-02-15 00:59')))->toBeFalse()
        ->and($hours->isQuiet(riyadh('2027-02-15 01:00')))->toBeTrue()
        ->and($hours->isQuiet(riyadh('2027-02-15 11:59')))->toBeTrue()
        ->and($hours->nextAllowedAt(riyadh('2027-02-15 05:00'))->setTimezone('Asia/Riyadh')->format('H:i'))->toBe('12:00');
});
