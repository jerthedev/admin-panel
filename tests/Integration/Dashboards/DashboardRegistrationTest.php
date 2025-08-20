<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Dashboards;

use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Main;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Dashboard Registration Integration Tests.
 *
 * Tests the integration between dashboard classes and the AdminPanel
 * registration system, ensuring proper dashboard discovery and management.
 */
class DashboardRegistrationTest extends TestCase
{
    protected AdminPanel $adminPanel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminPanel = app(AdminPanel::class);
    }

    public function test_admin_panel_can_register_single_dashboard(): void
    {
        $this->adminPanel->dashboard(RegistrationIntegrationTestDashboard::class);

        $dashboards = $this->adminPanel->getDashboards();

        $this->assertCount(1, $dashboards);
        $this->assertEquals(RegistrationIntegrationTestDashboard::class, $dashboards->first());
    }

    public function test_admin_panel_can_register_multiple_dashboards(): void
    {
        $dashboards = [
            RegistrationIntegrationTestDashboard::class,
            AnotherTestDashboard::class,
        ];

        $this->adminPanel->registerDashboards($dashboards);

        $registeredDashboards = $this->adminPanel->getDashboards();

        $this->assertCount(2, $registeredDashboards);
        $this->assertEquals($dashboards, $registeredDashboards->toArray());
    }

    public function test_admin_panel_static_dashboards_method_works(): void
    {
        $dashboards = [
            RegistrationIntegrationTestDashboard::class,
            AnotherTestDashboard::class,
        ];

        AdminPanel::dashboards($dashboards);

        $adminPanel = app(AdminPanel::class);
        $registeredDashboards = $adminPanel->getDashboards();

        $this->assertCount(2, $registeredDashboards);
        $this->assertEquals($dashboards, $registeredDashboards->toArray());
    }

    public function test_dashboard_registration_is_cumulative(): void
    {
        $this->adminPanel->dashboard(RegistrationIntegrationTestDashboard::class);
        $this->adminPanel->dashboard(AnotherTestDashboard::class);

        $dashboards = $this->adminPanel->getDashboards();

        $this->assertCount(2, $dashboards);
        $this->assertContains(RegistrationIntegrationTestDashboard::class, $dashboards->toArray());
        $this->assertContains(AnotherTestDashboard::class, $dashboards->toArray());
    }

    public function test_register_dashboards_merges_with_existing(): void
    {
        $this->adminPanel->dashboard(RegistrationIntegrationTestDashboard::class);
        $this->adminPanel->registerDashboards([AnotherTestDashboard::class]);

        $dashboards = $this->adminPanel->getDashboards();

        $this->assertCount(2, $dashboards);
        $this->assertContains(RegistrationIntegrationTestDashboard::class, $dashboards->toArray());
        $this->assertContains(AnotherTestDashboard::class, $dashboards->toArray());
    }

    public function test_main_dashboard_can_be_registered(): void
    {
        $this->adminPanel->dashboard(Main::class);

        $dashboards = $this->adminPanel->getDashboards();

        $this->assertCount(1, $dashboards);
        $this->assertEquals(Main::class, $dashboards->first());
    }

    public function test_dashboard_registration_returns_fluent_interface(): void
    {
        $result = $this->adminPanel->dashboard(RegistrationIntegrationTestDashboard::class);

        $this->assertSame($this->adminPanel, $result);
    }

    public function test_register_dashboards_returns_fluent_interface(): void
    {
        $result = $this->adminPanel->registerDashboards([RegistrationIntegrationTestDashboard::class]);

        $this->assertSame($this->adminPanel, $result);
    }

    public function test_dashboard_registration_handles_empty_array(): void
    {
        $this->adminPanel->registerDashboards([]);

        $dashboards = $this->adminPanel->getDashboards();

        $this->assertCount(0, $dashboards);
    }

    public function test_dashboard_registration_preserves_order(): void
    {
        $dashboards = [
            RegistrationIntegrationTestDashboard::class,
            AnotherTestDashboard::class,
            Main::class,
        ];

        $this->adminPanel->registerDashboards($dashboards);

        $registeredDashboards = $this->adminPanel->getDashboards();

        $this->assertEquals($dashboards, $registeredDashboards->toArray());
    }

    public function test_dashboard_collection_is_immutable(): void
    {
        $this->adminPanel->dashboard(RegistrationIntegrationTestDashboard::class);

        $dashboards1 = $this->adminPanel->getDashboards();
        $dashboards2 = $this->adminPanel->getDashboards();

        $this->assertNotSame($dashboards1, $dashboards2);
        $this->assertEquals($dashboards1->toArray(), $dashboards2->toArray());
    }

    public function test_dashboard_registration_integration_with_service_provider(): void
    {
        // Test that the static method properly integrates with the service container
        AdminPanel::dashboards([RegistrationIntegrationTestDashboard::class]);

        // Get a fresh instance from the container
        $freshAdminPanel = app(AdminPanel::class);
        $dashboards = $freshAdminPanel->getDashboards();

        $this->assertCount(1, $dashboards);
        $this->assertEquals(RegistrationIntegrationTestDashboard::class, $dashboards->first());
    }

    public function test_dashboard_registration_works_with_dashboard_instances(): void
    {
        $dashboard = new RegistrationIntegrationTestDashboard;
        $this->adminPanel->dashboard(get_class($dashboard));

        $dashboards = $this->adminPanel->getDashboards();

        $this->assertCount(1, $dashboards);
        $this->assertEquals(RegistrationIntegrationTestDashboard::class, $dashboards->first());
    }
}

/**
 * Test dashboard class for registration integration testing.
 */
class RegistrationIntegrationTestDashboard extends Dashboard
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
 * Another test dashboard class for integration testing.
 */
class AnotherTestDashboard extends Dashboard
{
    public function cards(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'Another Test Dashboard';
    }

    public function uriKey(): string
    {
        return 'another-test-dashboard';
    }
}
