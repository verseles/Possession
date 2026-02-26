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
    public function canPossess(): bool { return true; }
}

class GuardTargetUserStub extends Authenticatable
{
    use ImpersonatesUsers;
    protected $guarded = [];
    protected $table = 'users';
    public function canBePossessed(): bool { return true; }
}

class GuardPossessionTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('possession.user_model', GuardTargetUserStub::class);
        $app['config']->set('possession.admin_guard', 'admin');
        $app['config']->set('possession.session_keys.original_user', 'possession_original_user');
        $app['config']->set('possession.session_keys.impersonation_guard', 'possession_impersonation_guard');

        // Admin guard
        $app['config']->set('auth.guards.admin', [
            'driver'   => 'session',
            'provider' => 'admins',
        ]);
        $app['config']->set('auth.providers.admins', [
            'driver' => 'eloquent',
            'model'  => GuardAdminStub::class,
        ]);

        // Customer guard (target)
        $app['config']->set('auth.guards.customer', [
            'driver'   => 'session',
            'provider' => 'customers',
        ]);
        $app['config']->set('auth.providers.customers', [
            'driver' => 'eloquent',
            'model'  => GuardTargetUserStub::class,
        ]);

        // Default guard is web, let's leave it as is to ensure we are testing non-default.
        $app['config']->set('auth.guards.web', [
            'driver'   => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model'  => GuardTargetUserStub::class,
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
    }

    public function test_possess_with_specific_guard()
    {
        $admin = GuardAdminStub::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'secret']);
        $user = GuardTargetUserStub::create(['name' => 'User', 'email' => 'user@test.com', 'password' => 'secret']);

        Auth::guard('admin')->login($admin);

        // Possess user on 'customer' guard
        Possession::possess($user, 'customer');

        // Assert admin is logged out
        $this->assertNull(Auth::guard('admin')->user());

        // Assert user is logged in on 'customer' guard
        $this->assertEquals($user->id, Auth::guard('customer')->id());

        // Assert user is NOT logged in on default 'web' guard
        $this->assertNull(Auth::guard('web')->user());

        // Assert session has correct guard stored
        $this->assertEquals('customer', Session::get('possession_impersonation_guard'));
    }

    public function test_unpossess_logs_out_correct_guard()
    {
        $admin = GuardAdminStub::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'secret']);
        $user = GuardTargetUserStub::create(['name' => 'User', 'email' => 'user@test.com', 'password' => 'secret']);

        Auth::guard('admin')->login($admin);
        Possession::possess($user, 'customer');

        Possession::unpossess();

        // Assert user is logged out from 'customer' guard
        $this->assertNull(Auth::guard('customer')->user());

        // Assert admin is logged back in
        $this->assertEquals($admin->id, Auth::guard('admin')->id());
    }
}
