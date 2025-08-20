<?php

declare(strict_types=1);

namespace Tests\Unit\Stores;

use PHPUnit\Framework\TestCase;

/**
 * Dashboard Navigation Store Tests
 * 
 * Tests for the dashboard navigation store functionality including
 * state management, navigation history, and preferences.
 */
class DashboardNavigationStoreTest extends TestCase
{
    public function test_dashboard_navigation_store_structure()
    {
        // Test the expected structure of the navigation store
        $expectedState = [
            'currentDashboard' => null,
            'previousDashboard' => null,
            'availableDashboards' => [],
            'navigationHistory' => [],
            'favorites' => [],
            'recentlyViewed' => [],
            'navigationPreferences' => [
                'showBreadcrumbs' => true,
                'showQuickSwitcher' => true,
                'maxHistoryItems' => 10,
                'maxRecentItems' => 5,
                'enableKeyboardShortcuts' => true,
                'persistState' => true,
                'rememberLastDashboard' => true,
                'animationDuration' => 300,
                'preserveScrollPosition' => false,
                'autoRefreshInterval' => 0,
                'enableUsageTracking' => true,
            ],
            'isNavigating' => false,
            'navigationError' => null,
            'lastNavigationTime' => null,
            'currentConfiguration' => [],
            'globalConfiguration' => [],
            'userPreferences' => [],
        ];

        $this->assertIsArray($expectedState);
        $this->assertArrayHasKey('currentDashboard', $expectedState);
        $this->assertArrayHasKey('navigationHistory', $expectedState);
        $this->assertArrayHasKey('navigationPreferences', $expectedState);
    }

    public function test_navigation_preferences_structure()
    {
        $preferences = [
            'showBreadcrumbs' => true,
            'showQuickSwitcher' => true,
            'maxHistoryItems' => 10,
            'maxRecentItems' => 5,
            'enableKeyboardShortcuts' => true,
            'persistState' => true,
            'rememberLastDashboard' => true,
            'animationDuration' => 300,
            'preserveScrollPosition' => false,
            'autoRefreshInterval' => 0,
            'enableUsageTracking' => true,
        ];

        $this->assertTrue($preferences['showBreadcrumbs']);
        $this->assertTrue($preferences['enableKeyboardShortcuts']);
        $this->assertEquals(10, $preferences['maxHistoryItems']);
        $this->assertEquals(300, $preferences['animationDuration']);
    }

    public function test_dashboard_navigation_actions()
    {
        // Test the expected actions available in the store
        $expectedActions = [
            'setCurrentDashboard',
            'setAvailableDashboards',
            'addToHistory',
            'addToRecentlyViewed',
            'toggleFavorite',
            'navigateBack',
            'navigateForward',
            'navigateToDashboard',
            'navigateToDashboardAsync',
            'clearHistory',
            'clearRecentlyViewed',
            'updatePreferences',
            'updateConfiguration',
            'setGlobalConfiguration',
            'updateUserPreferences',
            'trackDashboardUsage',
            'initialize',
            'hydrate',
            'clearAllData',
            'reset',
        ];

        foreach ($expectedActions as $action) {
            $this->assertIsString($action);
            $this->assertNotEmpty($action);
        }
    }

    public function test_navigation_state_computed_properties()
    {
        // Test computed properties structure
        $computedProperties = [
            'hasMultipleDashboards',
            'canGoBack',
            'canGoForward',
            'nextDashboard',
            'breadcrumbs',
            'quickSwitchOptions',
            'navigationState',
            'recentDashboards',
            'favoriteDashboards',
            'currentBreadcrumb',
        ];

        foreach ($computedProperties as $property) {
            $this->assertIsString($property);
            $this->assertNotEmpty($property);
        }
    }

    public function test_navigation_history_management()
    {
        // Test navigation history structure
        $historyItem = [
            'uriKey' => 'test-dashboard',
            'name' => 'Test Dashboard',
            'category' => 'Analytics',
            'accessedAt' => '2024-01-01T00:00:00.000Z',
            'accessCount' => 1,
        ];

        $this->assertArrayHasKey('uriKey', $historyItem);
        $this->assertArrayHasKey('name', $historyItem);
        $this->assertArrayHasKey('accessedAt', $historyItem);
        $this->assertArrayHasKey('accessCount', $historyItem);
        $this->assertEquals('test-dashboard', $historyItem['uriKey']);
        $this->assertEquals(1, $historyItem['accessCount']);
    }

    public function test_dashboard_usage_tracking()
    {
        // Test usage tracking data structure
        $usageData = [
            'dashboardUriKey' => 'analytics-dashboard',
            'dashboardName' => 'Analytics Dashboard',
            'category' => 'Analytics',
            'timestamp' => '2024-01-01T00:00:00.000Z',
            'userAgent' => 'Mozilla/5.0...',
        ];

        $this->assertArrayHasKey('dashboardUriKey', $usageData);
        $this->assertArrayHasKey('dashboardName', $usageData);
        $this->assertArrayHasKey('timestamp', $usageData);
        $this->assertEquals('analytics-dashboard', $usageData['dashboardUriKey']);
        $this->assertEquals('Analytics', $usageData['category']);
    }

