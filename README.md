# Laravel Magic Auth

A secure, flexible passwordless authentication package for Laravel using magic links. Supports multiple notification channels including email, WhatsApp, and SMS.

## Features

- 🔐 Secure passwordless authentication
- 📧 Multi-channel notifications (Email, WhatsApp, SMS)
- ⚡ Easy integration with Laravel's authentication system
- 🛡️ Rate limiting and link expiration
- 🔄 Event-driven architecture
- 🎨 Customizable templates and messages
- 🚦 Multiple guard support

## Installation

```bash
composer require haikallfiqih/laravel-magic-auth
```

### Configuration

1. Publish the configuration and migrations:
```bash
php artisan vendor:publish --provider="LaravelLinkAuth\MagicAuth\MagicAuthServiceProvider"
```

2. Run the migrations:
```bash
php artisan migrate
```

3. Add these environment variables to your `.env` file:
```env
# For Email (default)
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Your App Name"

# For WhatsApp/SMS (optional)
MAGIC_AUTH_WHATSAPP_PROVIDER=twilio
MAGIC_AUTH_WHATSAPP_FROM=+1234567890
MAGIC_AUTH_SMS_FROM=+1234567890
TWILIO_SID=your-twilio-sid
TWILIO_TOKEN=your-twilio-token
```

## Basic Usage

### Sending Magic Links

```php
use LaravelLinkAuth\MagicAuth\Facades\MagicAuth;

// Send via email (default)
MagicAuth::sendMagicLink('user@example.com');

// Send via WhatsApp
MagicAuth::sendMagicLink('+1234567890', 'web', [], ['whatsapp']);

// Send via SMS
MagicAuth::sendMagicLink('+1234567890', 'web', [], ['sms']);

// With custom attributes for new users
MagicAuth::sendMagicLink('user@example.com', 'web', [
    'name' => 'John Doe',
    'company_id' => 1
]);
```

### Route Configuration

Add these routes to your `web.php`:

```php
use LaravelLinkAuth\MagicAuth\Http\Controllers\MagicAuthController;

Route::post('/magic-link', [MagicAuthController::class, 'sendMagicLink'])
    ->name('magic-auth.send');

Route::get('/auth/verify', [MagicAuthController::class, 'verify'])
    ->name('magic-auth.verify')
    ->middleware('signed');
```

### Event Handling

```php
use LaravelLinkAuth\MagicAuth\Facades\Events;
use LaravelLinkAuth\MagicAuth\Events\MagicAuthEvents;

// Before generating a magic link
Events::listen(MagicAuthEvents::GENERATING, function ($notifiable, $guard, $attributes) {
    // Validate or modify attributes
});

// After sending a magic link
Events::listen(MagicAuthEvents::SENT, function ($notifiable, $guard, $linkId) {
    // Log or track magic link usage
});

// When verification succeeds
Events::listen(MagicAuthEvents::VERIFICATION_COMPLETED, function ($user, $guard) {
    // Handle successful login
});
```

## WhatsApp/SMS Integration

To use WhatsApp or SMS notifications:

1. Install Twilio SDK:
```bash
composer require twilio/sdk
```

2. Configure your Twilio credentials in `.env`:
```env
TWILIO_SID=your-twilio-sid
TWILIO_TOKEN=your-twilio-token
MAGIC_AUTH_WHATSAPP_FROM=+1234567890
MAGIC_AUTH_SMS_FROM=+1234567890
```

## Configuration Options

```php
return [
    // Link expiration time in minutes
    'expires' => 15,

    // Authentication guards configuration
    'guards' => [
        'web' => [
            'provider' => 'users',
            'model' => \App\Models\User::class,
            'redirect_on_success' => '/dashboard',
        ],
    ],

    // Rate limiting settings
    'throttle' => [
        'max_attempts' => 5,
        'decay_minutes' => 10,
    ],

    // Available notification channels
    'channels' => [
        'default' => ['mail'],
        'available' => ['mail', 'whatsapp', 'sms'],
    ],
];
```

## Security

- Links are single-use and expire after a configurable time
- Rate limiting prevents abuse
- Signed URLs prevent tampering
- Automatic cleanup of expired links

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Haikal Fiqih](https://github.com/haikallfiqih)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
