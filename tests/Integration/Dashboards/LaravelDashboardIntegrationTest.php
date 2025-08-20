<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Dashboards;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Main;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Laravel Dashboard Integration Tests.
 *
 * Tests the complete integration of the dashboard system with Laravel,
 * including service provider registration, configuration, and artisan commands.
 */
class LaravelDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        // Clean up any generated files
        $this->cleanupGeneratedFiles();
        parent::tearDown();
    }

    public function test_admin_panel_service_provider_registers_dashboard_command(): void
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('admin-panel:dashboard', $commands);
        $this->assertInstanceOf(
            \JTD\AdminPanel\Console\Commands\MakeDashboardCommand::class,
            $commands['admin-panel:dashboard'],
        );
    }

    public function test_make_dashboard_command_creates_dashboard_file(): void
    {
        $dashboardPath = app_path('Admin/Dashboards/TestIntegrationDashboard.php');

        // Ensure file doesn't exist
        if (File::exists($dashboardPath)) {
            File::delete($dashboardPath);
        }

        Artisan::call('admin-panel:dashboard', ['name' => 'TestIntegrationDashboard']);

        $this->assertTrue(File::exists($dashboardPath));

        $content = File::get($dashboardPath);
        $this->assertStringContainsString('class TestIntegrationDashboard extends Dashboard', $content);
        $this->assertStringContainsString('namespace App\\Admin\\Dashboards;', $content);
        $this->assertStringContainsString('test-integration-dashboard', $content);
    }

    public function test_dashboard_configuration_is_loaded_correctly(): void
    {
        $defaultDashboard = config('admin-panel.dashboard.default');
        $dashboards = config('admin-panel.dashboard.dashboards');
        $defaultCards = config('admin-panel.dashboard.default_cards');

        $this->assertEquals(Main::class, $defaultDashboard);
        $this->assertIsArray($dashboards);
        $this->assertContains(Main::class, $dashboards);
        $this->assertIsArray($defaultCards);
    }

    public function test_main_dashboard_returns_empty_cards(): void
    {
        $dashboard = new Main;
        $cards = $dashboard->cards();

        $this->assertIsArray($cards);
        $this->assertCount(0, $cards);
    }

    public function test_admin_panel_singleton_registration(): void
    {
        $adminPanel1 = app(AdminPanel::class);
        $adminPanel2 = app(AdminPanel::class);

        $this->assertSame($adminPanel1, $adminPanel2);
    }

    public function test_dashboard_registration_persists_across_requests(): void
    {
        // Register dashboards
        AdminPanel::dashboards([
            Main::class,
            \Tests\Integration\Dashboards\PersistentTestDashboard::class,
        ]);

        // Simulate new request by getting fresh instance
        $adminPanel = app(AdminPanel::class);
        $dashboards = $adminPanel->getDashboards();

        $this->assertCount(2, $dashboards);
        $this->assertContains(Main::class, $dashboards->toArray());
        $this->assertContains(\Tests\Integration\Dashboards\PersistentTestDashboard::class, $dashboards->toArray());
    }

    public function test_dashboard_route_integration(): void
    {
        // This would test actual HTTP routes if they were defined
        // For now, we test the controller integration
        $request = Request::create('/admin', 'GET');
        $controller = new \JTD\AdminPanel\Http\Controllers\DashboardController;

        $response = $controller->index($request);

        $this->assertInstanceOf(\Inertia\Response::class, $response);
    }

    public function test_dashboard_authorization_integration_with_laravel_gates(): void
    {
        // Mock a user for testing
        $user = new \Illuminate\Foundation\Auth\User;
        $user->id = 1;

        $request = Request::create('/admin');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $dashboard = new \Tests\Integration\Dashboards\GateAuthorizedDashboard;

        // Test without gate defined (should default to false for null user)
        $request->setUserResolver(function () {
            return null;
        });

        $this->assertFalse($dashboard->authorizedToSee($request));
    }

    public function test_dashboard_menu_integration(): void
    {
        $dashboard = new Main;
        $request = Request::create('/admin');

        $menuItem = $dashboard->menu($request);

        $this->assertInstanceOf(\JTD\AdminPanel\Menu\MenuItem::class, $menuItem);
        $this->assertEquals('Main', $menuItem->label);
        $this->assertEquals('/admin/dashboards/main', $menuItem->url);
    }

    public function test_dashboard_json_serialization_integration(): void
    {
        $dashboard = new Main;
        $dashboard->showRefreshButton();

        $json = json_encode($dashboard);
        $decoded = json_decode($json, true);

        $this->assertEquals('Main', $decoded['name']);
        $this->assertEquals('main', $decoded['uriKey']);
        $this->assertTrue($decoded['showRefreshButton']);
    }

    public function test_dashboard_error_handling_integration(): void
    {
        $dashboard = new Main;

        // Should not throw exception, should handle gracefully
        $cards = $dashboard->cards();

        $this->assertIsArray($cards);
        $this->assertCount(0, $cards);
    }

    public function test_make_dashboard_command_with_force_option(): void
    {
        $dashboardPath = app_path('Admin/Dashboards/ForcedDashboard.php');

        // Create initial file
        Artisan::call('admin-panel:dashboard', ['name' => 'ForcedDashboard']);
        $this->assertTrue(File::exists($dashboardPath));

        // Try to create again with force
        Artisan::call('admin-panel:dashboard', [
            'name' => 'ForcedDashboard',
            '--force' => true,
        ]);

        $this->assertTrue(File::exists($dashboardPath));
    }

    public function test_dashboard_system_performance_with_multiple_dashboards(): void
    {
        $dashboards = [];
        for ($i = 0; $i < 10; $i++) {
            $dashboards[] = \Tests\Integration\Dashboards\PerformanceTestDashboard::class;
        }

        $start = microtime(true);
        AdminPanel::dashboards($dashboards);
        $adminPanel = app(AdminPanel::class);
        $registeredDashboards = $adminPanel->getDashboards();
        $end = microtime(true);

        $this->assertCount(10, $registeredDashboards);
        $this->assertLessThan(0.1, $end - $start); // Should complete in under 100ms
    }

    /**
     * Clean up any files generated during testing.
     */
    protected function cleanupGeneratedFiles(): void
    {
        $files = [
            app_path('Admin/Dashboards/TestIntegrationDashboard.php'),
            app_path('Admin/Dashboards/ForcedDashboard.php'),
        ];

        foreach ($files as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Clean up directory if empty
        $dir = app_path('Admin/Dashboards');
        if (File::isDirectory($dir) && count(File::files($dir)) === 0) {
            File::deleteDirectory($dir);
        }

        $adminDir = app_path('Admin');
        if (File::isDirectory($adminDir) && count(File::allFiles($adminDir)) === 0) {
            File::deleteDirectory($adminDir);
        }
    }
}

/**
 * Test card for configuration testing.
 */
class TestConfigCard extends \JTD\AdminPanel\Cards\Card
{
    public function component(): string
    {
        return 'test-config-card';
    }

    public function data(Request $request): array
    {
        return ['config' => 'test'];
    }
}

/**
 * Persistent test dashboard.
 */
class PersistentTestDashboard extends Dashboard
{
    public function cards(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'Persistent Test';
    }
}

/**
 * Gate authorized dashboard.
 */
class GateAuthorizedDashboard extends Dashboard
{
    public function __construct()
    {
        $this->canSeeWhen('view-dashboard');
    }

    public function cards(): array
    {
        return [];
    }
}

/**
 * Performance test dashboard.
 */
class PerformanceTestDashboard extends Dashboard
{
    public function cards(): array
    {
        return [];
    }
}
