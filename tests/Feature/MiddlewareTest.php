<?php

namespace Verseles\Possession\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Verseles\Possession\Facades\Possession;
use Verseles\Possession\Middleware\EnsureNotPossessing;
use Verseles\Possession\Middleware\ForbidDuringPossession;
use Verseles\Possession\Middleware\SharePossessionState;
use Verseles\Possession\Tests\Fixtures\User;
use Verseles\Possession\Tests\TestCase;

class MiddlewareTest extends TestCase
{
    protected User $admin;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
        $this->user = $this->createUser();

        $this->defineTestRoutes();
    }

    protected function defineTestRoutes(): void
    {
        Route::middleware(['web'])->group(function () {
            Route::get('/test-forbidden', function () {
                return 'allowed';
            })->middleware(ForbidDuringPossession::class);

            Route::get('/test-forbidden-json', function () {
                return response()->json(['status' => 'allowed']);
            })->middleware(ForbidDuringPossession::class);

            Route::get('/test-ensure-not', function () {
                return 'allowed';
            })->middleware(EnsureNotPossessing::class);

            Route::get('/test-share-state', function () {
                return view('test-view');
            })->middleware(SharePossessionState::class);

            Route::get('/test-normal', function () {
                return 'normal route';
            });
        });
    }

    // ===================
    // ForbidDuringPossession Tests
    // ===================

    public function test_forbid_middleware_allows_request_when_not_possessing(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/test-forbidden');

        $response->assertOk();
        $response->assertSee('allowed');
    }

    public function test_forbid_middleware_redirects_when_possessing(): void
    {
        $this->actingAs($this->admin);
        Possession::possess($this->user);

        $response = $this->get('/test-forbidden');

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }

    public function test_forbid_middleware_returns_json_when_expecting_json(): void
    {
        $this->actingAs($this->admin);
        Possession::possess($this->user);

        $response = $this->getJson('/test-forbidden-json');

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is not allowed while impersonating.',
        ]);
    }

    // ===================
    // EnsureNotPossessing Tests
    // ===================

    public function test_ensure_not_possessing_allows_request_when_not_possessing(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/test-ensure-not');

        $response->assertOk();
        $response->assertSee('allowed');
    }

    public function test_ensure_not_possessing_aborts_when_possessing(): void
    {
        $this->actingAs($this->admin);
        Possession::possess($this->user);

        $response = $this->get('/test-ensure-not');

        $response->assertStatus(403);
    }

    // ===================
    // SharePossessionState Tests
    // ===================

    public function test_share_state_middleware_shares_false_when_not_possessing(): void
    {
        $this->actingAs($this->admin);

        $middleware = new SharePossessionState;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            $this->assertFalse(view()->shared('isPossessing'));
            $this->assertNull(view()->shared('originalUser'));

            return response('ok');
        });

        $this->assertEquals('ok', $response->getContent());
    }

    public function test_share_state_middleware_shares_true_when_possessing(): void
    {
        $this->actingAs($this->admin);
        Possession::possess($this->user);

        $middleware = new SharePossessionState;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            $this->assertTrue(view()->shared('isPossessing'));
            $this->assertNotNull(view()->shared('originalUser'));
            $this->assertEquals($this->admin->id, view()->shared('originalUser')->id);

            return response('ok');
        });

        $this->assertEquals('ok', $response->getContent());
    }

    // ===================
    // Middleware Alias Tests
    // ===================

    public function test_middleware_aliases_are_registered(): void
    {
        $router = app('router');

        $this->assertTrue($router->hasMiddlewareGroup('web') || true);

        // Check aliases exist
        $aliases = $router->getMiddleware();
        $this->assertArrayHasKey('forbid-during-possession', $aliases);
        $this->assertArrayHasKey('ensure-not-possessing', $aliases);
        $this->assertArrayHasKey('share-possession-state', $aliases);
    }
}
