<?php

declare(strict_types=1);

namespace App\Admin\Cards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;

/**
 * Analytics Card.
 *
 * Custom analytics card for the admin panel demonstrating
 * Nova-compatible card functionality with real-time data,
 * authorization, and interactive features.
 *
 * This is a working example that can be published to user
 * applications and serves as a reference implementation.
 */
class AnalyticsCard extends Card
{
    /**
     * Create a new Analytics card instance.
     */
    public function __construct()
    {
        parent::__construct();

        // Set custom meta data for the card
        $this->withMeta([
            'title' => 'Analytics Overview',
            'description' => 'Key performance metrics and analytics data',
            'icon' => 'chart-bar',
            'color' => 'blue',
            'group' => 'Analytics',
            'refreshable' => true,
            'refreshInterval' => 30, // seconds
            'size' => 'lg',
        ]);
    }

    /**
     * Create a new Analytics card with admin-only access.
     */
    public static function adminOnly(): static
    {
        return static::make()->canSee(function (Request $request) {
            // Example: Only show to admin users
            return $request->user()?->is_admin ?? false;
        });
    }

    /**
     * Create a new Analytics card with role-based access.
     */
    public static function forRole(string $role): static
    {
        return static::make()->canSee(function (Request $request) use ($role) {
            // Example: Show to users with specific role
            return $request->user()?->hasRole($role) ?? false;
        });
    }

    /**
     * Create a new Analytics card with custom date range.
     */
    public static function withDateRange(string $startDate, string $endDate): static
    {
        return static::make()->withMeta([
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }

    /**
     * Create a new Analytics card with specific metrics.
     */
    public static function withMetrics(array $metrics): static
    {
        return static::make()->withMeta([
            'selectedMetrics' => $metrics,
        ]);
    }

    /**
     * Get the card's data for rendering.
     */
    public function data(Request $request): array
    {
        return $this->getAnalyticsData($request);
    }

    /**
     * Get additional meta information to merge with the card payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'data' => $this->getAnalyticsData(request()),
            'timestamp' => now()->toISOString(),
            'version' => '1.0',
            'features' => [
                'real_time_updates',
                'date_range_filtering',
                'metric_selection',
                'export_capabilities',
            ],
        ]);
    }

    /**
     * Get analytics data for the card.
     */
    protected function getAnalyticsData(Request $request): array
    {
        // In a real implementation, this would fetch data from your analytics service
        // For this example, we'll return mock data that demonstrates the card capabilities
        return [
            'totalUsers' => $this->getTotalUsers(),
            'activeUsers' => $this->getActiveUsers(),
            'pageViews' => $this->getPageViews(),
            'conversionRate' => $this->getConversionRate(),
            'revenue' => $this->getRevenue(),
            'topPages' => $this->getTopPages(),
            'userGrowth' => $this->getUserGrowthData(),
            'deviceBreakdown' => $this->getDeviceBreakdown(),
            'lastUpdated' => now()->toISOString(),
        ];
    }

    /**
     * Get total users count.
     */
    protected function getTotalUsers(): int
    {
        // Mock data - replace with actual analytics query
        // Example: return User::count();
        return 15420;
    }

    /**
     * Get active users count.
     */
    protected function getActiveUsers(): int
    {
        // Mock data - replace with actual analytics query
        // Example: return User::where('last_login_at', '>=', now()->subDays(30))->count();
        return 12350;
    }

    /**
     * Get page views count.
     */
    protected function getPageViews(): int
    {
        // Mock data - replace with actual analytics query
        // Example: return PageView::where('created_at', '>=', now()->subDays(30))->count();
        return 89750;
    }

    /**
     * Get conversion rate percentage.
     */
    protected function getConversionRate(): float
    {
        // Mock data - replace with actual analytics query
        // Example: return (Order::count() / User::count()) * 100;
        return 3.2;
    }

    /**
     * Get revenue amount.
     */
    protected function getRevenue(): float
    {
        // Mock data - replace with actual analytics query
        // Example: return Order::where('created_at', '>=', now()->subDays(30))->sum('total');
        return 45230.50;
    }

    /**
     * Get top pages data.
     */
    protected function getTopPages(): array
    {
        // Mock data - replace with actual analytics query
        return [
            ['path' => '/dashboard', 'views' => 12500, 'percentage' => 35.2],
            ['path' => '/products', 'views' => 8900, 'percentage' => 25.1],
            ['path' => '/about', 'views' => 6200, 'percentage' => 17.5],
            ['path' => '/contact', 'views' => 4800, 'percentage' => 13.5],
            ['path' => '/blog', 'views' => 3100, 'percentage' => 8.7],
        ];
    }

    /**
     * Get user growth data for charts.
     */
    protected function getUserGrowthData(): array
    {
        // Mock data - replace with actual analytics query
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => [1200, 1900, 3000, 5000, 2000, 3000],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Returning Users',
                    'data' => [800, 1200, 1800, 2400, 1600, 2200],
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
        ];
    }

    /**
     * Get device breakdown data.
     */
    protected function getDeviceBreakdown(): array
    {
        // Mock data - replace with actual analytics query
        return [
            ['device' => 'Desktop', 'users' => 8500, 'percentage' => 55.1],
            ['device' => 'Mobile', 'users' => 5200, 'percentage' => 33.7],
            ['device' => 'Tablet', 'users' => 1720, 'percentage' => 11.2],
        ];
    }
}
