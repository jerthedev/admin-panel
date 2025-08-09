<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Resources\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * HasCaching Trait.
 *
 * Provides intelligent caching functionality for admin panel resources.
 * Enables performance optimization through configurable caching strategies.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
trait HasCaching
{
    /**
     * Whether caching is enabled for this resource.
     */
    protected static bool $resourceCachingEnabled = true;

    /**
     * The cache TTL in seconds.
     */
    protected static int $resourceCacheTtl = 3600; // 1 hour

    /**
     * The cache store to use.
     */
    protected static ?string $resourceCacheStore = null;

    /**
     * Cache tags for this resource.
     */
    protected static array $resourceCacheTags = [];

    /**
     * Whether to cache index queries.
     */
    protected static bool $resourceCacheIndex = true;

    /**
     * Whether to cache individual resources.
     */
    protected static bool $resourceCacheResources = true;

    /**
     * Whether to cache field data.
     */
    protected static bool $resourceCacheFields = true;

    /**
     * Whether to cache relationship data.
     */
    protected static bool $resourceCacheRelationships = true;

    /**
     * Get caching configuration values.
     */
    protected static function getCachingConfig(): array
    {
        return [
            'enabled' => property_exists(static::class, 'cachingEnabled') ? static::$cachingEnabled : static::$resourceCachingEnabled,
            'ttl' => property_exists(static::class, 'cacheTtl') ? static::$cacheTtl : static::$resourceCacheTtl,
            'store' => property_exists(static::class, 'cacheStore') ? static::$cacheStore : static::$resourceCacheStore,
            'tags' => property_exists(static::class, 'cacheTags') ? static::$cacheTags : static::$resourceCacheTags,
            'index' => property_exists(static::class, 'cacheIndex') ? static::$cacheIndex : static::$resourceCacheIndex,
            'resources' => property_exists(static::class, 'cacheResources') ? static::$cacheResources : static::$resourceCacheResources,
            'fields' => property_exists(static::class, 'cacheFields') ? static::$cacheFields : static::$resourceCacheFields,
            'relationships' => property_exists(static::class, 'cacheRelationships') ? static::$cacheRelationships : static::$resourceCacheRelationships,
        ];
    }

    /**
     * Get the cache key for the resource index.
     */
    public static function getIndexCacheKey(Request $request): string
    {
        $baseKey = 'admin_panel:'.static::uriKey().':index';

        // Include relevant request parameters in cache key
        $params = [
            'search' => $request->get('search'),
            'filters' => $request->get('filters', []),
            'sort' => $request->get('orderBy'),
            'direction' => $request->get('orderByDirection'),
            'per_page' => $request->get('perPage'),
            'page' => $request->get('page'),
        ];

        $paramHash = md5(serialize(array_filter($params)));

        return "{$baseKey}:{$paramHash}";
    }

    /**
     * Get the cache key for a specific resource.
     */
    public function getResourceCacheKey(): string
    {
        return 'admin_panel:'.static::uriKey().':resource:'.$this->getKey();
    }

    /**
     * Get the cache key for resource fields.
     */
    public function getFieldsCacheKey(Request $request): string
    {
        $baseKey = 'admin_panel:'.static::uriKey().':fields:'.$this->getKey();
        $context = $request->get('editing') ? 'edit' : 'view';

        return "{$baseKey}:{$context}";
    }

    /**
     * Get the cache key for relationship data.
     */
    public function getRelationshipCacheKey(string $relationship): string
    {
        return 'admin_panel:'.static::uriKey().':relationship:'.$this->getKey().':'.$relationship;
    }

    /**
     * Get the cache store instance.
     */
    public static function getCacheStore()
    {
        $config = static::getCachingConfig();

        return $config['store'] ? Cache::store($config['store']) : Cache::store();
    }

    /**
     * Get cache tags for this resource.
     */
    public static function getCacheTags(): array
    {
        $config = static::getCachingConfig();
        $tags = $config['tags'];

        // Add default tags
        $tags[] = 'admin_panel';
        $tags[] = 'admin_panel:'.static::uriKey();

        return array_unique($tags);
    }

    /**
     * Cache the index results.
     */
    public static function cacheIndex(Request $request, Collection $resources): Collection
    {
        $config = static::getCachingConfig();

        if (! $config['enabled'] || ! $config['index']) {
            return $resources;
        }

        $cacheKey = static::getIndexCacheKey($request);
        $cache = static::getCacheStore();

        if (static::supportsTags()) {
            $cache->tags(static::getCacheTags())->put($cacheKey, $resources, $config['ttl']);
        } else {
            $cache->put($cacheKey, $resources, $config['ttl']);
        }

        return $resources;
    }

    /**
     * Get cached index results.
     */
    public static function getCachedIndex(Request $request): ?Collection
    {
        $config = static::getCachingConfig();

        if (! $config['enabled'] || ! $config['index']) {
            return null;
        }

        $cacheKey = static::getIndexCacheKey($request);
        $cache = static::getCacheStore();

        if (static::supportsTags()) {
            return $cache->tags(static::getCacheTags())->get($cacheKey);
        }

        return $cache->get($cacheKey);
    }

    /**
     * Cache a resource instance.
     */
    public function cacheResource(): static
    {
        $config = static::getCachingConfig();

        if (! $config['enabled'] || ! $config['resources']) {
            return $this;
        }

        $cacheKey = $this->getResourceCacheKey();
        $cache = static::getCacheStore();

        if (static::supportsTags()) {
            $cache->tags(static::getCacheTags())->put($cacheKey, $this, $config['ttl']);
        } else {
            $cache->put($cacheKey, $this, $config['ttl']);
        }

        return $this;
    }

