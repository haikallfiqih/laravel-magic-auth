<?php

/**
 * Laravel Magic Auth
 * 
 * Provides secure, passwordless authentication using magic links.
 * Features include rate limiting, custom user attributes, guard-specific settings,
 * and comprehensive event dispatching.
 *
 * @package LaravelLinkAuth\MagicAuth
 * @author Haikal Fiqih
 * @license MIT
 */

namespace LaravelLinkAuth\MagicAuth;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use LaravelLinkAuth\MagicAuth\Notifications\MagicLinkNotification;
use Carbon\Carbon;

class MagicAuth
{
    /**
     * Clean up expired magic links.
     *
     * @return int Number of deleted records
     */
    public function cleanup(): int
    {
        return DB::table(config('magic-auth.table'))
            ->where('expires_at', '<', now())
            ->delete();
    }

    /**
     * Invalidate all magic links for a specific contact.
     *
     * @param string $contact Email or phone number
     * @param string|null $guard
     * @return int Number of invalidated links
     */
    public function invalidateLinks(string $contact, ?string $guard = null): int
    {
        $query = DB::table(config('magic-auth.table'))
            ->where('used', false);

        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $query->where('email', $contact);
        } else {
            $query->where('phone', $contact);
        }

        if ($guard) {
            $query->where('guard', $guard);
        }

