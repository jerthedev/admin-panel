<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Metric Cache Manager.
 *
 * Centralized cache management for metrics with advanced features:
 * - Bulk cache operations
 * - Cache warming strategies
 * - Performance monitoring
 * - Cache invalidation patterns
 * - Memory usage tracking
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MetricCacheManager
{
    /**
     * Default cache store for metrics.
     */
    protected string $defaultStore;

    /**
     * Cache key prefix for all metrics.
     */
    protected string $keyPrefix = 'admin_panel_metric';

    /**
     * Cache statistics.
     */
    protected array $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0,
    ];

    /**
     * Create a new metric cache manager.
     */
    public function __construct(?string $defaultStore = null)
    {
        $this->defaultStore = $defaultStore ?? config('admin-panel.performance.cache_store', 'default');
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        return array_merge($this->stats, [
            'hit_ratio' => $this->getHitRatio(),
            'total_operations' => array_sum($this->stats),
        ]);
    }

    /**
     * Get cache hit ratio.
     */
    public function getHitRatio(): float
    {
        $total = $this->stats['hits'] + $this->stats['misses'];

        return $total > 0 ? $this->stats['hits'] / $total : 0.0;
    }

    /**
     * Reset cache statistics.
     */
    public function resetStats(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'writes' => 0,
            'deletes' => 0,
        ];
    }

    /**
     * Get cached metric result.
     */
    public function get(string $key, ?string $store = null): mixed
    {
        $store = Cache::store($store ?? $this->defaultStore);
        $result = $store->get($key);

        if ($result !== null) {
            $this->stats['hits']++;
        } else {
            $this->stats['misses']++;
        }

        return $result;
    }

    /**
     * Cache metric result.
     */
    public function put(string $key, mixed $value, int $ttl, ?string $store = null): bool
    {
        $store = Cache::store($store ?? $this->defaultStore);
        $result = $store->put($key, $value, $ttl);

        if ($result) {
            $this->stats['writes']++;
        }

        return $result;
    }

    /**
     * Remember metric result with callback.
     */
    public function remember(string $key, int $ttl, callable $callback, ?string $store = null): mixed
    {
        $store = Cache::store($store ?? $this->defaultStore);

        $exists = $store->has($key);
        $result = $store->remember($key, $ttl, $callback);

        if ($exists) {
            $this->stats['hits']++;
        } else {
            $this->stats['misses']++;
            $this->stats['writes']++;
        }

        return $result;
    }

    /**
     * Delete cached metric result.
     */
    public function forget(string $key, ?string $store = null): bool
    {
        $store = Cache::store($store ?? $this->defaultStore);
        $result = $store->forget($key);

        if ($result) {
            $this->stats['deletes']++;
        }

        return $result;
    }

    /**
     * Warm cache for a metric with multiple parameter sets.
     */
    public function warmMetric(Metric $metric, ?array $parameterSets = null): array
    {
        $parameterSets = $parameterSets ?? $this->getDefaultParameterSets($metric);
        $results = [];

        foreach ($parameterSets as $index => $params) {
            $request = new Request($params);

            try {
                $cacheKey = $this->generateCacheKey($metric, $request);
                $store = Cache::store($this->defaultStore);

                if (! $store->has($cacheKey)) {
                    $result = $metric->calculate($request);
                    $ttl = $this->getMetricTtl($metric);

                    $this->put($cacheKey, $result, $ttl);
                    $results[$index] = [
                        'status' => 'warmed',
                        'params' => $params,
                        'ttl' => $ttl,
                        'key' => $cacheKey,
                    ];
                } else {
                    $results[$index] = [
                        'status' => 'already_cached',
                        'params' => $params,
                        'key' => $cacheKey,
                    ];
                }
            } catch (\Exception $e) {
                $results[$index] = [
                    'status' => 'error',
                    'params' => $params,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Warm cache for multiple metrics.
     */
    public function warmMetrics(array $metrics, ?array $parameterSets = null): array
    {
        $results = [];

        foreach ($metrics as $metric) {
            $metricClass = is_string($metric) ? $metric : get_class($metric);
            $metricInstance = is_string($metric) ? new $metric : $metric;

            $results[$metricClass] = $this->warmMetric($metricInstance, $parameterSets);
        }

        return $results;
    }

    /**
     * Invalidate cache for a specific metric.
     */
    public function invalidateMetric(string $metricClass): bool
    {
        $pattern = $this->keyPrefix.'_'.class_basename($metricClass).'_*';

        return $this->invalidatePattern($pattern);
    }

    /**
     * Invalidate cache by pattern.
     */
    public function invalidatePattern(string $pattern, ?string $store = null): bool
    {
        $store = Cache::store($store ?? $this->defaultStore);

        // For Redis stores
        if (method_exists($store, 'getRedis')) {
            try {
                $redis = $store->getRedis();
                $keys = $redis->keys($pattern);

                if (! empty($keys)) {
                    $deleted = $redis->del($keys);
                    $this->stats['deletes'] += $deleted;

                    return $deleted > 0;
                }

                return true;
            } catch (\Exception $e) {
                // Fall back to other methods
            }
        }

        // For other stores, we can't easily pattern match
        return true;
    }

    /**
     * Invalidate all metric caches.
     */
    public function invalidateAll(?string $store = null): bool
    {
        return $this->invalidatePattern($this->keyPrefix.'_*', $store);
    }

    /**
     * Get cache memory usage for metrics.
     */
    public function getMemoryUsage(?string $store = null): array
    {
        $store = Cache::store($store ?? $this->defaultStore);
        $usage = [
            'total_keys' => 0,
            'total_memory' => 0,
            'by_metric' => [],
        ];

        // For Redis stores
        if (method_exists($store, 'getRedis')) {
            try {
                $redis = $store->getRedis();
                $pattern = $this->keyPrefix.'_*';
                $keys = $redis->keys($pattern);

                $usage['total_keys'] = count($keys);

                foreach ($keys as $key) {
                    $memory = $redis->memory('usage', $key);
                    $usage['total_memory'] += $memory;

                    // Extract metric class from key
                    if (preg_match('/admin_panel_metric_([^_]+)_/', $key, $matches)) {
                        $metricClass = $matches[1];

                        if (! isset($usage['by_metric'][$metricClass])) {
                            $usage['by_metric'][$metricClass] = [
                                'keys' => 0,
                                'memory' => 0,
                            ];
                        }

                        $usage['by_metric'][$metricClass]['keys']++;
                        $usage['by_metric'][$metricClass]['memory'] += $memory;
                    }
                }
            } catch (\Exception $e) {
                $usage['error'] = $e->getMessage();
            }
        }

        return $usage;
    }

    /**
     * Generate cache key for a metric and request.
     */
    protected function generateCacheKey(Metric $metric, Request $request): string
    {
        $baseKey = $this->keyPrefix.'_'.class_basename(get_class($metric));
        $range = $request->get('range', 'default');
        $timezone = $request->get('timezone', config('app.timezone', 'UTC'));

        return strtolower(sprintf(
            '%s_%s_%s',
            $baseKey,
            $range,
            md5($timezone),
        ));
    }

    /**
     * Get TTL for a metric.
     */
    protected function getMetricTtl(Metric $metric): int
    {
        $cacheFor = $metric->cacheFor();

        if ($cacheFor !== null) {
            if ($cacheFor instanceof \DateTimeInterface) {
                return $cacheFor->getTimestamp() - now()->getTimestamp();
            }

            if ($cacheFor instanceof \DateInterval) {
                return $cacheFor->s + ($cacheFor->i * 60) + ($cacheFor->h * 3600) + ($cacheFor->d * 86400);
            }

            return (int) $cacheFor;
        }

        return config('admin-panel.performance.cache_ttl', 3600);
    }

    /**
     * Get default parameter sets for cache warming.
     */
    protected function getDefaultParameterSets(Metric $metric): array
    {
        $ranges = array_keys($metric->ranges());
        $parameterSets = [];

        foreach ($ranges as $range) {
            $parameterSets[] = ['range' => $range];
        }

        // Add timezone variations for global applications
        $timezones = config('admin-panel.performance.cache_timezones', ['UTC']);

        if (count($timezones) > 1) {
            $baseParameterSets = $parameterSets;
            $parameterSets = [];

            foreach ($baseParameterSets as $params) {
                foreach ($timezones as $timezone) {
                    $parameterSets[] = array_merge($params, ['timezone' => $timezone]);
                }
            }
        }

        return $parameterSets;
    }

    /**
     * Get cache keys for a metric.
     */
    public function getMetricKeys(string $metricClass, ?string $store = null): array
    {
        $store = Cache::store($store ?? $this->defaultStore);
        $pattern = $this->keyPrefix.'_'.class_basename($metricClass).'_*';
        $keys = [];

        // For Redis stores
        if (method_exists($store, 'getRedis')) {
            try {
                $redis = $store->getRedis();
                $keys = $redis->keys($pattern);
            } catch (\Exception $e) {
                // Fall back to empty array
            }
        }

        return $keys;
    }

    /**
     * Analyze cache performance for metrics.
     */
    public function analyzeCachePerformance(): array
    {
        $analysis = [
            'stats' => $this->getStats(),
            'memory_usage' => $this->getMemoryUsage(),
            'recommendations' => [],
        ];

        // Generate recommendations based on stats
        if ($analysis['stats']['hit_ratio'] < 0.5) {
            $analysis['recommendations'][] = 'Low cache hit ratio. Consider increasing cache TTL or warming cache more frequently.';
        }

        if ($analysis['memory_usage']['total_memory'] > 100 * 1024 * 1024) { // 100MB
            $analysis['recommendations'][] = 'High memory usage. Consider reducing cache TTL or implementing cache size limits.';
        }

        return $analysis;
    }
}
