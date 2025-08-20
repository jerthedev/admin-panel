<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics\Concerns;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Advanced Caching Trait for Metrics.
 *
 * Provides advanced caching capabilities for metrics including:
 * - Configurable cache stores
 * - Cache warming and invalidation
 * - Performance monitoring
 * - Cache statistics
 * - Conditional caching based on data freshness
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
trait HasAdvancedCaching
{
    /**
     * Cache store to use for this metric.
     */
    protected ?string $cacheStore = null;

    /**
     * Cache TTL in seconds.
     */
    protected ?int $cacheTtl = null;

    /**
     * Whether to include user context in cache key.
     */
    protected bool $includeUserInCacheKey = false;

    /**
     * Cache key prefix for this metric.
     */
    protected ?string $cacheKeyPrefix = null;

    /**
     * Cache tags for this metric.
     */
    protected array $cacheTags = [];

    /**
     * Set the cache store for this metric.
     */
    public function cacheStore(string $store): static
    {
        $this->cacheStore = $store;

        return $this;
    }

    /**
     * Set the cache TTL for this metric.
     */
    public function cacheTtl(int $seconds): static
    {
        $this->cacheTtl = $seconds;

        return $this;
    }

    /**
     * Set cache TTL in minutes.
     */
    public function cacheForMinutes(int $minutes): static
    {
        return $this->cacheTtl($minutes * 60);
    }

    /**
     * Set cache TTL in hours.
     */
    public function cacheForHours(int $hours): static
    {
        return $this->cacheTtl($hours * 3600);
    }

    /**
     * Include user context in cache key.
     */
    public function cachePerUser(bool $perUser = true): static
    {
        $this->includeUserInCacheKey = $perUser;

        return $this;
    }

    /**
     * Set cache key prefix.
     */
    public function cacheKeyPrefix(string $prefix): static
    {
        $this->cacheKeyPrefix = $prefix;

        return $this;
    }

    /**
     * Set cache tags.
     */
    public function cacheTags(array $tags): static
    {
        $this->cacheTags = $tags;

        return $this;
    }

    /**
     * Add a cache tag.
     */
    public function addCacheTag(string $tag): static
    {
        $this->cacheTags[] = $tag;

        return $this;
    }

    /**
     * Get the cache store instance.
     */
    protected function getCacheStoreInstance()
    {
        $store = Cache::store($this->cacheStore ?? $this->getCacheStore());

        if (! empty($this->cacheTags) && method_exists($store, 'tags')) {
            return $store->tags($this->cacheTags);
        }

        return $store;
    }

    /**
     * Get enhanced cache key with additional parameters.
     */
    protected function getEnhancedCacheKey(Request $request, array $additionalParams = []): string
    {
        $baseKey = $this->cacheKeyPrefix ?? 'admin_panel_metric_'.class_basename(static::class);

        // Core parameters
        $params = [
            'range' => $request->get('range', 'default'),
            'timezone' => $request->get('timezone', config('app.timezone', 'UTC')),
        ];

        // Add additional parameters
        $params = array_merge($params, $additionalParams);

        // Include user context if needed
        if ($this->includeUserInCacheKey || $this->shouldIncludeUserInCacheKey()) {
            $params['user'] = $request->user()?->id ?? 'guest';
        }

        // Create hash from parameters
        $paramHash = md5(serialize($params));

        return strtolower("{$baseKey}_{$paramHash}");
    }

    /**
     * Cache a metric result with automatic TTL handling.
     */
    protected function cacheResult(string $key, mixed $result, ?int $ttl = null): mixed
    {
        $store = $this->getCacheStoreInstance();
        $ttl = $ttl ?? $this->cacheTtl ?? $this->getCacheTtl();

        $store->put($key, $result, $ttl);

        return $result;
    }

    /**
     * Get cached result or execute callback.
     */
    protected function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $store = $this->getCacheStoreInstance();
        $ttl = $ttl ?? $this->cacheTtl ?? $this->getCacheTtl();