        return $query->update(['used' => true]);
    }

    /**
     * Check if a magic link exists and is valid.
     *
     * @param string $token
     * @param string $guard
     * @return bool
     */
    public function isValidLink(string $token, string $guard = 'web'): bool
    {
        return DB::table(config('magic-auth.table'))
            ->where('token', $token)
            ->where('guard', $guard)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Send a magic link for authentication.
     *
     * @param mixed $notifiable The user or email to send the link to
     * @param string $guard The authentication guard to use
     * @param array $attributes Custom attributes for user creation
     * @param array|string|null $channels Specific channels to send the notification through
     * @throws \InvalidArgumentException If guard is invalid
     * @throws \RuntimeException If rate limit exceeded or sending fails
     * @return bool
     */
    public function sendMagicLink($notifiable, string $guard = 'web', array $attributes = [], $channels = null)
    {
        if (!array_key_exists($guard, config('magic-auth.guards'))) {
            throw new \InvalidArgumentException("Invalid guard specified: {$guard}");
        }

        // If notifiable is a string (email/phone), create a temporary notifiable object
        if (is_string($notifiable)) {
            $notifiable = new class($notifiable) {
                public $email;
                public $phone;
                public $magicLinkChannels;

                public function __construct($contact)
                {
                    if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
                        $this->email = $contact;
                        $this->magicLinkChannels = ['mail'];
                    } else {
                        $this->phone = $contact;
                        $this->magicLinkChannels = ['whatsapp', 'sms'];
                    }
                }

                public function routeNotificationFor($driver)
                {
                    if ($driver === 'mail') {
                        return $this->email;
                    }
                    return $this->phone;
                }
            };
        }

        // Apply rate limiting
        $key = 'magic-link:' . ($notifiable->email ?? $notifiable->phone);
        $maxAttempts = config('magic-auth.throttle.max_attempts', 5);
        $decayMinutes = config('magic-auth.throttle.decay_minutes', 10);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            throw new \RuntimeException("Too many magic link requests. Please try again in {$seconds} seconds.");
        }

        try {
            $token = Str::random(64);
            $guardConfig = config("magic-auth.guards.{$guard}");
            $expiresAt = now()->addMinutes($guardConfig['link_expiration'] ?? config('magic-auth.expires'));

            // Check for existing unused links and invalidate them
            DB::table(config('magic-auth.table'))->where('email', $notifiable->email ?? null)
                ->where('phone', $notifiable->phone ?? null)
                ->where('guard', $guard)
                ->where('used', false)
                ->where('expires_at', '>', now())
                ->update(['used' => true]);

            // Create new magic link
            $magicLinkId = DB::table(config('magic-auth.table'))->insertGetId([
                'email' => $notifiable->email ?? null,
                'phone' => $notifiable->phone ?? null,
                'token' => $token,
                'guard' => $guard,
                'expires_at' => $expiresAt,
                'attributes' => json_encode($attributes),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $magicLink = URL::temporarySignedRoute(
                'magic-auth.verify',
                $expiresAt,
                ['token' => $token, 'guard' => $guard]
            );

            try {
                Event::dispatch('magic-auth.link.generating', [$notifiable, $guard, $attributes]);
                
                // Override channels if specified
                if ($channels) {
                    $notifiable->magicLinkChannels = (array) $channels;
                }

                Notification::send($notifiable, new MagicLinkNotification(
                    $magicLink,
                    $expiresAt->diffInMinutes(now())
                ));

                Event::dispatch('magic-auth.link.sent', [$notifiable, $guard, $magicLinkId]);
                RateLimiter::hit($key, $decayMinutes * 60);
            } catch (\Exception $e) {
                // If sending fails, mark the link as used and rethrow
                DB::table(config('magic-auth.table'))
                    ->where('token', $token)
                    ->update(['used' => true]);
                
                Event::dispatch('magic-auth.link.failed', [$notifiable, $e->getMessage()]);
                throw new \RuntimeException('Failed to send magic link: ' . $e->getMessage());
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Magic link creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get magic link statistics.
     *
     * @param string|null $contact Filter by email or phone
     * @param string|null $guard Filter by guard
     * @return array
     */
    public function getStats(?string $contact = null, ?string $guard = null): array
    {
        $query = DB::table(config('magic-auth.table'));

        if ($contact) {
            if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
                $query->where('email', $contact);
            } else {
                $query->where('phone', $contact);
            }
        }

        if ($guard) {
            $query->where('guard', $guard);
        }

        $total = $query->count();
        $used = $query->where('used', true)->count();
        $expired = $query->where('expires_at', '<', now())->count();
        $active = $query->where('used', false)
            ->where('expires_at', '>', now())
            ->count();

        return [
            'total' => $total,
            'used' => $used,
            'expired' => $expired,
            'active' => $active,
        ];
    }

    /**
     * Get the remaining attempts for magic link requests.
     *
     * @param string $contact Email or phone number
     * @return int
     */
    public function getRemainingAttempts(string $contact): int
    {
        $key = 'magic-link:' . $contact;
        $maxAttempts = config('magic-auth.throttle.max_attempts', 5);
        
        return RateLimiter::remaining($key, $maxAttempts);
    }

    /**
     * Verify a magic link token and authenticate the user.
     *
     * @param string $token The magic link token
     * @param string $guard The authentication guard to use
     * @throws \InvalidArgumentException If guard is invalid
     * @throws \Exception If verification fails
     * @return string|bool Redirect path on success, false on failure
     */
    public function verifyAndLogin($token, $guard = 'web')
    {
        if (!array_key_exists($guard, config('magic-auth.guards'))) {
            throw new \InvalidArgumentException("Invalid guard specified: {$guard}");
        }

        try {
            $magicLink = DB::table(config('magic-auth.table'))
                ->where('token', $token)
                ->where('guard', $guard)
                ->where('used', false)
                ->where('expires_at', '>', now())
                ->first();

            if (!$magicLink) {
                Event::dispatch('magic-auth.verification.failed', [$token, 'Invalid or expired token']);
                return false;
            }

            // Start a database transaction to ensure atomicity
            return DB::transaction(function () use ($magicLink, $guard) {
                Event::dispatch('magic-auth.verification.started', [$magicLink->email ?? $magicLink->phone, $guard]);
                
                // Mark the magic link as used
                DB::table(config('magic-auth.table'))
                    ->where('id', $magicLink->id)
                    ->update(['used' => true]);

                $guardConfig = config("magic-auth.guards.{$guard}");
                $modelClass = $guardConfig['model'];

                // Generate a random password for new users
                $defaultPassword = Str::random(32);

                // Get custom attributes if any
                $attributes = json_decode($magicLink->attributes ?? '{}', true);
                
                // Determine identifier field and value
                $identifier = $magicLink->email ? 'email' : 'phone';
                $identifierValue = $magicLink->email ?? $magicLink->phone;

                $createAttributes = array_merge(
                    [
                        $identifier => $identifierValue,
                        'name' => $attributes['name'] ?? explode('@', $identifierValue)[0],
                        'password' => Hash::make($defaultPassword),
                        'email_verified_at' => now(),
                    ],
                    $attributes
                );

                // First try to find the user, if not found create with attributes
                $user = $modelClass::firstOrCreate(
                    [$identifier => $identifierValue],
                    $createAttributes
                );

                // If user was found but doesn't have a password, set one
                if (!$user->password) {
                    $user->password = Hash::make($defaultPassword);
                    $user->save();
                }

                auth()->guard($guard)->login($user);
                
                Event::dispatch('magic-auth.verification.completed', [$user, $guard]);

                return $guardConfig['redirect_on_success'];
            });
        } catch (\Exception $e) {
            Event::dispatch('magic-auth.verification.error', [$token, $e->getMessage()]);
            \Log::error('Magic link verification failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
