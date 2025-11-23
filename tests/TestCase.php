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
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
    }
}
