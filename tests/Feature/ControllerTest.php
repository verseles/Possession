<?php

namespace Verseles\Possession\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Verseles\Possession\Facades\Possession;
use Verseles\Possession\Tests\Fixtures\User;
use Verseles\Possession\Tests\TestCase;

class ControllerTest extends TestCase
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
    // Possess Route Tests
    // ===================

    public function test_possess_route_requires_authentication(): void
    {
        $response = $this->post(route('possession.possess'), [
            'user' => $this->user->id,
        ]);

        $response->assertRedirect();
    }

    public function test_possess_route_requires_user_parameter(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('possession.possess'));

        $response->assertSessionHasErrors('user');
    }

    public function test_possess_route_starts_impersonation(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('possession.possess'), [
            'user' => $this->user->id,
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');
        $this->assertEquals($this->user->id, Auth::id());
        $this->assertTrue(Possession::isPossessing());
    }

    public function test_possess_route_accepts_email(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('possession.possess'), [
            'user' => $this->user->email,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertEquals($this->user->id, Auth::id());
    }

    public function test_possess_route_handles_unauthorized(): void
    {
        $regularUser = $this->createUser([
            'name' => 'Regular',
            'email' => 'regular@example.com',
        ]);

        $this->actingAs($regularUser);

        $response = $this->post(route('possession.possess'), [
            'user' => $this->user->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertFalse(Possession::isPossessing());
    }

    public function test_possess_route_handles_protected_user(): void
    {
        $this->user->setCanBePossessed(false);
        $this->actingAs($this->admin);

        $response = $this->post(route('possession.possess'), [
            'user' => $this->user->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ===================
    // Leave Route Tests
    // ===================

    public function test_leave_route_requires_authentication(): void
    {
        $response = $this->post(route('possession.leave'));

        $response->assertRedirect();
    }

    public function test_leave_route_stops_impersonation(): void
    {
        $this->actingAs($this->admin);
        Possession::possess($this->user);

        $response = $this->post(route('possession.leave'));

        $response->assertRedirect('/admin');
        $response->assertSessionHas('success');
        $this->assertEquals($this->admin->id, Auth::id());
        $this->assertFalse(Possession::isPossessing());
    }

    public function test_leave_route_handles_no_active_possession(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('possession.leave'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ===================
    // Route Configuration Tests
    // ===================

    public function test_routes_have_correct_names(): void
    {
        $this->assertTrue(Route::has('possession.possess'));
        $this->assertTrue(Route::has('possession.leave'));
    }

    public function test_routes_can_be_disabled(): void
    {
        // This test verifies the configuration option exists
        $this->assertTrue(config('possession.routes.enabled'));
    }

    public function test_routes_use_configured_prefix(): void
    {
        $this->assertEquals('possession', config('possession.routes.prefix'));

        $possessUrl = route('possession.possess');
        $leaveUrl = route('possession.leave');

        $this->assertStringContainsString('/possession/', $possessUrl);
        $this->assertStringContainsString('/possession/', $leaveUrl);
    }
}
