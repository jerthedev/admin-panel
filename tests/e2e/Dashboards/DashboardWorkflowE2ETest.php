<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\e2e\Dashboards;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Main;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Dashboard Workflow E2E Tests.
 *
 * End-to-end tests covering the complete dashboard workflow from
 * creation to rendering, ensuring Nova compatibility and real-world usage.
 */
class DashboardWorkflowE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupGeneratedFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupGeneratedFiles();
        parent::tearDown();
    }

    public function test_complete_dashboard_creation_and_usage_workflow(): void
    {
        // Step 1: Generate a new dashboard using artisan command
        $dashboardName = 'E2ETestDashboard';
        $dashboardPath = app_path("Admin/Dashboards/{$dashboardName}.php");

        Artisan::call('admin-panel:dashboard', ['name' => $dashboardName]);

        $this->assertTrue(File::exists($dashboardPath));

        // Step 2: Verify generated dashboard content
        $content = File::get($dashboardPath);
        $this->assertStringContainsString("class {$dashboardName} extends Dashboard", $content);
        $this->assertStringContainsString('namespace App\\Admin\\Dashboards;', $content);
        $this->assertStringContainsString('e2-e-test-dashboard', $content);

        // Step 3: Load and instantiate the generated dashboard
        require_once $dashboardPath;
        $dashboardClass = "App\\Admin\\Dashboards\\{$dashboardName}";
        $dashboard = new $dashboardClass;

        $this->assertInstanceOf(Dashboard::class, $dashboard);
        $this->assertEquals($dashboardName, $dashboard->name());
        $this->assertEquals('e2-e-test-dashboard', $dashboard->uriKey());

        // Step 4: Register dashboard with AdminPanel
        AdminPanel::dashboards([$dashboardClass]);

        $adminPanel = app(AdminPanel::class);
        $registeredDashboards = $adminPanel->getDashboards();

        $this->assertContains($dashboardClass, $registeredDashboards->toArray());

        // Step 5: Test dashboard authorization
        $request = Request::create('/admin/dashboards/e2-e-test-dashboard');
        $this->assertTrue($dashboard->authorizedToSee($request));

        // Step 6: Test dashboard rendering through HTTP request
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)->get('/admin/dashboards/e2-e-test-dashboard');

        // Test response is successful and contains dashboard data
        $response->assertOk();
        $responseData = $response->getOriginalContent()->getData();
        $this->assertArrayHasKey('page', $responseData);

        $pageData = $responseData['page'];
        $this->assertEquals('Dashboard', $pageData['component']);
        $this->assertArrayHasKey('dashboard', $pageData['props']);
        $this->assertEquals($dashboardName, $pageData['props']['dashboard']['name']);
        $this->assertEquals('e2-e-test-dashboard', $pageData['props']['dashboard']['uriKey']);
    }

    public function test_nova_compatibility_workflow(): void
    {
        // Test that our dashboard system works exactly like Nova's

        // Step 1: Create dashboard with Nova-like API
        $dashboard = new NovaCompatibleDashboard;

        // Step 2: Test Nova-like method signatures
        $this->assertEquals('Nova Compatible', $dashboard->name());
        $this->assertEquals('nova-compatible', $dashboard->uriKey());
        $this->assertIsArray($dashboard->cards());

        // Step 3: Test Nova-like authorization
        $request = Request::create('/admin');
        $this->assertTrue($dashboard->authorizedToSee($request));

        $dashboard->canSee(function () {
            return false;
        });
        $this->assertFalse($dashboard->authorizedToSee($request));

        // Step 4: Test Nova-like fluent interface
        $result = $dashboard->showRefreshButton();
        $this->assertSame($dashboard, $result);
        $this->assertTrue($dashboard->shouldShowRefreshButton());

        // Step 5: Test Nova-like static make method
        $staticDashboard = NovaCompatibleDashboard::make();
        $this->assertInstanceOf(NovaCompatibleDashboard::class, $staticDashboard);

        // Step 6: Test Nova-like menu generation
        $menuItem = $dashboard->menu($request);
        $this->assertInstanceOf(\JTD\AdminPanel\Menu\MenuItem::class, $menuItem);
        $this->assertEquals('Nova Compatible', $menuItem->label);
    }

    public function test_main_dashboard_complete_workflow(): void
    {
        // Step 1: Create Main dashboard instance
        $dashboard = new Main;

        // Step 2: Test card loading (empty by default)
        $cards = $dashboard->cards();
        $this->assertIsArray($cards);
        $this->assertCount(0, $cards);

        // Step 3: Test main dashboard rendering through HTTP
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)->get('/admin');

        // Test response is successful and contains dashboard data
        $response->assertOk();
        $responseData = $response->getOriginalContent()->getData();
        $this->assertArrayHasKey('page', $responseData);

        $pageData = $responseData['page'];
        $this->assertEquals('Dashboard', $pageData['component']);
        $this->assertArrayHasKey('dashboard', $pageData['props']);
        $this->assertEquals('Main', $pageData['props']['dashboard']['name']);
        $this->assertArrayHasKey('cards', $pageData['props']);
        $this->assertCount(0, $pageData['props']['cards']);
    }

    public function test_dashboard_error_handling_workflow(): void
    {
        // Step 1: Create Main dashboard
        $dashboard = new Main;

        // Step 2: Test that dashboard handles gracefully (empty by default)
        $cards = $dashboard->cards();

        // Should be empty array
        $this->assertIsArray($cards);
        $this->assertCount(0, $cards);

        // Step 3: Test error handling through HTTP (should not throw exceptions)
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)->get('/admin');

        // Test response is successful and contains dashboard data
        $response->assertOk();
        $responseData = $response->getOriginalContent()->getData();
        $this->assertArrayHasKey('page', $responseData);

        $pageData = $responseData['page'];
        $this->assertEquals('Dashboard', $pageData['component']);
        $this->assertArrayHasKey('cards', $pageData['props']);
        $this->assertCount(0, $pageData['props']['cards']);
    }

    public function test_dashboard_authorization_workflow(): void
    {
        // Step 1: Create dashboard with custom authorization
        $dashboard = new AuthorizedE2EDashboard;
        $request = Request::create('/admin');

        // Step 2: Test default authorization (should be true)
        $this->assertTrue($dashboard->authorizedToSee($request));

        // Step 3: Set custom authorization callback
        $dashboard->canSee(function (Request $req) {
            return $req->has('authorized');
        });

        // Step 4: Test unauthorized request
        $this->assertFalse($dashboard->authorizedToSee($request));

        // Step 5: Test authorized request
        $authorizedRequest = Request::create('/admin?authorized=1');
        $this->assertTrue($dashboard->authorizedToSee($authorizedRequest));

        // Step 6: Test through controller (should return 403)
        $controller = new \JTD\AdminPanel\Http\Controllers\DashboardController;

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->show($request, $dashboard);
    }

    public function test_multiple_dashboards_registration_workflow(): void
    {
        // Step 1: Create multiple dashboards
        $dashboards = [
            Main::class,
            NovaCompatibleDashboard::class,
            AuthorizedE2EDashboard::class,
        ];

        // Step 2: Register all dashboards
        AdminPanel::dashboards($dashboards);

        // Step 3: Verify registration
        $adminPanel = app(AdminPanel::class);
        $registered = $adminPanel->getDashboards();

        $this->assertGreaterThanOrEqual(3, $registered->count()); // May have more from other tests
        foreach ($dashboards as $dashboardClass) {
            $this->assertContains($dashboardClass, $registered->toArray());
        }

        // Step 4: Test each dashboard individually
        foreach ($dashboards as $dashboardClass) {
            $dashboard = new $dashboardClass;
            $this->assertInstanceOf(Dashboard::class, $dashboard);
            $this->assertIsString($dashboard->name());
            $this->assertIsString($dashboard->uriKey());
            $this->assertIsArray($dashboard->cards());
        }
    }

    public function test_dashboard_json_serialization_workflow(): void
    {
        // Step 1: Create dashboard with various states
        $dashboard = new NovaCompatibleDashboard;
        $dashboard->showRefreshButton();

        // Step 2: Test JSON serialization
        $json = json_encode($dashboard);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals('Nova Compatible', $decoded['name']);
        $this->assertEquals('nova-compatible', $decoded['uriKey']);
        $this->assertTrue($decoded['showRefreshButton']);

        // Step 3: Test toArray method
        $array = $dashboard->toArray();
        $this->assertEquals($decoded, $array);
    }

    /**
     * Clean up generated files.
     */
    protected function cleanupGeneratedFiles(): void
    {
        $files = [
            app_path('Admin/Dashboards/E2ETestDashboard.php'),
        ];

        foreach ($files as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Clean up directories if empty
        $dirs = [
            app_path('Admin/Dashboards'),
            app_path('Admin'),
        ];

        foreach ($dirs as $dir) {
            if (File::isDirectory($dir) && count(File::allFiles($dir)) === 0) {
                File::deleteDirectory($dir);
            }
        }
    }
}

