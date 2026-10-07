<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'fcm' => [
        // اتركه فارغاً لاستخدام storage/app/firebase-service-account.json
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],

    'gemini' => [
        'key'     => env('GEMINI_API_KEY'),
        // قائمة موديلات مفصولة بفاصلة تُجرَّب بالترتيب عند الفشل/الحصّة
        'models'  => array_values(array_filter(array_map('trim', explode(',', (string) env('GEMINI_MODELS', 'gemini-3.5-flash-lite,gemini-3.7-flash,gemini-3.5-flash'))))),
        'timeout' => (int) env('GEMINI_TIMEOUT', 12),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        // نسخ كل إشعار للتطبيق إلى تيليغرام لمن ربط حسابه بالبوت. ضعها false لإيقاف النسخ
        // (الإشعارات تتضمن علامات وإنذارات غياب).
        'forward_notifications' => env('TELEGRAM_FORWARD_NOTIFICATIONS', true),
    ],

];
