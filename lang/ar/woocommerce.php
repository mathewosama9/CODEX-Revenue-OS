<?php

// WooCommerce connect failures the merchant can act on (WooConnectFailed).
return [
    'invalid_url' => 'اكتب رابط الموقع كاملًا ويبدأ بـ https://، مثل https://shop.example.com.',
    'unreachable' => 'تعذّر الوصول إلى الموقع. تأكد من الرابط ومن أن الموقع يعمل، ثم حاول مرة أخرى.',
    'redirected' => 'الموقع يحوّل إلى رابط آخر. اكتب الرابط النهائي كما يظهر في المتصفح.',
    'bad_keys' => 'رفض WooCommerce المفاتيح. تأكد من Consumer key وConsumer secret ومن أن صلاحيتهما Read.',
    'not_woocommerce' => 'لم نجد WooCommerce REST API على هذا الموقع. تأكد من تفعيل WooCommerce ومن الرابط.',
    'currency' => 'عملة المتجر يجب أن تكون الريال السعودي (SAR). غيّرها من إعدادات WooCommerce ثم حاول مرة أخرى.',
    'other_workspace' => 'هذا المتجر مربوط بمساحة عمل أخرى. افصله منها أولًا، أو تواصل مع فريق CODEX.',
    'other_platform' => 'مساحة العمل هذه مربوطة بمتجر آخر. افصله أولًا ثم اربط WooCommerce.',
];