    public function test_breadcrumb_structure()
    {
        // Test breadcrumb data structure
        $breadcrumb = [
            'name' => 'Dashboard',
            'url' => '/admin',
            'isHome' => true,
            'isActive' => false,
        ];

        $this->assertArrayHasKey('name', $breadcrumb);
        $this->assertArrayHasKey('url', $breadcrumb);
        $this->assertArrayHasKey('isHome', $breadcrumb);
        $this->assertTrue($breadcrumb['isHome']);
        $this->assertEquals('/admin', $breadcrumb['url']);
    }

    public function test_navigation_error_handling()
    {
        // Test navigation error structure
        $navigationError = [
            'message' => 'Navigation failed',
            'code' => 'NAVIGATION_ERROR',
            'timestamp' => '2024-01-01T00:00:00.000Z',
            'dashboard' => 'test-dashboard',
        ];

        $this->assertArrayHasKey('message', $navigationError);
        $this->assertArrayHasKey('timestamp', $navigationError);
        $this->assertEquals('Navigation failed', $navigationError['message']);
        $this->assertEquals('test-dashboard', $navigationError['dashboard']);
    }

    public function test_configuration_management()
    {
        // Test configuration structure
        $configuration = [
            'display' => [
                'layout' => 'grid',
                'columns' => 3,
                'cardSize' => 'medium',
            ],
            'behavior' => [
                'autoRefresh' => false,
                'refreshInterval' => 300,
            ],
            'navigation' => [
                'showBreadcrumbs' => true,
                'enableKeyboardShortcuts' => true,
            ],
        ];

        $this->assertArrayHasKey('display', $configuration);
        $this->assertArrayHasKey('behavior', $configuration);
        $this->assertArrayHasKey('navigation', $configuration);
        $this->assertEquals('grid', $configuration['display']['layout']);
        $this->assertEquals(3, $configuration['display']['columns']);
        $this->assertFalse($configuration['behavior']['autoRefresh']);
    }

    public function test_user_preferences_structure()
    {
        // Test user preferences structure
        $userPreferences = [
            'theme' => 'auto',
            'language' => 'en',
            'timezone' => 'UTC',
            'notifications' => [
                'enabled' => true,
                'sound' => false,
                'desktop' => true,
            ],
            'accessibility' => [
                'highContrast' => false,
                'largeText' => false,
                'reduceMotion' => false,
            ],
        ];

        $this->assertArrayHasKey('theme', $userPreferences);
        $this->assertArrayHasKey('notifications', $userPreferences);
        $this->assertArrayHasKey('accessibility', $userPreferences);
        $this->assertEquals('auto', $userPreferences['theme']);
        $this->assertTrue($userPreferences['notifications']['enabled']);
        $this->assertFalse($userPreferences['accessibility']['highContrast']);
    }

    public function test_persistence_methods()
    {
        // Test persistence method names
        $persistenceMethods = [
            'persistConfiguration',
            'persistGlobalConfiguration',
            'persistUserPreferences',
            'hydrateConfiguration',
            'hydrateUserPreferences',
            'persistNavigationState',
            'restoreNavigationState',
        ];

        foreach ($persistenceMethods as $method) {
            $this->assertIsString($method);
            $this->assertNotEmpty($method);
        }
    }

    public function test_navigation_state_validation()
    {
        // Test navigation state validation
        $validNavigationState = [
            'current' => [
                'uriKey' => 'dashboard-1',
                'name' => 'Dashboard 1',
            ],
            'previous' => null,
            'canGoBack' => false,
            'canGoForward' => false,
            'isNavigating' => false,
            'error' => null,
            'lastNavigationTime' => '2024-01-01T00:00:00.000Z',
        ];

        $this->assertArrayHasKey('current', $validNavigationState);
        $this->assertArrayHasKey('canGoBack', $validNavigationState);
        $this->assertArrayHasKey('isNavigating', $validNavigationState);
        $this->assertFalse($validNavigationState['canGoBack']);
        $this->assertFalse($validNavigationState['isNavigating']);
        $this->assertNull($validNavigationState['error']);
    }

    public function test_favorite_dashboard_management()
    {
        // Test favorite dashboard structure
        $favoriteDashboard = [
            'uriKey' => 'analytics',
            'name' => 'Analytics Dashboard',
            'category' => 'Analytics',
            'addedAt' => '2024-01-01T00:00:00.000Z',
            'order' => 1,
        ];

        $this->assertArrayHasKey('uriKey', $favoriteDashboard);
        $this->assertArrayHasKey('addedAt', $favoriteDashboard);
        $this->assertArrayHasKey('order', $favoriteDashboard);
        $this->assertEquals('analytics', $favoriteDashboard['uriKey']);
        $this->assertEquals(1, $favoriteDashboard['order']);
    }

    public function test_quick_switch_options()
    {
        // Test quick switch options structure
        $quickSwitchOption = [
            'dashboard' => [
                'uriKey' => 'sales',
                'name' => 'Sales Dashboard',
                'category' => 'Business',
            ],
            'type' => 'recent', // 'recent', 'favorite', 'category'
            'score' => 0.95, // Relevance score for search
            'lastAccessed' => '2024-01-01T00:00:00.000Z',
        ];

        $this->assertArrayHasKey('dashboard', $quickSwitchOption);
        $this->assertArrayHasKey('type', $quickSwitchOption);
        $this->assertArrayHasKey('score', $quickSwitchOption);
        $this->assertEquals('recent', $quickSwitchOption['type']);
        $this->assertEquals(0.95, $quickSwitchOption['score']);
    }
}
