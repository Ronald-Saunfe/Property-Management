<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CheckRoleTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckRole();
    }

    /**
     * Test middleware allows access when user has the required role.
     *
     * @return void
     */
    public function test_middleware_allows_access_when_user_has_required_role()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $next = function ($request) {
            return response('OK');
        };

        $response = $this->middleware->handle($request, $next, 'admin');

        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test middleware allows access when user has one of multiple required roles.
     *
     * @return void
     */
    public function test_middleware_allows_access_when_user_has_one_of_multiple_required_roles()
    {
        $user = User::factory()->create(['role' => 'agent']);
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $next = function ($request) {
            return response('OK');
        };

        $response = $this->middleware->handle($request, $next, 'admin', 'agent');

        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test middleware denies access when user doesn't have the required role.
     *
     * @return void
     */
    public function test_middleware_denies_access_when_user_does_not_have_required_role()
    {
        $user = User::factory()->create(['role' => 'landlord']);
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $next = function ($request) {
            return response('OK');
        };

        $response = $this->middleware->handle($request, $next, 'admin');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Unauthorized', $response->getContent());
    }

    /**
     * Test middleware denies access when user doesn't have any of the required roles.
     *
     * @return void
     */
    public function test_middleware_denies_access_when_user_does_not_have_any_required_role()
    {
        $user = User::factory()->create(['role' => 'landlord']);
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $next = function ($request) {
            return response('OK');
        };

        $response = $this->middleware->handle($request, $next, 'admin', 'agent');

        $this->assertEquals(403, $response->getStatusCode());
    }
}