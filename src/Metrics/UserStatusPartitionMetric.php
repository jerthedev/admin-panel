<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * User Status Partition Metric.
 *
 * Example Partition metric that demonstrates user distribution by status.
 * Shows the breakdown of users by their status (active, inactive, pending, etc.)
 * with pie chart visualization and custom colors/labels.
 *
 * This metric serves as a reference implementation for Partition metrics, showcasing:
 * - Categorical data aggregation (count, sum, average, max, min)
 * - Custom label formatting with closures
 * - Custom color configuration for pie chart segments
 * - Manual result building capability
 * - Caching for performance optimization
 * - Configurable user model support
 * - Range selection for time-based filtering
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class UserStatusPartitionMetric extends Partition
{
    /**
     * The metric's display name.
     */
    public string $name = 'User Status Distribution';

    /**
     * The user model class to query.
     */
    protected string $userModel = 'App\Models\User';

    /**
     * Cache duration in minutes.
     */
    protected int $cacheMinutes = 15;

    /**
     * Status column name.
     */
    protected string $statusColumn = 'status';

    /**
     * Calculate the partition data for the metric.
     */
    public function calculate(Request $request): PartitionResult
    {
        // Use caching for performance
        $cacheKey = $this->getCacheKey($request);

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            return $this->count($request, $this->userModel, $this->statusColumn)
                ->labels($this->getStatusLabels())
                ->colors($this->getStatusColors())
                ->suffix(' users');
        });
    }

    /**
     * Get the ranges available for this metric.
     */
    public function ranges(): array
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            365 => '1 Year',
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
            'ALL' => 'All Time',
        ];
    }

    /**
     * Determine the cache duration for this metric.
     */
    public function cacheFor(): int
    {
        return $this->cacheMinutes * 60; // Convert to seconds
    }

    /**
     * Set the user model to query.
     */
    public function userModel(string $model): static
    {
        $this->userModel = $model;

        return $this;
    }

    /**
     * Set the status column name.
     */
    public function statusColumn(string $column): static
    {
        $this->statusColumn = $column;

        return $this;
    }

    /**
     * Set the cache duration in minutes.
     */
    public function cacheForMinutes(int $minutes): static
    {
        $this->cacheMinutes = $minutes;

        return $this;
    }

    /**
     * Get custom labels for user statuses.
     */
    protected function getStatusLabels(): array
    {
        return [
            'active' => 'Active Users',
            'inactive' => 'Inactive Users',
            'pending' => 'Pending Approval',
            'suspended' => 'Suspended Users',
            'banned' => 'Banned Users',
            'verified' => 'Verified Users',
            'unverified' => 'Unverified Users',
        ];
    }

    /**
     * Get custom colors for user statuses.
     */
    protected function getStatusColors(): array
    {
        return [
            'active' => '#10B981',     // Green
            'inactive' => '#6B7280',   // Gray
            'pending' => '#F59E0B',    // Yellow
            'suspended' => '#F97316',  // Orange
            'banned' => '#EF4444',     // Red
            'verified' => '#3B82F6',   // Blue
            'unverified' => '#8B5CF6', // Purple
        ];
    }

    /**
     * Calculate partition by user roles instead of status.
     */
    public function calculateByRoles(Request $request): PartitionResult
    {
        $cacheKey = $this->getCacheKey($request, 'roles');

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            return $this->countWithCustomGrouping($request, $this->userModel, function ($user) {
                // Assuming users have a roles relationship
                return $user->roles->pluck('name')->join(', ') ?: 'No Role';
            })
                ->labels($this->getRoleLabels())
                ->colors($this->getRoleColors())
                ->suffix(' users');
        });
    }

    /**
     * Calculate partition by user registration date ranges.
     */
    public function calculateByRegistrationPeriods(Request $request): PartitionResult
    {
        $cacheKey = $this->getCacheKey($request, 'periods');

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            $ranges = [
                'Last 7 Days' => [now()->subDays(7), now()],
                'Last 30 Days' => [now()->subDays(30), now()->subDays(7)],
                'Last 90 Days' => [now()->subDays(90), now()->subDays(30)],
                'Older' => [now()->subYears(10), now()->subDays(90)],
            ];

            return $this->countByDateRanges($request, $this->userModel, $ranges)
                ->colors([
                    'Last 7 Days' => '#10B981',
                    'Last 30 Days' => '#3B82F6',
                    'Last 90 Days' => '#F59E0B',
                    'Older' => '#6B7280',
                ])
                ->suffix(' users');
        });
    }

    /**
     * Calculate partition by user activity levels (based on login frequency).
     */
    public function calculateByActivityLevel(Request $request): PartitionResult
    {
        $cacheKey = $this->getCacheKey($request, 'activity');

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            return $this->countWithCustomGrouping($request, $this->userModel, function ($user) {
                $lastLogin = $user->last_login_at;

                if (! $lastLogin) {
                    return 'Never Logged In';
                }

                $daysSinceLogin = now()->diffInDays($lastLogin);

                return match (true) {
                    $daysSinceLogin <= 1 => 'Very Active (Daily)',
                    $daysSinceLogin <= 7 => 'Active (Weekly)',
                    $daysSinceLogin <= 30 => 'Moderate (Monthly)',
                    $daysSinceLogin <= 90 => 'Low Activity',
                    default => 'Inactive',
                };
            })
                ->colors([
                    'Very Active (Daily)' => '#10B981',
                    'Active (Weekly)' => '#3B82F6',
                    'Moderate (Monthly)' => '#F59E0B',
                    'Low Activity' => '#F97316',
                    'Inactive' => '#EF4444',
                    'Never Logged In' => '#6B7280',
                ])
                ->suffix(' users');
        });
    }

    /**
     * Get custom labels for user roles.
     */
    protected function getRoleLabels(): array
    {
        return [
            'admin' => 'Administrators',
            'moderator' => 'Moderators',
            'user' => 'Regular Users',
            'guest' => 'Guest Users',
            'No Role' => 'Unassigned',
        ];
    }

    /**
     * Get custom colors for user roles.
     */
    protected function getRoleColors(): array
    {
        return [
            'admin' => '#EF4444',      // Red
            'moderator' => '#F59E0B',  // Yellow
            'user' => '#10B981',       // Green
            'guest' => '#6B7280',      // Gray
            'No Role' => '#8B5CF6',    // Purple
        ];
    }

    /**
     * Generate a cache key for the metric.
     */
    protected function getCacheKey(Request $request, string $suffix = ''): string
    {
        $range = $request->get('range', 30);
        $timezone = $request->get('timezone', config('app.timezone', 'UTC'));

        $key = sprintf(
            'admin_panel_metric_user_status_partition_%s_%s_%s_%s',
            md5($this->userModel),
            $this->statusColumn,
            $range,
            md5($timezone),
        );

        if ($suffix) {
            $key .= '_'.$suffix;
        }

        return $key;
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'user-status-distribution';
    }

    /**
     * Determine if the metric should be displayed.
     */
    public function authorize(Request $request): bool
    {
        // Only show to users who can view user data
        return $request->user()?->can('viewAny', $this->userModel) ?? false;
    }

    /**
     * Get help text for the metric.
     */
    public function help(): ?string
    {
        return 'Shows the distribution of users by their status with pie chart visualization.';
    }

    /**
     * Get the metric's icon.
     */
    public function icon(): string
    {
        return 'chart-pie';
    }

    /**
     * Get additional metadata for the metric.
     */
    public function meta(): array
    {
        return [
            'model' => $this->userModel,
            'status_column' => $this->statusColumn,
            'cache_minutes' => $this->cacheMinutes,
            'help' => $this->help(),
            'icon' => $this->icon(),
        ];
    }

    /**
     * Override date range application to support "ALL" time.
     */
    protected function applyDateRange(\Illuminate\Database\Eloquent\Builder $query, string|int $range, string $timezone): void
    {
        if ($range === 'ALL') {
            // Don't apply any date filtering for "All Time"
            return;
        }

        parent::applyDateRange($query, $range, $timezone);
    }
}