/**
 * Nova-compatible dashboard for testing.
 */
class NovaCompatibleDashboard extends Dashboard
{
    public function cards(): array
    {
        return [
            new E2ETestCard,
        ];
    }

    public function name(): string
    {
        return 'Nova Compatible';
    }

    public function uriKey(): string
    {
        return 'nova-compatible';
    }
}

/**
 * Authorized E2E dashboard.
 */
class AuthorizedE2EDashboard extends Dashboard
{
    public function cards(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'Authorized E2E';
    }
}

/**
 * E2E test card.
 */
class E2ETestCard extends Card
{
    public function component(): string
    {
        return 'e2e-test-card';
    }

    public function data(Request $request): array
    {
        return ['e2e' => 'test'];
    }

    public function title(): string
    {
        return 'E2E Test Card';
    }
}

/**
 * Another E2E test card.
 */
class AnotherE2ETestCard extends Card
{
    public function component(): string
    {
        return 'another-e2e-test-card';
    }

    public function data(Request $request): array
    {
        return [];
    }
}

/**
 * E2E error card.
 */
class E2EErrorCard extends Card
{
    public function __construct()
    {
        throw new \Exception('E2E test error');
    }

    public function component(): string
    {
        return 'error-card';
    }

    public function data(Request $request): array
    {
        return [];
    }
}

/**
 * Invalid E2E card (doesn't extend Card).
 */
class InvalidE2ECard
{
    // Not a Card instance
}
