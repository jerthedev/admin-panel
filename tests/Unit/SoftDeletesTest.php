<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use JTD\AdminPanel\Resources\Concerns\HasSoftDeletes;
use PHPUnit\Framework\TestCase;

/**
 * Test class that uses the HasSoftDeletes trait.
 */
class TestSoftDeletesClass
{
    use HasSoftDeletes;

    public static int $trashRetentionDays = 30;
    public static bool $showRestoreAction = true;
    public static bool $showForceDeleteAction = true;
}

/**
 * SoftDeletes Test Class
 */
class SoftDeletesTest extends TestCase
{
    private TestSoftDeletesClass $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new TestSoftDeletesClass();
    }

    // ========================================
    // Basic Trait Tests
    // ========================================

    public function test_trait_has_soft_deletes_methods(): void
    {
        $this->assertTrue(method_exists($this->testClass, 'isTrashed'));
        $this->assertTrue(method_exists($this->testClass, 'getDeletedAt'));
        $this->assertTrue(method_exists($this->testClass, 'getDaysSinceDeletion'));
        $this->assertTrue(method_exists($this->testClass, 'isPermanentlyDeletable'));
        $this->assertTrue(method_exists($this->testClass, 'restore'));
        $this->assertTrue(method_exists($this->testClass, 'forceDelete'));
        $this->assertTrue(method_exists($this->testClass, 'softDelete'));
    }

    public function test_trait_has_static_properties(): void
    {
        $this->assertEquals(30, TestSoftDeletesClass::$trashRetentionDays);
        $this->assertTrue(TestSoftDeletesClass::$showRestoreAction);
        $this->assertTrue(TestSoftDeletesClass::$showForceDeleteAction);
    }

    public function test_trait_has_static_methods(): void
    {
        $this->assertTrue(method_exists(TestSoftDeletesClass::class, 'usesSoftDeletes'));
        $this->assertTrue(method_exists(TestSoftDeletesClass::class, 'getSoftDeleteColumn'));
        $this->assertTrue(method_exists(TestSoftDeletesClass::class, 'getTrashStats'));
        $this->assertTrue(method_exists(TestSoftDeletesClass::class, 'bulkRestore'));
        $this->assertTrue(method_exists(TestSoftDeletesClass::class, 'bulkForceDelete'));
        $this->assertTrue(method_exists(TestSoftDeletesClass::class, 'cleanupOldTrashed'));
    }

    // ========================================
    // Configuration Tests
    // ========================================

    public function test_default_configuration_values(): void
    {
        $this->assertEquals(30, TestSoftDeletesClass::$trashRetentionDays);
        $this->assertTrue(TestSoftDeletesClass::$showRestoreAction);
        $this->assertTrue(TestSoftDeletesClass::$showForceDeleteAction);
    }

    public function test_soft_deletes_trait_methods_exist(): void
    {
        $methods = [
            'isTrashed',
            'getDeletedAt',
            'getDaysSinceDeletion',
            'isPermanentlyDeletable',
            'restore',
            'forceDelete',
            'softDelete',
            'getSoftDeleteActions',
            'getTrashStatusInfo'
        ];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists($this->testClass, $method),
                "Method {$method} should exist in HasSoftDeletes trait"
            );
        }
    }

    public function test_soft_deletes_static_methods_exist(): void
    {
        $staticMethods = [
            'usesSoftDeletes',
            'getSoftDeleteColumn',
            'getTrashedResources',
            'getResourcesWithTrashed',
            'getActiveResources',
            'getTrashStats',
            'bulkRestore',
            'bulkForceDelete',
            'cleanupOldTrashed'
        ];

        foreach ($staticMethods as $method) {
            $this->assertTrue(
                method_exists(TestSoftDeletesClass::class, $method),
                "Static method {$method} should exist in HasSoftDeletes trait"
            );
        }
    }
}
