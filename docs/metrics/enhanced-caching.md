# Enhanced Metric Caching System

The AdminPanel metrics system includes a comprehensive caching layer designed to optimize dashboard performance and provide flexible cache management capabilities.

## Overview

The enhanced caching system provides:

- **Configurable Cache Stores**: Support for Redis, file, database, and array cache stores
- **Per-Metric Cache TTL**: Individual cache duration configuration for each metric
- **Advanced Cache Key Generation**: Range, timezone, and user-aware cache keys
- **Cache Invalidation**: Pattern-based and targeted cache invalidation
- **Cache Warming**: Proactive cache population for improved performance
- **Performance Monitoring**: Cache hit ratios, memory usage, and statistics
- **User-Specific Caching**: Per-user cache isolation when needed

## Basic Caching

### Default Caching Behavior

All metrics inherit basic caching functionality from the base `Metric` class:

```php
<?php

namespace App\Metrics;

use Illuminate\Http\Request;
use JTD\AdminPanel\Metrics\Value;
use JTD\AdminPanel\Metrics\ValueResult;

class RevenueMetric extends Value
{
    public string $name = 'Total Revenue';

    public function calculate(Request $request): ValueResult
    {
        return $this->sum($request, Order::class, 'total');
    }

    /**
     * Cache results for 30 minutes.
     */
    public function cacheFor(): int
    {
        return 1800; // 30 minutes in seconds
    }
}
```

### Cache Key Generation

Cache keys are automatically generated with range and timezone awareness:

```php
// Cache key includes metric class, range, and timezone
// Example: admin_panel_metric_revenuemetric_30_a1b2c3d4
$cacheKey = $metric->getCacheKey($request);
```

### Cache TTL Configuration

Configure cache duration using the `cacheFor()` method:

```php
public function cacheFor(): int
{
    return 3600; // 1 hour
}

// Or use DateInterval
public function cacheFor(): DateInterval
{
    return new DateInterval('PT1H'); // 1 hour
}

// Or use DateTime
public function cacheFor(): DateTime
{
    return now()->addHours(2); // 2 hours from now
}
```

## Advanced Caching with HasAdvancedCaching Trait

For more sophisticated caching needs, use the `HasAdvancedCaching` trait:

```php
<?php

namespace App\Metrics;

use Illuminate\Http\Request;
use JTD\AdminPanel\Metrics\Concerns\HasAdvancedCaching;
use JTD\AdminPanel\Metrics\Value;
use JTD\AdminPanel\Metrics\ValueResult;

class AdvancedRevenueMetric extends Value
{
    use HasAdvancedCaching;

    public string $name = 'Advanced Revenue';

    public function calculate(Request $request): ValueResult
    {
        $cacheKey = $this->getEnhancedCacheKey($request);
        
        return $this->remember($cacheKey, function () use ($request) {
            return $this->sum($request, Order::class, 'total');
        });
    }

    public function __construct()
    {
        $this->cacheStore('redis')
             ->cacheForHours(2)
             ->cacheTags(['revenue', 'orders'])
             ->cacheKeyPrefix('advanced_revenue');
    }
}
```

### Advanced Caching Configuration

```php
// Configure cache store
$metric->cacheStore('redis');

// Configure TTL
$metric->cacheTtl(3600);           // Seconds
$metric->cacheForMinutes(30);      // Minutes
$metric->cacheForHours(2);         // Hours

// Per-user caching
$metric->cachePerUser(true);

// Custom cache key prefix
$metric->cacheKeyPrefix('custom_prefix');

// Cache tags (for tagged cache stores)
$metric->cacheTags(['metrics', 'dashboard']);
$metric->addCacheTag('performance');
```

### Advanced Cache Operations

```php
// Remember with custom TTL
$result = $metric->remember($key, function () {
    return $this->expensiveCalculation();
}, 7200);

// Remember forever
$result = $metric->rememberForever($key, function () {
    return $this->staticData();
});

// Cache with timestamp metadata
$result = $metric->cacheWithTimestamp($key, $data);

// Check cache freshness
$isFresh = $metric->isCacheFresh($key, $dataTimestamp);
```

## Cache Management with MetricCacheManager

The `MetricCacheManager` provides centralized cache operations:

```php
use JTD\AdminPanel\Metrics\MetricCacheManager;

$cacheManager = new MetricCacheManager('redis');

// Basic operations
$cacheManager->put('key', $value, 3600);
$value = $cacheManager->get('key');
$cacheManager->forget('key');

// Remember functionality
$result = $cacheManager->remember('key', 3600, function () {
    return $this->expensiveOperation();
});
```

### Cache Warming

Proactively populate cache for better performance:

```php
// Warm cache for a single metric
$results = $cacheManager->warmMetric($metric, [
    ['range' => 30],
    ['range' => 60],
    ['range' => 90],
]);

// Warm cache for multiple metrics
$results = $cacheManager->warmMetrics([
    RevenueMetric::class,
    OrderCountMetric::class,
    CustomerMetric::class,
]);

// Warm with custom parameter sets
$parameterSets = [
    ['range' => 30, 'timezone' => 'UTC'],
    ['range' => 30, 'timezone' => 'America/New_York'],
    ['range' => 60, 'timezone' => 'UTC'],
];

$results = $cacheManager->warmMetric($metric, $parameterSets);
```

