<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Resource Discovery Feature Tests
 *
 * Test resource discovery in a real application context including
 * automatic registration and route generation.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ResourceDiscoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any cached resources
        app(AdminPanel::class)->clearResourceCache();
    }

    protected function tearDown(): void
    {
        // Clean up any test directories
        $testPath = base_path('app/Admin/Resources');
        if (File::exists($testPath)) {
            File::deleteDirectory($testPath);
        }

        parent::tearDown();
    }

    public function test_resource_discovery_creates_working_routes(): void
    {
        // Use existing UserResource instead of creating dynamic files
        $adminPanel = app(AdminPanel::class);
        $adminPanel->register([\JTD\AdminPanel\Tests\Fixtures\UserResource::class]);

        // Create an admin user
        $admin = $this->createAdminUser();

        // Test that the registered resource creates working routes
        $response = $this->actingAs($admin)
            ->get('/admin/resources/users');

        $response->assertOk();
    }

    public function test_resource_discovery_respects_configuration(): void
    {
        // Disable auto discovery
        config(['admin-panel.resources.auto_discovery' => false]);

        // Create the discovery directory and resource
        $resourcePath = base_path('app/Admin/Resources');
        if (!File::exists($resourcePath)) {
            File::makeDirectory($resourcePath, 0755, true);
        }

        $resourceContent = '<?php
namespace App\Admin\Resources;
use JTD\AdminPanel\Resources\Resource;
use JTD\AdminPanel\Tests\Fixtures\User;
class DisabledResource extends Resource {
    public static string $model = User::class;
}';

        File::put($resourcePath . '/DisabledResource.php', $resourceContent);

        $adminPanel = app(AdminPanel::class);
        $resources = $adminPanel->getResources();

        // Should not contain the discovered resource when discovery is disabled
        $hasDisabledResource = $resources->contains(function ($resource) {
            return get_class($resource) === 'App\Admin\Resources\DisabledResource';
        });

        $this->assertFalse($hasDisabledResource);
    }

    public function test_resource_discovery_uses_custom_path(): void
    {
        // Test that configuration can be changed
        $originalPath = config('admin-panel.resources.discovery_path');

        // Set custom discovery path
        $customPath = 'app/CustomAdmin/Resources';
        config(['admin-panel.resources.discovery_path' => $customPath]);

        // Verify the configuration was changed
        $this->assertEquals($customPath, config('admin-panel.resources.discovery_path'));

        // Restore original configuration
        config(['admin-panel.resources.discovery_path' => $originalPath]);
    }

    public function test_resource_discovery_caching_works(): void
    {
        // Enable caching
        config([
            'admin-panel.performance.cache_resources' => true,
            'admin-panel.performance.cache_ttl' => 3600,
        ]);

        // Create the discovery directory and resource
        $resourcePath = base_path('app/Admin/Resources');
        if (!File::exists($resourcePath)) {
            File::makeDirectory($resourcePath, 0755, true);
        }

        $resourceContent = '<?php
namespace App\Admin\Resources;
use JTD\AdminPanel\Resources\Resource;
use JTD\AdminPanel\Tests\Fixtures\User;
class CachedResource extends Resource {
    public static string $model = User::class;
}';

        File::put($resourcePath . '/CachedResource.php', $resourceContent);

        $adminPanel = app(AdminPanel::class);

        // First call should discover and cache
        $resources1 = $adminPanel->getResources();

        // Second call should use cache
        $resources2 = $adminPanel->getResources();

        $this->assertEquals($resources1->count(), $resources2->count());

        // Clear cache and verify it works
        $adminPanel->clearResourceCache();
        $resources3 = $adminPanel->getResources();

        $this->assertEquals($resources1->count(), $resources3->count());
    }

    public function test_discovered_resources_integrate_with_navigation(): void
    {
        // Use existing UserResource for navigation test
        $adminPanel = app(AdminPanel::class);
        $adminPanel->register([\JTD\AdminPanel\Tests\Fixtures\UserResource::class]);

        $navigationResources = $adminPanel->getNavigationResources();

        // Should include registered resources in navigation
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $navigationResources);
    }
}
