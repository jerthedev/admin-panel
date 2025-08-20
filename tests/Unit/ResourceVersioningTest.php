<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use JTD\AdminPanel\Resources\Concerns\HasVersioning;
use PHPUnit\Framework\TestCase;

/**
 * Mock Resource class for testing versioning.
 */
class MockVersionedResource
{
    public $id = 1;
    public $name = 'Test Resource';
    public $description = 'Test Description';
    public $status = 'active';
    public $created_at = '2024-01-01 00:00:00';
    public $updated_at = '2024-01-01 00:00:00';

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function save(): bool
    {
        return true;
    }
}

/**
 * Test class that uses the HasVersioning trait.
 */
class TestVersioningClass
{
    use HasVersioning;

    public static bool $versioningEnabled = true;
    public static int $maxVersions = 50;
    public static bool $versionAllFields = true;
    public static array $versionedFields = [];
    public static array $excludedFields = ['created_at', 'updated_at', 'deleted_at'];
    public static bool $autoVersion = true;
    public static bool $compressVersions = false;

    public MockVersionedResource $resource;
    protected Collection $storedVersions;

    public function __construct()
    {
        $this->resource = new MockVersionedResource();
        $this->storedVersions = collect();
    }

    public function getKey()
    {
        return $this->resource->id;
    }

    public static function class(): string
    {
        return static::class;
    }
}

/**
 * ResourceVersioning Test Class
 */
class ResourceVersioningTest extends TestCase
{
    private TestVersioningClass $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new TestVersioningClass();

