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

class GuardTargetUserStub extends Authenticatable
{
    use ImpersonatesUsers;

    protected $guarded = [];
    protected $table = 'users';

    public function canBePossessed(): bool
    {
        return true;
    }
}

class CustomerUserStub extends Authenticatable
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
        $app['config']->set('possession.user_model', GuardTargetUserStub::class);
        $app['config']->set('possession.admin_guard', 'admin');
        $app['config']->set('possession.session_keys.original_user', 'possession_original_user');
        $app['config']->set('possession.session_keys.impersonated_guard', 'possession_impersonated_guard');

        // Configure admin guard
        $app['config']->set('auth.guards.admin', [
            'driver'   => 'session',
            'provider' => 'admins',
        ]);
        $app['config']->set('auth.providers.admins', [
            'driver' => 'eloquent',
            'model'  => GuardAdminStub::class,
        ]);

        // Configure web guard (default)
        $app['config']->set('auth.guards.web', [
            'driver'   => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model'  => GuardTargetUserStub::class,
        ]);

        // Configure customer guard
        $app['config']->set('auth.guards.customer', [
            'driver'   => 'session',
            'provider' => 'customers',
        ]);
        $app['config']->set('auth.providers.customers', [
            'driver' => 'eloquent',
            'model'  => CustomerUserStub::class,
        ]);

        $schema = $app['db']->connection()->getSchemaBuilder();

        $schema->create('admins', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });

        $schema->create('users', function ($table) {
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

    public function test_possess_uses_specific_guard()
    {
        $admin = GuardAdminStub::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);
        $customer = CustomerUserStub::create(['name' => 'Customer', 'email' => 'customer@example.com', 'password' => 'password']);

        // Login admin
        Auth::guard('admin')->login($admin);

        // Possess customer using 'customer' guard
        // This relies on the new feature we are about to implement
        // Currently this will fail or ignore the second argument if not implemented
        Possession::possess($customer, 'customer');

        // Assert customer is logged in on 'customer' guard
        $this->assertEquals($customer->id, Auth::guard('customer')->id());
        $this->assertTrue(Auth::guard('customer')->check());

        // Assert admin is logged out (or session destroyed/switched)
        // Since session is flushed, admin should be logged out from 'admin' guard too?
        // Actually, Auth::guard('admin')->check() relies on session. So yes.
        // However, in tests, the guard instance might hold the user in memory.
        Auth::guard('admin')->forgetUser();
        $this->assertFalse(Auth::guard('admin')->check());

        // Assert session has original user
        $this->assertTrue(Session::has('possession_original_user'));

        // Assert session has impersonated guard
        $this->assertEquals('customer', Session::get('possession_impersonated_guard'));

        // Now unpossess
        Possession::unpossess();

        // Assert admin is back
        $this->assertEquals($admin->id, Auth::guard('admin')->id());

        // Assert customer is logged out
        $this->assertFalse(Auth::guard('customer')->check());
    }
}
