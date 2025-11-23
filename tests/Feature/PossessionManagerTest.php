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

        $this->admin = $this->createAdmin();
        $this->user = $this->createUser();
    }

    // ===================
    // Basic Possession Tests
    // ===================

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

    // ===================
    // Authorization Tests
    // ===================

    public function test_non_admin_cannot_possess(): void
    {
        $regularUser = $this->createUser([
            'name' => 'Regular',
            'email' => 'regular@example.com',
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

    public function test_cannot_unpossess_when_original_user_lost_permission(): void
    {
        Auth::login($this->admin);
        Possession::possess($this->user);

        // Simulate admin losing possession rights
        $this->admin->setCanPossess(false);
        $this->admin->save();

        $this->expectException(ImpersonationException::class);
        Possession::unpossess();
    }

    // ===================
    // State Management Tests
    // ===================

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

    public function test_is_possessing_returns_false_initially(): void
    {
        Auth::login($this->admin);

        $this->assertFalse(Possession::isPossessing());
    }

    public function test_is_possessing_returns_true_during_possession(): void
    {
        Auth::login($this->admin);
        Possession::possess($this->user);

        $this->assertTrue(Possession::isPossessing());
    }

    // ===================
    // Event Tests
    // ===================

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

    public function test_events_contain_correct_user_instances(): void
    {
        $dispatchedEvents = [];

        Event::listen(PossessionStarted::class, function ($event) use (&$dispatchedEvents) {
            $dispatchedEvents['started'] = $event;
        });

        Event::listen(PossessionEnded::class, function ($event) use (&$dispatchedEvents) {
            $dispatchedEvents['ended'] = $event;
        });

        Auth::login($this->admin);
        Possession::possess($this->user);
        Possession::unpossess();

        $this->assertInstanceOf(User::class, $dispatchedEvents['started']->admin);
        $this->assertInstanceOf(User::class, $dispatchedEvents['started']->target);
        $this->assertInstanceOf(User::class, $dispatchedEvents['ended']->admin);
        $this->assertInstanceOf(User::class, $dispatchedEvents['ended']->target);
    }

    // ===================
    // Trait Tests
    // ===================

    public function test_is_impersonating_alias_works(): void
    {
        Auth::login($this->admin);
        Possession::possess($this->user);

        $this->assertTrue(Auth::user()->isImpersonating());
        $this->assertTrue(Auth::user()->isPossessed());
    }

    public function test_can_possess_returns_false_by_default(): void
    {
        $user = new User;

        $this->assertFalse($user->canPossess());
    }

    public function test_can_be_possessed_returns_true_by_default(): void
    {
        $user = new User;

        $this->assertTrue($user->canBePossessed());
    }

    // ===================
    // Session Tests
    // ===================

    public function test_session_is_regenerated_on_possess(): void
    {
        Auth::login($this->admin);
        $originalSessionId = session()->getId();

        Possession::possess($this->user);

        $this->assertNotEquals($originalSessionId, session()->getId());
    }

    public function test_session_is_regenerated_on_unpossess(): void
    {
        Auth::login($this->admin);
        Possession::possess($this->user);
        $possessedSessionId = session()->getId();

        Possession::unpossess();

        $this->assertNotEquals($possessedSessionId, session()->getId());
    }

    // ===================
    // Edge Cases
    // ===================

    public function test_can_possess_multiple_users_sequentially(): void
    {
        $secondUser = $this->createUser([
            'name' => 'Second User',
            'email' => 'second@example.com',
        ]);

        Auth::login($this->admin);
        Possession::possess($this->user);
        Possession::unpossess();
        Possession::possess($secondUser);

        $this->assertEquals($secondUser->id, Auth::id());
        $this->assertTrue(Possession::isPossessing());
    }

    public function test_user_not_found_by_id_throws_exception(): void
    {
        Auth::login($this->admin);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Possession::possess(99999);
    }

    public function test_user_not_found_by_email_throws_exception(): void
    {
        Auth::login($this->admin);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Possession::possess('nonexistent@example.com');
    }
}
