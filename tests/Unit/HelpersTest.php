<?php

namespace Verseles\Possession\Tests\Unit;

use Illuminate\Support\Facades\Auth;
use Verseles\Possession\Exceptions\ImpersonationException;
use Verseles\Possession\Tests\Fixtures\User;
use Verseles\Possession\Tests\TestCase;

class HelpersTest extends TestCase
{
    protected User $admin;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
        $this->user = $this->createUser();
    }

    public function test_possess_helper_function(): void
    {
        Auth::login($this->admin);

        possess($this->user);

        $this->assertEquals($this->user->id, Auth::id());
    }

    public function test_possess_helper_with_id(): void
    {
        Auth::login($this->admin);

        possess($this->user->id);

        $this->assertEquals($this->user->id, Auth::id());
    }

    public function test_possess_helper_with_email(): void
    {
        Auth::login($this->admin);

        possess($this->user->email);

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

    public function test_possess_helper_throws_exception_for_unauthorized(): void
    {
        $regularUser = $this->createUser([
            'name' => 'Regular',
            'email' => 'regular@example.com',
        ]);

        Auth::login($regularUser);

        $this->expectException(ImpersonationException::class);
        possess($this->user);
    }

    public function test_unpossess_helper_throws_exception_when_not_possessing(): void
    {
        Auth::login($this->admin);

        $this->expectException(ImpersonationException::class);
        unpossess();
    }
}
