<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Menu\Badge;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Tests\TestCase;

class BadgeCachingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
    }

    public function test_badge_caching_is_disabled_by_default(): void
    {
        $callCount = 0;
        $menuItem = MenuItem::make('Test', '/test')
            ->withBadge(function () use (&$callCount) {
                $callCount++;
                return 'Dynamic';
            });

        // Call resolveBadge multiple times
        $menuItem->resolveBadge();
        $menuItem->resolveBadge();
        $menuItem->resolveBadge();

        // Should be called each time (no caching)
        $this->assertEquals(3, $callCount);
    }

    public function test_badge_caching_can_be_enabled(): void
    {
        $callCount = 0;
        $menuItem = MenuItem::make('Test', '/test')
            ->withBadge(function () use (&$callCount) {
                $callCount++;
                return 'Dynamic';
            })
            ->cacheBadge(60); // Cache for 60 seconds

        // Call resolveBadge multiple times
        $result1 = $menuItem->resolveBadge();
        $result2 = $menuItem->resolveBadge();
        $result3 = $menuItem->resolveBadge();

        // Should be called only once (cached)
        $this->assertEquals(1, $callCount);
        $this->assertEquals('Dynamic', $result1);
        $this->assertEquals('Dynamic', $result2);
        $this->assertEquals('Dynamic', $result3);
    }

    public function test_badge_cache_key_includes_menu_item_identifier(): void
    {
        $menuItem1 = MenuItem::make('Test1', '/test1')
            ->withBadge(fn() => 'Badge1')
            ->cacheBadge(60);

        $menuItem2 = MenuItem::make('Test2', '/test2')
            ->withBadge(fn() => 'Badge2')
            ->cacheBadge(60);

        $result1 = $menuItem1->resolveBadge();
        $result2 = $menuItem2->resolveBadge();

        $this->assertEquals('Badge1', $result1);
        $this->assertEquals('Badge2', $result2);
    }

    public function test_badge_cache_respects_ttl(): void
    {
        $callCount = 0;
        $menuItem = MenuItem::make('Test', '/test')
            ->withBadge(function () use (&$callCount) {
                $callCount++;
                return 'Dynamic';
            })
            ->cacheBadge(1); // Cache for 1 second

        // First call
        $menuItem->resolveBadge();
        $this->assertEquals(1, $callCount);

        // Second call (should be cached)
        $menuItem->resolveBadge();
        $this->assertEquals(1, $callCount);

        // Wait for cache to expire
        sleep(2);

        // Third call (should call closure again)
        $menuItem->resolveBadge();
        $this->assertEquals(2, $callCount);
    }

    public function test_badge_cache_with_request_parameter(): void
    {
        $callCount = 0;
        $menuItem = MenuItem::make('Test', '/test')
            ->withBadge(function ($request) use (&$callCount) {
                $callCount++;
                return $request ? 'With Request' : 'Without Request';
            })
            ->cacheBadge(60);

        $request = Request::create('/test');

        // Call with request
        $result1 = $menuItem->resolveBadge($request);
        $result2 = $menuItem->resolveBadge($request);

        // Call without request
        $result3 = $menuItem->resolveBadge();
        $result4 = $menuItem->resolveBadge();

        // Should be called twice (once for each scenario)
        $this->assertEquals(2, $callCount);
        $this->assertEquals('With Request', $result1);
        $this->assertEquals('With Request', $result2);
        $this->assertEquals('Without Request', $result3);
        $this->assertEquals('Without Request', $result4);
    }

    public function test_badge_cache_can_be_cleared(): void
    {
        $callCount = 0;
        $menuItem = MenuItem::make('Test', '/test')
            ->withBadge(function () use (&$callCount) {
                $callCount++;
                return 'Dynamic';
            })
            ->cacheBadge(60);

        // First call
        $menuItem->resolveBadge();
        $this->assertEquals(1, $callCount);

        // Clear cache
        $menuItem->clearBadgeCache();

        // Second call (should call closure again)
        $menuItem->resolveBadge();
        $this->assertEquals(2, $callCount);
    }

    public function test_menu_section_badge_caching(): void
    {
        $callCount = 0;
        $section = MenuSection::make('Test')
            ->withBadge(function () use (&$callCount) {
                $callCount++;
                return 'Section Badge';
            })
            ->cacheBadge(60);

        // Call resolveBadge multiple times
        $result1 = $section->resolveBadge();
        $result2 = $section->resolveBadge();

        $this->assertEquals(1, $callCount);
        $this->assertEquals('Section Badge', $result1);
        $this->assertEquals('Section Badge', $result2);
    }

    public function test_badge_instance_caching(): void
    {
        $callCount = 0;
        $badge = Badge::make(function () use (&$callCount) {
            $callCount++;
            return 'Badge Value';
        })->cache(60);

        $result1 = $badge->resolve();
        $result2 = $badge->resolve();

        $this->assertEquals(1, $callCount);
        $this->assertEquals('Badge Value', $result1);
        $this->assertEquals('Badge Value', $result2);
    }

    public function test_static_badge_values_are_not_cached(): void
    {
        $menuItem = MenuItem::make('Test', '/test')
            ->withBadge('Static Badge')
            ->cacheBadge(60);

        $result1 = $menuItem->resolveBadge();
        $result2 = $menuItem->resolveBadge();

        $this->assertEquals('Static Badge', $result1);
        $this->assertEquals('Static Badge', $result2);
        
        // Should not create cache entries for static values
        $this->assertFalse(Cache::has($menuItem->getBadgeCacheKey()));
    }

    public function test_badge_cache_key_generation(): void
    {
        $menuItem = MenuItem::make('Test Item', '/test/path')
            ->cacheBadge(60);

        $cacheKey = $menuItem->getBadgeCacheKey();

        $this->assertIsString($cacheKey);
        $this->assertStringStartsWith('menu_badge_', $cacheKey);
        $this->assertStringContains(md5('Test Item:/test/path'), $cacheKey);
    }

    public function test_badge_cache_key_with_request(): void
    {
        $menuItem = MenuItem::make('Test', '/test')
            ->cacheBadge(60);

        $request = Request::create('/admin/test');
        $cacheKeyWithRequest = $menuItem->getBadgeCacheKey($request);
        $cacheKeyWithoutRequest = $menuItem->getBadgeCacheKey();

        $this->assertNotEquals($cacheKeyWithRequest, $cacheKeyWithoutRequest);
        $this->assertStringContains('with_request', $cacheKeyWithRequest);
        $this->assertStringContains('without_request', $cacheKeyWithoutRequest);
    }

    public function test_badge_caching_performance_improvement(): void
    {
        // Create a badge with expensive computation
        $menuItem = MenuItem::make('Expensive', '/expensive')
            ->withBadge(function () {
                // Simulate expensive operation
                usleep(10000); // 10ms delay
                return 'Expensive Result';
            })
            ->cacheBadge(60);

        // Measure time for first call (should be slow)
        $start1 = microtime(true);
        $result1 = $menuItem->resolveBadge();
        $time1 = microtime(true) - $start1;

        // Measure time for second call (should be fast due to caching)
        $start2 = microtime(true);
        $result2 = $menuItem->resolveBadge();
        $time2 = microtime(true) - $start2;

        $this->assertEquals('Expensive Result', $result1);
        $this->assertEquals('Expensive Result', $result2);
        
        // Second call should be significantly faster
        $this->assertLessThan($time1 / 2, $time2);
    }
}
