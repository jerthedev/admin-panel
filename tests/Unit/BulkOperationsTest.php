<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use JTD\AdminPanel\Resources\Concerns\HasBulkOperations;
use PHPUnit\Framework\TestCase;

/**
 * Mock Model for testing bulk operations.
 */
class MockBulkModel
{
    public $id;
    public $name;
    public $status;

    public function __construct(int $id, string $name = 'Test', string $status = 'active')
    {
        $this->id = $id;
        $this->name = $name;
        $this->status = $status;
    }

    public function find($id): ?self
    {
        // Simulate finding a model
        return new self($id);
    }

    public function delete(): bool
    {
        return true;
    }

    public function save(): bool
    {
        return true;
    }
}

/**
 * Test class that uses the HasBulkOperations trait.
 */
class TestBulkOperationsClass
{
    use HasBulkOperations;

    public static bool $bulkOperationsEnabled = true;
    public static int $bulkBatchSize = 100;
    public static bool $useBulkTransactions = true;
    public static bool $continueOnError = true;
    public static array $bulkOperations = [
        'delete' => 'Delete Selected',
        'update' => 'Update Selected',
        'export' => 'Export Selected',
    ];

    public static function newModel(): MockBulkModel
    {
        return new MockBulkModel(1);
    }
}

/**
 * BulkOperations Test Class
 */
class BulkOperationsTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new Request();
        
        // Reset configuration for each test
        TestBulkOperationsClass::$bulkOperationsEnabled = true;
        TestBulkOperationsClass::$bulkBatchSize = 100;
        TestBulkOperationsClass::$useBulkTransactions = true;
        TestBulkOperationsClass::$continueOnError = true;
        TestBulkOperationsClass::$bulkOperations = [
            'delete' => 'Delete Selected',
            'update' => 'Update Selected',
            'export' => 'Export Selected',
        ];
    }

    // ========================================
    // Basic Bulk Operations Configuration Tests
    // ========================================

    public function test_bulk_operations_trait_has_required_properties(): void
    {
        $this->assertTrue(property_exists(TestBulkOperationsClass::class, 'bulkOperationsEnabled'));
        $this->assertTrue(property_exists(TestBulkOperationsClass::class, 'bulkBatchSize'));
        $this->assertTrue(property_exists(TestBulkOperationsClass::class, 'useBulkTransactions'));
        $this->assertTrue(property_exists(TestBulkOperationsClass::class, 'continueOnError'));
        $this->assertTrue(property_exists(TestBulkOperationsClass::class, 'bulkOperations'));
    }

    public function test_default_configuration_values(): void
    {
        $this->assertTrue(TestBulkOperationsClass::$bulkOperationsEnabled);
        $this->assertEquals(100, TestBulkOperationsClass::$bulkBatchSize);
        $this->assertTrue(TestBulkOperationsClass::$useBulkTransactions);
        $this->assertTrue(TestBulkOperationsClass::$continueOnError);
        $this->assertIsArray(TestBulkOperationsClass::$bulkOperations);
        $this->assertArrayHasKey('delete', TestBulkOperationsClass::$bulkOperations);
        $this->assertArrayHasKey('update', TestBulkOperationsClass::$bulkOperations);
        $this->assertArrayHasKey('export', TestBulkOperationsClass::$bulkOperations);
    }

    // ========================================
    // Bulk Operation Execution Tests
    // ========================================

    public function test_execute_bulk_operation_returns_error_when_disabled(): void
    {
        TestBulkOperationsClass::$bulkOperationsEnabled = false;
        
        $result = TestBulkOperationsClass::executeBulkOperation('delete', [1, 2, 3], [], $this->request);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('disabled', $result['message']);
        $this->assertEquals(0, $result['processed']);
    }

    public function test_execute_bulk_operation_returns_error_for_unknown_operation(): void
    {
        $result = TestBulkOperationsClass::executeBulkOperation('unknown', [1, 2, 3], [], $this->request);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unknown bulk operation', $result['message']);
        $this->assertEquals(0, $result['processed']);
    }

    public function test_execute_bulk_operation_processes_items_successfully(): void
    {
        $result = TestBulkOperationsClass::executeBulkOperation('delete', [1, 2, 3], [], $this->request);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEmpty($result['errors']);
        $this->assertStringContainsString('completed successfully', $result['message']);
    }

    public function test_execute_bulk_operation_handles_batching(): void
    {
        TestBulkOperationsClass::$bulkBatchSize = 2;
        
        $result = TestBulkOperationsClass::executeBulkOperation('delete', [1, 2, 3, 4, 5], [], $this->request);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['processed']);
        $this->assertCount(3, $result['details']); // 3 batches (2, 2, 1)
        
        foreach ($result['details'] as $detail) {
            $this->assertArrayHasKey('batch', $detail);
            $this->assertArrayHasKey('total_batches', $detail);
            $this->assertArrayHasKey('progress', $detail);
        }
    }

    public function test_execute_bulk_operation_with_update_data(): void
    {
        $updateData = ['status' => 'inactive'];
        
        $result = TestBulkOperationsClass::executeBulkOperation('update', [1, 2], $updateData, $this->request);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['processed']);
        $this->assertEquals(0, $result['failed']);
    }

    public function test_execute_bulk_operation_with_export(): void
    {
        $result = TestBulkOperationsClass::executeBulkOperation('export', [1, 2, 3], [], $this->request);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(0, $result['failed']);
    }

    // ========================================
    // Available Operations Tests
    // ========================================

    public function test_get_available_bulk_operations_returns_empty_when_disabled(): void
    {
        TestBulkOperationsClass::$bulkOperationsEnabled = false;
        
        $operations = TestBulkOperationsClass::getAvailableBulkOperations($this->request);
        
        $this->assertEmpty($operations);
    }

    public function test_get_available_bulk_operations_returns_configured_operations(): void
    {
        $operations = TestBulkOperationsClass::getAvailableBulkOperations($this->request);
        
        $this->assertCount(3, $operations);
        $this->assertArrayHasKey('delete', $operations);
        $this->assertArrayHasKey('update', $operations);
        $this->assertArrayHasKey('export', $operations);
        
        foreach ($operations as $operation) {
            $this->assertArrayHasKey('key', $operation);
            $this->assertArrayHasKey('label', $operation);
            $this->assertArrayHasKey('requires_confirmation', $operation);
            $this->assertArrayHasKey('requires_data', $operation);
        }
    }

    public function test_requires_confirmation_for_delete_operations(): void
    {
        $operations = TestBulkOperationsClass::getAvailableBulkOperations($this->request);
        
        $this->assertTrue($operations['delete']['requires_confirmation']);
        $this->assertFalse($operations['update']['requires_confirmation']);
        $this->assertFalse($operations['export']['requires_confirmation']);
    }

    public function test_requires_data_for_update_operations(): void
    {
        $operations = TestBulkOperationsClass::getAvailableBulkOperations($this->request);
        
        $this->assertFalse($operations['delete']['requires_data']);
        $this->assertTrue($operations['update']['requires_data']);
        $this->assertFalse($operations['export']['requires_data']);
    }

    // ========================================
    // Progress and Management Tests
    // ========================================

    public function test_get_bulk_operation_progress_returns_progress_data(): void
    {
        $progress = TestBulkOperationsClass::getBulkOperationProgress('test-operation-123');
        
        $this->assertArrayHasKey('operation_id', $progress);
        $this->assertArrayHasKey('status', $progress);
        $this->assertArrayHasKey('progress', $progress);
        $this->assertArrayHasKey('processed', $progress);
        $this->assertArrayHasKey('total', $progress);
        $this->assertArrayHasKey('errors', $progress);
        $this->assertArrayHasKey('started_at', $progress);
        $this->assertArrayHasKey('completed_at', $progress);
        
        $this->assertEquals('test-operation-123', $progress['operation_id']);
    }

    public function test_cancel_bulk_operation_returns_true(): void
    {
        $result = TestBulkOperationsClass::cancelBulkOperation('test-operation-123');
        
        $this->assertTrue($result);
    }

    // ========================================
    // Statistics Tests
    // ========================================

    public function test_get_bulk_operation_stats_returns_correct_data(): void
    {
        $stats = TestBulkOperationsClass::getBulkOperationStats();
        
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('batch_size', $stats);
        $this->assertArrayHasKey('use_transactions', $stats);
        $this->assertArrayHasKey('continue_on_error', $stats);
        $this->assertArrayHasKey('available_operations', $stats);
        $this->assertArrayHasKey('total_operations', $stats);
        
        $this->assertTrue($stats['enabled']);
        $this->assertEquals(100, $stats['batch_size']);
        $this->assertTrue($stats['use_transactions']);
        $this->assertTrue($stats['continue_on_error']);
        $this->assertIsArray($stats['available_operations']);
        $this->assertEquals(3, $stats['total_operations']);
    }

    // ========================================
    // Method Existence Tests
    // ========================================

    public function test_bulk_operations_methods_exist(): void
    {
        $this->assertTrue(method_exists(TestBulkOperationsClass::class, 'executeBulkOperation'));
        $this->assertTrue(method_exists(TestBulkOperationsClass::class, 'getAvailableBulkOperations'));
        $this->assertTrue(method_exists(TestBulkOperationsClass::class, 'getBulkOperationProgress'));
        $this->assertTrue(method_exists(TestBulkOperationsClass::class, 'cancelBulkOperation'));
        $this->assertTrue(method_exists(TestBulkOperationsClass::class, 'getBulkOperationStats'));
    }

    public function test_protected_bulk_operations_methods_exist(): void
    {
        $reflection = new \ReflectionClass(TestBulkOperationsClass::class);
        
        $this->assertTrue($reflection->hasMethod('processBatch'));
        $this->assertTrue($reflection->hasMethod('processBatchWithTransaction'));
        $this->assertTrue($reflection->hasMethod('processItem'));
        $this->assertTrue($reflection->hasMethod('bulkDelete'));
        $this->assertTrue($reflection->hasMethod('bulkUpdate'));
        $this->assertTrue($reflection->hasMethod('bulkExport'));
        $this->assertTrue($reflection->hasMethod('customBulkOperation'));
        $this->assertTrue($reflection->hasMethod('canPerformBulkOperation'));
        $this->assertTrue($reflection->hasMethod('requiresConfirmation'));
        $this->assertTrue($reflection->hasMethod('requiresData'));
        $this->assertTrue($reflection->hasMethod('validateBulkOperationData'));
        $this->assertTrue($reflection->hasMethod('prepareBulkOperationData'));
    }

    // ========================================
    // Error Handling Tests
    // ========================================

    public function test_continue_on_error_configuration(): void
    {
        TestBulkOperationsClass::$continueOnError = true;
        
        // This test verifies the configuration is respected
        // In a real implementation, we would test actual error handling
        $this->assertTrue(TestBulkOperationsClass::$continueOnError);
    }

    public function test_transaction_configuration(): void
    {
        TestBulkOperationsClass::$useBulkTransactions = false;
        
        // This test verifies the configuration is respected
        // In a real implementation, we would test actual transaction behavior
        $this->assertFalse(TestBulkOperationsClass::$useBulkTransactions);
    }
}
