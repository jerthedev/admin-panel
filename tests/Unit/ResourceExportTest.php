<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use JTD\AdminPanel\Resources\Concerns\HasExport;
use PHPUnit\Framework\TestCase;

/**
 * Mock Model for testing export functionality.
 */
class MockExportModel
{
    public $id;
    public $name;
    public $email;
    public $status;
    public $password;
    public $created_at;
    public $updated_at;

    public function __construct(array $attributes = [])
    {
        $this->id = $attributes['id'] ?? 1;
        $this->name = $attributes['name'] ?? 'Test User';
        $this->email = $attributes['email'] ?? 'test@example.com';
        $this->status = $attributes['status'] ?? 'active';
        $this->password = $attributes['password'] ?? 'secret';
        $this->created_at = $attributes['created_at'] ?? '2024-01-01 00:00:00';
        $this->updated_at = $attributes['updated_at'] ?? '2024-01-01 00:00:00';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'password' => $this->password,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function newQuery()
    {
        return new MockQueryBuilder();
    }
}

/**
 * Mock Query Builder for testing.
 */
class MockQueryBuilder
{
    protected array $data = [];
    protected int $limit = 0;

    public function __construct()
    {
        $this->data = [
            new MockExportModel(['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com']),
            new MockExportModel(['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com']),
            new MockExportModel(['id' => 3, 'name' => 'User 3', 'email' => 'user3@example.com']),
        ];
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function get(): Collection
    {
        $data = $this->data;
        
        if ($this->limit > 0) {
            $data = array_slice($data, 0, $this->limit);
        }
        
        return collect($data);
    }

    public function getModel(): MockExportModel
    {
        return new MockExportModel();
    }
}

/**
 * Test class that uses the HasExport trait.
 */
class TestExportClass
{
    use HasExport;

    public static bool $exportEnabled = true;
    public static array $exportFormats = [
        'csv' => 'CSV',
        'xlsx' => 'Excel',
        'json' => 'JSON',
        'xml' => 'XML',
        'pdf' => 'PDF',
    ];
    public static string $defaultExportFormat = 'csv';
    public static int $maxExportRecords = 10000;
    public static bool $includeTimestamps = true;
    public static bool $includeTrashed = false;
    public static array $excludeFromExport = ['password', 'remember_token'];
    public static array $exportTransformations = [];

    public static function newModel(): MockExportModel
    {
        return new MockExportModel();
    }

    public static function applySearchToQuery($query, string $search)
    {
        return $query;
    }

    public static function applyFiltersToQuery($query, array $filters)
    {
        return $query;
    }
}

/**
 * ResourceExport Test Class
 */
class ResourceExportTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new Request();
        
        // Reset configuration for each test
        TestExportClass::$exportEnabled = true;
        TestExportClass::$exportFormats = [
            'csv' => 'CSV',
            'xlsx' => 'Excel',
            'json' => 'JSON',
            'xml' => 'XML',
            'pdf' => 'PDF',
        ];
        TestExportClass::$defaultExportFormat = 'csv';
        TestExportClass::$maxExportRecords = 10000;
        TestExportClass::$includeTimestamps = true;
        TestExportClass::$includeTrashed = false;
        TestExportClass::$excludeFromExport = ['password', 'remember_token'];
        TestExportClass::$exportTransformations = [];
    }

    // ========================================
    // Basic Export Configuration Tests
    // ========================================

    public function test_export_trait_has_required_properties(): void
    {
        $this->assertTrue(property_exists(TestExportClass::class, 'exportEnabled'));
        $this->assertTrue(property_exists(TestExportClass::class, 'exportFormats'));
        $this->assertTrue(property_exists(TestExportClass::class, 'defaultExportFormat'));
        $this->assertTrue(property_exists(TestExportClass::class, 'maxExportRecords'));
        $this->assertTrue(property_exists(TestExportClass::class, 'includeTimestamps'));
        $this->assertTrue(property_exists(TestExportClass::class, 'includeTrashed'));
        $this->assertTrue(property_exists(TestExportClass::class, 'excludeFromExport'));
        $this->assertTrue(property_exists(TestExportClass::class, 'exportTransformations'));
    }

