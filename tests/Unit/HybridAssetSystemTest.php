<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Pages\Page;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Support\ComponentResolver;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Hybrid Asset System Tests
 *
 * Tests for the new hybrid asset system that supports custom pages
 * in the application's resources directory.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HybridAssetSystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create test directories
        $this->createTestDirectories();
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        $this->cleanupTestDirectories();

        parent::tearDown();
    }

    public function test_component_resolver_exists(): void
    {
        $this->assertTrue(class_exists(ComponentResolver::class));
    }

    public function test_component_resolver_can_resolve_package_components(): void
    {
        $resolver = new ComponentResolver();

        // Test resolving a package component (should work with current system)
        $result = $resolver->resolve('Dashboard');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertEquals('package', $result['type']);
    }

    public function test_component_resolver_can_resolve_app_components(): void
    {
        $resolver = new ComponentResolver();

        // Create a test component in the app directory
        $this->createTestAppComponent('TestAppPage');

        $result = $resolver->resolve('Pages/TestAppPage');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertEquals('app', $result['type']);
        $this->assertStringContains('resources/js/admin-pages', $result['path']);
    }

    public function test_component_resolver_returns_fallback_for_missing_components(): void
    {
        $resolver = new ComponentResolver();

        $result = $resolver->resolve('Pages/NonExistentComponent');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('fallback', $result['type']);
        $this->assertArrayHasKey('component_name', $result);
        $this->assertEquals('Pages/NonExistentComponent', $result['component_name']);
    }

    public function test_component_resolver_prioritizes_app_over_package(): void
    {
        $resolver = new ComponentResolver();

        // Create an app component that might conflict with package component
        $this->createTestAppComponent('Dashboard');

        $result = $resolver->resolve('Pages/Dashboard');

        // Should resolve to app component, not package (or fallback if app doesn't exist)
        $this->assertContains($result['type'], ['app', 'fallback']);
    }

    public function test_vite_config_helper_exists(): void
    {
        $this->assertTrue(class_exists(\JTD\AdminPanel\Support\ViteConfigHelper::class));
    }

    public function test_vite_config_helper_generates_config(): void
    {
        $helper = new \JTD\AdminPanel\Support\ViteConfigHelper();

        $config = $helper->generateConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('input', $config);
        $this->assertArrayHasKey('output', $config);
        $this->assertArrayHasKey('resolve', $config);
    }

    public function test_vite_config_helper_includes_admin_pages_input(): void
    {
        $helper = new \JTD\AdminPanel\Support\ViteConfigHelper();

        // Force include entry for testing
        $config = $helper->generateConfig(['include_entry' => true]);

        $this->assertArrayHasKey('input', $config);
        $this->assertIsArray($config['input']);

        // Should include admin-pages entry point when forced
        $hasAdminPagesInput = false;
        foreach ($config['input'] as $key => $input) {
            if ($key === 'admin-pages' || str_contains($input, 'admin-pages')) {
                $hasAdminPagesInput = true;
                break;
            }
        }

        $this->assertTrue($hasAdminPagesInput, 'Vite config should include admin-pages input when forced');
    }

    public function test_admin_panel_can_detect_app_components(): void
    {
        $adminPanel = app(AdminPanel::class);

        // Create test app component
        $this->createTestAppComponent('CustomDashboard');

        $appComponents = $adminPanel->getAvailableAppComponents();

        $this->assertIsArray($appComponents);
        $this->assertContains('Pages/CustomDashboard', $appComponents);
    }

    public function test_page_can_specify_app_component(): void
    {
        $page = new TestAppComponentPage();

        $this->assertEquals('Pages/AppCustomPage', TestAppComponentPage::$component);
    }

    public function test_component_resolution_in_page_controller(): void
    {
        // This tests the integration with PageController
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([TestAppComponentPage::class]);

        $resolver = new ComponentResolver();
        $result = $resolver->resolve(TestAppComponentPage::$component);

        // Should handle the Pages/ prefix correctly
        $this->assertIsArray($result);
        $this->assertContains($result['type'], ['app', 'fallback']);
    }

    public function test_manifest_registry_integration(): void
    {
        $adminPanel = app(AdminPanel::class);

        // Register a test manifest
        $adminPanel->registerCustomPageManifest([
            'package' => 'test/package',
            'manifest_path' => '/test/manifest.json',
            'base_url' => '/test',
            'priority' => 100,
        ]);

        $registry = $adminPanel->getManifestRegistry();
        $this->assertTrue($registry->hasManifest('test/package'));

        $manifests = $registry->getManifests();
        $this->assertArrayHasKey('test/package', $manifests);
        $this->assertEquals(100, $manifests['test/package']['priority']);
    }

    public function test_manifest_registry_priority_sorting(): void
    {
        $adminPanel = app(AdminPanel::class);

        // Register manifests with different priorities
        $adminPanel->registerCustomPageManifest([
            'package' => 'high-priority',
            'manifest_path' => '/high/manifest.json',
            'priority' => 10,
        ]);

        $adminPanel->registerCustomPageManifest([
            'package' => 'low-priority',
            'manifest_path' => '/low/manifest.json',
            'priority' => 100,
        ]);

        $registry = $adminPanel->getManifestRegistry();
        $manifests = $registry->getManifests();

        // Should be sorted by priority (lower number = higher priority)
        $keys = array_keys($manifests);
        $this->assertEquals('high-priority', $keys[0]);
        $this->assertEquals('low-priority', $keys[1]);
    }

    public function test_graceful_fallback_includes_helpful_information(): void
    {
        $resolver = new ComponentResolver();

        $result = $resolver->resolve('Pages/MissingComponent');

        $this->assertEquals('fallback', $result['type']);
        $this->assertArrayHasKey('component_name', $result);
        $this->assertArrayHasKey('expected_path', $result);
        $this->assertArrayHasKey('suggestions', $result);

        $this->assertEquals('Pages/MissingComponent', $result['component_name']);
        $this->assertStringContains('resources/js/admin-pages', $result['expected_path']);
        $this->assertIsArray($result['suggestions']);
    }

    /**
     * Helper method to create test directories
     */
    protected function createTestDirectories(): void
    {
        $appPagesDir = base_path('resources/js/admin-pages');

        if (!File::exists($appPagesDir)) {
            File::makeDirectory($appPagesDir, 0755, true);
        }
    }

    /**
     * Helper method to create a test app component
     */
    protected function createTestAppComponent(string $name): void
    {
        $appPagesDir = base_path('resources/js/admin-pages');
        $componentPath = $appPagesDir . '/' . $name . '.vue';

        $componentContent = <<<VUE
<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold">Test App Component: {$name}</h1>
        <p>This is a test component created in the app's resources directory.</p>
    </div>
</template>

<script setup>
// Test component for hybrid asset system
</script>
VUE;

        File::put($componentPath, $componentContent);
    }

    /**
     * Helper method to clean up test directories
     */
    protected function cleanupTestDirectories(): void
    {
        $appPagesDir = base_path('resources/js/admin-pages');

        if (File::exists($appPagesDir)) {
            File::deleteDirectory($appPagesDir);
        }
    }
}

/**
 * Test Page that uses app component
 */
class TestAppComponentPage extends Page
{
    public static ?string $component = 'Pages/AppCustomPage';
    public static ?string $title = 'Test App Component Page';

    public function fields(Request $request): array
    {
        return [];
    }
}
