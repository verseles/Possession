<?php

namespace Verseles\Possession\Tests\Unit;

use Illuminate\Support\Facades\Auth;
use Verseles\Possession\Tests\Fixtures\User;
use Verseles\Possession\Tests\TestCase;

class HelpersTest extends TestCase
{
    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->admin->setCanPossess(true);

        $this->user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_possess_helper_function(): void
    {
        Auth::login($this->admin);

        possess($this->user);

        $this->assertEquals($this->user->id, Auth::id());
    }

    public function test_unpossess_helper_function(): void
    {
        Auth::login($this->admin);
        possess($this->user);

        unpossess();

        $this->assertEquals($this->admin->id, Auth::id());
    }

    public function test_is_possessing_helper_function(): void
    {
        Auth::login($this->admin);

        $this->assertFalse(isPossessing());

        possess($this->user);

        $this->assertTrue(isPossessing());
    }
}
