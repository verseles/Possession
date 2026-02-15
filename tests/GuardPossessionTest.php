<?php

namespace Verseles\Possession\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Verseles\Possession\Facades\Possession;
use Verseles\Possession\Traits\ImpersonatesUsers;

class GuardAdminStub extends Authenticatable
{
    use ImpersonatesUsers;

    protected $guarded = [];
    protected $table = 'admins';

    public function canPossess(): bool
    {
        return true;
    }
}

class GuardCustomerStub extends Authenticatable
{
    use ImpersonatesUsers;

    protected $guarded = [];
    protected $table = 'customers';

    public function canBePossessed(): bool
    {
        return true;
    }
}

class GuardPossessionTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('possession.user_model', GuardCustomerStub::class);
        $app['config']->set('possession.admin_guard', 'admin');
        $app['config']->set('possession.session_keys.original_user', 'possession_original_user');

        // Admin guard
        $app['config']->set('auth.guards.admin', [
            'driver'   => 'session',
            'provider' => 'admins',
        ]);
        $app['config']->set('auth.providers.admins', [
            'driver' => 'eloquent',
            'model'  => GuardAdminStub::class,
        ]);

        // Customer guard (non-default)
        $app['config']->set('auth.guards.customer', [
            'driver'   => 'session',
            'provider' => 'customers',
        ]);
        $app['config']->set('auth.providers.customers', [
            'driver' => 'eloquent',
            'model'  => GuardCustomerStub::class,
        ]);

        // Default guard is web
        $app['config']->set('auth.guards.web', [
            'driver'   => 'session',
            'provider' => 'users',
        ]);
         $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model'  => GuardCustomerStub::class,
        ]);

        $schema = $app['db']->connection()->getSchemaBuilder();

        $schema->create('admins', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });

        $schema->create('customers', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
    }

    public function test_possessing_user_on_specific_guard()
    {
        $admin = GuardAdminStub::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);
        $customer = GuardCustomerStub::create(['name' => 'Customer', 'email' => 'customer@example.com', 'password' => 'password']);

        Auth::guard('admin')->login($admin);

        // Possess user on 'customer' guard
        Possession::possess($customer, 'customer');

        // Verify we are logged in as customer on 'customer' guard
        $this->assertTrue(Auth::guard('customer')->check());
        $this->assertEquals($customer->id, Auth::guard('customer')->id());

        // Verify we are NOT logged in on default 'web' guard
        $this->assertFalse(Auth::guard('web')->check());

        // Verify we can unpossess
        Possession::unpossess();

        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertEquals($admin->id, Auth::guard('admin')->id());
    }
}
