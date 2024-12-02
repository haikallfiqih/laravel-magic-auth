<?php

namespace LaravelLinkAuth\MagicAuth;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class MagicAuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/magic-auth.php', 'magic-auth'
        );

        $this->app->singleton('magic-auth', function ($app) {
            return new MagicAuth();
        });

        $this->app->singleton('magic-auth.events', function ($app) {
            return Event::getFacadeRoot();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/magic-auth.php' => config_path('magic-auth.php'),
        ], 'magic-auth-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'magic-auth-migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }
}
