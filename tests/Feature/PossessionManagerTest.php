<?php

namespace Verseles\Possession\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Verseles\Possession\Events\PossessionEnded;
use Verseles\Possession\Events\PossessionStarted;
use Verseles\Possession\Exceptions\ImpersonationException;
use Verseles\Possession\Facades\Possession;
use Verseles\Possession\Tests\Fixtures\User;
use Verseles\Possession\Tests\TestCase;

class PossessionManagerTest extends TestCase
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

    public function test_admin_can_possess_user(): void
    {
        Auth::login($this->admin);

        Possession::possess($this->user);

        $this->assertEquals($this->user->id, Auth::id());
        $this->assertTrue(Possession::isPossessing());
    }

    public function test_admin_can_possess_user_by_id(): void
    {
        Auth::login($this->admin);

        Possession::possess($this->user->id);

        $this->assertEquals($this->user->id, Auth::id());
    }

    public function test_admin_can_possess_user_by_email(): void
    {
        Auth::login($this->admin);

        Possession::possess($this->user->email);

        $this->assertEquals($this->user->id, Auth::id());
    }

    public function test_admin_can_unpossess(): void
    {
        Auth::login($this->admin);
        Possession::possess($this->user);

        Possession::unpossess();

        $this->assertEquals($this->admin->id, Auth::id());
        $this->assertFalse(Possession::isPossessing());
    }

    public function test_non_admin_cannot_possess(): void
    {
        $regularUser = User::create([
            'name' => 'Regular',
            'email' => 'regular@example.com',
            'password' => bcrypt('password'),
        ]);
        $regularUser->setCanPossess(false);

        Auth::login($regularUser);

        $this->expectException(ImpersonationException::class);
        Possession::possess($this->user);
    }

    public function test_cannot_possess_protected_user(): void
    {
        $this->user->setCanBePossessed(false);

        Auth::login($this->admin);

        $this->expectException(ImpersonationException::class);
        Possession::possess($this->user);
    }

    public function test_cannot_possess_self(): void
    {
        Auth::login($this->admin);

        $this->expectException(ImpersonationException::class);
        Possession::possess($this->admin);
    }

    public function test_cannot_unpossess_without_active_possession(): void
    {
        Auth::login($this->admin);

        $this->expectException(ImpersonationException::class);
        Possession::unpossess();
    }

    public function test_get_original_user_returns_admin(): void
    {
        Auth::login($this->admin);
        Possession::possess($this->user);

        $originalUser = Possession::getOriginalUser();

        $this->assertEquals($this->admin->id, $originalUser->id);
    }

    public function test_get_original_user_returns_null_when_not_possessing(): void
    {
        Auth::login($this->admin);

        $this->assertNull(Possession::getOriginalUser());
    }

    public function test_possession_started_event_is_dispatched(): void
    {
        Event::fake([PossessionStarted::class]);

        Auth::login($this->admin);
        Possession::possess($this->user);

        Event::assertDispatched(PossessionStarted::class, function ($event) {
            return $event->admin->id === $this->admin->id
                && $event->target->id === $this->user->id;
        });
    }

    public function test_possession_ended_event_is_dispatched(): void
    {
        Event::fake([PossessionEnded::class]);

        Auth::login($this->admin);
        Possession::possess($this->user);
        Possession::unpossess();

        Event::assertDispatched(PossessionEnded::class, function ($event) {
            return $event->admin->id === $this->admin->id
                && $event->target->id === $this->user->id;
        });
    }

    public function test_is_impersonating_alias_works(): void
    {
        Auth::login($this->admin);
        Possession::possess($this->user);

        $this->assertTrue(Auth::user()->isImpersonating());
        $this->assertTrue(Auth::user()->isPossessed());
    }
}
