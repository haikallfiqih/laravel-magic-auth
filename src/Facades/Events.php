<?php

namespace LaravelLinkAuth\MagicAuth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void dispatch(string $event, array $payload = [])
 * @method static void listen(string $event, callable|array|string $callback)
 */
class Events extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'magic-auth.events';
    }
}
