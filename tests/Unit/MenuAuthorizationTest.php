<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Menu\MenuGroup;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Tests\TestCase;

class MenuAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
    }

    public function test_menu_item_authorization_caching(): void
    {
        $callCount = 0;
        $menuItem = MenuItem::make('Test Item', '/test')
            ->canSee(function () use (&$callCount) {
                $callCount++;
                return true;
            })
            ->cacheAuth(60);

        // First call
        $result1 = $menuItem->isVisible();
        $this->assertTrue($result1);
        $this->assertEquals(1, $callCount);

        // Second call (should be cached)
        $result2 = $menuItem->isVisible();
        $this->assertTrue($result2);
        $this->assertEquals(1, $callCount); // Still 1, not called again
    }

    public function test_menu_item_authorization_without_caching(): void
    {
        $callCount = 0;
        $menuItem = MenuItem::make('Test Item', '/test')
            ->canSee(function () use (&$callCount) {
                $callCount++;
                return true;
            });

        // Multiple calls without caching
        $menuItem->isVisible();
        $menuItem->isVisible();
        $menuItem->isVisible();

        $this->assertEquals(3, $callCount); // Called each time
    }

    public function test_menu_section_authorization_caching(): void
    {
        $callCount = 0;
        $section = MenuSection::make('Test Section')
            ->canSee(function () use (&$callCount) {
                $callCount++;
                return true;
            })
            ->cacheAuth(60);

        // First call
        $result1 = $section->isVisible();
        $this->assertTrue($result1);
        $this->assertEquals(1, $callCount);

        // Second call (should be cached)
        $result2 = $section->isVisible();
        $this->assertTrue($result2);
        $this->assertEquals(1, $callCount);
    }

    public function test_menu_group_authorization_caching(): void
    {
        $callCount = 0;
        $group = MenuGroup::make('Test Group')
            ->canSee(function () use (&$callCount) {
                $callCount++;
                return false;
            })
            ->cacheAuth(60);

        // First call
        $result1 = $group->isVisible();
        $this->assertFalse($result1);
        $this->assertEquals(1, $callCount);

        // Second call (should be cached)
        $result2 = $group->isVisible();
        $this->assertFalse($result2);
        $this->assertEquals(1, $callCount);
    }

    public function test_authorization_cache_can_be_cleared(): void
    {
        $callCount = 0;
        $menuItem = MenuItem::make('Test Item', '/test')
            ->canSee(function () use (&$callCount) {
                $callCount++;
                return true;
            })
            ->cacheAuth(60);

        // First call
        $menuItem->isVisible();
        $this->assertEquals(1, $callCount);

        // Clear cache
        $menuItem->clearAuthCache();

        // Second call (should call callback again)
        $menuItem->isVisible();
        $this->assertEquals(2, $callCount);
    }

    public function test_authorization_cache_with_request_parameter(): void
    {
        $callCount = 0;
        $menuItem = MenuItem::make('Test Item', '/test')
            ->canSee(function ($request) use (&$callCount) {
                $callCount++;
                return $request ? true : false;
            })
            ->cacheAuth(60);

        $request = Request::create('/test');

        // Call with request
        $result1 = $menuItem->isVisible($request);
        $this->assertTrue($result1);
        $this->assertEquals(1, $callCount);

        // Call again with same request (should be cached)
        $result2 = $menuItem->isVisible($request);
        $this->assertTrue($result2);
        $this->assertEquals(1, $callCount);

        // Call without request (different scenario, should call again)
        $result3 = $menuItem->isVisible();
        $this->assertFalse($result3);
        $this->assertEquals(2, $callCount);
    }

    public function test_authorization_cache_ttl_expiration(): void
    {
        $callCount = 0;
        $menuItem = MenuItem::make('Test Item', '/test')
            ->canSee(function () use (&$callCount) {
                $callCount++;
                return true;
            })
            ->cacheAuth(1); // 1 second TTL

        // First call
        $menuItem->isVisible();
        $this->assertEquals(1, $callCount);

        // Second call (should be cached)
        $menuItem->isVisible();
        $this->assertEquals(1, $callCount);

        // Wait for cache to expire
        sleep(2);

        // Third call (should call callback again)
        $menuItem->isVisible();
        $this->assertEquals(2, $callCount);
    }

    public function test_authorization_cache_key_generation(): void
    {
        $menuItem1 = MenuItem::make('Item 1', '/item1')
            ->canSee(fn() => true)
            ->cacheAuth(60);

        $menuItem2 = MenuItem::make('Item 2', '/item2')
            ->canSee(fn() => true)
            ->cacheAuth(60);

        $cacheKey1 = $menuItem1->getAuthCacheKey();
        $cacheKey2 = $menuItem2->getAuthCacheKey();

        $this->assertNotEquals($cacheKey1, $cacheKey2);
        $this->assertStringStartsWith('menu_auth_', $cacheKey1);
        $this->assertStringStartsWith('menu_auth_', $cacheKey2);
    }

    public function test_authorization_performance_improvement(): void
    {
        // Create a menu item with expensive authorization check
        $menuItem = MenuItem::make('Expensive Item', '/expensive')
            ->canSee(function () {
                // Simulate expensive operation
                usleep(10000); // 10ms delay
                return true;
            })
            ->cacheAuth(60);

        // Measure time for first call (should be slow)
        $start1 = microtime(true);
        $result1 = $menuItem->isVisible();
        $time1 = microtime(true) - $start1;

        // Measure time for second call (should be fast due to caching)
        $start2 = microtime(true);
        $result2 = $menuItem->isVisible();
        $time2 = microtime(true) - $start2;

        $this->assertTrue($result1);
        $this->assertTrue($result2);
        
        // Second call should be significantly faster
        $this->assertLessThan($time1 / 2, $time2);
    }

    public function test_authorization_caching_with_different_callbacks(): void
    {
        $callCount1 = 0;
        $callCount2 = 0;

        $menuItem1 = MenuItem::make('Item 1', '/item1')
            ->canSee(function () use (&$callCount1) {
                $callCount1++;
                return true;
            })
            ->cacheAuth(60);

        $menuItem2 = MenuItem::make('Item 1', '/item1') // Same label and URL
            ->canSee(function () use (&$callCount2) {
                $callCount2++;
                return false;
            })
            ->cacheAuth(60);

        // Both should be called independently due to different callbacks
        $result1 = $menuItem1->isVisible();
        $result2 = $menuItem2->isVisible();

        $this->assertTrue($result1);
        $this->assertFalse($result2);
        $this->assertEquals(1, $callCount1);
        $this->assertEquals(1, $callCount2);

        // Second calls should be cached
        $menuItem1->isVisible();
        $menuItem2->isVisible();

        $this->assertEquals(1, $callCount1); // Still 1
        $this->assertEquals(1, $callCount2); // Still 1
    }

    public function test_authorization_caching_fluent_interface(): void
    {
        $menuItem = MenuItem::make('Fluent Item', '/fluent')
            ->canSee(fn() => true)
            ->cacheAuth(60)
            ->withIcon('test')
            ->withBadge('Test', 'info');

        $this->assertTrue($menuItem->isVisible());
        $this->assertEquals('test', $menuItem->icon);
        $this->assertEquals('Test', $menuItem->badge);
    }

    public function test_section_and_group_authorization_cache_keys(): void
    {
        $section = MenuSection::make('Test Section')
            ->canSee(fn() => true)
            ->cacheAuth(60);

        $group = MenuGroup::make('Test Group')
            ->canSee(fn() => true)
            ->cacheAuth(60);

        $sectionKey = $section->getAuthCacheKey();
        $groupKey = $group->getAuthCacheKey();

        $this->assertStringStartsWith('menu_section_auth_', $sectionKey);
        $this->assertStringStartsWith('menu_group_auth_', $groupKey);
        $this->assertNotEquals($sectionKey, $groupKey);
    }
}
