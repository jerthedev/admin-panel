<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Dashboards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Dashboards\Dashboard;
use Mockery;
use Orchestra\Testbench\TestCase;

/**
 * Dashboard Enhanced Authorization Unit Tests.
 *
 * Tests the enhanced authorization features including policy integration,
 * model class authorization, and authorization caching.
 */
class DashboardEnhancedAuthorizationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \JTD\AdminPanel\AdminPanelServiceProvider::class,
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_see_when_with_model_class(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $user = Mockery::mock();
        $user->shouldReceive('can')
            ->with('view', 'App\\Models\\User')
            ->andReturn(true);

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $dashboard->canSeeWhen('view', 'App\\Models\\User');

        $this->assertTrue($dashboard->authorizedToSee($request));
    }

    public function test_can_see_when_with_array_arguments(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $user = Mockery::mock();
        $user->shouldReceive('can')
            ->with('view', 'arg1', 'arg2')
            ->andReturn(true);

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $dashboard->canSeeWhen('view', ['arg1', 'arg2']);

        $this->assertTrue($dashboard->authorizedToSee($request));
    }

    public function test_can_see_when_policy_integration(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $user = Mockery::mock();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Mock policy
        $policy = Mockery::mock(EnhancedAuthTestDashboardPolicy::class);
        $policy->shouldReceive('viewDashboard')
            ->with($user, 'test-arg')
            ->andReturn(true);

        $this->app->instance(EnhancedAuthTestDashboardPolicy::class, $policy);

        $dashboard->canSeeWhenPolicy(EnhancedAuthTestDashboardPolicy::class, 'viewDashboard', 'test-arg');

        $this->assertTrue($dashboard->authorizedToSee($request));
    }

    public function test_can_see_when_policy_with_array_arguments(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $user = Mockery::mock();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Mock policy
        $policy = Mockery::mock(EnhancedAuthTestDashboardPolicy::class);
        $policy->shouldReceive('viewDashboard')
            ->with($user, 'arg1', 'arg2')
            ->andReturn(true);

        $this->app->instance(EnhancedAuthTestDashboardPolicy::class, $policy);

        $dashboard->canSeeWhenPolicy(EnhancedAuthTestDashboardPolicy::class, 'viewDashboard', ['arg1', 'arg2']);

        $this->assertTrue($dashboard->authorizedToSee($request));
    }

    public function test_can_see_when_policy_fails_with_nonexistent_policy(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $user = Mockery::mock();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $dashboard->canSeeWhenPolicy('NonExistentPolicy', 'viewDashboard');

        $this->assertFalse($dashboard->authorizedToSee($request));
    }

    public function test_can_see_when_policy_fails_with_nonexistent_method(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $user = Mockery::mock();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Mock policy without the method
        $policy = Mockery::mock(EnhancedAuthTestDashboardPolicy::class);
        $this->app->instance(EnhancedAuthTestDashboardPolicy::class, $policy);

        $dashboard->canSeeWhenPolicy(EnhancedAuthTestDashboardPolicy::class, 'nonExistentMethod');

        $this->assertFalse($dashboard->authorizedToSee($request));
    }

    public function test_authorization_caching(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $user = Mockery::mock();
        $user->shouldReceive('can')
            ->with('view-dashboard')
            ->once() // Should only be called once due to caching
            ->andReturn(true);

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Enable caching and set authorization
        $dashboard->cacheAuth(300)
            ->canSeeWhen('view-dashboard');

        // First call should hit the authorization callback
        $this->assertTrue($dashboard->authorizedToSee($request));

        // Second call should use cached result
        $this->assertTrue($dashboard->authorizedToSee($request));
    }

    public function test_clear_auth_cache(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $user = Mockery::mock();
        $user->shouldReceive('can')
            ->with('view-dashboard')
            ->times(2) // Should be called twice after cache clear
            ->andReturn(true);

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Enable caching and set authorization
        $dashboard->cacheAuth(300)
            ->canSeeWhen('view-dashboard');

        // First call
        $this->assertTrue($dashboard->authorizedToSee($request));

        // Clear cache
        $dashboard->clearAuthCache();

        // Second call should hit the authorization callback again
        $this->assertTrue($dashboard->authorizedToSee($request));
    }

    public function test_authorization_without_user_returns_false(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $request->setUserResolver(function () {
            return null;
        });

        $dashboard->canSeeWhen('view-dashboard');

        $this->assertFalse($dashboard->authorizedToSee($request));
    }

    public function test_policy_authorization_without_user_returns_false(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $request->setUserResolver(function () {
            return null;
        });

        $dashboard->canSeeWhenPolicy(EnhancedAuthTestDashboardPolicy::class, 'viewDashboard');

        $this->assertFalse($dashboard->authorizedToSee($request));
    }

    public function test_method_chaining_with_authorization(): void
    {
        $dashboard = $this->createConcreteDashboard();
        $request = Request::create('/');

        $user = Mockery::mock();
        $user->shouldReceive('can')
            ->with('view-dashboard')
            ->andReturn(true);

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Test method chaining
        $result = $dashboard
            ->showRefreshButton()
            ->cacheAuth(300)
            ->canSeeWhen('view-dashboard');

        $this->assertSame($dashboard, $result);
        $this->assertTrue($dashboard->shouldShowRefreshButton());
        $this->assertTrue($dashboard->authorizedToSee($request));
    }

    /**
     * Create a concrete dashboard instance for testing.
     */
    protected function createConcreteDashboard(): EnhancedAuthTestDashboard
    {
        return new EnhancedAuthTestDashboard;
    }
}

/**
 * Test dashboard for enhanced authorization testing.
 */
class EnhancedAuthTestDashboard extends Dashboard
{
    public function cards(): array
    {
        return [];
    }

    public function name(): \Stringable|string
    {
        return 'Test Dashboard';
    }

    public function uriKey(): string
    {
        return 'enhanced-auth-test-dashboard';
    }
}

/**
 * Test policy for enhanced authorization policy integration testing.
 */
class EnhancedAuthTestDashboardPolicy
{
    public function viewDashboard($user, ...$arguments): bool
    {
        return true;
    }
}
