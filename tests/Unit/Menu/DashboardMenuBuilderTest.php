<?php

declare(strict_types=1);

namespace Tests\Unit\Menu;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Menu\DashboardMenuBuilder;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Dashboard Menu Builder Tests
 * 
 * Tests for the dashboard menu builder including menu item generation,
 * section grouping, and Nova v5 compatibility features.
 */
class DashboardMenuBuilderTest extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = Mockery::mock('Illuminate\Contracts\Foundation\Application');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_builds_dashboard_menu_items()
    {
        // Mock request
        $request = Mockery::mock(Request::class);

        // Mock dashboards
        $dashboard1 = $this->createMockDashboard('analytics', 'Analytics Dashboard', 'Analytics');
        $dashboard2 = $this->createMockDashboard('sales', 'Sales Dashboard', 'Business');

        // Mock AdminPanel
        AdminPanel::shouldReceive('getDashboards')
            ->andReturn(collect([$dashboard1, $dashboard2]));

        // Build menu items
        $menuItems = DashboardMenuBuilder::buildDashboardMenuItems($request);

        $this->assertInstanceOf(Collection::class, $menuItems);
        $this->assertCount(2, $menuItems);
        
        $firstItem = $menuItems->first();
        $this->assertInstanceOf(MenuItem::class, $firstItem);
        $this->assertTrue($firstItem->meta['dashboard']);
        $this->assertEquals('analytics', $firstItem->meta['dashboard_uri_key']);
    }

    public function test_builds_dashboard_menu_sections()
    {
        // Mock request
        $request = Mockery::mock(Request::class);

        // Mock dashboards with different categories
        $dashboard1 = $this->createMockDashboard('analytics', 'Analytics Dashboard', 'Analytics');
        $dashboard2 = $this->createMockDashboard('sales', 'Sales Dashboard', 'Business');
        $dashboard3 = $this->createMockDashboard('reports', 'Reports Dashboard', 'Analytics');

        AdminPanel::shouldReceive('getDashboards')
            ->andReturn(collect([$dashboard1, $dashboard2, $dashboard3]));

        // Build menu sections
        $menuSections = DashboardMenuBuilder::buildDashboardMenuSections($request);

        $this->assertInstanceOf(Collection::class, $menuSections);
        $this->assertCount(2, $menuSections); // Analytics and Business categories

        $analyticsSection = $menuSections->first(fn($section) => $section->name === 'Analytics');
        $this->assertNotNull($analyticsSection);
        $this->assertCount(2, $analyticsSection->items); // 2 analytics dashboards
    }

    public function test_builds_main_dashboard_menu_item()
    {
        // Mock request
        $request = Mockery::mock(Request::class);

        // Mock main dashboard
        $mainDashboard = $this->createMockDashboard('main', 'Main Dashboard', 'Overview');

        AdminPanel::shouldReceive('getDashboards')
            ->andReturn(collect([$mainDashboard]));

        // Build main dashboard menu item
        $menuItem = DashboardMenuBuilder::buildMainDashboardMenuItem($request);

        $this->assertInstanceOf(MenuItem::class, $menuItem);
        $this->assertTrue($menuItem->meta['main_dashboard']);
        $this->assertTrue($menuItem->meta['dashboard']);
        $this->assertEquals('main', $menuItem->meta['dashboard_uri_key']);
    }

    public function test_builds_dashboard_menu_section()
    {
        // Mock request
        $request = Mockery::mock(Request::class);

        // Mock dashboard classes
        $dashboardClasses = [
            'App\\Dashboards\\AnalyticsDashboard',
            'App\\Dashboards\\SalesDashboard',
        ];

        // Mock app() calls
        $this->app->shouldReceive('make')
            ->with('App\\Dashboards\\AnalyticsDashboard')
            ->andReturn($this->createMockDashboard('analytics', 'Analytics Dashboard', 'Analytics'));

        $this->app->shouldReceive('make')
            ->with('App\\Dashboards\\SalesDashboard')
            ->andReturn($this->createMockDashboard('sales', 'Sales Dashboard', 'Business'));

        // Build dashboard menu section
        $menuSection = DashboardMenuBuilder::buildDashboardMenuSection(
            'Business Intelligence',
            $dashboardClasses,
            $request,
            ['icon' => 'briefcase', 'collapsible' => true]
        );

        $this->assertInstanceOf(MenuSection::class, $menuSection);
        $this->assertEquals('Business Intelligence', $menuSection->name);
        $this->assertCount(2, $menuSection->items);
        $this->assertTrue($menuSection->meta['dashboard_section']);
    }

    public function test_gets_category_icon()
    {
        $reflection = new \ReflectionClass(DashboardMenuBuilder::class);
        $method = $reflection->getMethod('getCategoryIcon');
        $method->setAccessible(true);

        $this->assertEquals('chart-bar', $method->invoke(null, 'Analytics'));
        $this->assertEquals('briefcase', $method->invoke(null, 'Business'));
        $this->assertEquals('view-grid', $method->invoke(null, 'Unknown Category'));
    }

    public function test_builds_smart_dashboard_menu()
    {
        // Mock request
        $request = Mockery::mock(Request::class);

        // Mock dashboards
        $mainDashboard = $this->createMockDashboard('main', 'Main Dashboard', 'Overview');
        $analyticsDashboard = $this->createMockDashboard('analytics', 'Analytics Dashboard', 'Analytics');

        AdminPanel::shouldReceive('getDashboards')
            ->andReturn(collect([$mainDashboard, $analyticsDashboard]));

        // Mock session for recent dashboards
        $session = Mockery::mock('Illuminate\Session\SessionManager');
        $session->shouldReceive('get')
            ->with('dashboard_recent', [])
            ->andReturn(['analytics']);

        $this->app->shouldReceive('make')
            ->with('session')
            ->andReturn($session);

        // Build smart dashboard menu
        $menu = DashboardMenuBuilder::buildSmartDashboardMenu($request, [
            'show_quick_access' => true,
            'show_favorites' => false,
            'group_by_category' => true,
        ]);

        $this->assertIsArray($menu);
        $this->assertNotEmpty($menu);

        // Should have main dashboard as first item
        $firstItem = $menu[0];
        $this->assertInstanceOf(MenuItem::class, $firstItem);
        $this->assertTrue($firstItem->meta['main_dashboard']);
    }

    public function test_builds_nova_compatible_menu()
    {
        // Mock request
        $request = Mockery::mock(Request::class);

        // Mock dashboards
        $dashboards = collect([
            $this->createMockDashboard('main', 'Main Dashboard', 'Overview'),
            $this->createMockDashboard('analytics', 'Analytics Dashboard', 'Analytics'),
        ]);

        AdminPanel::shouldReceive('getDashboards')
            ->andReturn($dashboards);

        // Mock session
        session()->shouldReceive('get')
            ->with('dashboard_recent', [])
            ->andReturn([]);

        // Build Nova compatible menu
        $menu = DashboardMenuBuilder::buildNovaCompatibleMenu($request);

        $this->assertIsArray($menu);
        $this->assertNotEmpty($menu);
    }

    public function test_filters_unauthorized_dashboards()
    {
        // Mock request
        $request = Mockery::mock(Request::class);

        // Mock authorized dashboard
        $authorizedDashboard = $this->createMockDashboard('analytics', 'Analytics Dashboard', 'Analytics');
        $authorizedDashboard->shouldReceive('authorizedToSee')
            ->with($request)
            ->andReturn(true);

        // Mock unauthorized dashboard
        $unauthorizedDashboard = $this->createMockDashboard('admin', 'Admin Dashboard', 'Admin');
        $unauthorizedDashboard->shouldReceive('authorizedToSee')
            ->with($request)
            ->andReturn(false);

        AdminPanel::shouldReceive('getDashboards')
            ->andReturn(collect([$authorizedDashboard, $unauthorizedDashboard]));

        // Build menu items
        $menuItems = DashboardMenuBuilder::buildDashboardMenuItems($request);

        $this->assertCount(1, $menuItems);
        $this->assertEquals('analytics', $menuItems->first()->meta['dashboard_uri_key']);
    }

    public function test_handles_dashboard_menu_errors()
    {
        // Mock request
        $request = Mockery::mock(Request::class);

        // Mock dashboard that throws exception
        $faultyDashboard = Mockery::mock(Dashboard::class);
        $faultyDashboard->shouldReceive('authorizedToSee')
            ->with($request)
            ->andReturn(true);
        $faultyDashboard->shouldReceive('menu')
            ->with($request)
            ->andThrow(new \Exception('Menu error'));
        $faultyDashboard->shouldReceive('uriKey')
            ->andReturn('faulty');

        AdminPanel::shouldReceive('getDashboards')
            ->andReturn(collect([$faultyDashboard]));

        // Build menu items (should handle error gracefully)
        $menuItems = DashboardMenuBuilder::buildDashboardMenuItems($request);

        $this->assertCount(0, $menuItems); // Faulty dashboard should be filtered out
    }

    protected function createMockDashboard(string $uriKey, string $name, string $category): Dashboard
    {
        $dashboard = Mockery::mock(Dashboard::class);
        
        $dashboard->shouldReceive('uriKey')->andReturn($uriKey);
        $dashboard->shouldReceive('name')->andReturn($name);
        $dashboard->shouldReceive('description')->andReturn("Description for {$name}");
        $dashboard->shouldReceive('category')->andReturn($category);
        $dashboard->shouldReceive('icon')->andReturn('chart-bar');
        $dashboard->shouldReceive('authorizedToSee')->andReturn(true);
        
        // Mock menu method
        $menuItem = Mockery::mock(MenuItem::class);
        $menuItem->shouldReceive('withIcon')->andReturnSelf();
        $menuItem->shouldReceive('meta')->andReturnSelf();
        $menuItem->shouldReceive('canSee')->andReturnSelf();
        $menuItem->meta = [
            'dashboard' => true,
            'dashboard_uri_key' => $uriKey,
            'dashboard_name' => $name,
            'dashboard_description' => "Description for {$name}",
            'dashboard_category' => $category,
        ];
        
        $dashboard->shouldReceive('menu')->andReturn($menuItem);
        
        return $dashboard;
    }
}
