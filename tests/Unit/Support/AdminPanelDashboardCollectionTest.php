<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Support;

use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Main;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * AdminPanel Dashboard Collection Unit Tests.
 *
 * Tests the enhanced dashboard collection management with Nova v5
 * compatibility including dashboard instances and method chaining.
 */
class AdminPanelDashboardCollectionTest extends TestCase
{
    protected AdminPanel $adminPanel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminPanel = new AdminPanel(
            app(\JTD\AdminPanel\Support\ResourceDiscovery::class),
            app(\JTD\AdminPanel\Support\PageDiscovery::class),
            app(\JTD\AdminPanel\Support\PageRegistry::class),
        );
    }

    public function test_can_register_dashboard_class_names(): void
    {
        $this->adminPanel->registerDashboards([
            Main::class,
        ]);

        $dashboards = $this->adminPanel->getDashboards();

        $this->assertCount(1, $dashboards);
        $this->assertEquals(Main::class, $dashboards->first());
    }

    public function test_can_register_dashboard_instances(): void
    {
        $mainDashboard = Main::make()->showRefreshButton();

        $this->adminPanel->registerDashboards([
            $mainDashboard,
        ]);

        $instances = $this->adminPanel->getDashboardInstances();

        $this->assertCount(1, $instances);
        $this->assertInstanceOf(Main::class, $instances->first());
        $this->assertTrue($instances->first()->shouldShowRefreshButton());
    }

    public function test_can_register_mixed_dashboards(): void
    {
        $mainDashboard = Main::make()->showRefreshButton();

        $this->adminPanel->registerDashboards([
            Main::class,
            $mainDashboard,
        ]);

        $classNames = $this->adminPanel->getDashboards();
        $instances = $this->adminPanel->getDashboardInstances();

        $this->assertCount(1, $classNames);
        $this->assertCount(1, $instances);
        $this->assertEquals(Main::class, $classNames->first());
        $this->assertInstanceOf(Main::class, $instances->first());
    }

    public function test_get_all_dashboard_instances(): void
    {
        $mainDashboard = Main::make()->showRefreshButton();

        $this->adminPanel->registerDashboards([
            Main::class,
            $mainDashboard,
        ]);

        $allInstances = $this->adminPanel->getAllDashboardInstances();

        $this->assertCount(2, $allInstances);
        $this->assertContainsOnlyInstancesOf(Dashboard::class, $allInstances);
    }

    public function test_can_find_dashboard_by_uri_key(): void
    {
        $this->adminPanel->registerDashboards([
            Main::class,
        ]);

        $dashboard = $this->adminPanel->findDashboardByUriKey('main');

        $this->assertInstanceOf(Main::class, $dashboard);
        $this->assertEquals('main', $dashboard->uriKey());
    }

    public function test_find_dashboard_returns_null_for_unknown_uri_key(): void
    {
        $this->adminPanel->registerDashboards([
            Main::class,
        ]);

        $dashboard = $this->adminPanel->findDashboardByUriKey('unknown');

        $this->assertNull($dashboard);
    }

    public function test_get_navigation_dashboards_filters_by_authorization(): void
    {
        $request = $this->createMockRequest();

        // Create a dashboard that's not authorized
        $unauthorizedDashboard = new class extends Dashboard
        {
            public function cards(): array
            {
                return [];
            }

            public function name(): \Stringable|string
            {
                return 'Unauthorized';
            }

            public function authorizedToSee(\Illuminate\Http\Request $request): bool
            {
                return false;
            }
        };

        $this->adminPanel->registerDashboards([
            Main::class,
            $unauthorizedDashboard,
        ]);

        $navigationDashboards = $this->adminPanel->getNavigationDashboards($request);

        $this->assertCount(1, $navigationDashboards);
        $this->assertInstanceOf(Main::class, $navigationDashboards->first());
    }

    public function test_static_dashboards_method(): void
    {
        // Create a fresh AdminPanel instance to avoid accumulation
        $adminPanel = new AdminPanel(
            app(\JTD\AdminPanel\Support\ResourceDiscovery::class),
            app(\JTD\AdminPanel\Support\PageDiscovery::class),
            app(\JTD\AdminPanel\Support\PageRegistry::class),
        );

        // Bind it to the container temporarily
        $this->app->instance(AdminPanel::class, $adminPanel);

        AdminPanel::dashboards([
            Main::class,
        ]);

        $dashboards = $adminPanel->getDashboards();

        $this->assertCount(1, $dashboards);
        $this->assertEquals(Main::class, $dashboards->first());
    }

    public function test_dashboard_method_with_class_name(): void
    {
        $this->adminPanel->dashboard(Main::class);

        $dashboards = $this->adminPanel->getDashboards();

        $this->assertCount(1, $dashboards);
        $this->assertEquals(Main::class, $dashboards->first());
    }

    public function test_dashboard_method_with_instance(): void
    {
        $mainDashboard = Main::make()->showRefreshButton();

        $this->adminPanel->dashboard($mainDashboard);

        $instances = $this->adminPanel->getDashboardInstances();

        $this->assertCount(1, $instances);
        $this->assertInstanceOf(Main::class, $instances->first());
        $this->assertTrue($instances->first()->shouldShowRefreshButton());
    }

    public function test_nova_v5_method_chaining(): void
    {
        $dashboard = Main::make()
            ->showRefreshButton()
            ->canSee(function ($request) {
                return true;
            });

        $this->adminPanel->registerDashboards([$dashboard]);

        $instances = $this->adminPanel->getDashboardInstances();
        $retrievedDashboard = $instances->first();

        $this->assertTrue($retrievedDashboard->shouldShowRefreshButton());
        $this->assertTrue($retrievedDashboard->authorizedToSee($this->createMockRequest()));
    }

    protected function createMockRequest(): \Illuminate\Http\Request
    {
        return \Illuminate\Http\Request::create('/admin');
    }
}
