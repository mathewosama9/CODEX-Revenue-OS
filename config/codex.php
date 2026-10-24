<?php

use App\Modules\Commerce\Salla\SallaWebhookHandler;

return [

    /*
    | Key for hashing customer phone numbers per tenant (holdout assignment,
    | consent lookups). Never rotate without a re-hash plan (ADR-008).
    */
    'subject_hash_key' => env('CODEX_SUBJECT_HASH_KEY'),

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
    ],

];