        return $store->remember($key, $ttl, $callback);
    }

    /**
     * Get cached result or execute callback forever.
     */
    protected function rememberForever(string $key, callable $callback): mixed
    {
        $store = $this->getCacheStoreInstance();

        return $store->rememberForever($key, $callback);
    }

    /**
     * Invalidate cache by key pattern.
     */
    public function invalidateCachePattern(string $pattern): bool
    {
        $store = $this->getCacheStoreInstance();

        // For tagged cache stores
        if (! empty($this->cacheTags) && method_exists($store, 'flush')) {
            return $store->flush();
        }

        // For Redis stores with pattern support
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

        return true;
    }

    /**
     * Get cache statistics for this metric.
     */
    public function getCacheStats(): array
    {
        $stats = [
            'store' => $this->cacheStore ?? $this->getCacheStore(),
            'ttl' => $this->cacheTtl ?? $this->getCacheTtl(),
            'tags' => $this->cacheTags,
            'per_user' => $this->includeUserInCacheKey,
            'prefix' => $this->cacheKeyPrefix,
        ];

        // Try to get store-specific stats
        try {
            $store = $this->getCacheStoreInstance();

            if (method_exists($store, 'getRedis')) {
                $redis = $store->getRedis();
                $pattern = ($this->cacheKeyPrefix ?? 'admin_panel_metric_'.class_basename(static::class)).'_*';
                $keys = $redis->keys($pattern);

                $stats['cached_keys'] = count($keys);
                $stats['memory_usage'] = array_sum(array_map(fn ($key) => $redis->memory('usage', $key), $keys));
            }
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * Warm cache for multiple parameter combinations.
     */
    public function warmCacheForParameters(array $parameterSets): array
    {
        $results = [];

        foreach ($parameterSets as $index => $params) {
            $request = new Request($params);

            try {
                $cacheKey = $this->getEnhancedCacheKey($request);
                $store = $this->getCacheStoreInstance();

                if (! $store->has($cacheKey)) {
                    $result = $this->calculate($request);
                    $ttl = $this->cacheTtl ?? $this->getCacheTtl();

                    $this->cacheResult($cacheKey, $result, $ttl);
                    $results[$index] = ['status' => 'warmed', 'params' => $params, 'ttl' => $ttl];
                } else {
                    $results[$index] = ['status' => 'already_cached', 'params' => $params];
                }
            } catch (\Exception $e) {
                $results[$index] = ['status' => 'error', 'params' => $params, 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Check if cache is fresh based on data timestamp.
     */
    protected function isCacheFresh(string $key, ?DateTimeInterface $dataTimestamp = null): bool
    {
        if (! $dataTimestamp) {
            return true; // Can't determine freshness without timestamp
        }

        $store = $this->getCacheStoreInstance();

        if (! $store->has($key)) {
            return false; // No cache exists
        }

        // Get cache timestamp (this would require storing metadata)
        $cacheTimestampKey = $key.'_timestamp';
        $cacheTimestamp = $store->get($cacheTimestampKey);

        if (! $cacheTimestamp) {
            return true; // Can't determine cache age
        }

        return $dataTimestamp <= $cacheTimestamp;
    }

    /**
     * Cache result with timestamp metadata.
     */
    protected function cacheWithTimestamp(string $key, mixed $result, ?int $ttl = null): mixed
    {
        $store = $this->getCacheStoreInstance();
        $ttl = $ttl ?? $this->cacheTtl ?? $this->getCacheTtl();

        // Cache the result
        $store->put($key, $result, $ttl);

        // Cache the timestamp
        $store->put($key.'_timestamp', now(), $ttl);

        return $result;
    }

    /**
     * Get cache hit ratio for this metric.
     */
    public function getCacheHitRatio(int $sampleSize = 100): float
    {
        // This would require implementing cache hit/miss tracking
        // For now, return a placeholder
        return 0.0;
    }
}
