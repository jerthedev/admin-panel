<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Http\Request;
use JTD\AdminPanel\AdminPanelServiceProvider;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Dashboard Authorization Workflow Integration Tests.
 *
 * Tests the complete authorization workflow including policy integration,
 * caching, and complex authorization scenarios.
 */
class DashboardAuthorizationWorkflowTest extends TestCase
{
    public function test_policy_based_authorization_workflow(): void
    {
        // Create a policy
        $policy = new class
        {
            public function viewDashboard($user, $dashboard = null): bool
            {
                return $user->email === 'admin@example.com';
            }
        };

        $this->app->instance(AuthWorkflowTestPolicy::class, $policy);

        // Register dashboard with policy authorization
        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    AuthWorkflowTestDashboard::make()
                        ->canSeeWhenPolicy(AuthWorkflowTestPolicy::class, 'viewDashboard'),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        // Test with authorized user
        $authorizedUser = $this->createAdminUser(['email' => 'admin@example.com']);
        $response = $this->actingAs($authorizedUser)->get('/admin/dashboards/auth-workflow-test');
        $response->assertOk();

        // Test with unauthorized user
        $unauthorizedUser = $this->createAdminUser(['email' => 'user@example.com']);
        $response = $this->actingAs($unauthorizedUser)->get('/admin/dashboards/auth-workflow-test');
        $response->assertStatus(403);

        // Test navigation filtering
        $adminPanel = app(AdminPanel::class);

        $authorizedRequest = Request::create('/admin');
        $authorizedRequest->setUserResolver(fn () => $authorizedUser);
        $authorizedDashboards = $adminPanel->getNavigationDashboards($authorizedRequest);

        // Should have at least our test dashboard
        $testDashboard = $authorizedDashboards->first(function ($dashboard) {
            return $dashboard->uriKey() === 'auth-workflow-test';
        });
        $this->assertNotNull($testDashboard, 'Should find our test dashboard');

        $unauthorizedRequest = Request::create('/admin');
        $unauthorizedRequest->setUserResolver(fn () => $unauthorizedUser);
        $unauthorizedDashboards = $adminPanel->getNavigationDashboards($unauthorizedRequest);

        // Should not have our test dashboard
        $testDashboard = $unauthorizedDashboards->first(function ($dashboard) {
            return $dashboard->uriKey() === 'auth-workflow-test';
        });
        $this->assertNull($testDashboard, 'Should not find our test dashboard for unauthorized user');
    }

    public function test_model_class_authorization_workflow(): void
    {
        // Mock gate for model class authorization
        $this->app['auth']->shouldUse('web');

        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    AuthWorkflowTestDashboard::make()
                        ->canSeeWhen('viewAny', \App\Models\User::class),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        // Create user with permission
        $user = $this->createAdminUser();

        // Mock the gate to return true for this user
        \Gate::define('viewAny', function ($user, $model) {
            return $model === \App\Models\User::class && $user->is_admin;
        });

        $user->is_admin = true;
        $response = $this->actingAs($user)->get('/admin/dashboards/auth-workflow-test');
        $response->assertOk();

        // Test with user without permission
        $user->is_admin = false;
        $response = $this->actingAs($user)->get('/admin/dashboards/auth-workflow-test');
        $response->assertStatus(403);
    }

