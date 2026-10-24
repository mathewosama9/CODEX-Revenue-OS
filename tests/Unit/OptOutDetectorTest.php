<?php

use App\Modules\Compliance\Consent\OptOutDecision;
use App\Modules\Compliance\Consent\OptOutDetector;
use App\Shared\Arabic\ArabicText;

it('normalizes Arabic spelling variants', function () {
    expect(ArabicText::normalize('إِلْغَاءُ  الاشتراكْ!!'))->toBe('الغاء الاشتراك')
        ->and(ArabicText::normalize('رسالة'))->toBe('رساله')
        ->and(ArabicText::normalize('ـــوقـــف'))->toBe('وقف')
        ->and(ArabicText::normalize('STOP ✋'))->toBe('stop')
        ->and(ArabicText::normalize('١٢٣'))->toBe('123');
});

it('treats short stop messages as opt-out', function (string $message) {
    expect((new OptOutDetector)->detect($message))->toBe(OptOutDecision::OptOut);
})->with([
    'STOP',
    'stop please',
    'إيقاف',
    'ايقاف العروض',
    'الغاء الاشتراك',
    'إلغاء',
    'وقف 🙏',
    'لا ترسلوا لي شي',
    'ما ابي رسايل',
    'بطلوا',
    'Unsubscribe',
]);

it('treats not-now replies as ambiguous, not opt-out', function (string $message) {
    expect((new OptOutDetector)->detect($message))->toBe(OptOutDecision::Ambiguous);
})->with([
    'مو الحين',
    'مش دلوقتي',
    'بعدين ان شاء الله',
]);

it('flags a stop phrase inside a long message for a human', function () {
    $message = 'العطر وصلني بس الريحة مو نفس اللي طلبته ابي اعرف كيف الاستبدال وقف';

    expect((new OptOutDetector)->detect($message))->toBe(OptOutDecision::Ambiguous);
});

it('does not opt out on normal replies', function (string $message) {
    expect((new OptOutDetector)->detect($message))->toBe(OptOutDecision::None);
})->with([
    'كم سعر العطر؟',
    'تمام شكرا',
    'وقفتوا الشحن؟',
    '',
]);
