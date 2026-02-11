<?php

namespace Verseles\Possession\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Verseles\Possession\Facades\Possession;
use Verseles\Possession\Traits\ImpersonatesUsers;

class CustomGuardAdminStub extends Authenticatable
{
    use ImpersonatesUsers;

    protected $guarded = [];
    protected $table = 'admins';

    public function canPossess(): bool
    {
        return true;
    }
}

class CustomGuardTargetUserStub extends Authenticatable
{
    use ImpersonatesUsers;

    protected $guarded = [];
    protected $table = 'users';

    public function canBePossessed(): bool
    {
        return true;
    }
}

class CustomGuardPossessionTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('possession.user_model', CustomGuardTargetUserStub::class);
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
            'model'  => CustomGuardAdminStub::class,
        ]);

        // Configure customer guard (non-default)
        $app['config']->set('auth.guards.customer', [
            'driver'   => 'session',
            'provider' => 'customers',
        ]);

        $app['config']->set('auth.providers.customers', [
            'driver' => 'eloquent',
            'model'  => CustomGuardTargetUserStub::class,
        ]);

        // Default guard remains 'web' (implicit)

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
    }

    public function test_possess_with_specific_guard()
    {
        $admin = CustomGuardAdminStub::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);
        $user = CustomGuardTargetUserStub::create(['name' => 'User', 'email' => 'user@example.com', 'password' => 'password']);

        Auth::guard('admin')->login($admin);

        // Possess user on 'customer' guard
        // This call is expected to fail initially as possess() doesn't accept a second argument yet
        // Or if it accepts but ignores, it will use default guard 'web', not 'customer'
        Possession::possess($user, 'customer');

        // Verify user is logged in to 'customer' guard
        $this->assertEquals($user->id, Auth::guard('customer')->id());
        $this->assertTrue(Session::has('possession_original_user'));
        $this->assertTrue(Session::has('possession_impersonated_guard'));
        $this->assertEquals('customer', Session::get('possession_impersonated_guard'));

        // Verify admin is logged out (from admin guard)
        $this->assertNull(Auth::guard('admin')->user());

        // Unpossess
        Possession::unpossess();

        // Verify admin is restored
        $this->assertEquals($admin->id, Auth::guard('admin')->id());
        $this->assertFalse(Session::has('possession_original_user'));

        // Verify customer guard is logged out
        $this->assertNull(Auth::guard('customer')->user());
    }
}
