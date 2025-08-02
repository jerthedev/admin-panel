<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Restore Action
 * 
 * Bulk restore action for restoring soft-deleted resources
 * with proper validation and error handling.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Actions
 */
class RestoreAction extends Action
{
    /**
     * The action's name.
     */
    public string $name = 'Restore';

    /**
     * The action's URI key.
     */
    public string $uriKey = 'restore';

    /**
     * The action's icon.
     */
    public ?string $icon = 'ArrowUturnLeftIcon';

    /**
     * Create a new restore action instance.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->confirmationMessage = 'Are you sure you want to restore the selected resources?';
        $this->successMessage = 'Resources restored successfully.';
        $this->errorMessage = 'Failed to restore resources.';
    }

    /**
     * Perform the action on the given models.
     */
    public function handle(Collection $models, Request $request): array
    {
        $restoredCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($models as $model) {
            try {
                // Check if the model supports soft deletes
                if (! method_exists($model, 'restore')) {
                    $skippedCount++;
                    continue;
                }

                // Check if the model is actually soft deleted
                if (! $model->trashed()) {
                    $skippedCount++;
                    continue;
                }

                $model->restore();
                $restoredCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Failed to restore {$model->getKey()}: {$e->getMessage()}";
            }
        }

        if ($failedCount === 0 && $skippedCount === 0) {
            $message = $restoredCount === 1 
                ? '1 resource restored successfully.'
                : "{$restoredCount} resources restored successfully.";
                
            return $this->success($message);
        }

        if ($restoredCount === 0) {
            if ($skippedCount > 0) {
                return $this->info('No resources were restored. Selected resources are not deleted or do not support restoration.');
            }
            
            return $this->error('Failed to restore any resources. ' . implode(' ', $errors));
        }

        $messageParts = [];
        
        if ($restoredCount > 0) {
            $messageParts[] = "{$restoredCount} resources restored successfully";
        }
        
        if ($failedCount > 0) {
            $messageParts[] = "{$failedCount} failed";
        }
        
        if ($skippedCount > 0) {
            $messageParts[] = "{$skippedCount} skipped";
        }

        return $this->warning(implode(', ', $messageParts) . '.');
    }

    /**
     * Determine if the action should be displayed.
     */
    public function authorize(Request $request): bool
    {
        // Only show restore action if we're viewing trashed resources
        return $request->get('trashed') === 'only' || $request->get('trashed') === 'with';
    }
}