    public function test_authorization_caching_workflow(): void
    {
        $callCount = 0;

        $serviceProvider = new class($this->app, $callCount) extends AdminPanelServiceProvider
        {
            private $callCount;

            public function __construct($app, &$callCount)
            {
                parent::__construct($app);
                $this->callCount = &$callCount;
            }

            protected function dashboards(): array
            {
                return [
                    AuthWorkflowTestDashboard::make()
                        ->cacheAuth(300)
                        ->canSee(function ($request) {
                            $this->callCount++;

                            return true;
                        }),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        $user = $this->createAdminUser();
        $adminPanel = app(AdminPanel::class);
        $dashboard = $adminPanel->getDashboardInstances()->first();

        $request = Request::create('/admin');
        $request->setUserResolver(fn () => $user);

        // First call should execute the callback
        $this->assertTrue($dashboard->authorizedToSee($request));
        $this->assertEquals(1, $callCount);

        // Second call should use cache
        $this->assertTrue($dashboard->authorizedToSee($request));
        $this->assertEquals(1, $callCount); // Should not increment

        // Clear cache and test again
        $dashboard->clearAuthCache();
        $this->assertTrue($dashboard->authorizedToSee($request));
        $this->assertEquals(2, $callCount); // Should increment
    }

    public function test_complex_authorization_scenarios(): void
    {
        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    // Dashboard with multiple authorization conditions
                    AuthWorkflowTestDashboard::make()
                        ->canSee(function ($request) {
                            $user = $request->user();

                            return $user &&
                                   $user->is_admin &&
                                   in_array($user->email, ['admin@example.com', 'superadmin@example.com']);
                        }),

                    // Dashboard with time-based authorization
                    TimeBasedAuthDashboard::make()
                        ->canSee(function ($request) {
                            $hour = (int) date('H');

                            return $hour >= 9 && $hour <= 17; // Business hours only
                        }),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        $adminPanel = app(AdminPanel::class);

        // Test complex authorization
        $adminUser = $this->createAdminUser([
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        $regularUser = $this->createAdminUser([
            'email' => 'user@example.com',
            'is_admin' => false,
        ]);

        $request = Request::create('/admin');

        // Test with admin user
        $request->setUserResolver(fn () => $adminUser);
        $authorizedDashboards = $adminPanel->getNavigationDashboards($request);

        // Should have at least the complex auth dashboard, time-based depends on current time
        $complexAuthDashboard = $authorizedDashboards->first(function ($dashboard) {
            return $dashboard->uriKey() === 'auth-workflow-test';
        });
        $this->assertNotNull($complexAuthDashboard);

        // Test with regular user
        $request->setUserResolver(fn () => $regularUser);
        $unauthorizedDashboards = $adminPanel->getNavigationDashboards($request);

        $complexAuthDashboard = $unauthorizedDashboards->first(function ($dashboard) {
            return $dashboard->uriKey() === 'auth-workflow-test';
        });
        $this->assertNull($complexAuthDashboard);
    }

    public function test_authorization_with_configuration_integration(): void
    {
        // Test authorization with caching disabled
        config(['admin-panel.dashboard.dashboard_authorization.enable_caching' => false]);

        $callCount = 0;

        $serviceProvider = new class($this->app, $callCount) extends AdminPanelServiceProvider
        {
            private $callCount;

            public function __construct($app, &$callCount)
            {
                parent::__construct($app);
                $this->callCount = &$callCount;
            }

            protected function dashboards(): array
            {
                return [
                    AuthWorkflowTestDashboard::make()
                        ->cacheAuth() // Should be ignored due to config
                        ->canSee(function ($request) {
                            $this->callCount++;

                            return true;
                        }),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        $user = $this->createAdminUser();
        $adminPanel = app(AdminPanel::class);
        $dashboard = $adminPanel->getDashboardInstances()->first();

        $request = Request::create('/admin');
        $request->setUserResolver(fn () => $user);

        // Both calls should execute the callback (no caching)
        $this->assertTrue($dashboard->authorizedToSee($request));
        $this->assertEquals(1, $callCount);

        $this->assertTrue($dashboard->authorizedToSee($request));
        $this->assertEquals(2, $callCount); // Should increment both times
    }

    public function test_authorization_error_handling(): void
    {
        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    AuthWorkflowTestDashboard::make()
                        ->canSee(function ($request) {
                            throw new \Exception('Authorization error');
                        }),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        $user = $this->createAdminUser();

        // Should handle authorization errors gracefully
        $response = $this->actingAs($user)->get('/admin/dashboards/auth-workflow-test');
        $response->assertStatus(500); // Or whatever error handling is implemented
    }
}

/**
 * Test dashboard for authorization workflow testing.
 */
class AuthWorkflowTestDashboard extends Dashboard
{
    public function cards(): array
    {
        return [];
    }

    public function name(): \Stringable|string
    {
        return 'Auth Workflow Test';
    }

    public function uriKey(): string
    {
        return 'auth-workflow-test';
    }
}

/**
 * Time-based authorization test dashboard.
 */
class TimeBasedAuthDashboard extends Dashboard
{
    public function cards(): array
    {
        return [];
    }

    public function name(): \Stringable|string
    {
        return 'Time Based Auth';
    }

    public function uriKey(): string
    {
        return 'time-based-auth';
    }
}

/**
 * Test policy for authorization workflow testing.
 */
class AuthWorkflowTestPolicy
{
    public function viewDashboard($user, $dashboard = null): bool
    {
        return true;
    }
}
