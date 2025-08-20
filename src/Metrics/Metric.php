<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use DateInterval;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Cards\Card;

/**
 * Base Metric Class.
 *
 * Abstract base class for all dashboard metrics providing Nova-compatible
 * functionality for calculation, formatting, trend analysis, and card integration.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class Metric extends Card
{
    /**
     * The metric's display name.
     */
    public string $name;

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
        if (! $this->showTrend) {
            return null;
        }

        $currentValue = $this->calculate($request);
        $previousValue = $this->calculateForPeriod(
            Carbon::now()->subDays($this->trendPeriod),
            Carbon::now()->subDays($this->trendPeriod * 2),
            $request,
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
     * Get the metric's cache key with enhanced range and parameter support.
     */
    protected function getCacheKey(Request $request, string $suffix = ''): string
    {
        $baseKey = 'admin_panel_metric_'.class_basename(static::class);

        // Include range in cache key
        $range = $request->get('range', 'default');
        $timezone = $request->get('timezone', config('app.timezone', 'UTC'));

        // Include user context if needed
        $userKey = $this->shouldIncludeUserInCacheKey()
            ? '_user_'.($request->user()?->id ?? 'guest')
            : '';

        // Build comprehensive cache key
        $key = sprintf(
            '%s_%s_%s%s',
            $baseKey,
            $range,
            md5($timezone),
            $userKey,
        );

        if ($suffix) {
            $key .= '_'.$suffix;
        }

        return strtolower($key);
    }

    /**
     * Determine if user context should be included in cache key.
     * Override in subclasses for user-specific metrics.
     */
    protected function shouldIncludeUserInCacheKey(): bool
    {
        return false;
    }

    /**
     * Get the cache TTL in seconds.
     * Uses cacheFor() method if available, falls back to config.
     */
    protected function getCacheTtl(): int
    {
        $cacheFor = $this->cacheFor();

        if ($cacheFor !== null) {
            if ($cacheFor instanceof DateTimeInterface) {
                return $cacheFor->getTimestamp() - now()->getTimestamp();
            }

            if ($cacheFor instanceof DateInterval) {
                return $cacheFor->s + ($cacheFor->i * 60) + ($cacheFor->h * 3600) + ($cacheFor->d * 86400);
            }

            return (int) $cacheFor;
        }

        return config('admin-panel.performance.cache_ttl', 3600);
    }

    /**
     * Get the cache store to use for this metric.
     * Override in subclasses to use different cache stores.
     */
    protected function getCacheStore(): string
    {
        return config('admin-panel.performance.cache_store', 'default');
    }

    /**
     * Invalidate the cache for this metric.
     */
    public function invalidateCache(?Request $request = null): bool
    {
        $store = Cache::store($this->getCacheStore());

        if ($request) {
            // Invalidate specific cache key
            $cacheKey = $this->getCacheKey($request);

            return $store->forget($cacheKey);
        }

        // Invalidate all cache keys for this metric
        return $this->invalidateAllCache();
    }

    /**
     * Invalidate all cache entries for this metric.
     */
    public function invalidateAllCache(): bool
    {
        $store = Cache::store($this->getCacheStore());
        $pattern = 'admin_panel_metric_'.class_basename(static::class).'_*';

        // For Redis, use pattern-based deletion
        if (method_exists($store, 'getRedis')) {
            try {
                $redis = $store->getRedis();
                $keys = $redis->keys($pattern);

                if (! empty($keys)) {
                    return $redis->del($keys) > 0;
                }

                return true;
            } catch (\Exception $e) {
                // Fall back to individual key deletion
            }
        }

        // For other stores, we can't easily pattern match
        // This would require storing a list of cache keys
        return true;
    }

    /**
     * Warm the cache for this metric with common ranges.
     */
    public function warmCache(?array $ranges = null): array
    {
        $ranges = $ranges ?? array_keys($this->ranges());
        $results = [];

        foreach ($ranges as $range) {
            $request = new Request(['range' => $range]);

            try {
                $cacheKey = $this->getCacheKey($request);
                $store = Cache::store($this->getCacheStore());

                // Only warm if not already cached
                if (! $store->has($cacheKey)) {
                    $result = $this->calculate($request);
                    $ttl = $this->getCacheTtl();

                    $store->put($cacheKey, $result, $ttl);
                    $results[$range] = ['status' => 'warmed', 'ttl' => $ttl];
                } else {
                    $results[$range] = ['status' => 'already_cached'];
                }
            } catch (\Exception $e) {
                $results[$range] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Format a number value.
     */
    protected function formatNumber(mixed $value): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        $value = (float) $value;

        if ($value >= 1000000) {
            return number_format($value / 1000000, 1).'M';
        }

        if ($value >= 1000) {
            return number_format($value / 1000, 1).'K';
        }

        return number_format($value);
    }

    /**
     * Format a currency value.
     */
    protected function formatCurrency(mixed $value, string $currency = 'USD'): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        return '$'.$this->formatNumber($value);
    }

    /**
     * Format a percentage value.
     */
    protected function formatPercentage(mixed $value): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, 1).'%';
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

    /**
     * Get the ranges available for the metric.
     *
     * Override this method in subclasses to provide custom ranges.
     */
    public function ranges(): array
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            365 => '365 Days',
            'TODAY' => 'Today',
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
        ];
    }

    /**
     * Determine the amount of time the results should be cached.
     *
     * Override this method in subclasses to provide custom cache duration.
     */
    public function cacheFor(): DateTimeInterface|DateInterval|float|int|null
    {
        return null; // No caching by default
    }

    /**
     * Create a ValueResult instance.
     */
    protected function result(mixed $value): ValueResult
    {
        return new ValueResult($value);
    }

    /**
     * Create a TrendResult instance.
     */
    protected function trendResult(array $trend = []): TrendResult
    {
        return new TrendResult($trend);
    }

    /**
     * Create a PartitionResult instance.
     */
    protected function partitionResult(array $partitions = []): PartitionResult
    {
        return new PartitionResult($partitions);
    }

    /**
     * Create a ProgressResult instance.
     */
    protected function progressResult(mixed $value, mixed $target): ProgressResult
    {
        return new ProgressResult($value, $target);
    }

    /**
     * Create a TableResult instance.
     */
    protected function tableResult(array $data = []): TableResult
    {
        return new TableResult($data);
    }
}
