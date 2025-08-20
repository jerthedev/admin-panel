<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Http\Controllers;

use Illuminate\Http\Request;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Main;
use JTD\AdminPanel\Http\Controllers\DashboardController;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Dashboard Controller Routing Unit Tests.
 *
 * Tests the multiple dashboard routing functionality including
 * dashboard resolution by URI key and route generation.
 */
class DashboardControllerRoutingTest extends TestCase
{
    protected DashboardController $controller;

    protected AdminPanel $adminPanel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new DashboardController;
        $this->adminPanel = app(AdminPanel::class);
    }

    public function test_index_shows_main_dashboard(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        // Verify it's an Inertia response with correct component
        $this->assertEquals('Dashboard', $response->viewData('page')['component']);
    }

    public function test_show_by_uri_key_finds_main_dashboard(): void
    {
        $this->adminPanel->registerDashboards([Main::class]);
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/dashboards/main');

        $response->assertOk();
        // Verify it's an Inertia response with correct component
        $this->assertEquals('Dashboard', $response->viewData('page')['component']);
    }

    public function test_show_by_uri_key_finds_custom_dashboard(): void
    {
        $customDashboard = new class extends Dashboard
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

        $this->adminPanel->registerDashboards([$customDashboard]);
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/dashboards/analytics');

        $response->assertOk();
        // Verify it's an Inertia response with correct component
        $this->assertEquals('Dashboard', $response->viewData('page')['component']);
    }

    public function test_show_by_uri_key_throws_404_for_unknown_dashboard(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/dashboards/unknown');

        $response->assertStatus(404);
    }

    public function test_show_by_uri_key_with_method_chaining(): void
    {
        $dashboard = Main::make()->showRefreshButton();

        $this->adminPanel->registerDashboards([$dashboard]);
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/dashboards/main');

        $response->assertOk();
        // Verify it's an Inertia response with correct component
        $this->assertEquals('Dashboard', $response->viewData('page')['component']);
    }

    public function test_show_by_uri_key_respects_authorization(): void
    {
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

            public function uriKey(): string
            {
                return 'unauthorized';
            }

            public function authorizedToSee(Request $request): bool
            {
                return false;
            }
        };

        $this->adminPanel->registerDashboards([$unauthorizedDashboard]);
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/dashboards/unauthorized');

        $response->assertStatus(403);
    }

    public function test_dashboard_menu_generates_correct_urls(): void
    {
        $request = Request::create('/admin');

        // Test Main dashboard (should use root route)
        $mainDashboard = new Main;
        $mainMenu = $mainDashboard->menu($request);

        $this->assertEquals('Main', $mainMenu->label);
        $this->assertEquals(route('admin-panel.dashboard'), $mainMenu->url);

        // Test custom dashboard (should use dashboards.show route)
        $customDashboard = new class extends Dashboard
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

        $customMenu = $customDashboard->menu($request);

        $this->assertEquals('Analytics', $customMenu->label);
        $this->assertEquals(route('admin-panel.dashboards.show', ['uriKey' => 'analytics']), $customMenu->url);
    }

    public function test_show_method_works_with_dashboard_instance(): void
    {
        $dashboard = Main::make()->showRefreshButton();
        $request = Request::create('/admin');

        $response = $this->controller->show($request, $dashboard);

        // Test the response structure using reflection since properties are protected
        $reflection = new \ReflectionClass($response);
        $componentProperty = $reflection->getProperty('component');
        $componentProperty->setAccessible(true);
        $propsProperty = $reflection->getProperty('props');
        $propsProperty->setAccessible(true);

        $this->assertEquals('Dashboard', $componentProperty->getValue($response));
        $props = $propsProperty->getValue($response);
        $this->assertArrayHasKey('dashboard', $props);
        $this->assertEquals('Main', $props['dashboard']['name']);
        $this->assertEquals('main', $props['dashboard']['uriKey']);
        $this->assertTrue($props['dashboard']['showRefreshButton']);
    }

    public function test_show_method_defaults_to_main_dashboard(): void
    {
        $request = Request::create('/admin');

        $response = $this->controller->show($request);

        // Test the response structure using reflection since properties are protected
        $reflection = new \ReflectionClass($response);
        $componentProperty = $reflection->getProperty('component');
        $componentProperty->setAccessible(true);
        $propsProperty = $reflection->getProperty('props');
        $propsProperty->setAccessible(true);

        $this->assertEquals('Dashboard', $componentProperty->getValue($response));
        $props = $propsProperty->getValue($response);
        $this->assertArrayHasKey('dashboard', $props);
        $this->assertEquals('Main', $props['dashboard']['name']);
        $this->assertEquals('main', $props['dashboard']['uriKey']);
    }
}
