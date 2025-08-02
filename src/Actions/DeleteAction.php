<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Delete Action
 * 
 * Bulk delete action for removing multiple resources at once
 * with proper confirmation and error handling.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Actions
 */
class DeleteAction extends Action
{
    /**
     * The action's name.
     */
    public string $name = 'Delete';

    /**
     * The action's URI key.
     */
    public string $uriKey = 'delete';

    /**
     * The action's icon.
     */
    public ?string $icon = 'TrashIcon';

    /**
     * Whether the action is destructive.
     */
    public bool $destructive = true;

    /**
     * Whether to use soft deletes if available.
     */
    protected bool $softDelete = true;

    /**
     * Whether to force delete (bypass soft deletes).
     */
    protected bool $forceDelete = false;

    /**
     * Create a new delete action instance.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->confirmationMessage = 'Are you sure you want to delete the selected resources? This action cannot be undone.';
        $this->successMessage = 'Resources deleted successfully.';
        $this->errorMessage = 'Failed to delete resources.';
    }

    /**
     * Perform the action on the given models.
     */
    public function handle(Collection $models, Request $request): array
    {
        $deletedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($models as $model) {
            try {
                if ($this->forceDelete && method_exists($model, 'forceDelete')) {
                    $model->forceDelete();
                } elseif ($this->softDelete && method_exists($model, 'delete')) {
                    $model->delete();
                } else {
                    $model->delete();
                }
                
                $deletedCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Failed to delete {$model->getKey()}: {$e->getMessage()}";
            }
        }

        if ($failedCount === 0) {
            $message = $deletedCount === 1 
                ? '1 resource deleted successfully.'
                : "{$deletedCount} resources deleted successfully.";
                
            return $this->success($message);
        }

        if ($deletedCount === 0) {
            return $this->error('Failed to delete any resources. ' . implode(' ', $errors));
        }

        $message = "{$deletedCount} resources deleted successfully, {$failedCount} failed.";
        return $this->warning($message);
    }

    /**
     * Set whether to use soft deletes.
     */
    public function withSoftDelete(bool $softDelete = true): static
    {
        $this->softDelete = $softDelete;
        
        return $this;
    }

    /**
     * Set whether to force delete.
     */
    public function withForceDelete(bool $forceDelete = true): static
    {
        $this->forceDelete = $forceDelete;
        
        if ($forceDelete) {
            $this->confirmationMessage = 'Are you sure you want to permanently delete the selected resources? This action cannot be undone and will bypass soft deletes.';
        }
        
        return $this;
    }

    /**
     * Create a soft delete action.
     */
    public static function softDelete(): static
    {
        return (new static())
            ->withName('Move to Trash')
            ->withUriKey('soft-delete')
            ->withIcon('ArchiveBoxIcon')
            ->withSoftDelete(true)
            ->withConfirmation('Are you sure you want to move the selected resources to trash?');
    }

    /**
     * Create a force delete action.
     */
    public static function forceDelete(): static
    {
        return (new static())
            ->withName('Delete Permanently')
            ->withUriKey('force-delete')
            ->withIcon('TrashIcon')
            ->withForceDelete(true);
    }

    /**
     * Create a restore action for soft deleted models.
     */
    public static function restore(): static
    {
        return new RestoreAction();
    }
}