        // Reset configuration for each test
        TestVersioningClass::$versioningEnabled = true;
        TestVersioningClass::$maxVersions = 50;
        TestVersioningClass::$versionAllFields = true;
        TestVersioningClass::$versionedFields = [];
        TestVersioningClass::$excludedFields = ['created_at', 'updated_at', 'deleted_at'];
        TestVersioningClass::$autoVersion = true;
        TestVersioningClass::$compressVersions = false;
    }

    // ========================================
    // Basic Versioning Configuration Tests
    // ========================================

    public function test_versioning_trait_has_required_properties(): void
    {
        $this->assertTrue(property_exists(TestVersioningClass::class, 'versioningEnabled'));
        $this->assertTrue(property_exists(TestVersioningClass::class, 'maxVersions'));
        $this->assertTrue(property_exists(TestVersioningClass::class, 'versionAllFields'));
        $this->assertTrue(property_exists(TestVersioningClass::class, 'versionedFields'));
        $this->assertTrue(property_exists(TestVersioningClass::class, 'excludedFields'));
        $this->assertTrue(property_exists(TestVersioningClass::class, 'autoVersion'));
        $this->assertTrue(property_exists(TestVersioningClass::class, 'compressVersions'));
    }

    public function test_default_configuration_values(): void
    {
        $this->assertTrue(TestVersioningClass::$versioningEnabled);
        $this->assertEquals(50, TestVersioningClass::$maxVersions);
        $this->assertTrue(TestVersioningClass::$versionAllFields);
        $this->assertEquals([], TestVersioningClass::$versionedFields);
        $this->assertEquals(['created_at', 'updated_at', 'deleted_at'], TestVersioningClass::$excludedFields);
        $this->assertTrue(TestVersioningClass::$autoVersion);
        $this->assertFalse(TestVersioningClass::$compressVersions);
    }

    // ========================================
    // Version Creation Tests
    // ========================================

    public function test_create_version_returns_empty_array_when_versioning_disabled(): void
    {
        TestVersioningClass::$versioningEnabled = false;

        $result = $this->testClass->createVersion('Test reason');

        $this->assertEquals([], $result);
    }

    public function test_create_version_returns_version_data(): void
    {
        $result = $this->testClass->createVersion('Test reason', ['key' => 'value']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('resource_type', $result);
        $this->assertArrayHasKey('resource_id', $result);
        $this->assertArrayHasKey('version_number', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('reason', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('checksum', $result);

        $this->assertEquals(TestVersioningClass::class, $result['resource_type']);
        $this->assertEquals(1, $result['resource_id']);
        $this->assertEquals(1, $result['version_number']);
        $this->assertEquals('Test reason', $result['reason']);
        $this->assertEquals(['key' => 'value'], $result['metadata']);
    }

    public function test_create_version_excludes_configured_fields(): void
    {
        $result = $this->testClass->createVersion();

        $this->assertArrayNotHasKey('created_at', $result['data']);
        $this->assertArrayNotHasKey('updated_at', $result['data']);
        $this->assertArrayHasKey('name', $result['data']);
        $this->assertArrayHasKey('description', $result['data']);
        $this->assertArrayHasKey('status', $result['data']);
    }

    public function test_create_version_with_compression(): void
    {
        TestVersioningClass::$compressVersions = true;

        $result = $this->testClass->createVersion();

        $this->assertArrayHasKey('compressed', $result);
        $this->assertTrue($result['compressed']);
        $this->assertIsString($result['data']);
    }

    // ========================================
    // Version Retrieval Tests
    // ========================================

    public function test_get_versions_returns_empty_collection_when_versioning_disabled(): void
    {
        TestVersioningClass::$versioningEnabled = false;

        $result = $this->testClass->getVersions();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_get_versions_returns_stored_versions(): void
    {
        $this->testClass->createVersion('Version 1');
        $this->testClass->createVersion('Version 2');

        $versions = $this->testClass->getVersions();

        $this->assertInstanceOf(Collection::class, $versions);
        $this->assertCount(2, $versions);
    }

    public function test_get_version_returns_specific_version(): void
    {
        $this->testClass->createVersion('Version 1');
        $this->testClass->createVersion('Version 2');

        $version = $this->testClass->getVersion(1);

        $this->assertIsArray($version);
        $this->assertEquals(1, $version['version_number']);
        $this->assertEquals('Version 1', $version['reason']);
    }

    public function test_get_version_returns_null_for_nonexistent_version(): void
    {
        $version = $this->testClass->getVersion(999);

        $this->assertNull($version);
    }

    public function test_get_latest_version_returns_most_recent(): void
    {
        $this->testClass->createVersion('Version 1');
        $this->testClass->createVersion('Version 2');
        $this->testClass->createVersion('Version 3');

        $latest = $this->testClass->getLatestVersion();

        $this->assertIsArray($latest);
        $this->assertEquals(3, $latest['version_number']);
        $this->assertEquals('Version 3', $latest['reason']);
    }

    // ========================================
    // Version Comparison Tests
    // ========================================

    public function test_compare_versions_returns_empty_array_when_versioning_disabled(): void
    {
        TestVersioningClass::$versioningEnabled = false;

        $result = $this->testClass->compareVersions(1, 2);

        $this->assertEquals([], $result);
    }

    public function test_compare_versions_returns_changes(): void
    {
        $this->testClass->createVersion('Version 1');

        // Modify the resource
        $this->testClass->resource->name = 'Modified Name';
        $this->testClass->resource->status = 'inactive';

        $this->testClass->createVersion('Version 2');

        $comparison = $this->testClass->compareVersions(1, 2);

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('from_version', $comparison);
        $this->assertArrayHasKey('to_version', $comparison);
        $this->assertArrayHasKey('changes', $comparison);
        $this->assertArrayHasKey('total_changes', $comparison);

        $this->assertEquals(1, $comparison['from_version']);
        $this->assertEquals(2, $comparison['to_version']);
        $this->assertEquals(2, $comparison['total_changes']);

        $this->assertArrayHasKey('name', $comparison['changes']);
        $this->assertArrayHasKey('status', $comparison['changes']);
    }

    // ========================================
    // Version Statistics Tests
    // ========================================

    public function test_get_version_stats_when_versioning_disabled(): void
    {
        TestVersioningClass::$versioningEnabled = false;

        $stats = $this->testClass->getVersionStats();

        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('total_versions', $stats);
        $this->assertFalse($stats['enabled']);
        $this->assertEquals(0, $stats['total_versions']);
    }

    public function test_get_version_stats_returns_correct_data(): void
    {
        $this->testClass->createVersion('Version 1');
        $this->testClass->createVersion('Version 2');

        $stats = $this->testClass->getVersionStats();

        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('total_versions', $stats);
        $this->assertArrayHasKey('latest_version', $stats);
        $this->assertArrayHasKey('oldest_version', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('average_size', $stats);
        $this->assertArrayHasKey('compression_enabled', $stats);
        $this->assertArrayHasKey('auto_versioning', $stats);
        $this->assertArrayHasKey('max_versions', $stats);

        $this->assertTrue($stats['enabled']);
        $this->assertEquals(2, $stats['total_versions']);
        $this->assertEquals(2, $stats['latest_version']);
        $this->assertEquals(1, $stats['oldest_version']);
        $this->assertIsInt($stats['total_size']);
        $this->assertIsFloat($stats['average_size']);
        $this->assertFalse($stats['compression_enabled']);
        $this->assertTrue($stats['auto_versioning']);
        $this->assertEquals(50, $stats['max_versions']);
    }

    // ========================================
    // Helper Method Tests
    // ========================================

    public function test_versioning_methods_exist(): void
    {
        $this->assertTrue(method_exists($this->testClass, 'createVersion'));
        $this->assertTrue(method_exists($this->testClass, 'getVersions'));
        $this->assertTrue(method_exists($this->testClass, 'getVersion'));
        $this->assertTrue(method_exists($this->testClass, 'getLatestVersion'));
        $this->assertTrue(method_exists($this->testClass, 'restoreToVersion'));
        $this->assertTrue(method_exists($this->testClass, 'compareVersions'));
        $this->assertTrue(method_exists($this->testClass, 'getVersionStats'));
    }

    public function test_restore_to_version_returns_false_when_versioning_disabled(): void
    {
        TestVersioningClass::$versioningEnabled = false;

        $result = $this->testClass->restoreToVersion(1);

        $this->assertFalse($result);
    }

    public function test_restore_to_version_returns_false_for_nonexistent_version(): void
    {
        $result = $this->testClass->restoreToVersion(999);

        $this->assertFalse($result);
    }

    public function test_restore_to_version_restores_data(): void
    {
        // Create initial version
        $this->testClass->createVersion('Initial version');

        // Modify the resource
        $originalName = $this->testClass->resource->name;
        $this->testClass->resource->name = 'Modified Name';
        $this->testClass->createVersion('Modified version');

        // Restore to version 1
        $result = $this->testClass->restoreToVersion(1, 'Restored to original');

        $this->assertTrue($result);
        $this->assertEquals($originalName, $this->testClass->resource->name);
    }
}
