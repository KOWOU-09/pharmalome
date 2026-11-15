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

    'public_stats' => [
        'users_url' => env('USER_SERVICE_URL', 'http://127.0.0.1:8001'),
        'catalog_url' => env('CATALOG_SERVICE_URL', 'http://127.0.0.1:8002'),
        'orders_url' => env('ORDER_SERVICE_URL', 'http://127.0.0.1:8003'),
        'internal_key' => env('INTERNAL_KEY', 'change-me-internal-key'),
    ],

    'contact' => [
        'email' => env('CONTACT_EMAIL', 'contact@medexpress.tg'),
    ],

];
