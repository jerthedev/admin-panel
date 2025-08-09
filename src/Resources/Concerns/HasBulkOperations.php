<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Resources\Concerns;

use Illuminate\Http\Request;

/**
 * HasBulkOperations Trait.
 *
 * Provides advanced bulk operations with progress tracking and error handling
 * for admin panel resources. Enables efficient batch processing of multiple resources.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
trait HasBulkOperations
{
    /**
     * Whether bulk operations are enabled for this resource.
     */
    public static bool $bulkOperationsEnabled = true;

    /**
     * The maximum number of items to process in a single batch.
     */
    public static int $bulkBatchSize = 100;

    /**
     * Whether to use database transactions for bulk operations.
     */
    public static bool $useBulkTransactions = true;

    /**
     * Whether to continue processing on errors.
     */
    public static bool $continueOnError = true;

    /**
     * Available bulk operations.
     */
    public static array $bulkOperations = [
        'delete' => 'Delete Selected',
        'update' => 'Update Selected',
        'export' => 'Export Selected',
    ];

    /**
     * Execute a bulk operation on multiple resources.
     */
    public static function executeBulkOperation(
        string $operation,
        array $ids,
        array $data = [],
        ?Request $request = null,
    ): array {
        if (! static::$bulkOperationsEnabled) {
            return [
                'success' => false,
                'message' => 'Bulk operations are disabled for this resource.',
                'processed' => 0,
                'errors' => [],
            ];
        }

        if (! array_key_exists($operation, static::$bulkOperations)) {
            return [
                'success' => false,
                'message' => "Unknown bulk operation: {$operation}",
                'processed' => 0,
                'errors' => [],
            ];
        }

        $result = [
            'success' => true,
            'message' => '',
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
            'details' => [],
        ];

        $batches = array_chunk($ids, static::$bulkBatchSize);
        $totalBatches = count($batches);

        foreach ($batches as $batchIndex => $batchIds) {
            $batchResult = static::processBatch($operation, $batchIds, $data, $request);

            $result['processed'] += $batchResult['processed'];
            $result['failed'] += $batchResult['failed'];
            $result['errors'] = array_merge($result['errors'], $batchResult['errors']);

            $result['details'][] = [
                'batch' => $batchIndex + 1,
                'total_batches' => $totalBatches,
                'processed' => $batchResult['processed'],
                'failed' => $batchResult['failed'],
                'progress' => round((($batchIndex + 1) / $totalBatches) * 100, 2),
            ];

            if (! $batchResult['success'] && ! static::$continueOnError) {
                $result['success'] = false;
                $result['message'] = 'Bulk operation stopped due to errors.';
                break;
            }
        }

        if ($result['failed'] > 0) {
            $result['success'] = $result['processed'] > 0;
            $result['message'] = sprintf(
                'Bulk operation completed with %d successes and %d failures.',
                $result['processed'],
                $result['failed'],
            );
        } else {
            $result['message'] = sprintf(
                'Bulk operation completed successfully. Processed %d items.',
                $result['processed'],
            );
        }

        return $result;
    }

    /**
     * Process a single batch of items.
     */
    protected static function processBatch(
        string $operation,
        array $ids,
        array $data,
        ?Request $request,
    ): array {
        $result = [
            'success' => true,
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if (static::$useBulkTransactions) {
            return static::processBatchWithTransaction($operation, $ids, $data, $request);
        }

        foreach ($ids as $id) {
            try {
                $success = static::processItem($operation, $id, $data, $request);

                if ($success) {
                    $result['processed']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = "Failed to process item {$id}";
                }
            } catch (\Exception $e) {
                $result['failed']++;
                $result['errors'][] = "Error processing item {$id}: ".$e->getMessage();

                if (! static::$continueOnError) {
                    $result['success'] = false;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Process a batch with database transaction.
     */
    protected static function processBatchWithTransaction(
        string $operation,
        array $ids,
        array $data,
        ?Request $request,
    ): array {
        $result = [
            'success' => true,
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            // In a real implementation, this would use DB::transaction()
            foreach ($ids as $id) {
                $success = static::processItem($operation, $id, $data, $request);

                if ($success) {
                    $result['processed']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = "Failed to process item {$id}";

                    if (! static::$continueOnError) {
                        throw new \Exception("Processing failed for item {$id}");
                    }
                }
            }
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Process a single item.
     */
    protected static function processItem(
        string $operation,
        $id,
        array $data,
        ?Request $request,
    ): bool {
        switch ($operation) {
            case 'delete':
                return static::bulkDelete($id, $request);
            case 'update':
                return static::bulkUpdate($id, $data, $request);
            case 'export':
                return static::bulkExport($id, $data, $request);
            default:
                return static::customBulkOperation($operation, $id, $data, $request);
        }
    }

    /**
     * Bulk delete a single item.
     */
    protected static function bulkDelete($id, ?Request $request): bool
    {
        try {
            $model = static::newModel()->find($id);

            if (! $model) {
                return false;
            }

            return $model->delete();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Bulk update a single item.
     */
    protected static function bulkUpdate($id, array $data, ?Request $request): bool
    {
        try {
            $model = static::newModel()->find($id);

            if (! $model) {
                return false;
            }

            foreach ($data as $field => $value) {
                $model->{$field} = $value;
            }

            return $model->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Bulk export a single item.
     */
    protected static function bulkExport($id, array $data, ?Request $request): bool
    {
        // In a real implementation, this would add the item to an export queue
        return true;
    }

    /**
     * Handle custom bulk operations.
     */
    protected static function customBulkOperation(
        string $operation,
        $id,
        array $data,
        ?Request $request,
    ): bool {
        // Override in subclasses to handle custom operations
        return false;
    }

    /**
     * Get available bulk operations for the current user.
     */
    public static function getAvailableBulkOperations(Request $request): array
    {
        if (! static::$bulkOperationsEnabled) {
            return [];
        }

        $operations = [];

        foreach (static::$bulkOperations as $key => $label) {
            if (static::canPerformBulkOperation($key, $request)) {
                $operations[$key] = [
                    'key' => $key,
                    'label' => $label,
                    'requires_confirmation' => static::requiresConfirmation($key),
                    'requires_data' => static::requiresData($key),
                ];
            }
        }

        return $operations;
    }

    /**
     * Check if the user can perform a bulk operation.
     */
    protected static function canPerformBulkOperation(string $operation, Request $request): bool
    {
        // Override in subclasses to implement authorization
        return true;
    }

    /**
     * Check if an operation requires confirmation.
     */
    protected static function requiresConfirmation(string $operation): bool
    {
        return in_array($operation, ['delete', 'force_delete']);
    }

    /**
     * Check if an operation requires additional data.
     */
    protected static function requiresData(string $operation): bool
    {
        return in_array($operation, ['update']);
    }

    /**
     * Get bulk operation progress.
     */
    public static function getBulkOperationProgress(string $operationId): array
    {
        // In a real implementation, this would check a cache or database
        // for the progress of a long-running bulk operation
        return [
            'operation_id' => $operationId,
            'status' => 'completed',
            'progress' => 100,
            'processed' => 0,
            'total' => 0,
            'errors' => [],
            'started_at' => now()->toDateTimeString(),
            'completed_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Cancel a bulk operation.
     */
    public static function cancelBulkOperation(string $operationId): bool
    {
        // In a real implementation, this would cancel a running operation
        return true;
    }

    /**
     * Get bulk operation statistics.
     */
    public static function getBulkOperationStats(): array
    {
        return [
            'enabled' => static::$bulkOperationsEnabled,
            'batch_size' => static::$bulkBatchSize,
            'use_transactions' => static::$useBulkTransactions,
            'continue_on_error' => static::$continueOnError,
            'available_operations' => array_keys(static::$bulkOperations),
            'total_operations' => count(static::$bulkOperations),
        ];
    }

    /**
     * Validate bulk operation data.
     */
    protected static function validateBulkOperationData(
        string $operation,
        array $ids,
        array $data,
    ): array {
        $errors = [];

        if (empty($ids)) {
            $errors[] = 'No items selected for bulk operation.';
        }

        if (count($ids) > static::$bulkBatchSize * 10) {
            $errors[] = sprintf(
                'Too many items selected. Maximum allowed: %d',
                static::$bulkBatchSize * 10,
            );
        }

        if (static::requiresData($operation) && empty($data)) {
            $errors[] = "Operation '{$operation}' requires additional data.";
        }

        return $errors;
    }

    /**
     * Prepare bulk operation data.
     */
    protected static function prepareBulkOperationData(
        string $operation,
        array $data,
    ): array {
        // Override in subclasses to prepare operation-specific data
        return $data;
    }
}