### Cache Invalidation

```php
// Invalidate specific metric
$cacheManager->invalidateMetric(RevenueMetric::class);

// Invalidate by pattern
$cacheManager->invalidatePattern('admin_panel_metric_revenue_*');

// Invalidate all metrics
$cacheManager->invalidateAll();

// Invalidate specific cache key
$metric->invalidateCache($request);

// Invalidate all cache for a metric
$metric->invalidateAllCache();
```

## Performance Monitoring

### Cache Statistics

```php
// Get cache statistics
$stats = $cacheManager->getStats();
/*
[
    'hits' => 150,
    'misses' => 25,
    'writes' => 30,
    'deletes' => 5,
    'hit_ratio' => 0.857,
    'total_operations' => 210,
]
*/

// Reset statistics
$cacheManager->resetStats();
```

### Memory Usage Analysis

```php
// Get memory usage information
$usage = $cacheManager->getMemoryUsage();
/*
[
    'total_keys' => 45,
    'total_memory' => 2048576, // bytes
    'by_metric' => [
        'RevenueMetric' => [
            'keys' => 12,
            'memory' => 524288,
        ],
        'OrderCountMetric' => [
            'keys' => 8,
            'memory' => 262144,
        ],
    ],
]
*/
```

### Performance Analysis

```php
// Analyze cache performance
$analysis = $cacheManager->analyzeCachePerformance();
/*
[
    'stats' => [...],
    'memory_usage' => [...],
    'recommendations' => [
        'Low cache hit ratio. Consider increasing cache TTL.',
        'High memory usage. Consider reducing cache TTL.',
    ],
]
*/
```

## Configuration

### Environment Configuration

Configure caching in your `.env` file:

```env
# Default cache store for metrics
ADMIN_PANEL_CACHE_STORE=redis

# Default cache TTL (seconds)
ADMIN_PANEL_CACHE_TTL=3600

# Cache timezones for warming
ADMIN_PANEL_CACHE_TIMEZONES=UTC,America/New_York,Europe/London
```

### Config File

Add to `config/admin-panel.php`:

```php
return [
    'performance' => [
        'cache_store' => env('ADMIN_PANEL_CACHE_STORE', 'default'),
        'cache_ttl' => env('ADMIN_PANEL_CACHE_TTL', 3600),
        'cache_timezones' => explode(',', env('ADMIN_PANEL_CACHE_TIMEZONES', 'UTC')),
    ],
];
```

## Best Practices

### Cache TTL Guidelines

- **Real-time metrics**: 1-5 minutes
- **Hourly metrics**: 15-30 minutes  
- **Daily metrics**: 1-4 hours
- **Historical metrics**: 12-24 hours
- **Static data**: Cache forever with manual invalidation

### Cache Store Selection

- **Redis**: Best for high-traffic applications with multiple servers
- **File**: Good for single-server applications
- **Database**: When Redis is not available
- **Array**: Only for testing

### User-Specific Caching

Enable per-user caching only when necessary:

```php
// Enable for user-specific data
$metric->cachePerUser(true);

// Or override in metric class
protected function shouldIncludeUserInCacheKey(): bool
{
    return $this->hasUserSpecificData();
}
```

### Cache Warming Strategy

```php
// Warm cache during off-peak hours
Schedule::command('metrics:warm-cache')->hourly();

// Warm cache after data updates
Event::listen(OrderCreated::class, function () {
    app(MetricCacheManager::class)->invalidateMetric(RevenueMetric::class);
});
```

### Memory Management

```php
// Monitor cache memory usage
$usage = $cacheManager->getMemoryUsage();

if ($usage['total_memory'] > 100 * 1024 * 1024) { // 100MB
    // Implement cache cleanup strategy
    $cacheManager->invalidatePattern('admin_panel_metric_*_old_*');
}
```

## Troubleshooting

### Common Issues

1. **Cache not working**: Check cache store configuration
2. **High memory usage**: Reduce TTL or implement cleanup
3. **Low hit ratio**: Increase TTL or improve warming strategy
4. **Stale data**: Implement proper invalidation on data changes

### Debug Cache Keys

```php
// Log cache keys for debugging
Log::info('Cache key generated', [
    'metric' => get_class($metric),
    'key' => $metric->getCacheKey($request),
    'range' => $request->get('range'),
    'timezone' => $request->get('timezone'),
]);
```

### Performance Testing

```php
// Measure cache performance
$start = microtime(true);
$result = $metric->calculate($request);
$duration = microtime(true) - $start;

Log::info('Metric calculation time', [
    'metric' => get_class($metric),
    'duration' => $duration,
    'cached' => $cacheManager->getStats()['hits'] > 0,
]);
```

The enhanced caching system provides powerful tools for optimizing dashboard performance while maintaining flexibility and ease of use. Proper configuration and monitoring ensure optimal performance for your specific use case.
