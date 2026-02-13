<?php

namespace Verseles\Possession\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Verseles\Possession\Facades\Possession;
use Verseles\Possession\Traits\ImpersonatesUsers;
use Verseles\Possession\Exceptions\ImpersonationException;

class UserStub extends Authenticatable
{
    use ImpersonatesUsers;

    protected $guarded = [];
    protected $table = 'users';

    public function canPossess(): bool
    {
        return true;
    }
}

class PossessionTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('possession.user_model', UserStub::class);
        $app['config']->set('possession.admin_guard', 'web');
        $app['config']->set('possession.session_keys.original_user', 'possession_original_user');

        // Define migration
        $app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
    }

    public function test_it_prevents_nested_impersonation()
    {
        $admin = UserStub::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);
        $user1 = UserStub::create(['name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'password']);
        $user2 = UserStub::create(['name' => 'User 2', 'email' => 'user2@example.com', 'password' => 'password']);

        Auth::login($admin);

        // Possess user 1
        Possession::possess($user1);

        $this->assertEquals($user1->id, Auth::id());
        $this->assertEquals($admin->id, Session::get('possession_original_user'));

        // Try to possess again while impersonating
        try {
            Possession::possess($user2);
            $this->fail('Should have thrown ImpersonationException');
        } catch (ImpersonationException $e) {
            $this->assertEquals('Cannot impersonate while already impersonating', $e->getMessage());
        }
    }

    public function test_it_throws_exception_when_not_authenticated()
    {
        $user = UserStub::create(['name' => 'User', 'email' => 'user@example.com', 'password' => 'password']);

        // Ensure no user is logged in
        Auth::logout();

        try {
            Possession::possess($user);
            $this->fail('Should have thrown ImpersonationException');
        } catch (ImpersonationException $e) {
            $this->assertEquals('No authenticated user found', $e->getMessage());
        }
    }
}
