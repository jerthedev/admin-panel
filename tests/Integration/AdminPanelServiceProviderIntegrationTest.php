<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Http\Request;
use JTD\AdminPanel\AdminPanelServiceProvider;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Main;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * AdminPanel Service Provider Integration Tests.
 *
 * Tests the complete integration of the service provider with dashboard
 * registration, routing, and authorization workflows.
 */
class AdminPanelServiceProviderIntegrationTest extends TestCase
{
    public function test_service_provider_dashboard_registration_integration(): void
    {
        // Set up configuration
        config(['admin-panel.dashboard.dashboards' => [Main::class]]);

        // Create a custom service provider that overrides dashboards()
        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    Main::make()->showRefreshButton(),
                    ServiceProviderIntegrationTestDashboard::make()
                        ->canSee(function ($request) {
                            return true;
                        }),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        // Test that all dashboards are registered
        $adminPanel = app(AdminPanel::class);
        $classNames = $adminPanel->getDashboards();
        $instances = $adminPanel->getDashboardInstances();

        $this->assertCount(1, $classNames); // From config
        $this->assertCount(2, $instances);  // From service provider method

        // Test navigation integration
        $request = Request::create('/admin');
        $navigationDashboards = $adminPanel->getNavigationDashboards($request);

        $this->assertCount(3, $navigationDashboards); // All dashboards are authorized
    }

    public function test_dashboard_routing_integration_with_service_provider(): void
    {
        // Register dashboards through service provider
        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    ServiceProviderIntegrationTestDashboard::make(),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        $admin = $this->createAdminUser();

        // Test routing to custom dashboard
        $response = $this->actingAs($admin)->get('/admin/dashboards/service-provider-integration-test');

        $response->assertOk();
        $this->assertEquals('Dashboard', $response->viewData('page')['component']);
    }

    public function test_dashboard_authorization_integration(): void
    {
        // Create dashboard with authorization
        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    ServiceProviderIntegrationTestDashboard::make()
                        ->canSee(function ($request) {
                            return $request->user()?->email === 'admin@example.com';
                        }),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        // Test with authorized user
        $authorizedUser = $this->createAdminUser(['email' => 'admin@example.com']);
        $response = $this->actingAs($authorizedUser)->get('/admin/dashboards/service-provider-integration-test');
        $response->assertOk();

        // Test with unauthorized user
        $unauthorizedUser = $this->createAdminUser(['email' => 'user@example.com']);
        $response = $this->actingAs($unauthorizedUser)->get('/admin/dashboards/service-provider-integration-test');
        $response->assertStatus(403);
    }

    public function test_dashboard_navigation_menu_integration(): void
    {
        // Register multiple dashboards
        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    Main::make(),
                    ServiceProviderIntegrationTestDashboard::make(),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        $admin = $this->createAdminUser();

        // Test that dashboard data is included in Inertia response
        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $pageData = $response->viewData('page');

        $this->assertArrayHasKey('props', $pageData);
        $this->assertArrayHasKey('dashboards', $pageData['props']);

        $dashboards = $pageData['props']['dashboards'];
        $this->assertGreaterThanOrEqual(2, count($dashboards)); // May have more from other tests

        // Verify dashboard data structure
        $mainDashboard = collect($dashboards)->firstWhere('uriKey', 'main');
        $this->assertNotNull($mainDashboard);
        $this->assertEquals('Main', $mainDashboard['name']);
        $this->assertTrue($mainDashboard['visible']);

        $customDashboard = collect($dashboards)->firstWhere('uriKey', 'service-provider-integration-test');
        $this->assertNotNull($customDashboard);
        $this->assertEquals('Service Provider Integration Test', $customDashboard['name']);
        $this->assertTrue($customDashboard['visible']);
    }