    public function test_default_configuration_values(): void
    {
        $this->assertTrue(TestExportClass::$exportEnabled);
        $this->assertIsArray(TestExportClass::$exportFormats);
        $this->assertEquals('csv', TestExportClass::$defaultExportFormat);
        $this->assertEquals(10000, TestExportClass::$maxExportRecords);
        $this->assertTrue(TestExportClass::$includeTimestamps);
        $this->assertFalse(TestExportClass::$includeTrashed);
        $this->assertContains('password', TestExportClass::$excludeFromExport);
        $this->assertIsArray(TestExportClass::$exportTransformations);
    }

    // ========================================
    // Export Execution Tests
    // ========================================

    public function test_export_resources_returns_error_when_disabled(): void
    {
        TestExportClass::$exportEnabled = false;
        
        $result = TestExportClass::exportResources($this->request);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('disabled', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_export_resources_returns_error_for_unsupported_format(): void
    {
        $result = TestExportClass::exportResources($this->request, 'unsupported');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unsupported export format', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_export_resources_succeeds_with_csv_format(): void
    {
        $result = TestExportClass::exportResources($this->request, 'csv');
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('completed successfully', $result['message']);
        $this->assertNotNull($result['data']);
        $this->assertEquals('csv', $result['format']);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('generated_at', $result);
    }

    public function test_export_resources_succeeds_with_json_format(): void
    {
        $result = TestExportClass::exportResources($this->request, 'json');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('json', $result['format']);
        $this->assertIsString($result['data']);
        
        // Verify it's valid JSON
        $decoded = json_decode($result['data'], true);
        $this->assertIsArray($decoded);
    }

    public function test_export_resources_succeeds_with_xml_format(): void
    {
        $result = TestExportClass::exportResources($this->request, 'xml');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('xml', $result['format']);
        $this->assertIsString($result['data']);
        $this->assertStringContainsString('<?xml', $result['data']);
    }

    public function test_export_resources_uses_default_format_when_none_specified(): void
    {
        $result = TestExportClass::exportResources($this->request);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('csv', $result['format']);
    }

    // ========================================
    // Data Transformation Tests
    // ========================================

    public function test_export_excludes_password_field(): void
    {
        $result = TestExportClass::exportResources($this->request, 'json');
        
        $this->assertTrue($result['success']);
        $data = json_decode($result['data'], true);
        
        foreach ($data as $item) {
            $this->assertArrayNotHasKey('password', $item);
        }
    }

    public function test_export_includes_timestamps_by_default(): void
    {
        $result = TestExportClass::exportResources($this->request, 'json');
        
        $this->assertTrue($result['success']);
        $data = json_decode($result['data'], true);
        
        foreach ($data as $item) {
            $this->assertArrayHasKey('created_at', $item);
            $this->assertArrayHasKey('updated_at', $item);
        }
    }

    public function test_export_excludes_timestamps_when_configured(): void
    {
        TestExportClass::$includeTimestamps = false;
        
        $result = TestExportClass::exportResources($this->request, 'json');
        
        $this->assertTrue($result['success']);
        $data = json_decode($result['data'], true);
        
        foreach ($data as $item) {
            $this->assertArrayNotHasKey('created_at', $item);
            $this->assertArrayNotHasKey('updated_at', $item);
        }
    }

    public function test_export_with_field_selection(): void
    {
        $options = ['fields' => ['id', 'name', 'email']];
        
        $result = TestExportClass::exportResources($this->request, 'json', $options);
        
        $this->assertTrue($result['success']);
        $data = json_decode($result['data'], true);
        
        foreach ($data as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('email', $item);
            $this->assertArrayNotHasKey('status', $item);
        }
    }

    // ========================================
    // Format-Specific Tests
    // ========================================

    public function test_csv_format_contains_headers(): void
    {
        $result = TestExportClass::exportResources($this->request, 'csv');
        
        $this->assertTrue($result['success']);
        $lines = explode("\n", $result['data']);
        $headers = str_getcsv($lines[0]);
        
        $this->assertContains('id', $headers);
        $this->assertContains('name', $headers);
        $this->assertContains('email', $headers);
    }

    public function test_xml_format_has_correct_structure(): void
    {
        $result = TestExportClass::exportResources($this->request, 'xml');
        
        $this->assertTrue($result['success']);
        $xml = simplexml_load_string($result['data']);
        
        $this->assertNotFalse($xml);
        $this->assertEquals('resources', $xml->getName());
        $this->assertGreaterThan(0, count($xml->resource));
    }

    public function test_pdf_format_returns_text_representation(): void
    {
        $result = TestExportClass::exportResources($this->request, 'pdf');
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('PDF Export', $result['data']);
    }

    // ========================================
    // Available Formats Tests
    // ========================================

    public function test_get_available_export_formats_returns_empty_when_disabled(): void
    {
        TestExportClass::$exportEnabled = false;
        
        $formats = TestExportClass::getAvailableExportFormats($this->request);
        
        $this->assertEmpty($formats);
    }

    public function test_get_available_export_formats_returns_configured_formats(): void
    {
        $formats = TestExportClass::getAvailableExportFormats($this->request);
        
        $this->assertCount(5, $formats);
        $this->assertArrayHasKey('csv', $formats);
        $this->assertArrayHasKey('json', $formats);
        $this->assertArrayHasKey('xml', $formats);
        
        foreach ($formats as $format) {
            $this->assertArrayHasKey('key', $format);
            $this->assertArrayHasKey('label', $format);
            $this->assertArrayHasKey('is_default', $format);
        }
        
        $this->assertTrue($formats['csv']['is_default']);
    }

    // ========================================
    // Statistics Tests
    // ========================================

    public function test_get_export_stats_returns_correct_data(): void
    {
        $stats = TestExportClass::getExportStats();
        
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('available_formats', $stats);
        $this->assertArrayHasKey('default_format', $stats);
        $this->assertArrayHasKey('max_records', $stats);
        $this->assertArrayHasKey('include_timestamps', $stats);
        $this->assertArrayHasKey('include_trashed', $stats);
        $this->assertArrayHasKey('excluded_fields', $stats);
        $this->assertArrayHasKey('transformations_count', $stats);
        
        $this->assertTrue($stats['enabled']);
        $this->assertEquals('csv', $stats['default_format']);
        $this->assertEquals(10000, $stats['max_records']);
        $this->assertTrue($stats['include_timestamps']);
        $this->assertFalse($stats['include_trashed']);
        $this->assertContains('password', $stats['excluded_fields']);
        $this->assertEquals(0, $stats['transformations_count']);
    }

    // ========================================
    // Method Existence Tests
    // ========================================

    public function test_export_methods_exist(): void
    {
        $this->assertTrue(method_exists(TestExportClass::class, 'exportResources'));
        $this->assertTrue(method_exists(TestExportClass::class, 'getAvailableExportFormats'));
        $this->assertTrue(method_exists(TestExportClass::class, 'getExportStats'));
    }

    public function test_protected_export_methods_exist(): void
    {
        $reflection = new \ReflectionClass(TestExportClass::class);
        
        $this->assertTrue($reflection->hasMethod('getExportData'));
        $this->assertTrue($reflection->hasMethod('formatExportData'));
        $this->assertTrue($reflection->hasMethod('transformExportData'));
        $this->assertTrue($reflection->hasMethod('formatAsCsv'));
        $this->assertTrue($reflection->hasMethod('formatAsJson'));
        $this->assertTrue($reflection->hasMethod('formatAsXml'));
        $this->assertTrue($reflection->hasMethod('formatAsPdf'));
        $this->assertTrue($reflection->hasMethod('generateExportFilename'));
        $this->assertTrue($reflection->hasMethod('getResourceName'));
        $this->assertTrue($reflection->hasMethod('canExportFormat'));
        $this->assertTrue($reflection->hasMethod('validateExportParameters'));
    }

    // ========================================
    // Filename Generation Tests
    // ========================================

    public function test_filename_generation_includes_format_extension(): void
    {
        $result = TestExportClass::exportResources($this->request, 'csv');
        
        $this->assertTrue($result['success']);
        $this->assertStringEndsWith('.csv', $result['filename']);
        $this->assertStringContainsString('testexport', $result['filename']);
    }

    public function test_custom_filename_option(): void
    {
        $options = ['filename' => 'custom_export'];
        
        $result = TestExportClass::exportResources($this->request, 'json', $options);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('custom_export.json', $result['filename']);
    }
}
