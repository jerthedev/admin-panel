<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Support;

use Illuminate\Filesystem\Filesystem;
use JTD\AdminPanel\Support\CustomPageManifestRegistry;
use JTD\AdminPanel\Tests\TestCase;

/**
 * CustomPageManifestRegistry Card Support Tests.
 *
 * Tests the card component support added to the manifest registry.
 */
class CustomPageManifestRegistryCardTest extends TestCase
{
    protected Filesystem $files;
    protected CustomPageManifestRegistry $registry;
    protected string $testManifestPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->files = new Filesystem();
        $this->testManifestPath = sys_get_temp_dir() . '/admin-panel-manifest-test-' . uniqid();
        $this->files->makeDirectory($this->testManifestPath, 0755, true);
        
        $this->registry = new CustomPageManifestRegistry($this->files);
    }

    protected function tearDown(): void
    {
        if ($this->files->exists($this->testManifestPath)) {
            $this->files->deleteDirectory($this->testManifestPath);
        }
        
        parent::tearDown();
    }

    public function test_aggregate_manifests_includes_cards_section(): void
    {
        // Create test manifest with both pages and cards
        $manifestPath = $this->testManifestPath . '/test-manifest.json';
        $manifestData = [
            'Pages' => [
                'Dashboard' => ['file' => 'assets/Dashboard-abc123.js'],
                'Settings' => ['file' => 'assets/Settings-def456.js'],
            ],
            'Cards' => [
                'UserStats' => ['file' => 'assets/UserStats-ghi789.js'],
                'RevenueChart' => ['file' => 'assets/RevenueChart-jkl012.js'],
            ]
        ];
        
        $this->files->put($manifestPath, json_encode($manifestData));
        
        // Register the manifest
        $this->registry->register([
            'package' => 'test-package',
            'manifest_path' => $manifestPath,
            'base_url' => '/test',
            'priority' => 100,
        ]);
        
        $aggregated = $this->registry->getAggregatedManifest();
        
        $this->assertArrayHasKey('test-package', $aggregated);
        $this->assertArrayHasKey('components', $aggregated['test-package']);
        $this->assertArrayHasKey('cards', $aggregated['test-package']);
        
        $components = $aggregated['test-package']['components'];
        $cards = $aggregated['test-package']['cards'];
        
        $this->assertArrayHasKey('Dashboard', $components);
        $this->assertArrayHasKey('Settings', $components);
        $this->assertArrayHasKey('UserStats', $cards);
        $this->assertArrayHasKey('RevenueChart', $cards);
    }

    public function test_aggregate_manifests_handles_legacy_admin_pages_format(): void
    {
        // Create test manifest with legacy format
        $manifestPath = $this->testManifestPath . '/legacy-manifest.json';
        $manifestData = [
            'admin-pages' => [
                'Dashboard' => ['file' => 'assets/Dashboard-abc123.js'],
            ],
            'admin-cards' => [
                'UserStats' => ['file' => 'assets/UserStats-ghi789.js'],
            ]
        ];
        
        $this->files->put($manifestPath, json_encode($manifestData));
        
        $this->registry->register([
            'package' => 'legacy-package',
            'manifest_path' => $manifestPath,
            'base_url' => '/legacy',
            'priority' => 100,
        ]);
        
        $aggregated = $this->registry->getAggregatedManifest();
        
        $this->assertArrayHasKey('legacy-package', $aggregated);
        
        $components = $aggregated['legacy-package']['components'];
        $cards = $aggregated['legacy-package']['cards'];
        
        $this->assertArrayHasKey('Dashboard', $components);
        $this->assertArrayHasKey('UserStats', $cards);
    }

    public function test_resolve_component_finds_card_components(): void
    {
        // Create test manifest with cards
        $manifestPath = $this->testManifestPath . '/card-manifest.json';
        $manifestData = [
            'Cards' => [
                'UserStats' => ['file' => 'assets/UserStats-abc123.js'],
                'analytics/RevenueChart' => ['file' => 'assets/RevenueChart-def456.js'],
            ]
        ];
        
        $this->files->put($manifestPath, json_encode($manifestData));
        
        $this->registry->register([
            'package' => 'card-package',
            'manifest_path' => $manifestPath,
            'base_url' => '/cards',
            'priority' => 100,
        ]);
        
        // Test resolving card components
        $userStatsResult = $this->registry->resolveComponent('UserStats');
        $revenueChartResult = $this->registry->resolveComponent('analytics/RevenueChart');
        
        $this->assertNotNull($userStatsResult);
        $this->assertEquals('manifest', $userStatsResult['type']);
        $this->assertEquals('UserStats', $userStatsResult['component_name']);
        $this->assertEquals('card', $userStatsResult['component_type']);
        $this->assertEquals('/cards', $userStatsResult['base_url']);
        
        $this->assertNotNull($revenueChartResult);
        $this->assertEquals('card', $revenueChartResult['component_type']);
        $this->assertEquals('analytics/RevenueChart', $revenueChartResult['component_name']);
    }

    public function test_resolve_component_prioritizes_pages_over_cards(): void
    {
        // Create test manifest with same component name in both pages and cards
        $manifestPath = $this->testManifestPath . '/priority-manifest.json';
        $manifestData = [
            'Pages' => [
                'Dashboard' => ['file' => 'assets/PageDashboard-abc123.js'],
            ],
            'Cards' => [
                'Dashboard' => ['file' => 'assets/CardDashboard-def456.js'],
            ]
        ];
        
        $this->files->put($manifestPath, json_encode($manifestData));
        
        $this->registry->register([
            'package' => 'priority-package',
            'manifest_path' => $manifestPath,
            'base_url' => '/priority',
            'priority' => 100,
        ]);
        
        $result = $this->registry->resolveComponent('Dashboard');
        
        $this->assertNotNull($result);
        $this->assertEquals('page', $result['component_type']);
        $this->assertStringContainsString('PageDashboard', $result['asset_path']['file']);
    }

    public function test_get_available_components_includes_cards(): void
    {
        // Create test manifest with both pages and cards
        $manifestPath = $this->testManifestPath . '/available-manifest.json';
        $manifestData = [
            'Pages' => [
                'Dashboard' => ['file' => 'assets/Dashboard-abc123.js'],
                'Settings' => ['file' => 'assets/Settings-def456.js'],
            ],
            'Cards' => [
                'UserStats' => ['file' => 'assets/UserStats-ghi789.js'],
                'RevenueChart' => ['file' => 'assets/RevenueChart-jkl012.js'],
            ]
        ];
        
        $this->files->put($manifestPath, json_encode($manifestData));
        
        $this->registry->register([
            'package' => 'available-package',
            'manifest_path' => $manifestPath,
            'base_url' => '/available',
            'priority' => 100,
        ]);
        
        $components = $this->registry->getAvailableComponents();
        
        // Should have 4 components total (2 pages + 2 cards)
        $this->assertCount(4, $components);
        
        // Find page components
        $pageComponents = array_filter($components, fn($c) => $c['type'] === 'page');
        $cardComponents = array_filter($components, fn($c) => $c['type'] === 'card');
        
        $this->assertCount(2, $pageComponents);
        $this->assertCount(2, $cardComponents);
        
        // Check specific components
        $componentNames = array_column($components, 'name');
        $this->assertContains('Dashboard', $componentNames);
        $this->assertContains('Settings', $componentNames);
        $this->assertContains('UserStats', $componentNames);
        $this->assertContains('RevenueChart', $componentNames);
    }

    public function test_get_available_components_handles_legacy_format(): void
    {
        // Create test manifest with legacy direct format
        $manifestPath = $this->testManifestPath . '/legacy-direct-manifest.json';
        $manifestData = [
            'OldComponent' => ['file' => 'assets/OldComponent-abc123.js'],
            'AnotherOld' => ['file' => 'assets/AnotherOld-def456.js'],
        ];
        
        $this->files->put($manifestPath, json_encode($manifestData));
        
        $this->registry->register([
            'package' => 'legacy-direct-package',
            'manifest_path' => $manifestPath,
            'base_url' => '/legacy-direct',
            'priority' => 100,
        ]);
        
        $components = $this->registry->getAvailableComponents();
        
        $this->assertCount(2, $components);
        
        foreach ($components as $component) {
            $this->assertEquals('unknown', $component['type']);
            $this->assertEquals('legacy-direct-package', $component['source']);
        }
    }

    public function test_resolve_component_handles_missing_cards_section(): void
    {
        // Create test manifest with only pages
        $manifestPath = $this->testManifestPath . '/pages-only-manifest.json';
        $manifestData = [
            'Pages' => [
                'Dashboard' => ['file' => 'assets/Dashboard-abc123.js'],
            ]
        ];
        
        $this->files->put($manifestPath, json_encode($manifestData));
        
        $this->registry->register([
            'package' => 'pages-only-package',
            'manifest_path' => $manifestPath,
            'base_url' => '/pages-only',
            'priority' => 100,
        ]);
        
        // Try to resolve a card component (should return null)
        $result = $this->registry->resolveComponent('NonExistentCard');
        $this->assertNull($result);
        
        // Should still resolve page components
        $pageResult = $this->registry->resolveComponent('Dashboard');
        $this->assertNotNull($pageResult);
        $this->assertEquals('page', $pageResult['component_type']);
    }

    public function test_aggregate_manifests_handles_empty_cards_section(): void
    {
        // Create test manifest with empty cards section
        $manifestPath = $this->testManifestPath . '/empty-cards-manifest.json';
        $manifestData = [
            'Pages' => [
                'Dashboard' => ['file' => 'assets/Dashboard-abc123.js'],
            ],
            'Cards' => []
        ];
        
        $this->files->put($manifestPath, json_encode($manifestData));
        
        $this->registry->register([
            'package' => 'empty-cards-package',
            'manifest_path' => $manifestPath,
            'base_url' => '/empty-cards',
            'priority' => 100,
        ]);
        
        $aggregated = $this->registry->getAggregatedManifest();
        
        $this->assertArrayHasKey('empty-cards-package', $aggregated);
        $this->assertArrayHasKey('cards', $aggregated['empty-cards-package']);
        $this->assertEmpty($aggregated['empty-cards-package']['cards']);
        $this->assertNotEmpty($aggregated['empty-cards-package']['components']);
    }
}
