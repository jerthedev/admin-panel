<?php

/**
 * Dashboard Metadata & Configuration Examples
 * 
 * This file demonstrates various ways to use dashboard metadata and
 * configuration features including ordering, visibility, and display preferences.
 */

use Illuminate\Http\Request;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Concerns\HasMetadata;
use JTD\AdminPanel\Support\DashboardMetadataManager;
use JTD\AdminPanel\Support\DashboardConfigurationManager;
use JTD\AdminPanel\Support\DashboardOrderingManager;
use JTD\AdminPanel\Support\AdminPanel;

// Example 1: Dashboard with Rich Metadata
class AdvancedAnalyticsDashboard extends Dashboard
{
    use HasMetadata;

    public function name(): string
    {
        return 'Advanced Analytics';
    }

    public function cards(): array
    {
        return [
            // Dashboard cards here
        ];
    }

    // Configure metadata in boot method
    public function boot(): void
    {
        $this->withDescription('Comprehensive analytics dashboard with advanced metrics and insights')
             ->withIcon('chart-line')
             ->withCategory('Analytics')
             ->withTags(['analytics', 'metrics', 'insights', 'advanced'])
             ->withPriority(10) // High priority
             ->withAuthor('Analytics Team')
             ->withVersion('2.1.0')
             ->withColor('#3B82F6')
             ->withBackgroundColor('#EFF6FF')
             ->withTextColor('#1E40AF')
             ->requiresPermission('view-analytics')
             ->requiresPermission('view-advanced-metrics')
             ->dependsOn('analytics-service')
             ->dependsOn('metrics-collector');
    }

    // Custom configuration for this dashboard
    public function getConfiguration(): array
    {
        return [
            'display' => [
                'layout' => 'grid',
                'columns' => 4,
                'card_size' => 'large',
                'show_descriptions' => true,
                'show_icons' => true,
                'compact_mode' => false,
            ],
            'behavior' => [
                'auto_refresh' => true,
                'refresh_interval' => 60, // 1 minute
                'lazy_loading' => false, // Load all data immediately
                'preload_data' => true,
                'cache_data' => true,
                'cache_ttl' => 300,
            ],
            'advanced' => [
                'performance_monitoring' => true,
                'analytics_tracking' => true,
            ],
        ];
    }

    // Custom display options
    public function getDisplayOptions(): array
    {
        return [
            'chart_animations' => true,
            'real_time_updates' => true,
            'export_options' => ['pdf', 'excel', 'csv'],
            'drill_down_enabled' => true,
            'comparison_mode' => true,
        ];
    }
}

// Example 2: Dashboard with Conditional Visibility
class ExecutiveDashboard extends Dashboard
{
    use HasMetadata;

    public function name(): string
    {
        return 'Executive Dashboard';
    }

    public function cards(): array
    {
        return [];
    }

    public function boot(): void
    {
        $this->withDescription('High-level executive overview and KPIs')
             ->withIcon('briefcase')
             ->withCategory('Business')
             ->withTags(['executive', 'kpi', 'overview'])
             ->withPriority(5) // Very high priority
             ->withColor('#DC2626')
             ->requiresPermission('view-executive-data')
             ->requiresPermission('access-executive-dashboard');
    }

    // Override authorization for additional checks
    public function authorizedToSee(Request $request): bool
    {
        $user = $request->user();
        
        // Must be authenticated
        if (!$user) {
            return false;
        }

        // Must have executive role or be admin
        if (!$user->hasAnyRole(['executive', 'ceo', 'admin'])) {
            return false;
        }

        // Check business hours (example)
        $currentHour = now()->hour;
        if ($currentHour < 6 || $currentHour > 22) {
            return false; // Only available during business hours
        }

        return parent::authorizedToSee($request);
    }

    public function getConfiguration(): array
    {
        return [
            'display' => [
                'layout' => 'cards',
                'card_size' => 'large',
                'show_descriptions' => false, // Clean executive view
                'compact_mode' => true,
            ],
            'behavior' => [
                'auto_refresh' => true,
                'refresh_interval' => 300, // 5 minutes
                'cache_data' => false, // Always fresh data
            ],
            'accessibility' => [
                'high_contrast' => true, // Executive preference
                'large_text' => true,
            ],
        ];
    }
}

// Example 3: Dashboard with Dynamic Metadata
class CustomerSupportDashboard extends Dashboard
{
    use HasMetadata;

    public function name(): string
    {
        return 'Customer Support';
    }

    public function cards(): array
    {
        return [];
    }

    public function boot(): void
    {
        $this->withDescription('Customer support metrics and ticket management')
             ->withIcon('support')
             ->withCategory('Support')
             ->withTags(['support', 'tickets', 'customers'])
             ->withPriority(50);
    }

    // Dynamic metadata based on current state
    public function getMetadata(): array
    {
        $metadata = parent::getMetadata();
        
        // Add dynamic badge based on open tickets
        $openTickets = $this->getOpenTicketsCount();
        if ($openTickets > 0) {
            $metadata['badge'] = [
                'value' => $openTickets,
                'type' => $openTickets > 10 ? 'danger' : ($openTickets > 5 ? 'warning' : 'info'),
            ];
        }

        // Dynamic color based on ticket urgency
        $urgentTickets = $this->getUrgentTicketsCount();
        if ($urgentTickets > 0) {
            $metadata['color'] = '#DC2626'; // Red for urgent tickets
            $metadata['background_color'] = '#FEF2F2';
        }

        return $metadata;
    }

    private function getOpenTicketsCount(): int
    {
        // Example: return actual count from database
        return 7;
    }

