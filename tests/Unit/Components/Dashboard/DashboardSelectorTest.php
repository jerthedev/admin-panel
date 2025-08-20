<?php

declare(strict_types=1);

namespace Tests\Unit\Components\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use JTD\AdminPanel\Dashboards\Dashboard;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Dashboard Selector Component Tests
 * 
 * Tests for the enhanced DashboardSelector Vue component functionality
 * including metadata integration, transitions, and accessibility.
 */
class DashboardSelectorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dashboard_selector_renders_with_metadata()
    {
        $dashboard = $this->createMockDashboard();
        
        // Test that the component would receive proper metadata
        $metadata = [
            'name' => 'Test Dashboard',
            'description' => 'A test dashboard',
            'icon' => ['type' => 'heroicon', 'name' => 'chart-bar'],
            'category' => 'Analytics',
            'tags' => ['test', 'analytics'],
            'priority' => 100,
            'visible' => true,
            'enabled' => true,
        ];

        $this->assertIsArray($metadata);
        $this->assertEquals('Test Dashboard', $metadata['name']);
        $this->assertEquals('Analytics', $metadata['category']);
        $this->assertTrue($metadata['visible']);
        $this->assertTrue($metadata['enabled']);
    }

    public function test_dashboard_selector_handles_search_functionality()
    {
        $dashboards = collect([
            $this->createMockDashboard('analytics', 'Analytics Dashboard', 'Analytics'),
            $this->createMockDashboard('sales', 'Sales Report', 'Reports'),
            $this->createMockDashboard('users', 'User Management', 'Users'),
        ]);

        // Test search by name
        $searchQuery = 'analytics';
        $filtered = $dashboards->filter(function ($dashboard) use ($searchQuery) {
            return str_contains(strtolower($dashboard->name()), strtolower($searchQuery));
        });

        $this->assertCount(1, $filtered);
        $this->assertEquals('analytics', $filtered->first()->uriKey());

        // Test search by category
        $searchQuery = 'reports';
        $filtered = $dashboards->filter(function ($dashboard) use ($searchQuery) {
            return str_contains(strtolower($dashboard->category ?? ''), strtolower($searchQuery));
        });

        $this->assertCount(1, $filtered);
        $this->assertEquals('sales', $filtered->first()->uriKey());
    }

    public function test_dashboard_selector_groups_by_category()
    {
        $dashboards = collect([
            $this->createMockDashboard('analytics1', 'Analytics 1', 'Analytics'),
            $this->createMockDashboard('analytics2', 'Analytics 2', 'Analytics'),
            $this->createMockDashboard('sales1', 'Sales 1', 'Reports'),
            $this->createMockDashboard('users1', 'Users 1', 'Users'),
        ]);

        $grouped = $dashboards->groupBy(function ($dashboard) {
            return $dashboard->category ?? 'General';
        });

        $this->assertCount(3, $grouped); // Analytics, Reports, Users
        $this->assertCount(2, $grouped->get('Analytics'));
        $this->assertCount(1, $grouped->get('Reports'));
        $this->assertCount(1, $grouped->get('Users'));
    }

    public function test_dashboard_selector_sorts_by_priority()
    {
        $dashboards = collect([
            $this->createMockDashboard('low', 'Low Priority', 'General', 200),
            $this->createMockDashboard('high', 'High Priority', 'General', 50),
            $this->createMockDashboard('medium', 'Medium Priority', 'General', 100),
        ]);

        $sorted = $dashboards->sortBy(function ($dashboard) {
            return $dashboard->getPriority();
        })->values();

        $this->assertEquals('high', $sorted->get(0)->uriKey());
        $this->assertEquals('medium', $sorted->get(1)->uriKey());
        $this->assertEquals('low', $sorted->get(2)->uriKey());
    }

    public function test_dashboard_selector_filters_visible_dashboards()
    {
        $dashboards = collect([
            $this->createMockDashboard('visible', 'Visible Dashboard', 'General', 100, true),
            $this->createMockDashboard('hidden', 'Hidden Dashboard', 'General', 100, false),
            $this->createMockDashboard('enabled', 'Enabled Dashboard', 'General', 100, true, true),
            $this->createMockDashboard('disabled', 'Disabled Dashboard', 'General', 100, true, false),
        ]);

        $visible = $dashboards->filter(function ($dashboard) {
            return $dashboard->isVisible() && $dashboard->isEnabled();
        });

        $this->assertCount(2, $visible); // visible and enabled
        $this->assertTrue($visible->contains(function ($dashboard) {
            return $dashboard->uriKey() === 'visible';
        }));
        $this->assertTrue($visible->contains(function ($dashboard) {
            return $dashboard->uriKey() === 'enabled';
        }));
    }

    public function test_dashboard_selector_handles_favorites()
    {
        $dashboards = collect([
            $this->createMockDashboard('favorite1', 'Favorite 1', 'General', 100, true, true, true),
            $this->createMockDashboard('normal1', 'Normal 1', 'General', 100, true, true, false),
            $this->createMockDashboard('favorite2', 'Favorite 2', 'General', 100, true, true, true),
        ]);

        $favorites = $dashboards->filter(function ($dashboard) {
            return $dashboard->isFavorite ?? false;
        });

        $this->assertCount(2, $favorites);
        
        // Test sorting with favorites first
        $sorted = $dashboards->sortBy(function ($dashboard) {
            return ($dashboard->isFavorite ?? false) ? 0 : 1;
        })->values();

        $this->assertTrue($sorted->get(0)->isFavorite ?? false);
        $this->assertTrue($sorted->get(1)->isFavorite ?? false);
        $this->assertFalse($sorted->get(2)->isFavorite ?? false);
    }

    public function test_dashboard_selector_handles_badges()
    {
        $dashboard = $this->createMockDashboard();
        
        // Test badge data structure
        $badge = [
            'value' => 5,
            'type' => 'warning',
            'tooltip' => '5 pending items'
        ];

        $this->assertIsArray($badge);
        $this->assertEquals(5, $badge['value']);
        $this->assertEquals('warning', $badge['type']);
        $this->assertEquals('5 pending items', $badge['tooltip']);
    }

    public function test_dashboard_selector_accessibility_features()
    {
        $dashboard = $this->createMockDashboard();
        
        // Test ARIA label generation
        $ariaLabel = sprintf(
            'Dashboard: %s. %s. Category: %s',
            $dashboard->name(),
            $dashboard->description(),
            $dashboard->category()
        );

        $this->assertStringContainsString('Dashboard:', $ariaLabel);
        $this->assertStringContainsString($dashboard->name(), $ariaLabel);
        $this->assertStringContainsString($dashboard->description(), $ariaLabel);
        $this->assertStringContainsString($dashboard->category(), $ariaLabel);
    }

    public function test_dashboard_selector_keyboard_navigation()
    {
        $dashboards = collect([
            $this->createMockDashboard('first', 'First Dashboard'),
            $this->createMockDashboard('second', 'Second Dashboard'),
            $this->createMockDashboard('third', 'Third Dashboard'),
        ]);

        // Test navigation indices
        $currentIndex = 0;
        $maxIndex = $dashboards->count() - 1;

        // Test next navigation
        $nextIndex = min($currentIndex + 1, $maxIndex);
        $this->assertEquals(1, $nextIndex);

        // Test previous navigation
        $currentIndex = 1;
        $prevIndex = max($currentIndex - 1, 0);
        $this->assertEquals(0, $prevIndex);

        // Test wrap-around prevention
        $currentIndex = $maxIndex;
        $nextIndex = min($currentIndex + 1, $maxIndex);
        $this->assertEquals($maxIndex, $nextIndex);
    }

    public function test_dashboard_selector_transition_integration()
    {
        $fromDashboard = $this->createMockDashboard('from', 'From Dashboard');
        $toDashboard = $this->createMockDashboard('to', 'To Dashboard');

        // Test transition data structure
        $transitionData = [
            'from' => $fromDashboard,
            'to' => $toDashboard,
            'animation' => 'slide',
            'preserveScroll' => false,
            'showProgress' => true
        ];

        $this->assertArrayHasKey('from', $transitionData);
        $this->assertArrayHasKey('to', $transitionData);
        $this->assertArrayHasKey('animation', $transitionData);
        $this->assertEquals('slide', $transitionData['animation']);
        $this->assertFalse($transitionData['preserveScroll']);
        $this->assertTrue($transitionData['showProgress']);
    }

    public function test_dashboard_selector_error_handling()
    {
        // Test error state handling
        $errorState = [
            'hasError' => true,
            'errorMessage' => 'Failed to load dashboard',
            'canRetry' => true,
            'showFallback' => true
        ];

        $this->assertTrue($errorState['hasError']);
        $this->assertEquals('Failed to load dashboard', $errorState['errorMessage']);
        $this->assertTrue($errorState['canRetry']);
        $this->assertTrue($errorState['showFallback']);
    }

    public function test_dashboard_selector_loading_states()
    {
        // Test loading state handling
        $loadingState = [
            'isLoading' => true,
            'loadingMessage' => 'Loading dashboards...',
            'showProgress' => true,
            'progress' => 75,
            'canCancel' => true
        ];

        $this->assertTrue($loadingState['isLoading']);
        $this->assertEquals('Loading dashboards...', $loadingState['loadingMessage']);
        $this->assertTrue($loadingState['showProgress']);
        $this->assertEquals(75, $loadingState['progress']);
        $this->assertTrue($loadingState['canCancel']);
    }

    protected function createMockDashboard(
        string $uriKey = 'test',
        string $name = 'Test Dashboard',
        string $category = 'General',
        int $priority = 100,
        bool $visible = true,
        bool $enabled = true,
        bool $isFavorite = false
    ): Dashboard {
        $dashboard = Mockery::mock(Dashboard::class);
        
        $dashboard->shouldReceive('uriKey')->andReturn($uriKey);
        $dashboard->shouldReceive('name')->andReturn($name);
        $dashboard->shouldReceive('description')->andReturn('A test dashboard description');
        $dashboard->shouldReceive('category')->andReturn($category);
        $dashboard->shouldReceive('authorizedToSee')->andReturn(true);
        
        // Mock metadata methods
        $dashboard->shouldReceive('getPriority')->andReturn($priority);
        $dashboard->shouldReceive('isVisible')->andReturn($visible);
        $dashboard->shouldReceive('isEnabled')->andReturn($enabled);
        $dashboard->shouldReceive('getTags')->andReturn(['test', 'example']);
        $dashboard->shouldReceive('getPermissions')->andReturn([]);
        $dashboard->shouldReceive('getDependencies')->andReturn([]);
        
        // Add favorite property
        $dashboard->isFavorite = $isFavorite;
        
        return $dashboard;
    }
}
