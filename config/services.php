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

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        'mode' => env('PAYSTACK_MODE', 'test'),
        'base_url' => 'https://api.paystack.co',
    ],

    // Tier 2 KYC — NIN verification via a NIMC-licensed partner (IdentityPass).
    // Raw NIN is sent ONLY to this partner over TLS; we never persist it.
    'identitypass' => [
        'enabled' => (bool) env('USE_IDENTITYPASS', false),
        'key' => env('IDENTITYPASS_API_KEY'),
        'base_url' => env('IDENTITYPASS_BASE_URL', 'https://api.myidentitypass.com'),
        'nin_endpoint' => '/api/v2/biometrics/merchant/data/verification/nin',
        'cost_naira' => env('IDENTITYPASS_COST_NGN', 100),
        'monthly_cap_naira' => env('IDENTITYPASS_MONTHLY_CAP_NGN', 50000),
    ],

    // Tier 3 KYC — driver anti-spoof liveness (Smile Identity SmartSelfie).
    'smile' => [
        'enabled' => (bool) env('USE_SMILE', false),
        'partner_id' => env('SMILE_PARTNER_ID'),
        'api_key' => env('SMILE_API_KEY'),
        'sid_server' => env('SMILE_SID_SERVER', 'https://api.usesmileid.com'),
        'webhook_secret' => env('SMILE_WEBHOOK_SECRET'),
        'anti_spoof_threshold' => env('SMILE_ANTI_SPOOF_THRESHOLD', 80),
        'cost_naira' => env('SMILE_COST_NGN', 400),
    ],

    // Tier-0 OTP delivery — pluggable SMS providers (Termii/Twilio). The code
    // goes to the database + application log until WORKRIDE_SMS_ENABLED is set.
    'termii' => [
        'key' => env('TERMII_API_KEY'),
        'sender_id' => env('TERMII_SENDER_ID', 'WorkRide'),
        'base_url' => env('TERMII_BASE_URL', 'https://api.ng.termii.com'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    // FCM push (roadmap P3.2) — legacy HTTP send API. Needs FEATURE_PUSH=true
    // plus a Firebase Cloud Messaging legacy server key to be active.
    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
        'endpoint' => env('FCM_ENDPOINT', 'https://fcm.googleapis.com/fcm/send'),
    ],

];
