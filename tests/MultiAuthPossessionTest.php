<?php

namespace Verseles\Possession\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Verseles\Possession\Facades\Possession;
use Verseles\Possession\Traits\ImpersonatesUsers;

class AdminStub extends Authenticatable
{
    use ImpersonatesUsers;

    protected $guarded = [];
    protected $table = 'admins';

    public function canPossess(): bool
    {
        return true;
    }
}

class TargetUserStub extends Authenticatable
{
    use ImpersonatesUsers;

    protected $guarded = [];
    protected $table = 'users';
}

class MultiAuthPossessionTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('possession.user_model', TargetUserStub::class);
        $app['config']->set('possession.admin_guard', 'admin');
        $app['config']->set('possession.session_keys.original_user', 'possession_original_user');

        // Configure admin guard with its own provider
        $app['config']->set('auth.guards.admin', [
            'driver'   => 'session',
            'provider' => 'admins',
        ]);

        $app['config']->set('auth.providers.admins', [
            'driver' => 'eloquent',
            'model'  => AdminStub::class,
        ]);

        // Configure user guard/provider
        $app['config']->set('auth.guards.web', [
            'driver'   => 'session',
            'provider' => 'users',
        ]);

        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model'  => TargetUserStub::class,
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

    public function test_admin_can_possess_and_unpossess_with_different_guards()
    {
        $admin = AdminStub::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);
        $user = TargetUserStub::create(['name' => 'User', 'email' => 'user@example.com', 'password' => 'password']);

        Auth::guard('admin')->login($admin);

        Possession::possess($user);

        $this->assertEquals($user->id, Auth::id());
        $this->assertTrue(Session::has('possession_original_user'));

        Possession::unpossess();

        $this->assertEquals($admin->id, Auth::guard('admin')->id());
        $this->assertFalse(Session::has('possession_original_user'));
    }

    public function test_admin_can_possess_user_with_same_id_different_model()
    {
        $admin = AdminStub::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);
        $user = TargetUserStub::create(['name' => 'User', 'email' => 'user@example.com', 'password' => 'password']);

        // Both have ID = 1
        $this->assertEquals(1, $admin->id);
        $this->assertEquals(1, $user->id);

        Auth::guard('admin')->login($admin);

        // Should NOT throw selfPossession since they are different models
        Possession::possess($user);

        $this->assertEquals($user->id, Auth::id());
    }
}
