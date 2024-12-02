<?php

namespace LaravelLinkAuth\MagicAuth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool sendMagicLink(string $email, string $guard = 'web', array $attributes = [])
 * @method static string|bool verifyAndLogin(string $token, string $guard = 'web')
 * @method static int cleanup()
 * @method static int invalidateLinks(string $email, ?string $guard = null)
 * @method static bool isValidLink(string $token, string $guard = 'web')
 * @method static array getStats(?string $email = null, ?string $guard = null)
 * @method static int getRemainingAttempts(string $email)
 *
 * @see \LaravelLinkAuth\MagicAuth\MagicAuth
 */
class MagicAuth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'magic-auth';
    }
}
