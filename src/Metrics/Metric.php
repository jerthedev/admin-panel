<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Base Metric Class
 *
 * Abstract base class for all dashboard metrics providing common
 * functionality for calculation, formatting, and trend analysis.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Metrics
 */
abstract class Metric
{
    /**
     * The metric's display name.
     */
    protected string $name;

    /**
     * The metric's icon.
     */
    protected string $icon = 'ChartBarIcon';

    /**
     * The metric's color scheme.
     */
    protected string $color = 'blue';

    /**
     * The metric's number format.
     */
    protected string $format = 'number';

    /**
     * Whether to show trend information.
     */
    protected bool $showTrend = true;

    /**
     * The trend comparison period in days.
     */
    protected int $trendPeriod = 30;

    /**
     * Get the metric's display name.
     */
    public function name(): string
    {
        return $this->name ?? class_basename(static::class);
    }

    /**
     * Get the metric's icon.
     */
    public function icon(): string
    {
        return $this->icon;
    }

    /**
     * Get the metric's color scheme.
     */
    public function color(): string
    {
        return $this->color;
    }

    /**
     * Get the metric's format type.
     */
    public function format(): string
    {
        return $this->format;
    }

    /**
     * Calculate the metric value.
     */
    abstract public function calculate(Request $request): mixed;

    /**
     * Calculate the trend for this metric.
     */
    public function trend(Request $request): ?array
    {
        if (!$this->showTrend) {
            return null;
        }

        $currentValue = $this->calculate($request);
        $previousValue = $this->calculateForPeriod(
            Carbon::now()->subDays($this->trendPeriod),
            Carbon::now()->subDays($this->trendPeriod * 2),
            $request
        );

        if ($previousValue === 0 || $previousValue === null) {
            return null;
        }

        $percentage = (($currentValue - $previousValue) / $previousValue) * 100;
        $direction = $percentage >= 0 ? 'up' : 'down';

        return [
            'percentage' => round(abs($percentage), 1),
            'direction' => $direction,
            'previous_value' => $previousValue,
            'current_value' => $currentValue,
        ];
    }

    /**
     * Calculate the metric value for a specific period.
     */
    protected function calculateForPeriod(Carbon $start, Carbon $end, Request $request): mixed
    {
        // Default implementation - subclasses can override
        return $this->calculate($request);
    }

    /**
     * Determine if the user is authorized to view this metric.
     */
    public function authorize(Request $request): bool
    {
        return true;
    }

    /**
     * Get the metric's cache key.
     */
    protected function getCacheKey(Request $request, string $suffix = ''): string
    {
        $key = 'admin_panel_metric_' . class_basename(static::class);
        
        if ($suffix) {
            $key .= '_' . $suffix;
        }

        return strtolower($key);
    }

    /**
     * Get the cache TTL in seconds.
     */
    protected function getCacheTtl(): int
    {
        return config('admin-panel.performance.cache_ttl', 3600);
    }

    /**
     * Format a number value.
     */
    protected function formatNumber(mixed $value): string
    {
        if (!is_numeric($value)) {
            return (string) $value;
        }

        $value = (float) $value;

        if ($value >= 1000000) {
            return number_format($value / 1000000, 1) . 'M';
        }

        if ($value >= 1000) {
            return number_format($value / 1000, 1) . 'K';
        }

        return number_format($value);
    }

    /**
     * Format a currency value.
     */
    protected function formatCurrency(mixed $value, string $currency = 'USD'): string
    {
        if (!is_numeric($value)) {
            return (string) $value;
        }

        return '$' . $this->formatNumber($value);
    }

    /**
     * Format a percentage value.
     */
    protected function formatPercentage(mixed $value): string
    {
        if (!is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, 1) . '%';
    }

    /**
     * Set the metric's name.
     */
    public function withName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the metric's icon.
     */
    public function withIcon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Set the metric's color.
     */
    public function withColor(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Set the metric's format.
     */
    public function withFormat(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Disable trend calculation.
     */
    public function withoutTrend(): static
    {
        $this->showTrend = false;
        return $this;
    }

    /**
     * Set the trend period.
     */
    public function withTrendPeriod(int $days): static
    {
        $this->trendPeriod = $days;
        return $this;
    }
}
