<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Metrics\Concerns\HasAdvancedCaching;
use JTD\AdminPanel\Metrics\Metric;
use JTD\AdminPanel\Metrics\MetricCacheManager;
use JTD\AdminPanel\Metrics\ValueResult;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Metric Caching Tests.
 *
 * Comprehensive tests for the enhanced metric caching system including:
 * - Cache key generation with range support
 * - Cache invalidation methods
 * - Cache warming capabilities
 * - Different cache stores support
 * - Performance monitoring
 * - Advanced caching trait functionality
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MetricCachingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear all caches
        Cache::flush();
    }

    public function test_enhanced_cache_key_generation(): void
    {
        $metric = new TestCacheMetric;
        $request = new Request(['range' => 30, 'timezone' => 'America/New_York']);

        $cacheKey = $metric->getPublicCacheKey($request);

        $this->assertStringContains('admin_panel_metric_testcachemetric', $cacheKey);
        $this->assertStringContains('30', $cacheKey);
        $this->assertIsString($cacheKey);
    }

    public function test_cache_key_includes_suffix(): void
    {
        $metric = new TestCacheMetric;
        $request = new Request(['range' => 30]);

        $cacheKey = $metric->getPublicCacheKey($request, 'custom_suffix');

        $this->assertStringContains('custom_suffix', $cacheKey);
    }

    public function test_cache_key_includes_user_when_configured(): void
    {
        $metric = new TestUserSpecificCacheMetric;
        $user = $this->createMockUser(123);
        $request = new Request(['range' => 30]);
        $request->setUserResolver(fn () => $user);

        $cacheKey = $metric->getPublicCacheKey($request);

        $this->assertStringContains('user_123', $cacheKey);
    }

    public function test_cache_ttl_from_cache_for_method(): void
    {
        $metric = new TestCacheMetric;
        $metric->setCacheDuration(1800); // 30 minutes

        $ttl = $metric->getPublicCacheTtl();

        $this->assertEquals(1800, $ttl);
    }

    public function test_cache_ttl_falls_back_to_config(): void
    {
        config(['admin-panel.performance.cache_ttl' => 7200]);

        $metric = new TestCacheMetric;

        $ttl = $metric->getPublicCacheTtl();

        $this->assertEquals(7200, $ttl);
    }

    public function test_cache_store_configuration(): void
    {
        $metric = new TestCacheMetric;

        $store = $metric->getPublicCacheStore();

        $this->assertEquals('array', $store);
    }

    public function test_cache_invalidation_specific_key(): void
    {
        $metric = new TestCacheMetric;
        $request = new Request(['range' => 30]);
        $cacheKey = $metric->getPublicCacheKey($request);

        // Cache some data
        Cache::store('array')->put($cacheKey, 'test_data', 3600);
        $this->assertTrue(Cache::store('array')->has($cacheKey));

        // Invalidate specific cache
        $result = $metric->invalidateCache($request);

        $this->assertTrue($result);
        $this->assertFalse(Cache::store('array')->has($cacheKey));
    }

    public function test_cache_warming_with_ranges(): void
    {
        $metric = new TestCacheMetric;

        $results = $metric->warmCache([30, 60, 90]);

        $this->assertIsArray($results);
        $this->assertCount(3, $results);

        foreach ($results as $range => $result) {
            $this->assertArrayHasKey('status', $result);
            $this->assertContains($result['status'], ['warmed', 'already_cached', 'error']);
        }
    }

    public function test_advanced_caching_trait_cache_store_configuration(): void
    {
        $metric = new TestAdvancedCacheMetric;

        $metric->cacheStore('redis');
        $this->assertEquals('redis', $metric->getCacheStore());
    }

    public function test_advanced_caching_trait_ttl_configuration(): void
    {
        $metric = new TestAdvancedCacheMetric;

        $metric->cacheTtl(1800);
        $this->assertEquals(1800, $metric->getCacheTtl());

        $metric->cacheForMinutes(30);
        $this->assertEquals(1800, $metric->getCacheTtl());

        $metric->cacheForHours(2);
        $this->assertEquals(7200, $metric->getCacheTtl());
    }

    public function test_advanced_caching_trait_per_user_caching(): void
    {
        $metric = new TestAdvancedCacheMetric;
        $user = $this->createMockUser(456);
        $request = new Request(['range' => 30]);
        $request->setUserResolver(fn () => $user);

        $metric->cachePerUser(true);
        $cacheKey1 = $metric->getPublicEnhancedCacheKey($request);

        // Test with different user
        $user2 = $this->createMockUser(789);
        $request2 = new Request(['range' => 30]);
        $request2->setUserResolver(fn () => $user2);
        $cacheKey2 = $metric->getPublicEnhancedCacheKey($request2);

        // Cache keys should be different for different users
        $this->assertNotEquals($cacheKey1, $cacheKey2);
    }

    public function test_advanced_caching_trait_cache_key_prefix(): void
    {
        $metric = new TestAdvancedCacheMetric;
        $request = new Request(['range' => 30]);

        $metric->cacheKeyPrefix('custom_prefix');
        $cacheKey = $metric->getPublicEnhancedCacheKey($request);

        $this->assertStringContains('custom_prefix', $cacheKey);
    }

    public function test_advanced_caching_trait_cache_tags(): void
    {
        $metric = new TestAdvancedCacheMetric;

        $metric->cacheTags(['metrics', 'dashboard']);
        $metric->addCacheTag('performance');

        $this->assertContains('metrics', $metric->getCacheTags());
        $this->assertContains('dashboard', $metric->getCacheTags());
        $this->assertContains('performance', $metric->getCacheTags());
    }

    public function test_advanced_caching_trait_remember_functionality(): void
    {
        $metric = new TestAdvancedCacheMetric;
        $key = 'test_remember_key';
        $callbackExecuted = false;

        $result = $metric->publicRemember($key, function () use (&$callbackExecuted) {
            $callbackExecuted = true;

            return 'cached_result';
        }, 3600);

        $this->assertEquals('cached_result', $result);
        $this->assertTrue($callbackExecuted);

        // Second call should use cache
        $callbackExecuted = false;
        $result2 = $metric->publicRemember($key, function () use (&$callbackExecuted) {
            $callbackExecuted = true;

            return 'new_result';
        }, 3600);

        $this->assertEquals('cached_result', $result2);
        $this->assertFalse($callbackExecuted);
    }

    public function test_metric_cache_manager_basic_operations(): void
    {
        $manager = new MetricCacheManager('array');

        // Test put and get
        $result = $manager->put('test_key', 'test_value', 3600);
        $this->assertTrue($result);

        $value = $manager->get('test_key');
        $this->assertEquals('test_value', $value);

        // Test forget
        $result = $manager->forget('test_key');
        $this->assertTrue($result);

        $value = $manager->get('test_key');
        $this->assertNull($value);
    }

    public function test_metric_cache_manager_statistics(): void
    {
        $manager = new MetricCacheManager('array');

        // Generate some cache operations
        $manager->get('non_existent_key'); // Miss
        $manager->put('test_key', 'value', 3600); // Write
        $manager->get('test_key'); // Hit
        $manager->forget('test_key'); // Delete

        $stats = $manager->getStats();

        $this->assertEquals(1, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
        $this->assertEquals(1, $stats['writes']);
        $this->assertEquals(1, $stats['deletes']);
        $this->assertEquals(0.5, $stats['hit_ratio']);
        $this->assertEquals(4, $stats['total_operations']);
    }

    public function test_metric_cache_manager_remember_functionality(): void
    {
        $manager = new MetricCacheManager('array');
        $callbackExecuted = false;

        $result = $manager->remember('remember_key', 3600, function () use (&$callbackExecuted) {
            $callbackExecuted = true;

            return 'remembered_value';
        });

        $this->assertEquals('remembered_value', $result);
        $this->assertTrue($callbackExecuted);

        // Second call should use cache
        $callbackExecuted = false;
        $result2 = $manager->remember('remember_key', 3600, function () use (&$callbackExecuted) {
            $callbackExecuted = true;

            return 'new_value';
        });

        $this->assertEquals('remembered_value', $result2);
        $this->assertFalse($callbackExecuted);
    }

    public function test_metric_cache_manager_warm_metric(): void
    {
        $manager = new MetricCacheManager('array');
        $metric = new TestCacheMetric;

        $parameterSets = [
            ['range' => 30],
            ['range' => 60],
            ['range' => 90],
        ];

        $results = $manager->warmMetric($metric, $parameterSets);

        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertArrayHasKey('status', $result);
            $this->assertArrayHasKey('params', $result);
            $this->assertContains($result['status'], ['warmed', 'already_cached', 'error']);
        }
    }

    public function test_metric_cache_manager_invalidate_metric(): void
    {
        $manager = new MetricCacheManager('array');

        // Cache some data for the metric
        $manager->put('admin_panel_metric_testcachemetric_30_hash1', 'data1', 3600);
        $manager->put('admin_panel_metric_testcachemetric_60_hash2', 'data2', 3600);
        $manager->put('admin_panel_metric_othertestmetric_30_hash3', 'data3', 3600);

        // Invalidate specific metric
        $result = $manager->invalidateMetric(TestCacheMetric::class);

        $this->assertTrue($result);

        // Other metric cache should still exist
        $this->assertNotNull($manager->get('admin_panel_metric_othertestmetric_30_hash3'));
    }

    public function test_metric_cache_manager_reset_stats(): void
    {
        $manager = new MetricCacheManager('array');

        // Generate some operations
        $manager->get('test_key');
        $manager->put('test_key', 'value', 3600);

        $stats = $manager->getStats();
        $this->assertGreaterThan(0, $stats['total_operations']);

        // Reset stats
        $manager->resetStats();
        $newStats = $manager->getStats();

        $this->assertEquals(0, $newStats['hits']);
        $this->assertEquals(0, $newStats['misses']);
        $this->assertEquals(0, $newStats['writes']);
        $this->assertEquals(0, $newStats['deletes']);
        $this->assertEquals(0, $newStats['total_operations']);
    }

    public function test_metric_cache_manager_analyze_performance(): void
    {
        $manager = new MetricCacheManager('array');

        $analysis = $manager->analyzeCachePerformance();

        $this->assertArrayHasKey('stats', $analysis);
        $this->assertArrayHasKey('memory_usage', $analysis);
        $this->assertArrayHasKey('recommendations', $analysis);
        $this->assertIsArray($analysis['recommendations']);
    }

    protected function createMockUser(int $id): object
    {
        return (object) ['id' => $id];
    }
}