    /**
     * Get a cached resource instance.
     */
    public static function getCachedResource($id): ?static
    {
        $config = static::getCachingConfig();

        if (! $config['enabled'] || ! $config['resources']) {
            return null;
        }

        $cacheKey = 'admin_panel:'.static::uriKey().':resource:'.$id;
        $cache = static::getCacheStore();

        if (static::supportsTags()) {
            return $cache->tags(static::getCacheTags())->get($cacheKey);
        }

        return $cache->get($cacheKey);
    }

    /**
     * Cache field data.
     */
    public function cacheFields(Request $request, array $fields): array
    {
        $config = static::getCachingConfig();

        if (! $config['enabled'] || ! $config['fields']) {
            return $fields;
        }

        $cacheKey = $this->getFieldsCacheKey($request);
        $cache = static::getCacheStore();

        if (static::supportsTags()) {
            $cache->tags(static::getCacheTags())->put($cacheKey, $fields, $config['ttl']);
        } else {
            $cache->put($cacheKey, $fields, $config['ttl']);
        }

        return $fields;
    }

    /**
     * Get cached field data.
     */
    public function getCachedFields(Request $request): ?array
    {
        $config = static::getCachingConfig();

        if (! $config['enabled'] || ! $config['fields']) {
            return null;
        }

        $cacheKey = $this->getFieldsCacheKey($request);
        $cache = static::getCacheStore();

        if (static::supportsTags()) {
            return $cache->tags(static::getCacheTags())->get($cacheKey);
        }

        return $cache->get($cacheKey);
    }

    /**
     * Cache relationship data.
     */
    public function cacheRelationship(string $relationship, $data): mixed
    {
        $config = static::getCachingConfig();

        if (! $config['enabled'] || ! $config['relationships']) {
            return $data;
        }

        $cacheKey = $this->getRelationshipCacheKey($relationship);
        $cache = static::getCacheStore();

        if (static::supportsTags()) {
            $cache->tags(static::getCacheTags())->put($cacheKey, $data, $config['ttl']);
        } else {
            $cache->put($cacheKey, $data, $config['ttl']);
        }

        return $data;
    }

    /**
     * Get cached relationship data.
     */
    public function getCachedRelationship(string $relationship): mixed
    {
        $config = static::getCachingConfig();

        if (! $config['enabled'] || ! $config['relationships']) {
            return null;
        }

        $cacheKey = $this->getRelationshipCacheKey($relationship);
        $cache = static::getCacheStore();

        if (static::supportsTags()) {
            return $cache->tags(static::getCacheTags())->get($cacheKey);
        }

        return $cache->get($cacheKey);
    }

    /**
     * Clear all cache for this resource.
     */
    public static function clearCache(): void
    {
        $config = static::getCachingConfig();

        if (! $config['enabled']) {
            return;
        }

        $cache = static::getCacheStore();

        if (static::supportsTags()) {
            $cache->tags(static::getCacheTags())->flush();
        } else {
            // Fallback: clear specific patterns (less efficient)
            static::clearCacheByPattern();
        }
    }

    /**
     * Clear cache for a specific resource.
     */
    public function clearResourceCache(): void
    {
        $config = static::getCachingConfig();

        if (! $config['enabled']) {
            return;
        }

        $cache = static::getCacheStore();
        $patterns = [
            $this->getResourceCacheKey(),
            $this->getFieldsCacheKey(request()),
        ];

        foreach ($patterns as $pattern) {
            if (static::supportsTags()) {
                $cache->tags(static::getCacheTags())->forget($pattern);
            } else {
                $cache->forget($pattern);
            }
        }

        // Clear index cache as it may contain this resource
        static::clearIndexCache();
    }

    /**
     * Clear index cache.
     */
    public static function clearIndexCache(): void
    {
        $config = static::getCachingConfig();

        if (! $config['enabled']) {
            return;
        }

        $cache = static::getCacheStore();

        if (static::supportsTags()) {
            $cache->tags(['admin_panel:'.static::uriKey()])->flush();
        }
    }

    /**
     * Clear cache by pattern (fallback for stores without tag support).
     */
    protected static function clearCacheByPattern(): void
    {
        // This is a simplified implementation
        // In production, you might want to use a more sophisticated pattern matching
        $cache = static::getCacheStore();
        $prefix = 'admin_panel:'.static::uriKey();

        // Note: This is a basic implementation
        // Some cache stores may not support pattern-based clearing
        if (method_exists($cache, 'flush')) {
            $cache->flush();
        }
    }

    /**
     * Check if the cache store supports tags.
     */
    public static function supportsTags(): bool
    {
        $cache = static::getCacheStore();

        return method_exists($cache, 'tags');
    }

    /**
     * Get cache statistics for this resource.
     */
    public static function getCacheStats(): array
    {
        $config = static::getCachingConfig();

        return [
            'enabled' => $config['enabled'],
            'ttl' => $config['ttl'],
            'store' => $config['store'] ?? 'default',
            'tags' => static::getCacheTags(),
            'supports_tags' => static::supportsTags(),
            'cache_index' => $config['index'],
            'cache_resources' => $config['resources'],
            'cache_fields' => $config['fields'],
            'cache_relationships' => $config['relationships'],
        ];
    }

    /**
     * Warm up the cache for this resource.
     */
    public static function warmCache(Request $request): void
    {
        $config = static::getCachingConfig();

        if (! $config['enabled']) {
            return;
        }

        // Warm up index cache
        if ($config['index']) {
            $resources = static::getResources($request);
            static::cacheIndex($request, $resources);
        }
    }
}
