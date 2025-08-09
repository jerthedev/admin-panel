<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use InvalidArgumentException;
use JTD\AdminPanel\Support\CustomPageManifestRegistry;
use JTD\AdminPanel\Tests\TestCase;

class CustomPageManifestRegistryTest extends TestCase
{
    protected CustomPageManifestRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new CustomPageManifestRegistry();
    }

    /** @test */
    public function it_can_register_a_custom_page_manifest()
    {
        $config = [
            'package' => 'jerthedev/cms-blog-system',
            'manifest_url' => '/vendor/cms-blog-system/admin-pages-manifest.json',
            'priority' => 100,
            'base_url' => '/vendor/cms-blog-system',
        ];

        $this->registry->register($config);

        $this->assertTrue($this->registry->hasPackage('jerthedev/cms-blog-system'));
        $this->assertEquals(1, $this->registry->count());
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required field: package");

        $this->registry->register([
            'manifest_url' => '/some/path',
        ]);
    }

    /** @test */
    public function it_validates_manifest_url_is_required()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required field: manifest_path or manifest_url");

        $this->registry->register([
            'package' => 'test/package',
        ]);
    }

    /** @test */
    public function it_validates_priority_is_numeric()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Priority must be a non-negative integer");

        $this->registry->register([
            'package' => 'test/package',
            'manifest_url' => '/some/path',
            'priority' => 'invalid',
        ]);
    }

    /** @test */
    public function it_sorts_manifests_by_priority()
    {
        $this->registry->register([
            'package' => 'package-b',
            'manifest_url' => '/path-b',
            'priority' => 200,
        ]);

        $this->registry->register([
            'package' => 'package-a',
            'manifest_url' => '/path-a',
            'priority' => 100,
        ]);

        $this->registry->register([
            'package' => 'package-c',
            'manifest_url' => '/path-c',
            'priority' => 50,
        ]);

        $manifests = $this->registry->all();

        // Should be sorted by priority (lower numbers first)
        $this->assertEquals('package-c', $manifests->first()['package']);
        $this->assertEquals('package-a', $manifests->skip(1)->first()['package']);
        $this->assertEquals('package-b', $manifests->last()['package']);
    }

    /** @test */
    public function it_can_get_manifest_by_package()
    {
        $config = [
            'package' => 'jerthedev/cms-blog-system',
            'manifest_url' => '/vendor/cms-blog-system/admin-pages-manifest.json',
            'priority' => 100,
            'base_url' => '/vendor/cms-blog-system',
        ];

        $this->registry->register($config);

        $manifest = $this->registry->getByPackage('jerthedev/cms-blog-system');

        $this->assertNotNull($manifest);
        $this->assertEquals('jerthedev/cms-blog-system', $manifest['package']);
        $this->assertEquals(100, $manifest['priority']);
        $this->assertEquals('/vendor/cms-blog-system', $manifest['base_url']);
    }

    /** @test */
    public function it_can_unregister_a_package()
    {
        $this->registry->register([
            'package' => 'test/package',
            'manifest_url' => '/some/path',
        ]);

        $this->assertTrue($this->registry->hasPackage('test/package'));

        $result = $this->registry->unregister('test/package');

        $this->assertTrue($result);
        $this->assertFalse($this->registry->hasPackage('test/package'));
        $this->assertEquals(0, $this->registry->count());
    }

    /** @test */
    public function it_returns_false_when_unregistering_non_existent_package()
    {
        $result = $this->registry->unregister('non-existent/package');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_clear_all_manifests()
    {
        $this->registry->register([
            'package' => 'package-1',
            'manifest_url' => '/path-1',
        ]);

        $this->registry->register([
            'package' => 'package-2',
            'manifest_url' => '/path-2',
        ]);

        $this->assertEquals(2, $this->registry->count());

        $this->registry->clear();

        $this->assertEquals(0, $this->registry->count());
    }

    /** @test */
    public function it_sets_default_priority_when_not_provided()
    {
        $this->registry->register([
            'package' => 'test/package',
            'manifest_url' => '/some/path',
        ]);

        $manifest = $this->registry->getByPackage('test/package');

        $this->assertEquals(100, $manifest['priority']);
    }

    /** @test */
    public function it_sets_empty_base_url_when_not_provided()
    {
        $this->registry->register([
            'package' => 'test/package',
            'manifest_url' => '/some/path',
        ]);

        $manifest = $this->registry->getByPackage('test/package');

        $this->assertEquals('', $manifest['base_url']);
    }
}
