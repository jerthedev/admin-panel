<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Vite;

use Illuminate\Filesystem\Filesystem;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Vite\AdminPanelPlugin;

/**
 * AdminPanelPlugin Card Support Unit Tests.
 *
 * Tests the card auto-discovery functionality added to the Vite plugin.
 */
class AdminPanelPluginCardTest extends TestCase
{
    protected Filesystem $files;
    protected AdminPanelPlugin $plugin;
    protected string $testBasePath;
    protected string $testCardsPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->files = new Filesystem();
        $this->testBasePath = sys_get_temp_dir() . '/admin-panel-vite-test-' . uniqid();
        $this->testCardsPath = $this->testBasePath . '/resources/js/admin-cards';
        
        // Create test directories
        $this->files->makeDirectory($this->testCardsPath, 0755, true);
        
        $this->plugin = new AdminPanelPlugin($this->files, [
            'adminCardsPath' => 'resources/js/admin-cards',
            'manifestPath' => 'public/admin-manifest.json',
            'hotReload' => true,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->files->exists($this->testBasePath)) {
            $this->files->deleteDirectory($this->testBasePath);
        }
        
        parent::tearDown();
    }

    public function test_plugin_has_admin_cards_path_config(): void
    {
        $this->assertEquals('resources/js/admin-cards', $this->plugin->getAdminCardsPath());
    }

    public function test_detect_admin_card_components_returns_empty_when_directory_missing(): void
    {
        $nonExistentPath = $this->testBasePath . '/nonexistent';
        $components = $this->plugin->detectAdminCardComponents($nonExistentPath);
        
        $this->assertIsArray($components);
        $this->assertEmpty($components);
    }

    public function test_detect_admin_card_components_finds_vue_files(): void
    {
        // Create test card components
        $this->files->put($this->testCardsPath . '/TestCard.vue', '<template><div>Test Card</div></template>');
        $this->files->put($this->testCardsPath . '/UserStats.vue', '<template><div>User Stats</div></template>');

        // Create nested directory and component
        $nestedPath = $this->testCardsPath . '/nested';
        $this->files->makeDirectory($nestedPath, 0755, true);
        $this->files->put($nestedPath . '/NestedCard.vue', '<template><div>Nested Card</div></template>');
        
        // Create non-Vue files (should be ignored)
        $this->files->put($this->testCardsPath . '/README.md', '# Cards');
        $this->files->put($this->testCardsPath . '/config.json', '{}');
        
        $components = $this->plugin->detectAdminCardComponents($this->testBasePath);

        $this->assertCount(3, $components);

        // Convert to string for easier assertion
        $componentsString = implode('|', $components);
        $this->assertStringContainsString('TestCard.vue', $componentsString);
        $this->assertStringContainsString('UserStats.vue', $componentsString);
        $this->assertStringContainsString('nested/NestedCard.vue', $componentsString);
    }

    public function test_detect_admin_card_components_ignores_empty_files(): void
    {
        // Create empty Vue file
        $this->files->put($this->testCardsPath . '/EmptyCard.vue', '');
        
        // Create valid Vue file
        $this->files->put($this->testCardsPath . '/ValidCard.vue', '<template><div>Valid</div></template>');
        
        $components = $this->plugin->detectAdminCardComponents($this->testBasePath);
        
        $this->assertCount(1, $components);
        $this->assertStringContainsString('ValidCard.vue', $components[0]);
    }

    public function test_generate_build_entries_includes_card_components(): void
    {
        // Create test page and card components
        $pagesPath = $this->testBasePath . '/resources/js/admin-pages';
        $this->files->makeDirectory($pagesPath, 0755, true);
        $this->files->put($pagesPath . '/TestPage.vue', '<template><div>Test Page</div></template>');
        $this->files->put($this->testCardsPath . '/TestCard.vue', '<template><div>Test Card</div></template>');
        
        $entries = $this->plugin->generateBuildEntries($this->testBasePath);
        
        $this->assertArrayHasKey('admin-pages/TestPage', $entries);
        $this->assertArrayHasKey('admin-cards/TestCard', $entries);
        $this->assertStringContainsString('TestPage.vue', $entries['admin-pages/TestPage']);
        $this->assertStringContainsString('TestCard.vue', $entries['admin-cards/TestCard']);
    }

    public function test_generate_build_entries_handles_nested_card_components(): void
    {
        // Create nested card components
        $nestedPath = $this->testCardsPath . '/analytics';
        $this->files->makeDirectory($nestedPath, 0755, true);
        $this->files->put($nestedPath . '/RevenueChart.vue', '<template><div>Revenue Chart</div></template>');
        $this->files->put($nestedPath . '/UserMetrics.vue', '<template><div>User Metrics</div></template>');
        
        $entries = $this->plugin->generateBuildEntries($this->testBasePath);
        
        $this->assertArrayHasKey('admin-cards/analytics/RevenueChart', $entries);
        $this->assertArrayHasKey('admin-cards/analytics/UserMetrics', $entries);
    }

