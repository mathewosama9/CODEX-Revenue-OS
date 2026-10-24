<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
        'graph_base' => env('META_GRAPH_BASE', 'https://graph.facebook.com/v24.0/'),
        // ADR-006, irreversible per number: AE or BH. Registration refuses to run while empty.
        'data_localization_region' => env('META_DATA_LOCALIZATION_REGION'),
    ],

    'salla' => [
        'client_id' => env('SALLA_CLIENT_ID'),
        'client_secret' => env('SALLA_CLIENT_SECRET'),
        // Webhook signing secret from Salla Partners (strategy: signature, HMAC-SHA256).
        'webhook_secret' => env('SALLA_WEBHOOK_SECRET'),
        'api_base' => env('SALLA_API_BASE', 'https://api.salla.dev/admin/v2/'),
        'accounts_base' => env('SALLA_ACCOUNTS_BASE', 'https://accounts.salla.sa/'),
    ],

];
