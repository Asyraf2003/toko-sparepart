<?php

return [

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

    // Telegram low stock alert
    'telegram_low_stock' => [
        'enabled' => env('TELEGRAM_LOW_STOCK_ENABLED', false),
        'bot_token' => env('TELEGRAM_LOW_STOCK_BOT_TOKEN', ''),
        'chat_ids' => env('TELEGRAM_LOW_STOCK_CHAT_IDS', ''),
        'min_interval_seconds' => env('TELEGRAM_LOW_STOCK_MIN_INTERVAL_SECONDS', 86400),
        'reset_on_recover' => env('TELEGRAM_LOW_STOCK_RESET_ON_RECOVER', true),
        'throttle_on_failure' => env('TELEGRAM_LOW_STOCK_THROTTLE_ON_FAILURE', true),
    ],

];
