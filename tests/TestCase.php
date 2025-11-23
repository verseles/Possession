<?php

namespace Verseles\Possession\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Verseles\Possession\PossessionServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            PossessionServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Possession' => \Verseles\Possession\Facades\Possession::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('possession.user_model', \Verseles\Possession\Tests\Fixtures\User::class);
        $app['config']->set('possession.admin_guard', 'web');
        $app['config']->set('possession.session_keys.original_user', 'possession.original_user_id');
        $app['config']->set('possession.routes.enabled', true);
        $app['config']->set('possession.routes.prefix', 'possession');
        $app['config']->set('possession.routes.middleware', ['web']);
        $app['config']->set('possession.redirect_after_possess', '/dashboard');
        $app['config']->set('possession.redirect_after_unpossess', '/admin');
        $app['config']->set('possession.forbidden_redirect', '/');

        // Database configuration for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations(['--database' => 'testing']);
    }

    protected function createAdmin(array $attributes = []): \Verseles\Possession\Tests\Fixtures\User
    {
        $user = \Verseles\Possession\Tests\Fixtures\User::create(array_merge([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ], $attributes));
        $user->setCanPossess(true);

        return $user;
    }

    protected function createUser(array $attributes = []): \Verseles\Possession\Tests\Fixtures\User
    {
        return \Verseles\Possession\Tests\Fixtures\User::create(array_merge([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ], $attributes));
    }
}