    private function getUrgentTicketsCount(): int
    {
        // Example: return actual count from database
        return 2;
    }
}

// Example 4: Using Metadata Manager Directly
class DashboardMetadataExample
{
    public function demonstrateMetadataManager()
    {
        $dashboards = AdminPanel::getDashboards();

        // Get metadata for all dashboards
        $dashboardsWithMetadata = DashboardMetadataManager::getMultipleMetadata($dashboards);

        // Order dashboards by priority
        $orderedByPriority = DashboardMetadataManager::getOrderedDashboards(
            $dashboards, 
            'priority', 
            'asc'
        );

        // Filter visible dashboards
        $visibleDashboards = DashboardMetadataManager::getVisibleDashboards(
            $dashboards, 
            request()
        );

        // Group dashboards by category
        $groupedDashboards = DashboardMetadataManager::groupDashboardsByCategory($dashboards);

        // Search dashboards
        $searchResults = DashboardMetadataManager::searchDashboards($dashboards, 'analytics');

        return [
            'all_metadata' => $dashboardsWithMetadata,
            'ordered' => $orderedByPriority,
            'visible' => $visibleDashboards,
            'grouped' => $groupedDashboards,
            'search_results' => $searchResults,
        ];
    }
}

// Example 5: Using Configuration Manager
class DashboardConfigurationExample
{
    public function demonstrateConfigurationManager()
    {
        $dashboard = new AdvancedAnalyticsDashboard();
        $request = request();

        // Get configuration with user preferences
        $config = DashboardConfigurationManager::getConfiguration($dashboard, $request);

        // Set custom configuration
        $customConfig = [
            'display' => [
                'layout' => 'list',
                'compact_mode' => true,
            ],
            'behavior' => [
                'auto_refresh' => false,
            ],
        ];
        DashboardConfigurationManager::setConfiguration($dashboard, $customConfig, $request);

        // Get global configuration
        $globalConfig = DashboardConfigurationManager::getGlobalConfiguration();

        // Get user preferences
        $userPreferences = DashboardConfigurationManager::getUserPreferences(
            $request->user(), 
            $dashboard->uriKey()
        );

        return [
            'dashboard_config' => $config,
            'global_config' => $globalConfig,
            'user_preferences' => $userPreferences,
        ];
    }
}

// Example 6: Using Ordering Manager
class DashboardOrderingExample
{
    public function demonstrateOrderingManager()
    {
        $dashboards = AdminPanel::getDashboards();
        $request = request();

        // Get ordered dashboards
        $orderedDashboards = DashboardOrderingManager::getOrderedDashboards(
            $dashboards,
            'priority',
            'asc',
            $request
        );

        // Get visible dashboards
        $visibleDashboards = DashboardOrderingManager::getVisibleDashboards(
            $dashboards,
            $request
        );

        // Get grouped dashboards
        $groupedDashboards = DashboardOrderingManager::getGroupedDashboards(
            $dashboards,
            'name',
            'asc',
            $request
        );

        // Set custom order for user
        $customOrder = ['analytics', 'sales', 'support', 'admin'];
        DashboardOrderingManager::setCustomOrder($customOrder, $request);

        // Set dashboard visibility
        DashboardOrderingManager::setDashboardVisibility('admin', false, $request);

        // Set user sorting preferences
        DashboardOrderingManager::setUserSortingPreferences('name', 'desc', $request);

        return [
            'ordered' => $orderedDashboards,
            'visible' => $visibleDashboards,
            'grouped' => $groupedDashboards,
            'sort_options' => DashboardOrderingManager::getSortOptions(),
        ];
    }
}

// Example 7: Dashboard with Complex Configuration
class RealtimeDashboard extends Dashboard
{
    use HasMetadata;

    public function name(): string
    {
        return 'Realtime Monitoring';
    }

    public function cards(): array
    {
        return [];
    }

    public function boot(): void
    {
        $this->withDescription('Real-time system monitoring and alerts')
             ->withIcon('eye')
             ->withCategory('Monitoring')
             ->withTags(['realtime', 'monitoring', 'alerts'])
             ->withPriority(20)
             ->withColor('#10B981')
             ->setDisplayOption('realtime_updates', true)
             ->setDisplayOption('alert_sounds', true)
             ->setDisplayOption('auto_scroll', true)
             ->setConfiguration('websocket_enabled', true)
             ->setConfiguration('update_frequency', 1000) // 1 second
             ->setConfiguration('max_data_points', 100);
    }

    public function getConfiguration(): array
    {
        return [
            'display' => [
                'layout' => 'grid',
                'columns' => 2,
                'card_size' => 'medium',
                'show_timestamps' => true,
            ],
            'behavior' => [
                'auto_refresh' => true,
                'refresh_interval' => 1, // 1 second for realtime
                'lazy_loading' => false,
                'preload_data' => false,
                'cache_data' => false, // No caching for realtime data
            ],
            'realtime' => [
                'websocket_enabled' => true,
                'update_frequency' => 1000,
                'max_data_points' => 100,
                'alert_sounds' => true,
                'auto_scroll' => true,
            ],
        ];
    }

    // Override to check system requirements
    public function checkDependencies(): bool
    {
        // Check if WebSocket server is running
        if (!$this->isWebSocketServerRunning()) {
            return false;
        }

        // Check if monitoring service is available
        if (!$this->isMonitoringServiceAvailable()) {
            return false;
        }

        return parent::checkDependencies();
    }

    private function isWebSocketServerRunning(): bool
    {
        // Example check
        return true;
    }

    private function isMonitoringServiceAvailable(): bool
    {
        // Example check
        return true;
    }
}
