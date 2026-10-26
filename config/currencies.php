<?php

/*
|--------------------------------------------------------------------------
| Currencies (ADR-012)
|--------------------------------------------------------------------------
| One currency per workspace. Money columns (named *_halalas for history)
| hold integer minor units of that currency: amount × 10^exponent.
| The exponent is what stores show in practice, not always ISO 4217
| (IQD, LBP, SYP and YER are priced without fractions).
|
| `words`: what the sales agent may write next to a price, lower case and
| normalized like ArabicText::normalize (ة→ه, أ/إ/آ→ا). The response
| validator treats a number next to ANY currency's words as money.
|
| `usd_rate`: units of this currency per 1 USD, only to convert costs that
| are priced in dollars (Meta messages, AI). [افتراض] Approximate rates as
| of 2026-10-26: pegged currencies are exact; floating ones (marked) must be
| refreshed by hand every month. Prices and revenue are never converted.
*/

return [
    'default' => 'SAR',

    // A workspace's timezone until its store says otherwise (quiet hours, report days).
    'default_timezone' => 'Asia/Riyadh',

    'rates_as_of' => '2026-10-26',

    'list' => [
        // Gulf
        'SAR' => ['exponent' => 2, 'symbol' => ['ar' => 'ر.س', 'en' => 'SAR'], 'words' => ['ريال', 'ريالات', 'ريالا', 'ر.س', 'رس', 'sar', 'sr', 'riyal', 'riyals'], 'usd_rate' => 3.75],
        'AED' => ['exponent' => 2, 'symbol' => ['ar' => 'د.إ', 'en' => 'AED'], 'words' => ['درهم', 'دراهم', 'د.ا', 'aed', 'dirham', 'dirhams', 'dhs'], 'usd_rate' => 3.6725],
        'QAR' => ['exponent' => 2, 'symbol' => ['ar' => 'ر.ق', 'en' => 'QAR'], 'words' => ['ريال', 'ريالات', 'ر.ق', 'qar', 'qr', 'riyal', 'riyals'], 'usd_rate' => 3.64],
        'KWD' => ['exponent' => 3, 'symbol' => ['ar' => 'د.ك', 'en' => 'KWD'], 'words' => ['دينار', 'دنانير', 'د.ك', 'kwd', 'kd', 'dinar', 'dinars'], 'usd_rate' => 0.307], // floating, check
        'BHD' => ['exponent' => 3, 'symbol' => ['ar' => 'د.ب', 'en' => 'BHD'], 'words' => ['دينار', 'دنانير', 'د.ب', 'bhd', 'bd', 'dinar', 'dinars'], 'usd_rate' => 0.376],
        'OMR' => ['exponent' => 3, 'symbol' => ['ar' => 'ر.ع', 'en' => 'OMR'], 'words' => ['ريال', 'ريالات', 'ر.ع', 'omr', 'ro', 'riyal', 'rial', 'rials'], 'usd_rate' => 0.3845],
        // Levant and Iraq
        'JOD' => ['exponent' => 3, 'symbol' => ['ar' => 'د.أ', 'en' => 'JOD'], 'words' => ['دينار', 'دنانير', 'د.ا', 'jod', 'jd', 'dinar', 'dinars'], 'usd_rate' => 0.709],
        'IQD' => ['exponent' => 0, 'symbol' => ['ar' => 'د.ع', 'en' => 'IQD'], 'words' => ['دينار', 'دنانير', 'د.ع', 'iqd', 'dinar', 'dinars'], 'usd_rate' => 1310.0], // floating, check
        'LBP' => ['exponent' => 0, 'symbol' => ['ar' => 'ل.ل', 'en' => 'LBP'], 'words' => ['ليره', 'ليرات', 'ل.ل', 'lbp', 'lira', 'liras', 'll'], 'usd_rate' => 89500.0], // floating, check
        'SYP' => ['exponent' => 0, 'symbol' => ['ar' => 'ل.س', 'en' => 'SYP'], 'words' => ['ليره', 'ليرات', 'ل.س', 'syp', 'lira', 'liras'], 'usd_rate' => 13000.0], // floating, check
        // North Africa and the Nile
        'EGP' => ['exponent' => 2, 'symbol' => ['ar' => 'ج.م', 'en' => 'EGP'], 'words' => ['جنيه', 'جنيها', 'جنيهات', 'ج.م', 'egp', 'le', 'pound', 'pounds'], 'usd_rate' => 48.5], // floating, check
        'SDG' => ['exponent' => 2, 'symbol' => ['ar' => 'ج.س', 'en' => 'SDG'], 'words' => ['جنيه', 'جنيهات', 'ج.س', 'sdg', 'pound', 'pounds'], 'usd_rate' => 600.0], // floating, check
        'LYD' => ['exponent' => 3, 'symbol' => ['ar' => 'د.ل', 'en' => 'LYD'], 'words' => ['دينار', 'دنانير', 'د.ل', 'lyd', 'ld', 'dinar', 'dinars'], 'usd_rate' => 5.45], // floating, check
        'TND' => ['exponent' => 3, 'symbol' => ['ar' => 'د.ت', 'en' => 'TND'], 'words' => ['دينار', 'دنانير', 'د.ت', 'tnd', 'dt', 'dinar', 'dinars'], 'usd_rate' => 2.95], // floating, check
        'DZD' => ['exponent' => 2, 'symbol' => ['ar' => 'د.ج', 'en' => 'DZD'], 'words' => ['دينار', 'دنانير', 'د.ج', 'dzd', 'da', 'dinar', 'dinars'], 'usd_rate' => 131.0], // floating, check
        'MAD' => ['exponent' => 2, 'symbol' => ['ar' => 'د.م', 'en' => 'MAD'], 'words' => ['درهم', 'دراهم', 'د.م', 'mad', 'dh', 'dirham', 'dirhams'], 'usd_rate' => 9.2], // floating, check
        'MRU' => ['exponent' => 2, 'symbol' => ['ar' => 'أ.م', 'en' => 'MRU'], 'words' => ['اوقيه', 'mru', 'ouguiya'], 'usd_rate' => 40.0], // floating, check
        // Horn of Africa, Yemen, Comoros
        'YER' => ['exponent' => 0, 'symbol' => ['ar' => 'ر.ي', 'en' => 'YER'], 'words' => ['ريال', 'ريالات', 'ر.ي', 'yer', 'riyal', 'rial', 'rials'], 'usd_rate' => 535.0], // floating, check
        'SOS' => ['exponent' => 0, 'symbol' => ['ar' => 'ش.ص', 'en' => 'SOS'], 'words' => ['شلن', 'sos', 'shilling', 'shillings'], 'usd_rate' => 571.0], // floating, check
        'DJF' => ['exponent' => 0, 'symbol' => ['ar' => 'ف.ج', 'en' => 'DJF'], 'words' => ['فرنك', 'djf', 'franc', 'francs'], 'usd_rate' => 177.72],
        'KMF' => ['exponent' => 0, 'symbol' => ['ar' => 'ف.ق', 'en' => 'KMF'], 'words' => ['فرنك', 'kmf', 'franc', 'francs'], 'usd_rate' => 425.0], // pegged to EUR, check
        // Dollar
        'USD' => ['exponent' => 2, 'symbol' => ['ar' => '$', 'en' => '$'], 'words' => ['دولار', 'دولارات', '$', 'usd', 'dollar', 'dollars'], 'usd_rate' => 1.0],
    ],
];
