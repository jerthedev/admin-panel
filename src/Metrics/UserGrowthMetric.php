<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * User Growth Metric.
 *
 * Example Value metric that demonstrates user growth tracking with trend comparison.
 * Shows the total number of users registered in the selected time period compared
 * to the previous period, with percentage change calculation.
 *
 * This metric serves as a reference implementation for Value metrics, showcasing:
 * - Basic count aggregation with time-based filtering
 * - Previous period comparison for trend analysis
 * - Caching for performance optimization
 * - Configurable user model support
 * - Range selection (30, 60, 90 days, MTD, QTD, YTD)
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class UserGrowthMetric extends Value
{
    /**
     * The metric's display name.
     */
    public string $name = 'User Growth';

    /**
     * The user model class to query.
     */
    protected string $userModel = 'App\Models\User';

    /**
     * Cache duration in minutes.
     */
    protected int $cacheMinutes = 5;

    /**
     * Calculate the value of the metric.
     */
    public function calculate(Request $request): ValueResult
    {
        // Use caching for performance
        $cacheKey = $this->getCacheKey($request);

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            return $this->count($request, $this->userModel);
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
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
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
     * Set the cache duration in minutes.
     */
    public function cacheForMinutes(int $minutes): static
    {
        $this->cacheMinutes = $minutes;

        return $this;
    }

    /**
     * Generate a cache key for the metric.
     */
    protected function getCacheKey(Request $request, string $suffix = ''): string
    {
        $range = $request->get('range', 30);
        $timezone = $request->get('timezone', config('app.timezone', 'UTC'));

        $key = sprintf(
            'admin_panel_metric_user_growth_%s_%s_%s',
            md5($this->userModel),
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
        return 'user-growth';
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
        return 'Shows the number of new users registered in the selected time period compared to the previous period.';
    }

    /**
     * Get the metric's icon.
     */
    public function icon(): string
    {
        return 'users';
    }

    /**
     * Get additional metadata for the metric.
     */
    public function meta(): array
    {
        return [
            'model' => $this->userModel,
            'cache_minutes' => $this->cacheMinutes,
            'help' => $this->help(),
            'icon' => $this->icon(),
        ];
    }
}
