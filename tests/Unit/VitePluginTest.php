<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use JTD\AdminPanel\Vite\AdminPanelPlugin;
use PHPUnit\Framework\TestCase;

/**
 * Vite Plugin Test
 *
 * Tests the Vite plugin functionality for automatic admin pages detection,
 * manifest generation, and build integration.
 */
class VitePluginTest extends TestCase
{
    protected Filesystem $files;
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->files = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/admin-panel-vite-test-' . uniqid();
        $this->files->makeDirectory($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if ($this->files->exists($this->tempDir)) {
            $this->files->deleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    public function test_plugin_detects_admin_pages_directory(): void
    {
        // Create admin pages directory with components
        $adminPagesPath = $this->tempDir . '/resources/js/admin-pages';
        $this->files->makeDirectory($adminPagesPath, 0755, true);

        // Create test Vue components
        $this->files->put($adminPagesPath . '/UserDashboard.vue', '<template><div>User Dashboard</div></template>');
        $this->files->put($adminPagesPath . '/SystemSettings.vue', '<template><div>System Settings</div></template>');

        // Create subdirectory with component
        $this->files->makeDirectory($adminPagesPath . '/Reports', 0755, true);
        $this->files->put($adminPagesPath . '/Reports/Analytics.vue', '<template><div>Analytics</div></template>');

        $plugin = $this->createVitePlugin();
        $detectedComponents = $plugin->detectAdminPageComponents($this->tempDir);

        $this->assertCount(3, $detectedComponents);
        $this->assertContains('UserDashboard.vue', array_map('basename', $detectedComponents));
        $this->assertContains('SystemSettings.vue', array_map('basename', $detectedComponents));
        $this->assertContains('Analytics.vue', array_map('basename', $detectedComponents));
    }

    public function test_plugin_generates_build_entries(): void
    {
        // Create admin pages directory with components
        $adminPagesPath = $this->tempDir . '/resources/js/admin-pages';
        $this->files->makeDirectory($adminPagesPath, 0755, true);

        $this->files->put($adminPagesPath . '/Dashboard.vue', '<template><div>Dashboard</div></template>');
        $this->files->put($adminPagesPath . '/Settings.vue', '<template><div>Settings</div></template>');

        $plugin = $this->createVitePlugin();
        $buildEntries = $plugin->generateBuildEntries($this->tempDir);

        $this->assertArrayHasKey('admin-pages/Dashboard', $buildEntries);
        $this->assertArrayHasKey('admin-pages/Settings', $buildEntries);

        $this->assertStringContainsString('resources/js/admin-pages/Dashboard.vue', $buildEntries['admin-pages/Dashboard']);
        $this->assertStringContainsString('resources/js/admin-pages/Settings.vue', $buildEntries['admin-pages/Settings']);
    }

    public function test_plugin_handles_multi_component_pages(): void
    {
        // Create multi-component page structure
        $adminPagesPath = $this->tempDir . '/resources/js/admin-pages';
        $this->files->makeDirectory($adminPagesPath, 0755, true);

        // Multi-component page: UserManagement with Dashboard, Settings, Metrics
        $this->files->put($adminPagesPath . '/UserManagementDashboard.vue', '<template><div>User Dashboard</div></template>');
        $this->files->put($adminPagesPath . '/UserManagementSettings.vue', '<template><div>User Settings</div></template>');
        $this->files->put($adminPagesPath . '/UserManagementMetrics.vue', '<template><div>User Metrics</div></template>');

        $plugin = $this->createVitePlugin();
        $buildEntries = $plugin->generateBuildEntries($this->tempDir);

        $this->assertArrayHasKey('admin-pages/UserManagementDashboard', $buildEntries);
        $this->assertArrayHasKey('admin-pages/UserManagementSettings', $buildEntries);
        $this->assertArrayHasKey('admin-pages/UserManagementMetrics', $buildEntries);
    }

    public function test_plugin_generates_manifest_json(): void
    {
        // Create admin pages directory with components
        $adminPagesPath = $this->tempDir . '/resources/js/admin-pages';
        $this->files->makeDirectory($adminPagesPath, 0755, true);

        $this->files->put($adminPagesPath . '/TestPage.vue', '<template><div>Test Page</div></template>');

        // Create mock build output directory
        $buildPath = $this->tempDir . '/public/build';
        $this->files->makeDirectory($buildPath, 0755, true);

        $plugin = $this->createVitePlugin();

        // Mock built assets
        $builtAssets = [
            'admin-pages/TestPage' => [
                'file' => 'assets/TestPage-abc123.js',
                'css' => ['assets/TestPage-def456.css'],
            ],
        ];

        $manifest = $plugin->generateManifest($builtAssets, $this->tempDir);

        $this->assertArrayHasKey('admin-pages', $manifest);
        $this->assertArrayHasKey('TestPage', $manifest['admin-pages']);

        $testPageManifest = $manifest['admin-pages']['TestPage'];
        $this->assertEquals('assets/TestPage-abc123.js', $testPageManifest['file']);
        $this->assertContains('assets/TestPage-def456.css', $testPageManifest['css']);
    }

    public function test_plugin_handles_nested_directories(): void
    {
        // Create nested directory structure
        $adminPagesPath = $this->tempDir . '/resources/js/admin-pages';
        $this->files->makeDirectory($adminPagesPath . '/Users/Management', 0755, true);
        $this->files->makeDirectory($adminPagesPath . '/System/Settings', 0755, true);

        $this->files->put($adminPagesPath . '/Users/Management/Dashboard.vue', '<template><div>User Management</div></template>');
        $this->files->put($adminPagesPath . '/System/Settings/General.vue', '<template><div>General Settings</div></template>');

        $plugin = $this->createVitePlugin();
        $buildEntries = $plugin->generateBuildEntries($this->tempDir);

        $this->assertArrayHasKey('admin-pages/Users/Management/Dashboard', $buildEntries);
        $this->assertArrayHasKey('admin-pages/System/Settings/General', $buildEntries);
    }

    public function test_plugin_validates_vue_components(): void
    {
        // Create admin pages directory with mixed files
        $adminPagesPath = $this->tempDir . '/resources/js/admin-pages';
        $this->files->makeDirectory($adminPagesPath, 0755, true);

        // Valid Vue component
        $this->files->put($adminPagesPath . '/ValidComponent.vue', '<template><div>Valid</div></template>');

        // Invalid files that should be ignored
        $this->files->put($adminPagesPath . '/NotAComponent.js', 'console.log("not a vue component");');
        $this->files->put($adminPagesPath . '/README.md', '# Admin Pages');
        $this->files->put($adminPagesPath . '/EmptyFile.vue', '');

        $plugin = $this->createVitePlugin();
        $detectedComponents = $plugin->detectAdminPageComponents($this->tempDir);

        $this->assertCount(1, $detectedComponents);
        $this->assertStringContainsString('ValidComponent.vue', $detectedComponents[0]);
    }

    public function test_plugin_generates_component_paths_correctly(): void
    {
        // Create admin pages with various structures
        $adminPagesPath = $this->tempDir . '/resources/js/admin-pages';
        $this->files->makeDirectory($adminPagesPath . '/Nested/Deep', 0755, true);

        $this->files->put($adminPagesPath . '/RootComponent.vue', '<template><div>Root</div></template>');
        $this->files->put($adminPagesPath . '/Nested/NestedComponent.vue', '<template><div>Nested</div></template>');
        $this->files->put($adminPagesPath . '/Nested/Deep/DeepComponent.vue', '<template><div>Deep</div></template>');

        $plugin = $this->createVitePlugin();
        $buildEntries = $plugin->generateBuildEntries($this->tempDir);

        // Check that paths are generated correctly for manifest resolution
        $this->assertArrayHasKey('admin-pages/RootComponent', $buildEntries);
        $this->assertArrayHasKey('admin-pages/Nested/NestedComponent', $buildEntries);
        $this->assertArrayHasKey('admin-pages/Nested/Deep/DeepComponent', $buildEntries);
    }

    public function test_plugin_handles_empty_admin_pages_directory(): void
    {
        // Create empty admin pages directory
        $adminPagesPath = $this->tempDir . '/resources/js/admin-pages';
        $this->files->makeDirectory($adminPagesPath, 0755, true);

        $plugin = $this->createVitePlugin();
        $detectedComponents = $plugin->detectAdminPageComponents($this->tempDir);
        $buildEntries = $plugin->generateBuildEntries($this->tempDir);

        $this->assertEmpty($detectedComponents);
        $this->assertEmpty($buildEntries);
    }

    public function test_plugin_handles_missing_admin_pages_directory(): void
    {
        // Don't create admin pages directory
        $plugin = $this->createVitePlugin();
        $detectedComponents = $plugin->detectAdminPageComponents($this->tempDir);
        $buildEntries = $plugin->generateBuildEntries($this->tempDir);

        $this->assertEmpty($detectedComponents);
        $this->assertEmpty($buildEntries);
    }

    /**
     * Create a Vite plugin instance for testing.
     */
    protected function createVitePlugin(): AdminPanelPlugin
    {
        return new AdminPanelPlugin($this->files);
    }
}
