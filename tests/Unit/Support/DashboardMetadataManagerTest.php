<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Support\DashboardMetadataManager;
use JTD\AdminPanel\Tests\TestCase;
use Mockery;

/**
 * Dashboard Metadata Manager Tests
 * 
 * Tests for the dashboard metadata manager including metadata extraction,
 * validation, caching, and search functionality.
 */
class DashboardMetadataManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_gets_metadata_for_dashboard()
    {
        $dashboard = $this->createMockDashboard();
        
        $metadata = DashboardMetadataManager::getMetadata($dashboard, false);

        $this->assertIsArray($metadata);
        $this->assertEquals('Test Dashboard', $metadata['name']);
        $this->assertEquals('A test dashboard', $metadata['description']);
        $this->assertEquals('chart-bar', $metadata['icon']);
        $this->assertEquals('Testing', $metadata['category']);
        $this->assertEquals('test', $metadata['uri_key']);
        $this->assertTrue($metadata['visible']);
        $this->assertTrue($metadata['enabled']);
        $this->assertEquals(100, $metadata['priority']);
    }

    public function test_validates_metadata()
    {
        $validMetadata = [
            'name' => 'Test Dashboard',
            'description' => 'A test dashboard',
            'category' => 'Analytics',
            'priority' => 50,
            'visible' => true,
            'enabled' => true,
            'color' => '#FF0000',
            'tags' => ['test', 'example'],
        ];

        $reflection = new \ReflectionClass(DashboardMetadataManager::class);
        $method = $reflection->getMethod('validateAndNormalizeMetadata');
        $method->setAccessible(true);

        $result = $method->invoke(null, $validMetadata);

        $this->assertIsArray($result);
        $this->assertEquals('Test Dashboard', $result['name']);
        $this->assertEquals('Analytics', $result['category']);
        $this->assertEquals(50, $result['priority']);
        $this->assertEquals('#FF0000', $result['color']);
        $this->assertEquals(['test', 'example'], $result['tags']);
    }

    public function test_validates_metadata_with_invalid_data()
    {
        $invalidMetadata = [
            'name' => '', // Required field empty
            'category' => 'InvalidCategory', // Invalid category
            'priority' => -1, // Invalid priority
            'color' => 'invalid-color', // Invalid color format
        ];

        $reflection = new \ReflectionClass(DashboardMetadataManager::class);
        $method = $reflection->getMethod('validateAndNormalizeMetadata');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $method->invoke(null, $invalidMetadata);
    }

    public function test_normalizes_icon_formats()
    {
        $reflection = new \ReflectionClass(DashboardMetadataManager::class);
        $method = $reflection->getMethod('normalizeIcon');
        $method->setAccessible(true);

        // Test heroicon format
        $result = $method->invoke(null, 'heroicon:chart-bar');
        $this->assertEquals(['type' => 'heroicon', 'name' => 'chart-bar'], $result);

        // Test fontawesome format
        $result = $method->invoke(null, 'fas:chart-bar');
        $this->assertEquals(['type' => 'fontawesome', 'name' => 'fas:chart-bar'], $result);

        // Test image URL
        $result = $method->invoke(null, 'https://example.com/icon.png');
        $this->assertEquals(['type' => 'image', 'url' => 'https://example.com/icon.png'], $result);

        // Test SVG content
        $result = $method->invoke(null, '<svg>...</svg>');
        $this->assertEquals(['type' => 'svg', 'content' => '<svg>...</svg>'], $result);

        // Test emoji
        $result = $method->invoke(null, '📊');
        $this->assertEquals(['type' => 'emoji', 'emoji' => '📊'], $result);

        // Test default (plain string)
        $result = $method->invoke(null, 'chart-bar');
        $this->assertEquals(['type' => 'heroicon', 'name' => 'chart-bar'], $result);
    }

    public function test_gets_multiple_metadata()
    {
        $dashboards = collect([
            $this->createMockDashboard('test1', 'Dashboard 1'),
            $this->createMockDashboard('test2', 'Dashboard 2'),
        ]);

        $result = DashboardMetadataManager::getMultipleMetadata($dashboards, false);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        
        $first = $result->first();
        $this->assertArrayHasKey('dashboard', $first);
        $this->assertArrayHasKey('metadata', $first);
        $this->assertEquals('Dashboard 1', $first['metadata']['name']);
    }

    public function test_orders_dashboards_by_priority()
    {
        $dashboard1 = $this->createMockDashboard('test1', 'Dashboard 1', 'Testing', 200);
        $dashboard2 = $this->createMockDashboard('test2', 'Dashboard 2', 'Testing', 100);
        $dashboard3 = $this->createMockDashboard('test3', 'Dashboard 3', 'Testing', 150);

        $dashboards = collect([$dashboard1, $dashboard2, $dashboard3]);

        $ordered = DashboardMetadataManager::getOrderedDashboards($dashboards, 'priority', 'asc');

        $this->assertEquals('test2', $ordered->first()->uriKey());
        $this->assertEquals('test3', $ordered->get(1)->uriKey());
        $this->assertEquals('test1', $ordered->last()->uriKey());
    }

    public function test_filters_visible_dashboards()
    {
        $request = Mockery::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('user')->andReturn(null);

        $visibleDashboard = $this->createMockDashboard('visible', 'Visible Dashboard');
        $hiddenDashboard = $this->createMockDashboard('hidden', 'Hidden Dashboard');
        
        // Mock metadata for hidden dashboard
        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('put')->andReturn(true);

        $dashboards = collect([$visibleDashboard, $hiddenDashboard]);

        $visible = DashboardMetadataManager::getVisibleDashboards($dashboards, $request);

        $this->assertCount(2, $visible); // Both should be visible by default
    }

    public function test_groups_dashboards_by_category()
    {
        $dashboard1 = $this->createMockDashboard('test1', 'Dashboard 1', 'Analytics');
        $dashboard2 = $this->createMockDashboard('test2', 'Dashboard 2', 'Reports');
        $dashboard3 = $this->createMockDashboard('test3', 'Dashboard 3', 'Analytics');

        $dashboards = collect([$dashboard1, $dashboard2, $dashboard3]);

        $grouped = DashboardMetadataManager::groupDashboardsByCategory($dashboards);

        $this->assertInstanceOf(Collection::class, $grouped);
        $this->assertCount(2, $grouped); // Analytics and Reports
        $this->assertCount(2, $grouped->get('Analytics')); // 2 analytics dashboards
        $this->assertCount(1, $grouped->get('Reports')); // 1 reports dashboard
    }

    public function test_searches_dashboards()
    {
        $dashboard1 = $this->createMockDashboard('test1', 'Analytics Dashboard', 'Analytics');
        $dashboard2 = $this->createMockDashboard('test2', 'Sales Report', 'Reports');
        $dashboard3 = $this->createMockDashboard('test3', 'User Management', 'Users');

        $dashboards = collect([$dashboard1, $dashboard2, $dashboard3]);

        // Search by name
        $results = DashboardMetadataManager::searchDashboards($dashboards, 'analytics');
        $this->assertCount(1, $results);
        $this->assertEquals('test1', $results->first()->uriKey());

        // Search by category
        $results = DashboardMetadataManager::searchDashboards($dashboards, 'reports');
        $this->assertCount(1, $results);
        $this->assertEquals('test2', $results->first()->uriKey());

        // Search with no results
        $results = DashboardMetadataManager::searchDashboards($dashboards, 'nonexistent');
        $this->assertCount(0, $results);

        // Empty search returns all
        $results = DashboardMetadataManager::searchDashboards($dashboards, '');
        $this->assertCount(3, $results);
    }

    public function test_clears_cache()
    {
        Cache::shouldReceive('forget')->once()->with('admin_panel_dashboard_metadata:test');
        
        DashboardMetadataManager::clearCache('test');
    }

    public function test_gets_valid_metadata_fields()
    {
        $fields = DashboardMetadataManager::getValidMetadataFields();
        
        $this->assertIsArray($fields);
        $this->assertContains('name', $fields);
        $this->assertContains('description', $fields);
        $this->assertContains('icon', $fields);
        $this->assertContains('category', $fields);
    }

    public function test_gets_valid_categories()
    {
        $categories = DashboardMetadataManager::getValidCategories();
        
        $this->assertIsArray($categories);
        $this->assertContains('Analytics', $categories);
        $this->assertContains('Reports', $categories);
        $this->assertContains('General', $categories);
    }

    public function test_gets_valid_icon_types()
    {
        $iconTypes = DashboardMetadataManager::getValidIconTypes();
        
        $this->assertIsArray($iconTypes);
        $this->assertContains('heroicon', $iconTypes);
        $this->assertContains('fontawesome', $iconTypes);
        $this->assertContains('svg', $iconTypes);
    }

    protected function createMockDashboard(
        string $uriKey = 'test',
        string $name = 'Test Dashboard',
        string $category = 'Testing',
        int $priority = 100
    ): Dashboard {
        $dashboard = Mockery::mock(Dashboard::class);
        
        $dashboard->shouldReceive('uriKey')->andReturn($uriKey);
        $dashboard->shouldReceive('name')->andReturn($name);
        $dashboard->shouldReceive('description')->andReturn('A test dashboard');
        $dashboard->shouldReceive('icon')->andReturn('chart-bar');
        $dashboard->shouldReceive('category')->andReturn($category);
        $dashboard->shouldReceive('authorizedToSee')->andReturn(true);
        
        // Mock additional methods that might be called
        $dashboard->shouldReceive('getPriority')->andReturn($priority);
        $dashboard->shouldReceive('isVisible')->andReturn(true);
        $dashboard->shouldReceive('isEnabled')->andReturn(true);
        $dashboard->shouldReceive('getTags')->andReturn([]);
        $dashboard->shouldReceive('getPermissions')->andReturn([]);
        $dashboard->shouldReceive('getDependencies')->andReturn([]);
        $dashboard->shouldReceive('getConfiguration')->andReturn([]);
        $dashboard->shouldReceive('getDisplayOptions')->andReturn([]);
        
        return $dashboard;
    }
}
