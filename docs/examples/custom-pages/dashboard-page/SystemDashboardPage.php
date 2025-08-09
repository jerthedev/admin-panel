<?php

namespace App\Admin\Pages;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Fields\Number;
use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Fields\Select;
use JTD\AdminPanel\Pages\Page;
use JTD\AdminPanel\Actions\Action;

/**
 * System Dashboard Custom Page
 * 
 * Demonstrates a comprehensive dashboard page with system information,
 * performance metrics, and administrative controls.
 * 
 * Features:
 * - Real-time system metrics
 * - Server configuration display
 * - Administrative actions
 * - Field integration
 * - Authorization controls
 */
class SystemDashboardPage extends Page
{
    /**
     * The Vue components for this page.
     */
    public static array $components = ['SystemDashboard'];

    /**
     * The menu group this page belongs to.
     */
    public static ?string $group = 'System';

    /**
     * The display title for this page.
     */
    public static ?string $title = 'System Dashboard';

    /**
     * The icon for this page (Heroicon name).
     */
    public static ?string $icon = 'server';

    /**
     * Get the fields for this page.
     * 
     * These fields will be passed to the Vue component and can be used
     * for both display and interaction purposes.
     */
    public function fields(Request $request): array
    {
        return [
            // Server Information Section
            Text::make('Server Name')
                ->readonly()
                ->default(gethostname())
                ->help('The hostname of the current server'),

            Text::make('PHP Version')
                ->readonly()
                ->default(PHP_VERSION)
                ->help('Currently installed PHP version'),

            Text::make('Laravel Version')
                ->readonly()
                ->default(app()->version())
                ->help('Currently installed Laravel version'),

            // Performance Metrics Section
            Number::make('Memory Usage')
                ->readonly()
                ->suffix('%')
                ->default($this->getMemoryUsagePercentage())
                ->help('Current memory utilization percentage'),

            Number::make('CPU Load')
                ->readonly()
                ->suffix('%')
                ->default($this->getCpuLoadPercentage())
                ->help('Current CPU load average'),

            Number::make('Disk Usage')
                ->readonly()
                ->suffix('%')
                ->default($this->getDiskUsagePercentage())
                ->help('Current disk space utilization'),

            // System Configuration Section
            Boolean::make('Debug Mode')
                ->readonly()
                ->default(config('app.debug'))
                ->help('Application debug mode status'),

            Select::make('Environment')
                ->readonly()
                ->options([
                    'local' => 'Local Development',
                    'staging' => 'Staging',
                    'production' => 'Production',
                ])
                ->default(config('app.env'))
                ->help('Current application environment'),

            Boolean::make('Maintenance Mode')
                ->default(app()->isDownForMaintenance())
                ->help('Application maintenance mode status'),

            // Cache Information
            Text::make('Cache Driver')
                ->readonly()
                ->default(config('cache.default'))
                ->help('Currently configured cache driver'),

            Text::make('Queue Driver')
                ->readonly()
                ->default(config('queue.default'))
                ->help('Currently configured queue driver'),
        ];
    }

    /**
     * Get custom data for this page.
     * 
     * This data is passed to the Vue component and can include
     * any custom information needed for the dashboard.
     */
    public function data(Request $request): array
    {
        return [
            'system_info' => [
                'uptime' => $this->getSystemUptime(),
                'load_average' => $this->getLoadAverage(),
                'active_users' => $this->getActiveUsersCount(),
                'total_requests_today' => $this->getTotalRequestsToday(),
            ],
            'performance_metrics' => [
                'response_time_avg' => $this->getAverageResponseTime(),
                'database_queries_avg' => $this->getAverageDatabaseQueries(),
                'cache_hit_ratio' => $this->getCacheHitRatio(),
                'error_rate' => $this->getErrorRate(),
            ],
            'recent_activity' => [
                'recent_logins' => $this->getRecentLogins(5),
                'recent_errors' => $this->getRecentErrors(5),
                'system_events' => $this->getRecentSystemEvents(10),
            ],
            'alerts' => $this->getSystemAlerts(),
        ];
    }

    /**
     * Get the actions available on this page.
     */
    public function actions(Request $request): array
    {
        return [
            new ClearCacheAction(),
            new RestartQueueAction(),
            new GenerateSystemReportAction(),
        ];
    }

    /**
     * Get the metrics for this page.
     */
    public function metrics(Request $request): array
    {
        return [
            new ActiveUsersMetric(),
            new SystemHealthMetric(),
            new PerformanceMetric(),
        ];
    }

