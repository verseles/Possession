<?php

namespace Verseles\Possession\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;
use Verseles\Possession\Facades\Possession;
use Verseles\Possession\Traits\ImpersonatesUsers;
use Verseles\Possession\Exceptions\ImpersonationException;

class AdminStubMulti extends Authenticatable
{
    use ImpersonatesUsers;
    protected $table = 'admins';
    protected $guarded = [];

    public function canPossess(): bool
    {
        return true;
    }
}

class UserStubMulti extends Authenticatable
{
    use ImpersonatesUsers;
    protected $table = 'users';
    protected $guarded = [];

    public function canBePossessed(): bool
    {
        return true;
    }
}

class MultiAuthPossessionTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        // Configure database
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Configure auth
        $app['config']->set('auth.guards.admin', [
            'driver' => 'session',
            'provider' => 'admins',
        ]);
        $app['config']->set('auth.providers.admins', [
            'driver' => 'eloquent',
            'model' => AdminStubMulti::class,
        ]);

        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => UserStubMulti::class,
        ]);

        // Configure possession
        $app['config']->set('possession.user_model', UserStubMulti::class,);
        $app['config']->set('possession.admin_guard', 'admin');
        $app['config']->set('possession.session_keys.original_user', 'possession_original_user');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('admins', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_it_correctly_resolves_admin_model_when_unpossessing()
    {
        $admin = AdminStubMulti::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password')
        ]);

        $user = UserStubMulti::create([
            'id' => 2,
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => bcrypt('password')
        ]);

        // Login as admin
        Auth::guard('admin')->login($admin);

        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertEquals($admin->id, Auth::guard('admin')->id());

        // Possess user
        Possession::possess($user);

        // Assert user is logged in
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertEquals($user->id, Auth::guard('web')->id());
        $this->assertEquals($admin->id, Session::get('possession_original_user'));

        // Unpossess
        Possession::unpossess();

        // Assert admin is logged back in
        $this->assertTrue(Auth::guard('admin')->check(), 'Admin should be logged in on admin guard');
        $this->assertEquals($admin->id, Auth::guard('admin')->id());
        $this->assertFalse(Auth::guard('web')->check(), 'User should be logged out');
    }

    public function test_it_allows_possessing_different_model_with_same_id()
    {
        $admin = AdminStubMulti::create([
            'id' => 1,
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password')
        ]);

        $user = UserStubMulti::create([
            'id' => 1,
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => bcrypt('password')
        ]);

        Auth::guard('admin')->login($admin);

        try {
            Possession::possess($user);
            $this->assertTrue(true);
        } catch (ImpersonationException $e) {
            $this->fail('Should NOT have thrown ImpersonationException for different models with same ID: ' . $e->getMessage());
        }

        // Clean up session for next test?
        Possession::unpossess();
    }
}
