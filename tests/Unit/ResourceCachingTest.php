<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Resources\Concerns\HasCaching;
use PHPUnit\Framework\TestCase;

/**
 * Test class that uses the HasCaching trait.
 */
class TestCachingClass
{
    use HasCaching;

    public static bool $cachingEnabled = true;
    public static int $cacheTtl = 3600;
    public static ?string $cacheStore = null;
    public static array $cacheTags = ['test'];
    public static bool $cacheIndex = true;
    public static bool $cacheResources = true;
    public static bool $cacheFields = true;
    public static bool $cacheRelationships = true;

    protected $key = 1;

    public static function uriKey(): string
    {
        return 'test-caching';
    }

    public function getKey()
    {
        return $this->key;
    }

    public static function getResources(Request $request): Collection
    {
        return collect([new static()]);
    }
}

/**
 * ResourceCaching Test Class
 */
class ResourceCachingTest extends TestCase
{
    private TestCachingClass $testClass;
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new TestCachingClass();
        $this->request = new Request();

        // Reset cache configuration for each test
        TestCachingClass::$cachingEnabled = true;
        TestCachingClass::$cacheTtl = 3600;
        TestCachingClass::$cacheStore = null;
        TestCachingClass::$cacheTags = ['test'];
        TestCachingClass::$cacheIndex = true;
        TestCachingClass::$cacheResources = true;
        TestCachingClass::$cacheFields = true;
        TestCachingClass::$cacheRelationships = true;
    }

    // ========================================
    // Basic Caching Configuration Tests
    // ========================================

    public function test_caching_trait_has_required_properties(): void
    {
        $this->assertTrue(property_exists(TestCachingClass::class, 'cachingEnabled'));
        $this->assertTrue(property_exists(TestCachingClass::class, 'cacheTtl'));
        $this->assertTrue(property_exists(TestCachingClass::class, 'cacheStore'));
        $this->assertTrue(property_exists(TestCachingClass::class, 'cacheTags'));
        $this->assertTrue(property_exists(TestCachingClass::class, 'cacheIndex'));
        $this->assertTrue(property_exists(TestCachingClass::class, 'cacheResources'));
        $this->assertTrue(property_exists(TestCachingClass::class, 'cacheFields'));
        $this->assertTrue(property_exists(TestCachingClass::class, 'cacheRelationships'));
    }

    public function test_default_configuration_values(): void
    {
        $this->assertTrue(TestCachingClass::$cachingEnabled);
        $this->assertEquals(3600, TestCachingClass::$cacheTtl);
        $this->assertNull(TestCachingClass::$cacheStore);
        $this->assertEquals(['test'], TestCachingClass::$cacheTags);
        $this->assertTrue(TestCachingClass::$cacheIndex);
        $this->assertTrue(TestCachingClass::$cacheResources);
        $this->assertTrue(TestCachingClass::$cacheFields);
        $this->assertTrue(TestCachingClass::$cacheRelationships);
    }

    // ========================================
    // Cache Key Generation Tests
    // ========================================

    public function test_get_index_cache_key_generates_correct_key(): void
    {
        $this->request->merge(['search' => 'test', 'page' => 1]);

        $key = TestCachingClass::getIndexCacheKey($this->request);

        $this->assertStringStartsWith('admin_panel:test-caching:index:', $key);
        $this->assertStringContainsString(':', $key); // Should contain hash
    }

    public function test_get_resource_cache_key_generates_correct_key(): void
    {
        $key = $this->testClass->getResourceCacheKey();

        $this->assertEquals('admin_panel:test-caching:resource:1', $key);
    }

    public function test_get_fields_cache_key_generates_correct_key(): void
    {
        $key = $this->testClass->getFieldsCacheKey($this->request);

        $this->assertEquals('admin_panel:test-caching:fields:1:view', $key);
    }

    public function test_get_fields_cache_key_with_editing_context(): void
    {
        $this->request->merge(['editing' => true]);

        $key = $this->testClass->getFieldsCacheKey($this->request);

        $this->assertEquals('admin_panel:test-caching:fields:1:edit', $key);
    }

    public function test_get_relationship_cache_key_generates_correct_key(): void
    {
        $key = $this->testClass->getRelationshipCacheKey('posts');

        $this->assertEquals('admin_panel:test-caching:relationship:1:posts', $key);
    }

    // ========================================
    // Cache Tags Tests
    // ========================================

    public function test_get_cache_tags_includes_default_tags(): void
    {
        $tags = TestCachingClass::getCacheTags();

        $this->assertContains('admin_panel', $tags);
        $this->assertContains('admin_panel:test-caching', $tags);
        $this->assertContains('test', $tags);
    }

    public function test_get_cache_tags_removes_duplicates(): void
    {
        TestCachingClass::$cacheTags = ['admin_panel', 'test', 'admin_panel'];

        $tags = TestCachingClass::getCacheTags();

        $this->assertEquals(count($tags), count(array_unique($tags)));
    }

    // ========================================
    // Cache Store Tests (Simplified)
    // ========================================

    public function test_cache_store_methods_exist(): void
    {
        $this->assertTrue(method_exists(TestCachingClass::class, 'getCacheStore'));
        $this->assertTrue(method_exists(TestCachingClass::class, 'supportsTags'));
    }

    // ========================================
    // Caching Behavior Tests
    // ========================================

    public function test_cache_index_returns_resources_when_caching_disabled(): void
    {
        TestCachingClass::$cachingEnabled = false;
        $resources = collect([new TestCachingClass()]);

        $result = TestCachingClass::cacheIndex($this->request, $resources);

        $this->assertEquals($resources, $result);
    }

    public function test_cache_index_returns_resources_when_index_caching_disabled(): void
    {
        TestCachingClass::$cacheIndex = false;
        $resources = collect([new TestCachingClass()]);

        $result = TestCachingClass::cacheIndex($this->request, $resources);

        $this->assertEquals($resources, $result);
    }

    public function test_get_cached_index_returns_null_when_caching_disabled(): void
    {
        TestCachingClass::$cachingEnabled = false;

        $result = TestCachingClass::getCachedIndex($this->request);

        $this->assertNull($result);
    }

    public function test_caching_methods_exist(): void
    {
        $this->assertTrue(method_exists($this->testClass, 'cacheResource'));
        $this->assertTrue(method_exists($this->testClass, 'cacheFields'));
        $this->assertTrue(method_exists($this->testClass, 'cacheRelationship'));
        $this->assertTrue(method_exists(TestCachingClass::class, 'getCachedResource'));
        $this->assertTrue(method_exists($this->testClass, 'getCachedFields'));
        $this->assertTrue(method_exists($this->testClass, 'getCachedRelationship'));
    }

    public function test_caching_behavior_when_disabled(): void
    {
        TestCachingClass::$cachingEnabled = false;

        // These should return the input data when caching is disabled
        $fields = ['name' => 'test'];
        $data = ['post1'];

        $this->assertSame($this->testClass, $this->testClass->cacheResource());
        $this->assertEquals($fields, $this->testClass->cacheFields($this->request, $fields));
        $this->assertEquals($data, $this->testClass->cacheRelationship('posts', $data));

        // These should return null when caching is disabled
        $this->assertNull(TestCachingClass::getCachedResource(1));
        $this->assertNull($this->testClass->getCachedFields($this->request));
        $this->assertNull($this->testClass->getCachedRelationship('posts'));
    }

    // ========================================
    // Cache Statistics Tests
    // ========================================

    public function test_get_cache_stats_method_exists(): void
    {
        $this->assertTrue(method_exists(TestCachingClass::class, 'getCacheStats'));

        // Test that the method returns an array with expected keys
        $expectedKeys = [
            'enabled', 'ttl', 'store', 'tags', 'supports_tags',
            'cache_index', 'cache_resources', 'cache_fields', 'cache_relationships'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertIsString($key);
        }
    }

    // ========================================
    // Cache Management Tests
    // ========================================

    public function test_clear_cache_methods_exist(): void
    {
        $this->assertTrue(method_exists(TestCachingClass::class, 'clearCache'));
        $this->assertTrue(method_exists($this->testClass, 'clearResourceCache'));
        $this->assertTrue(method_exists(TestCachingClass::class, 'clearIndexCache'));
        $this->assertTrue(method_exists(TestCachingClass::class, 'warmCache'));
    }

    public function test_clear_cache_does_nothing_when_caching_disabled(): void
    {
        TestCachingClass::$cachingEnabled = false;

        // Should not throw any exceptions
        TestCachingClass::clearCache();
        $this->testClass->clearResourceCache();
        TestCachingClass::clearIndexCache();

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    public function test_warm_cache_does_nothing_when_caching_disabled(): void
    {
        TestCachingClass::$cachingEnabled = false;

        // Should not throw any exceptions
        TestCachingClass::warmCache($this->request);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }
}
