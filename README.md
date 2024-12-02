# Laravel Magic Auth

A secure, flexible passwordless authentication package for Laravel applications using magic links.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/haikallfiqih/laravel-magic-auth.svg?style=flat-square)](https://packagist.org/packages/haikallfiqih/laravel-magic-auth)
[![Total Downloads](https://img.shields.io/packagist/dt/haikallfiqih/laravel-magic-auth.svg?style=flat-square)](https://packagist.org/packages/haikallfiqih/laravel-magic-auth)

## Features

- 🔐 **Secure**: Uses Laravel's built-in security features
- 🎯 **Simple**: Easy to integrate and use
- 🔄 **Flexible**: Support for multiple authentication guards
- 🎨 **Customizable**: Highly configurable to suit your needs
- 📧 **Multi-Channel**: Send magic links via email, WhatsApp, or SMS
- 🧹 **Auto Cleanup**: Automatic cleanup of expired links

## Installation

You can install the package via composer:

```bash
composer require haikallfiqih/laravel-magic-auth
```

Publish the config file and migrations:

```bash
php artisan vendor:publish --provider="LaravelLinkAuth\MagicAuth\MagicAuthServiceProvider"
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

The package can be configured in `config/magic-auth.php`. Here are the key configuration options:

```php
return [
    // Table name for storing magic links
    'table' => 'magic_links',

    // Link expiration time in minutes
    'expires' => 15,

    // Guard configuration
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

## Basic Usage

### Sending Magic Links

```php
use LaravelLinkAuth\MagicAuth\Facades\MagicAuth;

// Send via email
MagicAuth::sendMagicLink('user@example.com');

// Send via WhatsApp
MagicAuth::sendMagicLink('+1234567890', 'web', [], ['whatsapp']);

// Send via SMS
MagicAuth::sendMagicLink('+1234567890', 'web', [], ['sms']);

// Send with custom attributes for new users
MagicAuth::sendMagicLink('user@example.com', 'web', [
    'name' => 'John Doe',
    'role' => 'user',
]);
```

### Verifying Magic Links

Add the verification route to your `web.php`:

```php
use LaravelLinkAuth\MagicAuth\Facades\MagicAuth;

Route::get('/magic-login/{token}', function ($token) {
    $result = MagicAuth::verifyAndLogin($token);
    
    if ($result === false) {
        return redirect()->route('login')
            ->withErrors(['email' => 'Invalid or expired magic link.']);
    }
    
    return redirect($result);
})->name('magic-auth.verify');
```

### Customizing Email Template

Create a new email template at `resources/views/vendor/magic-auth/emails/magic-link.blade.php`:

```blade
@component('mail::message')
# Login Link

Click the button below to log in to your account.

@component('mail::button', ['url' => $url])
Log In
@endcomponent

This link will expire in {{ $expiresInMinutes }} minutes.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```

## WhatsApp/SMS Configuration

To use WhatsApp or SMS notifications, you'll need to configure your provider credentials in your `.env` file:

```env
MAGIC_AUTH_WHATSAPP_PROVIDER=twilio
MAGIC_AUTH_WHATSAPP_FROM=+1234567890
TWILIO_SID=your-twilio-sid
TWILIO_TOKEN=your-twilio-token

MAGIC_AUTH_SMS_PROVIDER=twilio
MAGIC_AUTH_SMS_FROM=+1234567890
```

## Events

The package provides a dedicated Events facade for handling magic link events:

### Available Events

All available events are defined as constants in `MagicAuthEvents`:

```php
use LaravelLinkAuth\MagicAuth\Events\MagicAuthEvents;

// Link Generation
MagicAuthEvents::GENERATING      // 'magic-auth.link.generating'
MagicAuthEvents::SENT           // 'magic-auth.link.sent'
MagicAuthEvents::FAILED         // 'magic-auth.link.failed'

// Verification
MagicAuthEvents::VERIFICATION_STARTED   // 'magic-auth.verification.started'
MagicAuthEvents::VERIFICATION_COMPLETED // 'magic-auth.verification.completed'
MagicAuthEvents::VERIFICATION_FAILED    // 'magic-auth.verification.failed'
MagicAuthEvents::VERIFICATION_ERROR     // 'magic-auth.verification.error'
```

### Using Events

```php
use LaravelLinkAuth\MagicAuth\Facades\Events;
use LaravelLinkAuth\MagicAuth\Events\MagicAuthEvents;

// Before generating a magic link
// Use case: Validate user permissions, add custom attributes
Events::listen(MagicAuthEvents::GENERATING, function ($notifiable, $guard, $attributes) {
    // Example: Add company data for new users
    if ($notifiable instanceof \App\Models\User && !$notifiable->exists) {
        $attributes['company_id'] = request()->get('company_id');
    }
    
    // Example: Prevent magic links for blocked users
    if ($notifiable->is_blocked ?? false) {
        throw new \Exception('User is blocked from requesting magic links.');
    }
});

// After successfully sending a magic link
// Use case: Notify admins, log activity, trigger welcome flow
Events::listen(MagicAuthEvents::SENT, function ($notifiable, $guard, $linkId) {
    // Example: Log for security audit
    activity()
        ->performedOn($notifiable)
        ->withProperties(['link_id' => $linkId, 'guard' => $guard])
        ->log('Magic link requested');
        
    // Example: Notify admin about new user
    if ($notifiable instanceof \App\Models\User && !$notifiable->exists) {
        \App\Notifications\NewUserRequested::dispatch($notifiable);
    }
});

// When magic link sending fails
// Use case: Alert monitoring system, retry logic
Events::listen(MagicAuthEvents::FAILED, function ($notifiable, $error) {
    // Example: Log to monitoring service
    \Sentry::captureException($error);
    
    // Example: Notify admin if critical
    if ($error instanceof \Exception) {
        \App\Notifications\MagicLinkFailure::dispatch($notifiable, $error);
    }
});
```

### Verification Events

```php
// When verification process starts
// Use case: Track login attempts, detect suspicious activity
Events::listen(MagicAuthEvents::VERIFICATION_STARTED, function ($identifier, $guard) {
    // Example: Track login attempt location
    $location = geoip()->getLocation(request()->ip());
    
    activity()
        ->withProperties([
            'ip' => request()->ip(),
            'location' => $location->toArray(),
            'user_agent' => request()->userAgent()
        ])
        ->log('Magic link verification attempt');
});

// After successful verification and login
// Use case: Update user data, trigger onboarding
Events::listen(MagicAuthEvents::VERIFICATION_COMPLETED, function ($user, $guard) {
    // Example: Update last login timestamp
    $user->update(['last_login_at' => now()]);
    
    // Example: Start onboarding for new users
    if ($user->wasRecentlyCreated) {
        \App\Jobs\StartUserOnboarding::dispatch($user);
    }
    
    // Example: Clear old sessions
    if (config('magic-auth.single_session')) {
        \App\Jobs\ClearOtherSessions::dispatch($user);
    }
});

// When verification fails
// Use case: Security monitoring, fraud detection
Events::listen(MagicAuthEvents::VERIFICATION_FAILED, function ($token, $reason) {
    // Example: Track failed attempts for security
    cache()->increment("failed_magic_link_attempts:{$token}");
    
    // Example: Alert on suspicious activity
    $attempts = cache()->get("failed_magic_link_attempts:{$token}");
    if ($attempts > 3) {
        \App\Notifications\SuspiciousActivity::dispatch($token, $reason);
    }
});
```

### Registering Event Listeners

You can register these event listeners in your `EventServiceProvider`:

```php
use LaravelLinkAuth\MagicAuth\Facades\Events;
use LaravelLinkAuth\MagicAuth\Events\MagicAuthEvents;

class EventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Magic link generation monitoring
        Events::listen(MagicAuthEvents::GENERATING, [
            \App\Listeners\ValidateMagicLinkRequest::class,
            'handle'
        ]);
        
        // Security tracking for all verification events
        Events::listen('magic-auth.verification.*', [
            \App\Listeners\TrackMagicLinkSecurity::class,
            'handle'
        ]);
        
        // User onboarding flow
        Events::listen(MagicAuthEvents::VERIFICATION_COMPLETED, [
            \App\Listeners\HandleSuccessfulLogin::class,
            'handle'
        ]);
    }
}
```

Each event provides relevant data that you can use to implement custom logic, security measures, and user flows in your application.

## Security

- Magic links are single-use only
- Links expire after a configurable time
- Rate limiting prevents abuse
- Uses Laravel's secure URL signing
- Automatic cleanup of expired links

## Requirements

- PHP 8.0+
- Laravel 8.0+

## Credits

- [Haikal Fiqih](https://github.com/haikallfiqih)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
