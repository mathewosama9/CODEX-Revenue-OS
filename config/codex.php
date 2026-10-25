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
    | AiGateway (E10, ADR-007). One model, chosen by the founder 2026-10-25:
    | Claude Haiku 5.5, no escalation tier. Prices in USD per 1M tokens from
    | https://platform.claude.com/docs/en/about-claude/pricing [مؤكدة 2026-10-25
    | for input/output]; cache prices follow Anthropic's usual 0.1x / 1.25x
    | [افتراض: re-check on the pricing page before go-live].
    */
    'ai' => [
        'provider' => env('CODEX_AI_PROVIDER', 'anthropic'),
        'model' => env('CODEX_AI_MODEL', 'claude-haiku-5-5'),
        'timeout_seconds' => 20,
        'max_steps' => 6,
        'max_output_tokens' => 600,
        'pricing_usd_per_mtok' => [
            'claude-haiku-5-5' => ['input' => 0.10, 'output' => 0.50, 'cache_read' => 0.01, 'cache_write' => 0.125],
        ],
        // US-E10-06, founder decision 2026-10-25: $5 a day per merchant by default.
        'default_daily_cap_usd' => 5.00,
        'embeddings' => [
            'provider' => env('CODEX_EMBEDDINGS_PROVIDER', 'voyageai'),
            'model' => env('CODEX_EMBEDDINGS_MODEL', 'voyage-4'),
            'dimensions' => 1024,
            // [افتراض] Voyage price per 1M tokens; check https://docs.voyageai.com/docs/pricing before go-live.
            'usd_per_mtok' => 0.06,
        ],
    ],

    /*
    | Retention defaults (M10). [افتراض] Final windows come from the legal
    | opinion and the DPA; audit_events and consent_records are kept.
    */
    'retention' => [
        'webhook_payload_days' => 30,
        'message_body_days' => 90,
        'conversation_note_days' => 365,
    ],

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
