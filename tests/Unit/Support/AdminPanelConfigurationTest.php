<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Support;

use Illuminate\Http\Request;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Main;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * AdminPanel Configuration System Unit Tests.
 *
 * Tests the enhanced configuration system for dashboard navigation,
 * authorization caching, and service provider integration.
 */
class AdminPanelConfigurationTest extends TestCase
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

    public function test_dashboard_navigation_can_be_disabled(): void
    {
        config(['admin-panel.dashboard.dashboard_navigation.show_in_navigation' => false]);

        $this->adminPanel->registerDashboards([Main::make()]);

        $request = Request::create('/admin');
        $section = $this->adminPanel->createDashboardNavigationSection($request);
        $mainMenuItem = $this->adminPanel->getMainDashboardMenuItem($request);

        $this->assertNull($section);
        $this->assertNull($mainMenuItem);
    }

    public function test_dashboard_grouping_can_be_disabled(): void
    {
        config(['admin-panel.dashboard.dashboard_navigation.group_multiple_dashboards' => false]);

        $dashboard1 = Main::make();
        $dashboard2 = new class extends Dashboard
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

        $this->adminPanel->registerDashboards([$dashboard1, $dashboard2]);

        $request = Request::create('/admin');
        $section = $this->adminPanel->createDashboardNavigationSection($request);

        $this->assertNull($section);
    }

    public function test_main_dashboard_separate_display_can_be_disabled(): void
    {
        config(['admin-panel.dashboard.dashboard_navigation.show_main_dashboard_separately' => false]);

        $this->adminPanel->registerDashboards([Main::make()]);

        $request = Request::create('/admin');
        $mainMenuItem = $this->adminPanel->getMainDashboardMenuItem($request);

        $this->assertNull($mainMenuItem);
    }

    public function test_custom_navigation_icons(): void
    {
        config([
            'admin-panel.dashboard.dashboard_navigation.section_icon' => 'custom-chart',
            'admin-panel.dashboard.dashboard_navigation.main_dashboard_icon' => 'custom-home',
        ]);

        $dashboard1 = Main::make();
        $dashboard2 = new class extends Dashboard
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

        $this->adminPanel->registerDashboards([$dashboard1, $dashboard2]);

        $request = Request::create('/admin');
        $section = $this->adminPanel->createDashboardNavigationSection($request);
        $mainMenuItem = $this->adminPanel->getMainDashboardMenuItem($request);

        $this->assertEquals('custom-chart', $section->icon);
        $this->assertEquals('custom-home', $mainMenuItem->icon);
    }

    public function test_authorization_caching_configuration(): void
    {
        config([
            'admin-panel.dashboard.dashboard_authorization.enable_caching' => true,
            'admin-panel.dashboard.dashboard_authorization.cache_ttl' => 600,
            'admin-panel.dashboard.dashboard_authorization.cache_key_prefix' => 'custom_auth',
        ]);

        $dashboard = Main::make()->cacheAuth();

        // Use reflection to check private properties
        $reflection = new \ReflectionClass($dashboard);
        $ttlProperty = $reflection->getProperty('authCacheTtl');
        $ttlProperty->setAccessible(true);
        $keyProperty = $reflection->getProperty('authCacheKey');
        $keyProperty->setAccessible(true);

        $this->assertEquals(600, $ttlProperty->getValue($dashboard));
        $this->assertStringStartsWith('custom_auth_main_', $keyProperty->getValue($dashboard));
    }

    public function test_authorization_caching_can_be_disabled(): void
    {
        config(['admin-panel.dashboard.dashboard_authorization.enable_caching' => false]);

        $dashboard = Main::make()->cacheAuth();

        // Use reflection to check private properties
        $reflection = new \ReflectionClass($dashboard);
        $ttlProperty = $reflection->getProperty('authCacheTtl');
        $ttlProperty->setAccessible(true);

        $this->assertNull($ttlProperty->getValue($dashboard));
    }

    public function test_authorization_caching_with_custom_ttl(): void
    {
        config([
            'admin-panel.dashboard.dashboard_authorization.enable_caching' => true,
            'admin-panel.dashboard.dashboard_authorization.cache_ttl' => 300,
        ]);

        $dashboard = Main::make()->cacheAuth(900); // Override with custom TTL

        // Use reflection to check private properties
        $reflection = new \ReflectionClass($dashboard);
        $ttlProperty = $reflection->getProperty('authCacheTtl');
        $ttlProperty->setAccessible(true);

        $this->assertEquals(900, $ttlProperty->getValue($dashboard));
    }

    public function test_dashboard_configuration_integration(): void
    {
        // Test complete configuration integration
        config([
            'admin-panel.dashboard.dashboards' => [Main::class],
            'admin-panel.dashboard.dashboard_navigation.show_in_navigation' => true,
            'admin-panel.dashboard.dashboard_navigation.group_multiple_dashboards' => true,
            'admin-panel.dashboard.dashboard_navigation.section_icon' => 'dashboard-icon',
            'admin-panel.dashboard.dashboard_navigation.show_main_dashboard_separately' => true,
            'admin-panel.dashboard.dashboard_navigation.main_dashboard_icon' => 'main-icon',
            'admin-panel.dashboard.dashboard_authorization.enable_caching' => true,
            'admin-panel.dashboard.dashboard_authorization.cache_ttl' => 450,
            'admin-panel.dashboard.dashboard_authorization.cache_key_prefix' => 'test_auth',
        ]);

        // Register dashboards from config
        $configDashboards = config('admin-panel.dashboard.dashboards', []);
        $this->adminPanel->registerDashboards($configDashboards);

        $request = Request::create('/admin');

        // Test navigation
        $mainMenuItem = $this->adminPanel->getMainDashboardMenuItem($request);
        $this->assertNotNull($mainMenuItem);
        $this->assertEquals('main-icon', $mainMenuItem->icon);

        // Test authorization caching
        $dashboard = Main::make()->cacheAuth();
        $reflection = new \ReflectionClass($dashboard);
        $ttlProperty = $reflection->getProperty('authCacheTtl');
        $ttlProperty->setAccessible(true);
        $keyProperty = $reflection->getProperty('authCacheKey');
        $keyProperty->setAccessible(true);

        $this->assertEquals(450, $ttlProperty->getValue($dashboard));
        $this->assertStringStartsWith('test_auth_main_', $keyProperty->getValue($dashboard));
    }

    public function test_configuration_defaults(): void
    {
        // Clear all dashboard configuration to test defaults
        config(['admin-panel.dashboard' => []]);

        $this->adminPanel->registerDashboards([Main::make()]);

        $request = Request::create('/admin');

        // Test default navigation behavior
        $mainMenuItem = $this->adminPanel->getMainDashboardMenuItem($request);
        $this->assertNotNull($mainMenuItem);
        $this->assertEquals('home', $mainMenuItem->icon); // Default icon

        // Test default authorization caching
        $dashboard = Main::make()->cacheAuth();
        $reflection = new \ReflectionClass($dashboard);
        $ttlProperty = $reflection->getProperty('authCacheTtl');
        $ttlProperty->setAccessible(true);
        $keyProperty = $reflection->getProperty('authCacheKey');
        $keyProperty->setAccessible(true);

        $this->assertEquals(300, $ttlProperty->getValue($dashboard)); // Default TTL
        $this->assertStringStartsWith('dashboard_auth_main_', $keyProperty->getValue($dashboard)); // Default prefix
    }
}
