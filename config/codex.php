<?php

use App\Modules\Commerce\Salla\SallaAdapter;
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
        // "we need ~X more carts" message, in USD and converted to the
        // workspace currency (ADR-012). 5 SAR / 3.75 so a SAR workspace keeps
        // exactly 500 halalas. [افتراض] — set after P0 interviews.
        'mde_usd_per_subject' => 5.00 / 3.75,
        'method_version' => '1.0',
    ],

    /*
    | Meta per-message rates in USD by recipient country (ISO, from the phone's
    | calling code) and category, from 2026-10-01. Converted to the workspace
    | currency at its usd_rate (config/currencies.php, ADR-012).
    | A country set to null has no sourced price yet: its messages are booked
    | at the highest sourced rate for the category and marked estimated.
    */
    'meta_rates_usd' => [
        // spine §7.2 [مؤكدة، ثانوي]. Update when Meta changes them.
        'SA' => ['marketing' => 0.0576, 'utility' => 0.0107, 'authentication' => 0.0107, 'service' => 0.0107],
        // [افتراض: needs Meta pricing page] Fill in from Meta's official rate card, then drop the estimate.
        'EG' => null,
        'AE' => null,
        'KW' => null,
        'QA' => null,
        'BH' => null,
        'OM' => null,
        'JO' => null,
        'IQ' => null,
        'LB' => null,
        'MA' => null,
        'DZ' => null,
        'TN' => null,
        'LY' => null,
    ],

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
        // US-E09-01: the agent never quotes an older price; it hands off instead.
        'price_max_age_minutes' => (int) env('CODEX_PRICE_MAX_AGE_MINUTES', 15),
        'pricing_usd_per_mtok' => [
            'claude-haiku-5-5' => ['input' => 0.10, 'output' => 0.50, 'cache_read' => 0.01, 'cache_write' => 0.125],
            // Groq, for local trials only (founder 2026-10-25); production stays on Haiku 5.5.
            // [مؤكدة: https://console.groq.com/docs/model/openai/gpt-oss-120b, 2026-10-25]; cache_write [افتراض].
            'openai/gpt-oss-120b' => ['input' => 0.15, 'output' => 0.60, 'cache_read' => 0.075, 'cache_write' => 0.15],
            // DeepSeek, local trials only (founder 2026-10-25). [افتراض] Conservative guess: the pricing page
            // (api-docs.deepseek.com/quick_start/pricing) did not load; read the real cost in the DeepSeek dashboard.
            'deepseek-flash' => ['input' => 0.30, 'output' => 1.20, 'cache_read' => 0.03, 'cache_write' => 0.30],
        ],
        // US-E10-06, founder decision 2026-10-25: $5 a day per merchant by default.
        'default_daily_cap_usd' => 5.00,
        // Founder 2026-10-25: the AI answers a customer who wrote first unless they opted out.
        // true = also require documented marketing consent (the stricter P3 brief). [افتراض قانوني]
        'reply_requires_marketing_consent' => (bool) env('CODEX_AI_REPLY_REQUIRES_CONSENT', false),
        // A cart this big goes to a person before the AI answers (09 §9.11.3). [اقتراح] 1,500 SAR,
        // set in USD and converted to the workspace currency (ADR-012): 400 USD = exactly 150,000 halalas.
        'handoff_cart_usd' => 400.00,
        'embeddings' => [
            // Off: search runs on text alone (tests, and any env without a Voyage key).
            'enabled' => (bool) env('CODEX_EMBEDDINGS_ENABLED', true),
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
    | default — spine §7.5). Times are local to the workspace timezone
    | (WorkspaceLocale, ADR-012); `timezone` is only the fallback outside a
    | tenant. A window whose start is after its end crosses midnight.
    | [افتراض] The same hours and Ramadan dates apply in every country until
    | each market's rules are checked.
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
        // WooCommerce through the CODEX Connector plugin (docs/integrations/woocommerce-connector.md).
        'woocommerce' => 'App\\Modules\\Commerce\\WooCommerce\\WooWebhookHandler',
    ],

    // ADR-011: one adapter per commerce platform, picked by the store's provider (CommerceAdapters).
    'commerce_adapters' => [
        'salla' => SallaAdapter::class,
        'woocommerce' => 'App\\Modules\\Commerce\\WooCommerce\\WooAdapter',
    ],

];
