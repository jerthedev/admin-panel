<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Support\CustomPageManifestRegistry;
use JTD\AdminPanel\Tests\TestCase;

class PackageManifestRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected AdminPanel $adminPanel;
    protected CustomPageManifestRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminPanel = app(AdminPanel::class);
        $this->registry = app(CustomPageManifestRegistry::class);
    }

    /** @test */
    public function admin_panel_can_register_custom_page_manifest()
    {
        $config = [
            'package' => 'jerthedev/cms-blog-system',
            'manifest_url' => '/vendor/cms-blog-system/admin-pages-manifest.json',
            'priority' => 100,
            'base_url' => '/vendor/cms-blog-system',
        ];

        $this->adminPanel->registerCustomPageManifestInstance($config);

        $this->assertTrue($this->registry->hasPackage('jerthedev/cms-blog-system'));
    }

    /** @test */
    public function admin_panel_returns_manifest_registry_instance()
    {
        $registry = $this->adminPanel->getManifestRegistry();

        $this->assertInstanceOf(CustomPageManifestRegistry::class, $registry);
        $this->assertSame($this->registry, $registry);
    }

    /** @test */
    public function admin_panel_returns_aggregated_manifests()
    {
        // Register multiple package manifests
        $this->adminPanel->registerCustomPageManifestInstance([
            'package' => 'jerthedev/cms-blog-system',
            'manifest_url' => '/vendor/cms-blog-system/admin-pages-manifest.json',
            'priority' => 100,
            'base_url' => '/vendor/cms-blog-system',
        ]);

        $this->adminPanel->registerCustomPageManifestInstance([
            'package' => 'jerthedev/ecommerce-system',
            'manifest_url' => '/vendor/ecommerce-system/admin-pages-manifest.json',
            'priority' => 200,
            'base_url' => '/vendor/ecommerce-system',
        ]);

        $aggregated = $this->adminPanel->getAggregatedManifest();

        $this->assertIsArray($aggregated);
        // Note: The actual manifest data would be empty since the files don't exist,
        // but the structure should be there
        $this->assertArrayHasKey('jerthedev/cms-blog-system', $aggregated);
        $this->assertArrayHasKey('jerthedev/ecommerce-system', $aggregated);
    }

    /** @test */
    public function priority_based_resolution_works_correctly()
    {
        // Register manifests with different priorities
        $this->adminPanel->registerCustomPageManifestInstance([
            'package' => 'low-priority-package',
            'manifest_url' => '/vendor/low-priority/manifest.json',
            'priority' => 300,
            'base_url' => '/vendor/low-priority',
        ]);

        $this->adminPanel->registerCustomPageManifestInstance([
            'package' => 'high-priority-package',
            'manifest_url' => '/vendor/high-priority/manifest.json',
            'priority' => 50,
            'base_url' => '/vendor/high-priority',
        ]);

        $this->adminPanel->registerCustomPageManifestInstance([
            'package' => 'medium-priority-package',
            'manifest_url' => '/vendor/medium-priority/manifest.json',
            'priority' => 150,
            'base_url' => '/vendor/medium-priority',
        ]);

        $manifests = $this->registry->all();

        // Should be sorted by priority (lower numbers first)
        $this->assertEquals('high-priority-package', $manifests->first()['package']);
        $this->assertEquals('medium-priority-package', $manifests->skip(1)->first()['package']);
        $this->assertEquals('low-priority-package', $manifests->last()['package']);
    }

    /** @test */
    public function static_registration_method_works()
    {
        // Test the static method that packages would use in their service providers
        AdminPanel::registerCustomPageManifest([
            'package' => 'jerthedev/static-test',
            'manifest_url' => '/vendor/static-test/manifest.json',
            'priority' => 100,
            'base_url' => '/vendor/static-test',
        ]);

        $this->assertTrue($this->registry->hasPackage('jerthedev/static-test'));
    }

    /** @test */
    public function manifest_registry_is_singleton()
    {
        $registry1 = app(CustomPageManifestRegistry::class);
        $registry2 = app(CustomPageManifestRegistry::class);

        $this->assertSame($registry1, $registry2);

        // Register something in one instance
        $registry1->register([
            'package' => 'test/singleton',
            'manifest_url' => '/test/path',
        ]);

        // Should be available in the other instance
        $this->assertTrue($registry2->hasPackage('test/singleton'));
    }

    /** @test */
    public function error_handling_for_invalid_manifest_files()
    {
        // Register a manifest with a non-existent file
        $this->adminPanel->registerCustomPageManifestInstance([
            'package' => 'jerthedev/non-existent',
            'manifest_url' => '/vendor/non-existent/manifest.json',
            'priority' => 100,
            'base_url' => '/vendor/non-existent',
        ]);

        // Should not throw an error, but gracefully handle the missing file
        $aggregated = $this->adminPanel->getAggregatedManifest();

        $this->assertIsArray($aggregated);
        // The package should still be registered, but with empty components
        $this->assertArrayHasKey('jerthedev/non-existent', $aggregated);
    }
}