    /**
     * Determine if the user can view this page.
     * 
     * Only allow admin users to access system information.
     */
    public static function authorizedToViewAny(Request $request): bool
    {
        return $request->user()?->hasRole('admin') ?? false;
    }

    // Helper methods for gathering system information
    private function getMemoryUsagePercentage(): float
    {
        $memoryLimit = $this->convertToBytes(ini_get('memory_limit'));
        $memoryUsage = memory_get_usage(true);
        
        return round(($memoryUsage / $memoryLimit) * 100, 2);
    }

    private function getCpuLoadPercentage(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0] * 100, 2);
        }
        
        return 0.0;
    }

    private function getDiskUsagePercentage(): float
    {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        $usedSpace = $totalSpace - $freeSpace;
        
        return round(($usedSpace / $totalSpace) * 100, 2);
    }

    private function convertToBytes(string $value): int
    {
        $unit = strtolower(substr($value, -1));
        $value = (int) $value;
        
        switch ($unit) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }

    private function getSystemUptime(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = file_get_contents('/proc/uptime');
            $seconds = (int) explode(' ', $uptime)[0];
            
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            
            return "{$days}d {$hours}h {$minutes}m";
        }
        
        return 'N/A';
    }

    private function getLoadAverage(): array
    {
        if (function_exists('sys_getloadavg')) {
            return sys_getloadavg();
        }
        
        return [0, 0, 0];
    }

    private function getActiveUsersCount(): int
    {
        // Example: Count users active in last 15 minutes
        return \App\Models\User::where('last_activity', '>', now()->subMinutes(15))->count();
    }

    private function getTotalRequestsToday(): int
    {
        // Example: This would typically come from your analytics/logging system
        return rand(1000, 5000); // Placeholder
    }

    private function getAverageResponseTime(): float
    {
        // Example: This would come from your monitoring system
        return round(rand(50, 200) / 100, 2); // Placeholder in seconds
    }

    private function getAverageDatabaseQueries(): float
    {
        // Example: Average queries per request
        return round(rand(5, 25), 1); // Placeholder
    }

    private function getCacheHitRatio(): float
    {
        // Example: Cache hit percentage
        return round(rand(85, 98), 1); // Placeholder
    }

    private function getErrorRate(): float
    {
        // Example: Error rate percentage
        return round(rand(0, 5) / 10, 2); // Placeholder
    }

    private function getRecentLogins(int $limit): array
    {
        return \App\Models\User::latest('last_login_at')
            ->take($limit)
            ->get(['name', 'email', 'last_login_at'])
            ->toArray();
    }

    private function getRecentErrors(int $limit): array
    {
        // Example: This would come from your error logging system
        return [
            ['message' => 'Database connection timeout', 'time' => now()->subMinutes(5)],
            ['message' => 'API rate limit exceeded', 'time' => now()->subMinutes(12)],
        ];
    }

    private function getRecentSystemEvents(int $limit): array
    {
        // Example: System events log
        return [
            ['event' => 'Cache cleared', 'user' => 'admin@example.com', 'time' => now()->subMinutes(30)],
            ['event' => 'Backup completed', 'user' => 'system', 'time' => now()->subHours(2)],
        ];
    }

    private function getSystemAlerts(): array
    {
        $alerts = [];
        
        // Check for high memory usage
        if ($this->getMemoryUsagePercentage() > 80) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'High memory usage detected',
                'action' => 'Consider restarting services or investigating memory leaks'
            ];
        }
        
        // Check for maintenance mode
        if (app()->isDownForMaintenance()) {
            $alerts[] = [
                'type' => 'info',
                'message' => 'Application is in maintenance mode',
                'action' => 'Disable maintenance mode when ready'
            ];
        }
        
        return $alerts;
    }
}

// Example Action Classes (would typically be in separate files)
class ClearCacheAction extends Action
{
    public function handle(Request $request): array
    {
        \Artisan::call('cache:clear');
        
        return [
            'success' => true,
            'message' => 'Cache cleared successfully'
        ];
    }
}

class RestartQueueAction extends Action
{
    public function handle(Request $request): array
    {
        \Artisan::call('queue:restart');
        
        return [
            'success' => true,
            'message' => 'Queue workers restarted'
        ];
    }
}

class GenerateSystemReportAction extends Action
{
    public function handle(Request $request): array
    {
        // Generate and download system report
        return [
            'success' => true,
            'message' => 'System report generated',
            'download_url' => route('admin.reports.system.download')
        ];
    }
}
