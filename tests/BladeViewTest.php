<?php

namespace Verseles\Possession\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Verseles\Possession\Facades\Possession;
use Verseles\Possession\Traits\ImpersonatesUsers;

class BladeUserStub extends Authenticatable
{
    use ImpersonatesUsers;

    protected $guarded = [];
    protected $table = 'users';

    public function canPossess(): bool
    {
        return true;
    }
}

class BladeViewTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('possession.user_model', BladeUserStub::class);
        $app['config']->set('possession.admin_guard', 'web');
        $app['config']->set('possession.session_keys.original_user', 'possession_original_user');

        $app['router']->post('/possession/leave', function () {})->name('possession.leave');

        // Define migration
        $app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
    }

    public function test_view_renders_correctly_when_possessed()
    {
        $admin = BladeUserStub::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);
        $user1 = BladeUserStub::create(['name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'password']);

        Auth::login($admin);
        Possession::possess($user1);

        $view = View::make('possession::impersonating')->render();
        $this->assertStringContainsString('Impersonating User 1', $view);
    }
}
