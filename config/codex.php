<?php

use App\Modules\Commerce\Salla\SallaWebhookHandler;
use App\Modules\Messaging\Meta\MetaWebhookHandler;

return [

    /*
    | Key for hashing customer phone numbers per tenant (holdout assignment,
    | consent lookups). Never rotate without a re-hash plan (ADR-008).
    */
    'subject_hash_key' => env('CODEX_SUBJECT_HASH_KEY'),

    /*
    | Margin used when the merchant has entered none (E08). Reports flag any
    | figure that relies on it. [افتراض] — replace with a vertical benchmark.
    */
    'assumed_default_margin_bps' => (int) env('CODEX_ASSUMED_MARGIN_BPS', 3000),

    /*
    | Measurement (ADR-013).
    */
    'measurement' => [
        'default_holdout_bps' => 1000,
        'outcome_window_days' => 7,
        'attribution_window_hours' => 72,
        // Smallest effect per assigned customer worth detecting, for the
        // "we need ~X more carts" message. [افتراض] — set after P0 interviews.
        'mde_halalas_per_subject' => 500,
        'method_version' => '1.0',
    ],

    /*
    | Meta per-message rates in USD by recipient country and category, from
    | 2026-10-01 (spine §7.2 [مؤكدة، ثانوي]). Update when Meta changes them.
    */
    'meta_rates_usd' => [
        'SA' => ['marketing' => 0.0576, 'utility' => 0.0107, 'authentication' => 0.0107, 'service' => 0.0107],
    ],
    'usd_to_sar' => 3.75,

    /*
    | Promotional quiet hours (CST anti-spam rules, applied to WhatsApp by
    | default — spine §7.5). Times are local to `timezone`; a window whose
    | start is after its end crosses midnight.
    */
    'quiet_hours' => [
        'timezone' => 'Asia/Riyadh',
        'default' => ['start' => '22:00', 'end' => '09:00'],
        'ramadan' => ['start' => '01:00', 'end' => '12:00'],

        // [افتراض] Umm al-Qura estimates. Update manually every year before Sha'ban.
        'ramadan_periods' => [
            ['from' => '2026-02-18', 'to' => '2026-03-19'],
            ['from' => '2027-02-08', 'to' => '2027-03-09'],
        ],
    ],

    /*
    | Webhook handlers per provider (App\Shared\Webhooks\WebhookHandler).
    | Until a handler exists, events are stored and marked "unhandled".
    */
    'webhook_handlers' => [
        'salla' => SallaWebhookHandler::class,
        'meta' => MetaWebhookHandler::class,
    ],

];
