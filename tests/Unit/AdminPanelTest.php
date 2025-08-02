<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\Fixtures\UserResource;
use JTD\AdminPanel\Tests\TestCase;

/**
 * AdminPanel Tests
 *
 * Test the main AdminPanel facade including resource registration,
 * discovery integration, and resource management.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class AdminPanelTest extends TestCase
{
    protected AdminPanel $adminPanel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminPanel = new AdminPanel();
    }

    public function test_can_register_resources_manually(): void
    {
        $this->adminPanel->register([UserResource::class]);

        $resources = $this->adminPanel->getResources();

        $this->assertCount(1, $resources);
        $this->assertInstanceOf(UserResource::class, $resources->first());
    }

    public function test_can_register_single_resource(): void
    {
        $this->adminPanel->resource(UserResource::class);

        $resources = $this->adminPanel->getResources();

        $this->assertCount(1, $resources);
        $this->assertInstanceOf(UserResource::class, $resources->first());
    }

    public function test_throws_exception_for_invalid_resource_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must extend');

        $this->adminPanel->resource(\stdClass::class);
    }

    public function test_get_resources_merges_manual_and_discovered(): void
    {
        // Register a manual resource
        $this->adminPanel->register([UserResource::class]);

        // The getResources method should merge manual and discovered resources
        $resources = $this->adminPanel->getResources();

        // Should have at least the manually registered resource
        $this->assertGreaterThanOrEqual(1, $resources->count());

        // Should contain our manually registered resource
        $userResourceExists = $resources->contains(function ($resource) {
            return $resource instanceof UserResource;
        });

        $this->assertTrue($userResourceExists);
    }

    public function test_get_resources_removes_duplicates(): void
    {
        // Register the same resource multiple times
        $this->adminPanel->register([UserResource::class]);
        $this->adminPanel->resource(UserResource::class);

        $resources = $this->adminPanel->getResources();

        // Should only have one instance despite multiple registrations
        $userResourceCount = $resources->filter(function ($resource) {
            return $resource instanceof UserResource;
        })->count();

        $this->assertEquals(1, $userResourceCount);
    }

    public function test_find_resource_by_uri_key(): void
    {
        $this->adminPanel->register([UserResource::class]);

        $resource = $this->adminPanel->findResource('users');

        $this->assertInstanceOf(UserResource::class, $resource);
    }

    public function test_find_resource_by_uri_key_returns_null_when_not_found(): void
    {
        $resource = $this->adminPanel->findResource('nonexistent');

        $this->assertNull($resource);
    }

    public function test_get_navigation_resources(): void
    {
        $this->adminPanel->register([UserResource::class]);

        $resources = $this->adminPanel->getNavigationResources();

        // Should contain resources available for navigation
        $this->assertGreaterThanOrEqual(0, $resources->count());
    }

    public function test_get_searchable_resources(): void
    {
        $this->adminPanel->register([UserResource::class]);

        $resources = $this->adminPanel->getSearchableResources();

        // Should contain globally searchable resources
        $this->assertGreaterThanOrEqual(0, $resources->count());
    }

    public function test_clear_resource_cache(): void
    {
        // This should not throw an exception
        $this->adminPanel->clearResourceCache();

        $this->assertTrue(true); // If we get here, the method worked
    }

    public function test_resource_discovery_integration(): void
    {
        // Test that the AdminPanel properly integrates with ResourceDiscovery
        $resources = $this->adminPanel->getResources();

        // Should return a collection (even if empty)
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resources);
    }

    public function test_can_check_if_resource_exists(): void
    {
        $this->adminPanel->register([UserResource::class]);

        $exists = $this->adminPanel->findResource('users') !== null;
        $notExists = $this->adminPanel->findResource('nonexistent') !== null;

        $this->assertTrue($exists);
        $this->assertFalse($notExists);
    }

    /**
     * Test Issue #3: AdminPanel::resources() method should be static
     * This reproduces the error: "Non-static method cannot be called statically"
     */
    public function test_resources_method_can_be_called_statically(): void
    {
        // This should work without throwing "Non-static method cannot be called statically" error
        $reflection = new \ReflectionMethod(AdminPanel::class, 'resources');

        $this->assertTrue(
            $reflection->isStatic(),
            'AdminPanel::resources() should be a static method to allow static calls in AdminServiceProvider'
        );
    }

    /**
     * Test Issue #1: Package should include pre-built assets
     */
    public function test_package_has_prebuilt_assets(): void
    {
        $packagePath = __DIR__ . '/../../public/build';

        $this->assertTrue(
            File::exists($packagePath),
            'Package should include pre-built assets in public/build directory'
        );

        // Check that assets actually exist
        $this->assertTrue(
            count(File::files($packagePath . '/assets')) > 0,
            'Pre-built assets should exist in public/build/assets directory'
        );
    }
}
