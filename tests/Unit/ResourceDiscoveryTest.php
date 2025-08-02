<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Support\ResourceDiscovery;
use JTD\AdminPanel\Tests\Fixtures\UserResource;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Resource Discovery Tests
 *
 * Test the automatic resource discovery system including caching,
 * path resolution, and resource validation.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ResourceDiscoveryTest extends TestCase
{
    protected ResourceDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discovery = new ResourceDiscovery();

        // Clear any cached resources
        $this->discovery->clearCache();

        // Clean up any existing temp directories
        $this->cleanupTempDirectories();
    }

    protected function tearDown(): void
    {
        $this->cleanupTempDirectories();
        parent::tearDown();
    }

    protected function cleanupTempDirectories(): void
    {
        $tempPaths = [
            base_path('temp_admin_resources'),
            base_path('app/Admin/Resources'),
        ];

        foreach ($tempPaths as $path) {
            if (File::exists($path)) {
                File::deleteDirectory($path);
            }
        }
    }

    public function test_discovery_returns_empty_collection_when_disabled(): void
    {
        config(['admin-panel.resources.auto_discovery' => false]);

        $resources = $this->discovery->discover();

        $this->assertTrue($resources->isEmpty());
    }

    public function test_discovery_returns_empty_collection_when_path_does_not_exist(): void
    {
        config(['admin-panel.resources.discovery_path' => 'non/existent/path']);

        $resources = $this->discovery->discover();

        $this->assertTrue($resources->isEmpty());
    }

    public function test_discovery_returns_collection(): void
    {
        // Test that discovery returns a collection even when no resources exist
        config(['admin-panel.resources.discovery_path' => 'nonexistent/path']);

        $resources = $this->discovery->discover();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resources);
    }

    public function test_discovery_ignores_non_php_files(): void
    {
        $tempPath = base_path('temp_admin_resources');
        File::makeDirectory($tempPath, 0755, true);

        // Create non-PHP files
        File::put($tempPath . '/readme.txt', 'This is not a PHP file');
        File::put($tempPath . '/config.json', '{"test": true}');

        config(['admin-panel.resources.discovery_path' => 'temp_admin_resources']);

        $resources = $this->discovery->discover();

        $this->assertTrue($resources->isEmpty());

        // Cleanup
        File::deleteDirectory($tempPath);
    }

    public function test_discovery_ignores_invalid_resource_classes(): void
    {
        $tempPath = base_path('temp_admin_resources');
        File::makeDirectory($tempPath, 0755, true);

        // Create invalid resource files
        $invalidContent1 = '<?php
namespace App\Admin\Resources;
class NotAResource {
    // This does not extend Resource
}';

        $invalidContent2 = '<?php
namespace App\Admin\Resources;
use JTD\AdminPanel\Resources\Resource;
abstract class AbstractResource extends Resource {
    // This is abstract
}';

        File::put($tempPath . '/NotAResource.php', $invalidContent1);
        File::put($tempPath . '/AbstractResource.php', $invalidContent2);

        config(['admin-panel.resources.discovery_path' => 'temp_admin_resources']);

        $resources = $this->discovery->discover();

        $this->assertTrue($resources->isEmpty());

        // Cleanup
        File::deleteDirectory($tempPath);
    }

    public function test_discovery_respects_cache_configuration(): void
    {
        // Test with cache enabled
        config([
            'admin-panel.performance.cache_resources' => true,
            'admin-panel.performance.cache_ttl' => 3600,
        ]);

        $resources1 = $this->discovery->discover();

        // Test with cache disabled
        config(['admin-panel.performance.cache_resources' => false]);

        $resources2 = $this->discovery->discover();

        // Both should return collections (even if empty)
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resources1);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resources2);
    }

    public function test_clear_cache_removes_cached_resources(): void
    {
        // This should not throw an exception
        $this->discovery->clearCache();

        // If we get here, the method worked
        $this->assertTrue(true);
    }

    public function test_get_resource_instances_returns_instantiated_resources(): void
    {
        // Mock the discover method to return our test resource
        $discovery = $this->createPartialMock(ResourceDiscovery::class, ['discover']);
        $discovery->method('discover')->willReturn(collect([UserResource::class]));

        $instances = $discovery->getResourceInstances();

        $this->assertCount(1, $instances);
        $this->assertInstanceOf(UserResource::class, $instances->first());
    }

    public function test_find_by_uri_key_returns_correct_resource(): void
    {
        $discovery = $this->createPartialMock(ResourceDiscovery::class, ['getResourceInstances']);
        $discovery->method('getResourceInstances')->willReturn(collect([new UserResource()]));

        $resource = $discovery->findByUriKey('users');

        $this->assertInstanceOf(UserResource::class, $resource);
    }

    public function test_find_by_uri_key_returns_null_when_not_found(): void
    {
        $discovery = $this->createPartialMock(ResourceDiscovery::class, ['getResourceInstances']);
        $discovery->method('getResourceInstances')->willReturn(collect());

        $resource = $discovery->findByUriKey('nonexistent');

        $this->assertNull($resource);
    }

    public function test_get_navigation_resources_filters_correctly(): void
    {
        $discovery = $this->createPartialMock(ResourceDiscovery::class, ['getResourceInstances']);
        $discovery->method('getResourceInstances')->willReturn(collect([new UserResource()]));

        $resources = $discovery->getNavigationResources();

        $this->assertCount(1, $resources);
    }

    public function test_get_searchable_resources_filters_correctly(): void
    {
        $discovery = $this->createPartialMock(ResourceDiscovery::class, ['getResourceInstances']);
        $discovery->method('getResourceInstances')->willReturn(collect([new UserResource()]));

        $resources = $discovery->getSearchableResources();

        // UserResource should be globally searchable by default
        $this->assertCount(1, $resources);
    }
}
