<?php

use App\Modules\AiGateway\Data\ToolOutcome;
use App\Modules\AiGateway\ResponseValidator;

function product(array $overrides = []): ToolOutcome
{
    return new ToolOutcome('get_product', ['product_id' => 'p1'], $overrides + [
        'name' => 'Bleu Intense',
        'price_halalas' => 19900,
        'quantity' => 4,
        'variants' => [
            ['name' => '50ml', 'price_halalas' => 24900],
            ['name' => '100ml', 'price_halalas' => 39900],
        ],
    ]);
}

function check(string $reply, array $tools): array
{
    return (new ResponseValidator)->validate($reply, $tools)->unmatched;
}

it('accepts prices from the tool in riyals, halalas, decimals and Arabic digits', function (string $reply) {
    expect(check($reply, [product()]))->toBe([]);
})->with([
    'riyals' => 'السعر 199 ريال',
    'decimals' => 'السعر 199.00 ر.س',
    'Arabic-Indic digits' => 'السعر ١٩٩ ريال',
    'Arabic decimal mark' => 'السعر ١٩٩٫٠٠ ريال',
    'variant price' => 'حجم 100ml بـ 399 ريال',
    'thousands separator' => 'المجموع 19,900 هللة',
    'quantity' => 'باقي 4 حبات بس',
]);

it('blocks a price the customer claimed ("شفته بـ199")', function () {
    expect(check('صح، سعره 179 ريال', [product()]))->toBe(['179']);
});

it('blocks a total or a percentage the model worked out itself', function () {
    expect(check('لو أخذت 2 بيصير المجموع 398 ريال', [product()]))->toBe(['2', '398'])
        ->and(check('عليه خصم 10%', [product()]))->toBe(['10']);
});

it('blocks numbers written as words next to a currency', function () {
    expect(check('سعره مية وتسعة وتسعين ريال', [product()]))->toBe(['number_in_words'])
        ->and(check('ninety nine riyals only', [product()]))->toBe(['number_in_words']);
});

it('ignores the numbers of a failed tool, so a price from memory is blocked', function () {
    $failed = new ToolOutcome('get_product', ['product_id' => 'p1'], ['price_halalas' => 19900], failed: true);

    expect(check('سعره 199 ريال', [$failed]))->toBe(['199']);
});

it('blocks every number when no tool ran', function () {
    expect(check('عندنا 3 أحجام', []))->toBe(['3']);
});

it('reads numbers inside tool text, such as a shipping policy', function () {
    $policy = new ToolOutcome('get_policy', ['topic' => 'shipping'], ['text' => 'التوصيل خلال ٣-٥ أيام عمل']);

    expect(check('التوصيل يوصلك خلال 3 إلى 5 أيام عمل', [$policy]))->toBe([]);
});

it('does not read pseudonym tokens as numbers', function () {
    expect(check('تمام يا [NAME_A]، رقمك [PHONE_A] مسجل', []))->toBe([]);
});

it('accepts a reply with no numbers at all', function () {
    expect(check('أكيد، وش المناسبة اللي تبي العطر لها؟', []))->toBe([]);
});
