<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Dashboards;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Http\Controllers\DashboardController;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Dashboard Controller Integration Tests.
 *
 * Tests the integration between the DashboardController and dashboard classes,
 * ensuring proper rendering, authorization, and card loading.
 */
class DashboardControllerIntegrationTest extends TestCase
{
    protected DashboardController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new DashboardController;
    }

    public function test_index_renders_main_dashboard_by_default(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();

        $pageData = $response->getOriginalContent()->getData()['page'];
        $this->assertEquals('Dashboard', $pageData['component']);

        $props = $pageData['props'];
        $this->assertArrayHasKey('dashboard', $props);
        $this->assertEquals('Main', $props['dashboard']['name']);
        $this->assertEquals('main', $props['dashboard']['uriKey']);
        $this->assertFalse($props['dashboard']['showRefreshButton']);
    }

    public function test_show_renders_specific_dashboard(): void
    {
        $dashboard = new ControllerIntegrationTestDashboard;
        $request = Request::create('/admin/dashboards/test');

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
        $this->assertEquals('Test Dashboard', $props['dashboard']['name']);
        $this->assertEquals('test-dashboard', $props['dashboard']['uriKey']);
        $this->assertFalse($props['dashboard']['showRefreshButton']);
    }

    public function test_show_uses_main_dashboard_when_null_provided(): void
    {
        $request = Request::create('/admin');

        $response = $this->controller->show($request, null);

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

    public function test_dashboard_authorization_is_enforced(): void
    {
        $dashboard = new UnauthorizedDashboard;
        $request = Request::create('/admin/dashboards/unauthorized');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized to view this dashboard.');

        $this->controller->show($request, $dashboard);
    }

    public function test_dashboard_cards_are_loaded_from_dashboard_instance(): void
    {
        Config::set('admin-panel.dashboard.default_cards', []);

        $dashboard = new DashboardWithCards;
        $request = Request::create('/admin/dashboards/with-cards');

        $response = $this->controller->show($request, $dashboard);

        // Test the response structure using reflection since properties are protected
        $reflection = new \ReflectionClass($response);
        $componentProperty = $reflection->getProperty('component');
        $componentProperty->setAccessible(true);
        $propsProperty = $reflection->getProperty('props');
        $propsProperty->setAccessible(true);

        $this->assertEquals('Dashboard', $componentProperty->getValue($response));
        $props = $propsProperty->getValue($response);
        $this->assertArrayHasKey('cards', $props);
        $this->assertCount(1, $props['cards']);
        $this->assertEquals('test-card', $props['cards'][0]['component']);
        $this->assertEquals('Test Card', $props['cards'][0]['title']);
    }

    public function test_main_dashboard_loads_empty_cards_by_default(): void
    {
        $request = Request::create('/admin');

        $response = $this->controller->index($request);

        // Test the response structure using reflection since properties are protected
        $reflection = new \ReflectionClass($response);
        $componentProperty = $reflection->getProperty('component');
        $componentProperty->setAccessible(true);
        $propsProperty = $reflection->getProperty('props');
        $propsProperty->setAccessible(true);

        $this->assertEquals('Dashboard', $componentProperty->getValue($response));
        $props = $propsProperty->getValue($response);
        $this->assertArrayHasKey('cards', $props);
        $this->assertCount(0, $props['cards']);
    }

    public function test_dashboard_with_refresh_button_enabled(): void
    {
        $dashboard = new ControllerIntegrationTestDashboard;
        $dashboard->showRefreshButton();
        $request = Request::create('/admin/dashboards/test');

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
        $this->assertTrue($props['dashboard']['showRefreshButton']);
    }

    public function test_dashboard_controller_includes_all_required_data(): void
    {
        $request = Request::create('/admin');

        $response = $this->controller->index($request);

        // Test the response structure using reflection since properties are protected
        $reflection = new \ReflectionClass($response);
        $componentProperty = $reflection->getProperty('component');
        $componentProperty->setAccessible(true);
        $propsProperty = $reflection->getProperty('props');
        $propsProperty->setAccessible(true);

        $this->assertEquals('Dashboard', $componentProperty->getValue($response));
        $props = $propsProperty->getValue($response);
        $this->assertArrayHasKey('dashboard', $props);
        $this->assertArrayHasKey('metrics', $props);
        $this->assertArrayHasKey('cards', $props);
        $this->assertArrayHasKey('recentActivity', $props);
        $this->assertArrayHasKey('quickActions', $props);
        $this->assertArrayHasKey('systemInfo', $props);
    }

    public function test_dashboard_card_authorization_is_respected(): void
    {
        $dashboard = new DashboardWithUnauthorizedCard;
        $request = Request::create('/admin/dashboards/unauthorized-card');

        $response = $this->controller->show($request, $dashboard);

        // Test the response structure using reflection since properties are protected
        $reflection = new \ReflectionClass($response);
        $componentProperty = $reflection->getProperty('component');
        $componentProperty->setAccessible(true);
        $propsProperty = $reflection->getProperty('props');
        $propsProperty->setAccessible(true);

        $this->assertEquals('Dashboard', $componentProperty->getValue($response));
        $props = $propsProperty->getValue($response);
        $this->assertArrayHasKey('cards', $props);
        $this->assertCount(0, $props['cards']); // Unauthorized card should be filtered out
    }

    public function test_dashboard_handles_card_loading_errors_gracefully(): void
    {
        $dashboard = new DashboardWithErrorCard;
        $request = Request::create('/admin/dashboards/error-card');

        // Should not throw exception, should handle gracefully
        $response = $this->controller->show($request, $dashboard);

        // Test the response structure using reflection since properties are protected
        $reflection = new \ReflectionClass($response);
        $componentProperty = $reflection->getProperty('component');
        $componentProperty->setAccessible(true);
        $propsProperty = $reflection->getProperty('props');
        $propsProperty->setAccessible(true);

        $this->assertEquals('Dashboard', $componentProperty->getValue($response));
        $props = $propsProperty->getValue($response);
        $this->assertArrayHasKey('cards', $props);
        $this->assertCount(0, $props['cards']); // Error card should be filtered out
    }

    public function test_backward_compatibility_with_legacy_get_cards_method(): void
    {
        // Test that the getCards method works with dashboard instances
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getCards');
        $method->setAccessible(true);

        $dashboard = new ControllerIntegrationTestDashboard;
        $request = Request::create('/admin');

        $cards = $method->invoke($this->controller, $dashboard, $request);

        $this->assertIsArray($cards);
    }
}

