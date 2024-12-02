<?php

namespace LaravelLinkAuth\MagicAuth\Tests;

use LaravelLinkAuth\MagicAuth\MagicAuthServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            MagicAuthServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getEnvironmentSetUp($app)
    {
        // Set up default database configuration
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up mail configuration
        config()->set('mail.default', 'array');
        config()->set('mail.mailers.array', [
            'transport' => 'array',
        ]);
    }
}
