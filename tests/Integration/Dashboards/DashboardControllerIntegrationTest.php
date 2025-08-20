<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Dashboards;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
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
        $request = Request::create('/admin');

        $response = $this->controller->index($request);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('dashboard')
            ->where('dashboard.name', 'Main')
            ->where('dashboard.uriKey', 'main')
            ->where('dashboard.showRefreshButton', false),
        );
    }

    public function test_show_renders_specific_dashboard(): void
    {
        $dashboard = new ControllerIntegrationTestDashboard;
        $request = Request::create('/admin/dashboards/test');

        $response = $this->controller->show($request, $dashboard);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('dashboard')
            ->where('dashboard.name', 'Test Dashboard')
            ->where('dashboard.uriKey', 'test-dashboard')
            ->where('dashboard.showRefreshButton', false),
        );
    }

    public function test_show_uses_main_dashboard_when_null_provided(): void
    {
        $request = Request::create('/admin');

        $response = $this->controller->show($request, null);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('dashboard')
            ->where('dashboard.name', 'Main')
            ->where('dashboard.uriKey', 'main'),
        );
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

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('cards', 1)
            ->where('cards.0.component', 'test-card')
            ->where('cards.0.title', 'Test Card'),
        );
    }

    public function test_main_dashboard_loads_empty_cards_by_default(): void
    {
        $request = Request::create('/admin');

        $response = $this->controller->index($request);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('cards', 0),
        );
    }

    public function test_dashboard_with_refresh_button_enabled(): void
    {
        $dashboard = new ControllerIntegrationTestDashboard;
        $dashboard->showRefreshButton();
        $request = Request::create('/admin/dashboards/test');

        $response = $this->controller->show($request, $dashboard);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('dashboard.showRefreshButton', true),
        );
    }

    public function test_dashboard_controller_includes_all_required_data(): void
    {
        $request = Request::create('/admin');

        $response = $this->controller->index($request);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('dashboard')
            ->has('metrics')
            ->has('cards')
            ->has('recentActivity')
            ->has('quickActions')
            ->has('systemInfo'),
        );
    }

    public function test_dashboard_card_authorization_is_respected(): void
    {
        $dashboard = new DashboardWithUnauthorizedCard;
        $request = Request::create('/admin/dashboards/unauthorized-card');

        $response = $this->controller->show($request, $dashboard);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('cards', 0), // Unauthorized card should be filtered out
        );
    }

    public function test_dashboard_handles_card_loading_errors_gracefully(): void
    {
        $dashboard = new DashboardWithErrorCard;
        $request = Request::create('/admin/dashboards/error-card');

        // Should not throw exception, should handle gracefully
        $response = $this->controller->show($request, $dashboard);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('cards', 0), // Error card should be filtered out
        );
    }

    public function test_backward_compatibility_with_legacy_get_cards_method(): void
    {
        // Test that the legacy getCards method still works
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getCards');
        $method->setAccessible(true);

        $adminPanel = app(\JTD\AdminPanel\Support\AdminPanel::class);
        $request = Request::create('/admin');

        $cards = $method->invoke($this->controller, $adminPanel, $request);

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
