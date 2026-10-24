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
