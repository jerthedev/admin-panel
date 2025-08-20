<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Support;

use Illuminate\Http\Request;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Main;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * AdminPanel Dashboard Navigation Unit Tests.
 *
 * Tests the dashboard navigation system including automatic discovery,
 * menu item generation, and navigation section creation.
 */
class AdminPanelDashboardNavigationTest extends TestCase
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

    public function test_get_navigation_dashboards_filters_by_authorization(): void
    {
        $authorizedDashboard = Main::make();
        $unauthorizedDashboard = new class extends Dashboard
        {
            public function cards(): array
            {
                return [];
            }

            public function name(): \Stringable|string
            {
                return 'Unauthorized Dashboard';
            }

            public function uriKey(): string
            {
                return 'unauthorized';
            }

            public function authorizedToSee(Request $request): bool
            {
                return false;
            }
        };

        $this->adminPanel->registerDashboards([
            $authorizedDashboard,
            $unauthorizedDashboard,
        ]);

        $request = Request::create('/admin');
        $navigationDashboards = $this->adminPanel->getNavigationDashboards($request);

        $this->assertCount(1, $navigationDashboards);
        $this->assertInstanceOf(Main::class, $navigationDashboards->first());
    }

    public function test_get_dashboard_menu_items(): void
    {
        $dashboard1 = Main::make();
        $dashboard2 = new class extends Dashboard
        {
            public function cards(): array
            {
                return [];
            }

            public function name(): \Stringable|string
            {
                return 'Analytics Dashboard';
            }

            public function uriKey(): string
            {
                return 'analytics';
            }
        };

        $this->adminPanel->registerDashboards([$dashboard1, $dashboard2]);

        $request = Request::create('/admin');
        $menuItems = $this->adminPanel->getDashboardMenuItems($request);

        $this->assertCount(2, $menuItems);
        $this->assertContainsOnlyInstancesOf(MenuItem::class, $menuItems);

        $firstItem = $menuItems->first();
        $this->assertEquals('Main', $firstItem->label);
        $this->assertEquals(route('admin-panel.dashboard'), $firstItem->url);

        $secondItem = $menuItems->last();
        $this->assertEquals('Analytics Dashboard', $secondItem->label);
        $this->assertEquals(route('admin-panel.dashboards.show', ['uriKey' => 'analytics']), $secondItem->url);
    }

    public function test_create_dashboard_navigation_section(): void
    {
        $dashboard1 = Main::make();
        $dashboard2 = new class extends Dashboard
        {
            public function cards(): array
            {
                return [];
            }

            public function name(): \Stringable|string
            {
                return 'Analytics Dashboard';
            }

            public function uriKey(): string
            {
                return 'analytics';
            }
        };

        $this->adminPanel->registerDashboards([$dashboard1, $dashboard2]);

        $request = Request::create('/admin');
        $section = $this->adminPanel->createDashboardNavigationSection($request);

        $this->assertInstanceOf(MenuSection::class, $section);
        $this->assertEquals('Dashboards', $section->name);
        $this->assertEquals('chart-bar', $section->icon);
        $this->assertCount(2, $section->items);
    }

    public function test_create_dashboard_navigation_section_returns_null_for_empty_dashboards(): void
    {
        $request = Request::create('/admin');
        $section = $this->adminPanel->createDashboardNavigationSection($request);

        $this->assertNull($section);
    }

    public function test_create_dashboard_navigation_section_returns_null_for_only_main_dashboard(): void
    {
        $this->adminPanel->registerDashboards([Main::make()]);

        $request = Request::create('/admin');
        $section = $this->adminPanel->createDashboardNavigationSection($request);

        $this->assertNull($section); // Main dashboard is handled separately
    }

    public function test_get_main_dashboard_menu_item(): void
    {
        $this->adminPanel->registerDashboards([Main::make()]);

        $request = Request::create('/admin');
        $menuItem = $this->adminPanel->getMainDashboardMenuItem($request);

        $this->assertInstanceOf(MenuItem::class, $menuItem);
        $this->assertEquals('Main', $menuItem->label);
        $this->assertEquals(route('admin-panel.dashboard'), $menuItem->url);
        $this->assertEquals('home', $menuItem->icon);
    }

    public function test_get_main_dashboard_menu_item_returns_null_when_not_authorized(): void
    {
        $unauthorizedMain = new class extends Dashboard
        {
            public function cards(): array
            {
                return [];
            }

            public function name(): \Stringable|string
            {
                return 'Main';
            }

            public function uriKey(): string
            {
                return 'main';
            }

            public function authorizedToSee(Request $request): bool
            {
                return false;
            }
        };

        $this->adminPanel->registerDashboards([$unauthorizedMain]);

        $request = Request::create('/admin');
        $menuItem = $this->adminPanel->getMainDashboardMenuItem($request);

        $this->assertNull($menuItem);
    }

    public function test_get_main_dashboard_menu_item_returns_null_when_not_found(): void
    {
        $request = Request::create('/admin');
        $menuItem = $this->adminPanel->getMainDashboardMenuItem($request);

        $this->assertNull($menuItem);
    }

    public function test_dashboard_navigation_with_method_chaining(): void
    {
        $dashboard = Main::make()
            ->showRefreshButton()
            ->canSee(function ($request) {
                return true;
            });

        $this->adminPanel->registerDashboards([$dashboard]);

        $request = Request::create('/admin');
        $navigationDashboards = $this->adminPanel->getNavigationDashboards($request);

        $this->assertCount(1, $navigationDashboards);
        $retrievedDashboard = $navigationDashboards->first();
        $this->assertTrue($retrievedDashboard->shouldShowRefreshButton());
        $this->assertTrue($retrievedDashboard->authorizedToSee($request));
    }

    public function test_dashboard_navigation_integration(): void
    {
        // Test complete navigation integration
        $mainDashboard = Main::make();
        $analyticsDashboard = new class extends Dashboard
        {
            public function cards(): array
            {
                return [];
            }

            public function name(): \Stringable|string
            {
                return 'Analytics';
            }

            public function uriKey(): string
            {
                return 'analytics';
            }
        };

        $this->adminPanel->registerDashboards([$mainDashboard, $analyticsDashboard]);

        $request = Request::create('/admin');

        // Test navigation dashboards
        $navigationDashboards = $this->adminPanel->getNavigationDashboards($request);
        $this->assertCount(2, $navigationDashboards);

        // Test menu items
        $menuItems = $this->adminPanel->getDashboardMenuItems($request);
        $this->assertCount(2, $menuItems);

        // Test navigation section
        $section = $this->adminPanel->createDashboardNavigationSection($request);
        $this->assertInstanceOf(MenuSection::class, $section);

        // Test main dashboard menu item
        $mainMenuItem = $this->adminPanel->getMainDashboardMenuItem($request);
        $this->assertInstanceOf(MenuItem::class, $mainMenuItem);
    }
}
