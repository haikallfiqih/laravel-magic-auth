<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the table that will store magic links.
    |
    */
    'table' => 'magic_links',

    /*
    |--------------------------------------------------------------------------
    | Link Expiration Time
    |--------------------------------------------------------------------------
    |
    | How long (in minutes) a magic link should be valid for.
    |
    */
    'expires' => 15,

    /*
    |--------------------------------------------------------------------------
    | Guards Configuration
    |--------------------------------------------------------------------------
    |
    | Configure different authentication guards for magic link authentication.
    |
    */
    'guards' => [
        'web' => [
            'provider' => 'users',
            'model' => \App\Models\User::class,
            'link_expiration' => null, // Uses default from 'expires'
            'redirect_on_success' => '/dashboard',
        ],
        // disable by default
        // 'admin' => [
        //     'provider' => 'admins',
        //     'model' => \App\Models\Admin::class,
        //     'redirect_on_success' => '/admin/dashboard',
        //     'link_expiration' => 30,
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Throttling
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for magic link requests.
    |
    */
    'throttle' => [
        'max_attempts' => 5,
        'decay_minutes' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Configure notification channels for sending magic links.
    |
    */
    'channels' => [
        'default' => ['mail'],
        'available' => ['mail', 'whatsapp', 'sms'],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Configuration
    |--------------------------------------------------------------------------
    |
    | Configure WhatsApp notification settings.
    |
    */
    'whatsapp' => [
        'provider' => env('MAGIC_AUTH_WHATSAPP_PROVIDER', 'twilio'),
        'from' => env('MAGIC_AUTH_WHATSAPP_FROM'),
        'message' => "Your login link for :app\n\nClick here to login: :url\n\nThis link will expire in :minutes minutes.",
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configure SMS notification settings.
    |
    */
    'sms' => [
        'provider' => env('MAGIC_AUTH_SMS_PROVIDER', 'twilio'),
        'from' => env('MAGIC_AUTH_SMS_FROM'),
        'message' => "Your :app login link: :url (expires in :minutes minutes)",
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes used for magic link verification.
    |
    */
    'routes' => [
        'verify' => 'magic-auth.verify',
        'middleware' => ['web', 'guest'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the email settings for magic links.
    |
    */
    'mail' => [
        'subject' => env('MAGIC_AUTH_MAIL_SUBJECT', 'Your Magic Login Link'),
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'name' => env('MAIL_FROM_NAME', 'Laravel Magic Auth'),
        ],
        'view' => 'vendor.magic-auth.emails.magic-link',
    ],
];
