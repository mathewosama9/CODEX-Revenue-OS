<?php

/*
| Arabic template library submitted to each merchant's WABA (E03).
| All cart reminders are MARKETING — never submit them as UTILITY (the penalty
| can restrict the whole portfolio for 30 days; R09).
| Message 1 never carries a discount (ADR-014). Every template has a quick
| reply opt-out button. No customer names: we don't keep them.
|
| [اقتراح] Wording needs review by a Saudi speaker before the first submission.
*/

$optOutButton = ['type' => 'BUTTONS', 'buttons' => [['type' => 'QUICK_REPLY', 'text' => 'إيقاف العروض']]];

return [
    'opt_out_button_text' => 'إيقاف العروض',

    'opt_out_confirmation' => 'تم، ما راح نرسل لك عروض بعد اليوم. لو احتجت شي راسلنا هنا.',

    'library' => [
        'cart_recovery_1' => [
            'name' => 'codex_cart_reminder_1_ar_v1',
            'language' => 'ar',
            'category' => 'MARKETING',
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => "هلا 👋\nسلتك في {{1}} لسه محفوظة لك.\nتقدر تكمل طلبك من هنا: {{2}}",
                    'example' => ['body_text' => [['متجر العطور', 'https://store.example/checkout/123']]],
                ],
                $optOutButton,
            ],
        ],
        'cart_recovery_2' => [
            'name' => 'codex_cart_reminder_2_ar_v1',
            'language' => 'ar',
            'category' => 'MARKETING',
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => "تذكير من {{1}}: المنتجات اللي اخترتها ممكن تخلص.\nرابط سلتك: {{2}}",
                    'example' => ['body_text' => [['متجر العطور', 'https://store.example/checkout/123']]],
                ],
                $optOutButton,
            ],
        ],
        'cart_recovery_2_discount' => [
            'name' => 'codex_cart_reminder_2_discount_ar_v1',
            'language' => 'ar',
            'category' => 'MARKETING',
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => 'من {{1}}: استخدم الكود {{2}} وكمّل طلبك من هنا: {{3}}',
                    'example' => ['body_text' => [['متجر العطور', 'WELCOME10', 'https://store.example/checkout/123']]],
                ],
                $optOutButton,
            ],
        ],
    ],
];