/**
 * Test metric for caching functionality.
 */
class TestCacheMetric extends Metric
{
    protected ?int $cacheDuration = null;

    public function calculate(Request $request): ValueResult
    {
        return new ValueResult(100);
    }

    public function setCacheDuration(int $seconds): void
    {
        $this->cacheDuration = $seconds;
    }

    public function cacheFor(): ?int
    {
        return $this->cacheDuration;
    }

    public function ranges(): array
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
        ];
    }

    // Public methods for testing protected methods
    public function getPublicCacheKey(Request $request, string $suffix = ''): string
    {
        return $this->getCacheKey($request, $suffix);
    }

    public function getPublicCacheTtl(): int
    {
        return $this->getCacheTtl();
    }

    public function getPublicCacheStore(): string
    {
        return $this->getCacheStore();
    }

    protected function getCacheStore(): string
    {
        return 'array';
    }
}

/**
 * Test metric with user-specific caching.
 */
class TestUserSpecificCacheMetric extends Metric
{
    public function calculate(Request $request): ValueResult
    {
        return new ValueResult(200);
    }

    protected function shouldIncludeUserInCacheKey(): bool
    {
        return true;
    }

    public function ranges(): array
    {
        return [30 => '30 Days'];
    }

    // Public methods for testing protected methods
    public function getPublicCacheKey(Request $request, string $suffix = ''): string
    {
        return $this->getCacheKey($request, $suffix);
    }
}

/**
 * Test metric with advanced caching trait.
 */
class TestAdvancedCacheMetric extends Metric
{
    use HasAdvancedCaching;

    public function calculate(Request $request): ValueResult
    {
        return new ValueResult(300);
    }

    public function ranges(): array
    {
        return [30 => '30 Days'];
    }

    public function getCacheStore(): string
    {
        return $this->cacheStore ?? 'array';
    }

    public function getCacheTtl(): int
    {
        return $this->cacheTtl ?? 3600;
    }

    public function getCacheTags(): array
    {
        return $this->cacheTags;
    }

    // Public methods for testing protected methods
    public function getPublicEnhancedCacheKey(Request $request, array $additionalParams = []): string
    {
        return $this->getEnhancedCacheKey($request, $additionalParams);
    }

    public function publicRemember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return $this->remember($key, $callback, $ttl);
    }
}
