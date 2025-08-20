<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Registration Trend Metric.
 *
 * Example Trend metric that demonstrates user registration trends over time.
 * Shows the number of users registered per day/week/month with line chart
 * visualization and optional current value or sum display.
 *
 * This metric serves as a reference implementation for Trend metrics, showcasing:
 * - Time-based aggregation (countByDays, countByWeeks, countByMonths)
 * - Chart data formatting for frontend consumption
 * - Current value and trend sum display options
 * - Caching for performance optimization
 * - Configurable user model support
 * - Range selection with appropriate time units
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class RegistrationTrendMetric extends Trend
{
    /**
     * The metric's display name.
     */
    public string $name = 'Registration Trend';

    /**
     * The user model class to query.
     */
    protected string $userModel = 'App\Models\User';

    /**
     * Cache duration in minutes.
     */
    protected int $cacheMinutes = 10;

    /**
     * Default aggregation unit.
     */
    protected string $defaultUnit = 'day';

    /**
     * Calculate the trend data for the metric.
     */
    public function calculate(Request $request): TrendResult
    {
        // Use caching for performance
        $cacheKey = $this->getCacheKey($request);

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            $unit = $this->getAggregationUnit($request);

            return match ($unit) {
                'hour' => $this->countByHours($request, $this->userModel),
                'day' => $this->countByDays($request, $this->userModel),
                'week' => $this->countByWeeks($request, $this->userModel),
                'month' => $this->countByMonths($request, $this->userModel),
                default => $this->countByDays($request, $this->userModel),
            };
        });
    }

    /**
     * Get the ranges available for this metric.
     */
    public function ranges(): array
    {
        return [
            1 => 'Today',
            7 => '7 Days',
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            365 => '1 Year',
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
     * Set the default aggregation unit.
     */
    public function defaultUnit(string $unit): static
    {
        $this->defaultUnit = $unit;

        return $this;
    }

    /**
     * Get the aggregation unit based on the selected range.
     */
    protected function getAggregationUnit(Request $request): string
    {
        $range = $request->get('range', 30);
        $unit = $request->get('unit', $this->defaultUnit);

        // Auto-select appropriate unit based on range if not specified
        if ($unit === $this->defaultUnit) {
            return match (true) {
                $range === 1 || $range === 'TODAY' => 'hour',
                is_numeric($range) && $range <= 7 => 'day',
                is_numeric($range) && $range <= 90 => 'day',
                is_numeric($range) && $range <= 365 => 'week',
                $range === 'MTD' => 'day',
                $range === 'QTD' => 'week',
                $range === 'YTD' => 'month',
                default => 'day',
            };
        }

        return $unit;
    }

    /**
     * Generate a cache key for the metric.
     */
    protected function getCacheKey(Request $request, string $suffix = ''): string
    {
        $range = $request->get('range', 30);
        $unit = $this->getAggregationUnit($request);
        $timezone = $request->get('timezone', config('app.timezone', 'UTC'));

        $key = sprintf(
            'admin_panel_metric_registration_trend_%s_%s_%s_%s',
            md5($this->userModel),
            $range,
            $unit,
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
        return 'registration-trend';
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
        return 'Shows the trend of new user registrations over time with line chart visualization.';
    }

    /**
     * Get the metric's icon.
     */
    public function icon(): string
    {
        return 'chart-line';
    }

    /**
     * Get additional metadata for the metric.
     */
    public function meta(): array
    {
        return [
            'model' => $this->userModel,
            'cache_minutes' => $this->cacheMinutes,
            'default_unit' => $this->defaultUnit,
            'help' => $this->help(),
            'icon' => $this->icon(),
        ];
    }

    /**
     * Create a trend result with current value display.
     */
    public function withCurrentValue(): static
    {
        return $this;
    }

    /**
     * Create a trend result with sum display.
     */
    public function withSum(): static
    {
        return $this;
    }
}