    public function test_generate_manifest_includes_card_components(): void
    {
        $builtAssets = [
            'admin-pages/Dashboard' => [
                'file' => 'assets/Dashboard-abc123.js',
                'css' => ['assets/Dashboard-def456.css'],
            ],
            'admin-cards/TestCard' => [
                'file' => 'assets/TestCard-ghi789.js',
                'css' => ['assets/TestCard-jkl012.css'],
            ],
            'admin-cards/UserStats' => [
                'file' => 'assets/UserStats-mno345.js',
            ],
        ];
        
        $manifest = $this->plugin->generateManifest($builtAssets, $this->testBasePath);
        
        $this->assertArrayHasKey('admin-pages', $manifest);
        $this->assertArrayHasKey('admin-cards', $manifest);
        
        $this->assertArrayHasKey('Dashboard', $manifest['admin-pages']);
        $this->assertArrayHasKey('TestCard', $manifest['admin-cards']);
        $this->assertArrayHasKey('UserStats', $manifest['admin-cards']);
        
        $this->assertEquals('assets/TestCard-ghi789.js', $manifest['admin-cards']['TestCard']['file']);
        $this->assertEquals(['assets/TestCard-jkl012.css'], $manifest['admin-cards']['TestCard']['css']);
    }

    public function test_generate_component_path_handles_card_components(): void
    {
        $cardPath = $this->testCardsPath . '/TestCard.vue';
        $this->files->put($cardPath, '<template><div>Test Card</div></template>');
        
        $componentPath = $this->plugin->generateComponentPath($cardPath, $this->testBasePath);
        
        $this->assertEquals('Cards/TestCard', $componentPath);
    }

    public function test_generate_component_path_handles_nested_card_components(): void
    {
        $nestedPath = $this->testCardsPath . '/analytics/RevenueChart.vue';
        $this->files->makeDirectory(dirname($nestedPath), 0755, true);
        $this->files->put($nestedPath, '<template><div>Revenue Chart</div></template>');
        
        $componentPath = $this->plugin->generateComponentPath($nestedPath, $this->testBasePath);
        
        $this->assertEquals('Cards/analytics/RevenueChart', $componentPath);
    }

    public function test_generate_component_path_handles_both_pages_and_cards(): void
    {
        // Create page component
        $pagesPath = $this->testBasePath . '/resources/js/admin-pages';
        $this->files->makeDirectory($pagesPath, 0755, true);
        $pagePath = $pagesPath . '/TestPage.vue';
        $this->files->put($pagePath, '<template><div>Test Page</div></template>');
        
        // Create card component
        $cardPath = $this->testCardsPath . '/TestCard.vue';
        $this->files->put($cardPath, '<template><div>Test Card</div></template>');
        
        $pageComponentPath = $this->plugin->generateComponentPath($pagePath, $this->testBasePath);
        $cardComponentPath = $this->plugin->generateComponentPath($cardPath, $this->testBasePath);
        
        $this->assertEquals('Pages/TestPage', $pageComponentPath);
        $this->assertEquals('Cards/TestCard', $cardComponentPath);
    }

    public function test_plugin_config_supports_custom_cards_path(): void
    {
        $customPlugin = new AdminPanelPlugin($this->files, [
            'adminCardsPath' => 'custom/cards/path',
        ]);
        
        $this->assertEquals('custom/cards/path', $customPlugin->getAdminCardsPath());
    }

    public function test_detect_admin_card_components_handles_permission_errors_gracefully(): void
    {
        // Create a directory that will cause permission issues
        $restrictedPath = $this->testBasePath . '/restricted';
        $this->files->makeDirectory($restrictedPath, 0000, true);
        
        $components = $this->plugin->detectAdminCardComponents($restrictedPath);
        
        $this->assertIsArray($components);
        $this->assertEmpty($components);
        
        // Clean up
        chmod($restrictedPath, 0755);
    }

    public function test_generate_manifest_handles_empty_card_assets(): void
    {
        $builtAssets = [
            'admin-pages/Dashboard' => [
                'file' => 'assets/Dashboard-abc123.js',
            ],
        ];
        
        $manifest = $this->plugin->generateManifest($builtAssets, $this->testBasePath);
        
        $this->assertArrayHasKey('admin-cards', $manifest);
        $this->assertEmpty($manifest['admin-cards']);
    }

    public function test_generate_build_entries_handles_no_card_components(): void
    {
        // Only create page components
        $pagesPath = $this->testBasePath . '/resources/js/admin-pages';
        $this->files->makeDirectory($pagesPath, 0755, true);
        $this->files->put($pagesPath . '/TestPage.vue', '<template><div>Test Page</div></template>');
        
        // Remove cards directory
        $this->files->deleteDirectory($this->testCardsPath);
        
        $entries = $this->plugin->generateBuildEntries($this->testBasePath);
        
        $this->assertArrayHasKey('admin-pages/TestPage', $entries);
        $this->assertCount(1, $entries);
    }
}