    public function test_dashboard_configuration_integration(): void
    {
        // Test configuration-driven behavior
        config([
            'admin-panel.dashboard.dashboard_navigation.show_in_navigation' => true,
            'admin-panel.dashboard.dashboard_navigation.group_multiple_dashboards' => true,
            'admin-panel.dashboard.dashboard_navigation.section_icon' => 'custom-icon',
            'admin-panel.dashboard.dashboard_authorization.enable_caching' => true,
            'admin-panel.dashboard.dashboard_authorization.cache_ttl' => 600,
        ]);

        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    Main::make(),
                    ServiceProviderIntegrationTestDashboard::make()->cacheAuth(),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        $adminPanel = app(AdminPanel::class);
        $request = Request::create('/admin');

        // Test navigation section creation
        $section = $adminPanel->createDashboardNavigationSection($request);
        $this->assertNotNull($section);
        $this->assertEquals('custom-icon', $section->icon);

        // Test authorization caching configuration
        $dashboards = $adminPanel->getDashboardInstances();
        $cachedDashboard = $dashboards->last();

        // Use reflection to verify caching configuration
        $reflection = new \ReflectionClass($cachedDashboard);
        $ttlProperty = $reflection->getProperty('authCacheTtl');
        $ttlProperty->setAccessible(true);

        $this->assertEquals(600, $ttlProperty->getValue($cachedDashboard));
    }

    public function test_complete_dashboard_workflow_integration(): void
    {
        // Test the complete workflow from registration to rendering
        config(['admin-panel.dashboard.dashboards' => [Main::class]]);

        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    ServiceProviderIntegrationTestDashboard::make()
                        ->showRefreshButton()
                        ->canSee(function ($request) {
                            return true;
                        }),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        $admin = $this->createAdminUser();

        // 1. Test dashboard registration
        $adminPanel = app(AdminPanel::class);
        $allDashboards = $adminPanel->getAllDashboardInstances();
        $this->assertCount(2, $allDashboards);

        // 2. Test navigation discovery
        $request = Request::create('/admin');
        $navigationDashboards = $adminPanel->getNavigationDashboards($request);
        $this->assertCount(2, $navigationDashboards);

        // 3. Test menu generation
        $menuItems = $adminPanel->getDashboardMenuItems($request);
        $this->assertCount(2, $menuItems);

        // 4. Test routing
        $response = $this->actingAs($admin)->get('/admin/dashboards/service-provider-integration-test');
        $response->assertOk();

        // 5. Test dashboard data in response
        $pageData = $response->viewData('page');
        $dashboard = $pageData['props']['dashboard'];
        $this->assertEquals('Service Provider Integration Test', $dashboard['name']);
        $this->assertTrue($dashboard['showRefreshButton']);

        // 6. Test main dashboard route
        $response = $this->actingAs($admin)->get('/admin');
        $response->assertOk();
        $pageData = $response->viewData('page');
        $this->assertEquals('Main', $pageData['props']['dashboard']['name']);
    }

    public function test_dashboard_method_chaining_integration(): void
    {
        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    ServiceProviderIntegrationTestDashboard::make()
                        ->showRefreshButton()
                        ->cacheAuth(300)
                        ->canSee(function ($request) {
                            $user = $request->user();

                            return $user && $user->is_admin === true;
                        }),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        $adminPanel = app(AdminPanel::class);
        $dashboard = $adminPanel->getDashboardInstances()->first();

        // Verify method chaining worked
        $this->assertTrue($dashboard->shouldShowRefreshButton());

        // Test authorization
        $adminUser = $this->createAdminUser(['is_admin' => true]);
        $regularUser = $this->createUser(['is_admin' => false]); // Use createUser for non-admin

        $adminRequest = Request::create('/admin');
        $adminRequest->setUserResolver(fn () => $adminUser);

        $userRequest = Request::create('/admin');
        $userRequest->setUserResolver(fn () => $regularUser);

        $this->assertTrue($dashboard->authorizedToSee($adminRequest));

        // Debug the user properties
        $this->assertFalse($regularUser->is_admin, 'Regular user should not be admin');

        // Debug what the authorization callback receives
        $userFromRequest = $userRequest->user();
        $this->assertNotNull($userFromRequest, 'User should be available in request');
        $this->assertFalse($userFromRequest->is_admin, 'User from request should not be admin');

        // Clear any potential auth cache
        $dashboard->clearAuthCache();

        $authResult = $dashboard->authorizedToSee($userRequest);
        $this->assertFalse($authResult, 'Dashboard should not authorize non-admin user');
    }
}

/**
 * Test dashboard for service provider integration testing.
 */
class ServiceProviderIntegrationTestDashboard extends Dashboard
{
    public function cards(): array
    {
        return [];
    }

    public function name(): \Stringable|string
    {
        return 'Service Provider Integration Test';
    }

    public function uriKey(): string
    {
        return 'service-provider-integration-test';
    }
}
