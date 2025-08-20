<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\AdminPanelServiceProvider;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Main;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * AdminPanel Service Provider Dashboard Integration Tests.
 *
 * Tests the Nova v5 compatible dashboard registration through
 * the service provider's dashboards() method.
 */
class AdminPanelServiceProviderDashboardTest extends TestCase
{
    public function test_service_provider_registers_dashboards_from_config(): void
    {
        // Set up configuration with dashboards
        config(['admin-panel.dashboard.dashboards' => [Main::class]]);

        // Create a fresh service provider instance
        $serviceProvider = new AdminPanelServiceProvider($this->app);
        $serviceProvider->register();
        $serviceProvider->boot();

        // Verify dashboard was registered
        $adminPanel = app(AdminPanel::class);
        $dashboards = $adminPanel->getDashboards();

        $this->assertCount(1, $dashboards);
        $this->assertEquals(Main::class, $dashboards->first());
    }

    public function test_service_provider_dashboards_method_can_be_overridden(): void
    {
        // Create a custom service provider that overrides dashboards()
        $customServiceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    Main::make()->showRefreshButton(),
                ];
            }
        };

        $customServiceProvider->register();
        $customServiceProvider->boot();

        // Verify dashboard instance was registered
        $adminPanel = app(AdminPanel::class);
        $instances = $adminPanel->getDashboardInstances();

        $this->assertCount(1, $instances);
        $this->assertInstanceOf(Main::class, $instances->first());
        $this->assertTrue($instances->first()->shouldShowRefreshButton());
    }

    public function test_service_provider_registers_both_config_and_method_dashboards(): void
    {
        // Set up configuration with dashboards
        config(['admin-panel.dashboard.dashboards' => [Main::class]]);

        // Create a custom dashboard for the method
        $customDashboard = new class extends Dashboard
        {
            public function cards(): array
            {
                return [];
            }

            public function name(): \Stringable|string
            {
                return 'Custom Dashboard';
            }

            public function uriKey(): string
            {
                return 'custom';
            }
        };

        // Create a custom service provider that overrides dashboards()
        $customServiceProvider = new class($this->app, $customDashboard) extends AdminPanelServiceProvider
        {
            private $customDashboard;

            public function __construct($app, $customDashboard)
            {
                parent::__construct($app);
                $this->customDashboard = $customDashboard;
            }

            protected function dashboards(): array
            {
                return [$this->customDashboard];
            }
        };

        $customServiceProvider->register();
        $customServiceProvider->boot();

        // Verify both dashboards were registered
        $adminPanel = app(AdminPanel::class);
        $classNames = $adminPanel->getDashboards();
        $instances = $adminPanel->getDashboardInstances();

        $this->assertCount(1, $classNames); // From config
        $this->assertCount(1, $instances);  // From method
        $this->assertEquals(Main::class, $classNames->first());
        $this->assertEquals('Custom Dashboard', $instances->first()->name());
    }

    public function test_service_provider_handles_empty_dashboards_gracefully(): void
    {
        // Clear any existing configuration
        config(['admin-panel.dashboard.dashboards' => []]);

        // Create a service provider with empty dashboards
        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        // Verify no dashboards were registered
        $adminPanel = app(AdminPanel::class);
        $classNames = $adminPanel->getDashboards();
        $instances = $adminPanel->getDashboardInstances();

        $this->assertCount(0, $classNames);
        $this->assertCount(0, $instances);
    }

    public function test_service_provider_supports_nova_v5_method_chaining(): void
    {
        // Create a service provider with method chaining
        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    Main::make()
                        ->showRefreshButton()
                        ->canSee(function ($request) {
                            return true;
                        }),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        // Verify dashboard with method chaining was registered
        $adminPanel = app(AdminPanel::class);
        $instances = $adminPanel->getDashboardInstances();

        $this->assertCount(1, $instances);
        $dashboard = $instances->first();
        $this->assertInstanceOf(Main::class, $dashboard);
        $this->assertTrue($dashboard->shouldShowRefreshButton());
        $this->assertTrue($dashboard->authorizedToSee($this->createMockRequest()));
    }

    public function test_service_provider_dashboard_auto_registration_integration(): void
    {
        // Test the complete integration flow
        config(['admin-panel.dashboard.dashboards' => [Main::class]]);

        $serviceProvider = new class($this->app) extends AdminPanelServiceProvider
        {
            protected function dashboards(): array
            {
                return [
                    Main::make()->showRefreshButton(),
                ];
            }
        };

        $serviceProvider->register();
        $serviceProvider->boot();

        // Verify all dashboards are available
        $adminPanel = app(AdminPanel::class);
        $allInstances = $adminPanel->getAllDashboardInstances();

        $this->assertCount(2, $allInstances);

        // Verify we can find dashboards by URI key
        $mainFromConfig = $adminPanel->findDashboardByUriKey('main');
        $this->assertInstanceOf(Main::class, $mainFromConfig);

        // Verify navigation dashboards work
        $navigationDashboards = $adminPanel->getNavigationDashboards($this->createMockRequest());
        $this->assertCount(2, $navigationDashboards);
    }

    protected function createMockRequest(): \Illuminate\Http\Request
    {
        return \Illuminate\Http\Request::create('/admin');
    }
}
