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
    protected $table = 'admins';
    protected $guarded = [];
    public function canPossess(): bool { return true; }
}

class GuardCustomerStub extends Authenticatable
{
    use ImpersonatesUsers;
    protected $table = 'customers';
    protected $guarded = [];
}

class GuardUserStub extends Authenticatable
{
    use ImpersonatesUsers;
    protected $table = 'users';
    protected $guarded = [];
}

class GuardPossessionTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        // Define admin guard
        $app['config']->set('possession.admin_guard', 'admin');

        // Define user model (default for resolveUser)
        $app['config']->set('possession.user_model', GuardCustomerStub::class);

        // Guards
        $app['config']->set('auth.guards.admin', [
            'driver' => 'session',
            'provider' => 'admins',
        ]);
        $app['config']->set('auth.providers.admins', [
            'driver' => 'eloquent',
            'model' => GuardAdminStub::class,
        ]);

        // Default guard is web
        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => GuardUserStub::class,
        ]);

        // Customer guard (target)
        $app['config']->set('auth.guards.customer', [
            'driver' => 'session',
            'provider' => 'customers',
        ]);
        $app['config']->set('auth.providers.customers', [
            'driver' => 'eloquent',
            'model' => GuardCustomerStub::class,
        ]);

        // Database
        $schema = $app['db']->connection()->getSchemaBuilder();
        $schema->create('admins', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
        $schema->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
        $schema->create('customers', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function test_possess_uses_specified_guard()
    {
        $admin = GuardAdminStub::create(['name' => 'Admin']);
        $customer = GuardCustomerStub::create(['name' => 'Customer']);
        // Create a user with same ID
        $user = GuardUserStub::create(['name' => 'User']);

        Auth::guard('admin')->login($admin);

        // Possess customer on 'customer' guard
        Possession::possess($customer, 'customer');

        // Expectation: It logged in to 'customer' guard
        $this->assertNotNull(Auth::guard('customer')->user());
        $this->assertEquals($customer->id, Auth::guard('customer')->id());

        // And 'web' guard should NOT be logged in (or at least not as the customer/user)
        $this->assertNull(Auth::guard('web')->user());
    }

    public function test_unpossess_logs_out_impersonated_guard()
    {
        $admin = GuardAdminStub::create(['name' => 'Admin']);
        $customer = GuardCustomerStub::create(['name' => 'Customer']);

        Auth::guard('admin')->login($admin);

        // Possess customer on 'customer' guard
        Possession::possess($customer, 'customer');

        $this->assertTrue(Session::has('possession.impersonated_guard'));
        $this->assertEquals('customer', Session::get('possession.impersonated_guard'));

        // Unpossess
        Possession::unpossess();

        // Admin should be back
        $this->assertEquals($admin->id, Auth::guard('admin')->id());

        // Impersonated user should be logged out (session flushed/invalidated)
        // Note: Session::flush() handles this usually, but we want to ensure no artifacts remain if we were checking specific things
        $this->assertNull(Auth::guard('customer')->user());

        // Keys should be gone
        $this->assertFalse(Session::has('possession.original_user'));
        $this->assertFalse(Session::has('possession.impersonated_guard'));
    }

    public function test_possess_with_id_uses_specified_guard()
    {
        $admin = GuardAdminStub::create(['name' => 'Admin']);
        $customer = GuardCustomerStub::create(['name' => 'Customer']);

        Auth::guard('admin')->login($admin);

        Possession::possess($customer->id, 'customer');

        $this->assertEquals($customer->id, Auth::guard('customer')->id());
    }
}
