<?php

use App\Shared\Phone\PhoneNumber;

it('normalizes Saudi mobile formats to E.164', function (string $raw) {
    expect(PhoneNumber::tryParse($raw)?->e164)->toBe('+966512345678');
})->with([
    'local with zero' => '0512345678',
    'local without zero' => '512345678',
    'country code, no plus' => '966512345678',
    'with plus' => '+966512345678',
    'double zero prefix' => '00966512345678',
    'spaces and dashes' => '+966 51-234 5678',
    'Arabic-Indic digits' => '٠٥١٢٣٤٥٦٧٨',
    'Persian digits' => '۰۵۱۲۳۴۵۶۷۸',
]);

it('keeps other countries that carry a country code', function () {
    expect(PhoneNumber::tryParse('+201001234567')?->e164)->toBe('+201001234567');
});

it('rejects invalid numbers', function (?string $raw) {
    expect(PhoneNumber::tryParse($raw))->toBeNull();
})->with([
    'empty' => '',
    'null' => null,
    'Saudi landline' => '+966112345678',
    'too short' => '05123',
    'local non-Saudi without code' => '01001234567',
    'letters' => 'call me',
]);

it('reads a local number in the given country (ADR-012)', function (string $raw, string $country, ?string $e164) {
    expect(PhoneNumber::tryParse($raw, $country)?->e164)->toBe($e164);
})->with([
    'Egypt local' => ['01012345678', 'EG', '+201012345678'],
    'Egypt with code, no plus' => ['201012345678', 'EG', '+201012345678'],
    'Egypt landline refused' => ['0223456789', 'EG', null],
    'Kuwait 8 digits' => ['99123456', 'KW', '+96599123456'],
    'Jordan local' => ['0791234567', 'JO', '+962791234567'],
    'Saudi stays Saudi' => ['0512345678', 'SA', '+966512345678'],
    'unknown country refuses local' => ['01012345678', 'FR', null],
    'non-Arab billing country keeps the Saudi reading' => ['0512345678', 'US', '+966512345678'],
    'empty billing country keeps the Saudi reading' => ['0512345678', '', '+966512345678'],
    'international wins over country' => ['+966512345678', 'EG', '+966512345678'],
]);