/**
 * Test dashboard class for controller integration testing.
 */
class ControllerIntegrationTestDashboard extends Dashboard
{
    public function cards(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'Test Dashboard';
    }

    public function uriKey(): string
    {
        return 'test-dashboard';
    }
}

/**
 * Unauthorized dashboard for testing authorization.
 */
class UnauthorizedDashboard extends Dashboard
{
    public function cards(): array
    {
        return [];
    }

    public function authorizedToSee(Request $request): bool
    {
        return false;
    }
}

/**
 * Dashboard with cards for testing card loading.
 */
class DashboardWithCards extends Dashboard
{
    public function cards(): array
    {
        return [
            new TestCard,
        ];
    }
}

/**
 * Dashboard with unauthorized card for testing card authorization.
 */
class DashboardWithUnauthorizedCard extends Dashboard
{
    public function cards(): array
    {
        return [
            new UnauthorizedCard,
        ];
    }
}

/**
 * Dashboard with error card for testing error handling.
 */
class DashboardWithErrorCard extends Dashboard
{
    public function cards(): array
    {
        return [
            new ErrorCard,
        ];
    }
}

/**
 * Test card class.
 */
class TestCard extends Card
{
    public function component(): string
    {
        return 'test-card';
    }

    public function data(Request $request): array
    {
        return ['test' => 'data'];
    }

    public function title(): string
    {
        return 'Test Card';
    }
}

/**
 * Unauthorized card for testing.
 */
class UnauthorizedCard extends Card
{
    public function component(): string
    {
        return 'unauthorized-card';
    }

    public function data(Request $request): array
    {
        return [];
    }

    public function authorize(Request $request): bool
    {
        return false;
    }
}

/**
 * Error card for testing error handling.
 */
class ErrorCard extends Card
{
    public function component(): string
    {
        return 'error-card';
    }

    public function data(Request $request): array
    {
        throw new \Exception('Test error');
    }
}
