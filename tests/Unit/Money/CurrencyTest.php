<?php

use App\Shared\Money\Currencies;
use App\Shared\Money\JsonAmount;
use Tests\TestCase;

// Booted for config/currencies.php; no database.
uses(TestCase::class);

it('reads decimals into minor units for exponents 2, 3 and 0', function (string $code, string $decimal, int $minor) {
    expect(Currencies::get($code)->toMinor($decimal))->toBe($minor);
})->with([
    'SAR whole' => ['SAR', '199', 19900],
    'SAR cents' => ['SAR', '199.5', 19950],
    'SAR two' => ['SAR', '199.99', 19999],
    'EGP' => ['EGP', '345.50', 34550],
    'KWD three' => ['KWD', '12.345', 12345],
    'KWD one' => ['KWD', '12.5', 12500],
    'BHD' => ['BHD', '0.005', 5],
    'IQD whole' => ['IQD', '25000', 25000],
    'IQD with zeros' => ['IQD', '25000.00', 25000],
    'leading dot' => ['SAR', '.5', 50],
    'trailing dot' => ['SAR', '7.', 700],
    'plus sign' => ['SAR', '+3.10', 310],
    'spaces' => ['SAR', ' 3.10 ', 310],
]);

it('rounds extra decimals half away from zero', function (string $code, string $decimal, int $minor) {
    expect(Currencies::get($code)->toMinor($decimal))->toBe($minor);
})->with([
    ['SAR', '1.005', 101],
    ['SAR', '1.004', 100],
    ['SAR', '-1.005', -101],
    ['KWD', '1.2345', 1235],
    ['IQD', '999.5', 1000],
    ['IQD', '999.49', 999],
]);

it('keeps negative amounts (refunds) exact', function () {
    expect(Currencies::get('SAR')->toMinor('-3.10'))->toBe(-310)
        ->and(Currencies::get('KWD')->toMajor(-12345))->toBe('-12.345')
        ->and(Currencies::get('SAR')->toMajor(-5))->toBe('-0.05');
});

it('refuses anything that is not a plain decimal', function (string $value) {
    expect(fn () => Currencies::get('SAR')->toMinor($value))->toThrow(InvalidArgumentException::class);
})->with(['', '.', 'abc', '1,000', '1e3', '12.3.4', '--1', '١٩٩']);

it('writes minor units back as major decimals', function (string $code, int $minor, string $major) {
    expect(Currencies::get($code)->toMajor($minor))->toBe($major);
})->with([
    ['SAR', 19950, '199.50'],
    ['SAR', 5, '0.05'],
    ['SAR', 0, '0.00'],
    ['KWD', 12345, '12.345'],
    ['KWD', 7, '0.007'],
    ['IQD', 25000, '25000'],
]);

it('holds large Iraqi and Lebanese amounts without overflow or floats', function () {
    $iqd = Currencies::get('IQD');
    $lbp = Currencies::get('LBP');

    expect($iqd->toMinor('1500000000'))->toBe(1_500_000_000)
        ->and($iqd->toMajor(1_500_000_000))->toBe('1500000000')
        ->and($lbp->toMinor('89500000000'))->toBe(89_500_000_000)
        ->and($iqd->toMinor($iqd->toMajor(987_654_321_012)))->toBe(987_654_321_012);
});

it('links minor units to ledger micros by the exponent', function () {
    expect(Currencies::get('SAR')->microsPerMinor())->toBe(10_000)
        ->and(Currencies::get('KWD')->microsPerMinor())->toBe(1_000)
        ->and(Currencies::get('IQD')->microsPerMinor())->toBe(1_000_000);
});

it('reads platform JSON amounts as exact decimal strings', function (mixed $json, ?string $decimal) {
    expect(JsonAmount::decimal($json))->toBe($decimal);
})->with([
    'string' => ['199.00', '199.00'],
    'float' => [249.5, '249.5'],
    'float 3 digits' => [12.345, '12.345'],
    'tricky float' => [0.1 + 0.2, '0.3'],
    'int' => [12, '12'],
    'exponent string' => ['1e3', '1000.000000'],
    'empty' => ['', null],
    'null' => [null, null],
    'text' => ['abc', null],
    'bool' => [true, null],
    'infinite' => [INF, null],
]);

it('refuses an amount beyond a bigint instead of saturating it', function () {
    expect(Currencies::get('IQD')->toMinor('999999999999999999'))->toBe(999_999_999_999_999_999)
        ->and(fn () => Currencies::get('IQD')->toMinor('9999999999999999999'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Currencies::get('KWD')->toMinor('99999999999999999.5'))->toThrow(InvalidArgumentException::class)
        ->and(Currencies::get('SAR')->toMinor('000000000000000000012.34'))->toBe(1234);
});
