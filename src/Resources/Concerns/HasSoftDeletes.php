<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Resources\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * HasSoftDeletes Trait.
 *
 * Provides functionality for managing soft-deletable models in admin panel resources.
 * Enables trash and restore functionality with proper authorization and UI integration.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
trait HasSoftDeletes
{
    /**
     * Whether to show trashed resources by default.
     */
    public static bool $showTrashedByDefault = false;

    /**
     * Whether to include trashed resources in search.
     */
    public static bool $includeTrashedInSearch = true;

    /**
     * Whether to show the restore action.
     */
    public static bool $showRestoreAction = true;

    /**
     * Whether to show the force delete action.
     */
    public static bool $showForceDeleteAction = true;

    /**
     * The number of days to keep trashed resources before permanent deletion.
     */
    public static int $trashRetentionDays = 30;

    /**
     * Check if the resource model uses soft deletes.
     */
    public static function usesSoftDeletes(): bool
    {
        $model = static::newModel();

        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }

    /**
     * Get the soft delete column name.
     */
    public static function getSoftDeleteColumn(): string
    {
        if (! static::usesSoftDeletes()) {
            return '';
        }

        $model = static::newModel();

        return $model->getDeletedAtColumn();
    }

    /**
     * Check if the resource is trashed.
     */
    public function isTrashed(): bool
    {
        if (! static::usesSoftDeletes()) {
            return false;
        }

        return $this->resource->trashed();
    }

    /**
     * Get the deleted at timestamp.
     */
    public function getDeletedAt(): ?string
    {
        if (! $this->isTrashed()) {
            return null;
        }

        $deletedAtColumn = static::getSoftDeleteColumn();

        return $this->resource->{$deletedAtColumn}?->toDateTimeString();
    }

    /**
     * Get the number of days since deletion.
     */
    public function getDaysSinceDeletion(): ?int
    {
        if (! $this->isTrashed()) {
            return null;
        }

        $deletedAtColumn = static::getSoftDeleteColumn();
        $deletedAt = $this->resource->{$deletedAtColumn};

        return $deletedAt ? $deletedAt->diffInDays(now()) : null;
    }

    /**
     * Check if the resource is permanently deletable.
     */
    public function isPermanentlyDeletable(): bool
    {
        if (! $this->isTrashed()) {
            return false;
        }

        $daysSinceDeletion = $this->getDaysSinceDeletion();

        return $daysSinceDeletion !== null && $daysSinceDeletion >= static::$trashRetentionDays;
    }

    /**
     * Restore the resource from trash.
     */
    public function restore(): bool
    {
        if (! static::usesSoftDeletes() || ! $this->isTrashed()) {
            return false;
        }

        return $this->resource->restore();
    }

    /**
     * Force delete the resource permanently.
     */
    public function forceDelete(): bool
    {
        if (! static::usesSoftDeletes()) {
            return false;
        }

        return $this->resource->forceDelete();
    }

    /**
     * Soft delete the resource.
     */
    public function softDelete(): bool
    {
        if (! static::usesSoftDeletes()) {
            return false;
        }

        return $this->resource->delete();
    }

    /**
     * Get trashed resources.
     */
    public static function getTrashedResources(Request $request): Collection
    {
        if (! static::usesSoftDeletes()) {
            return collect();
        }

        $query = static::newModel()->onlyTrashed();

        // Apply any additional scopes
        $query = static::indexQuery($request, $query);

        return $query->get()->map(function ($model) {
            return new static($model);
        });
    }

    /**
     * Get resources including trashed.
     */
    public static function getResourcesWithTrashed(Request $request): Collection
    {
        if (! static::usesSoftDeletes()) {
            return static::getResources($request);
        }

        $query = static::newModel()->withTrashed();

        // Apply any additional scopes
        $query = static::indexQuery($request, $query);

        return $query->get()->map(function ($model) {
            return new static($model);
        });
    }

    /**
     * Get only non-trashed resources.
     */
    public static function getActiveResources(Request $request): Collection
    {
        $query = static::newModel()->newQuery();

        // Apply any additional scopes
        $query = static::indexQuery($request, $query);

        return $query->get()->map(function ($model) {
            return new static($model);
        });
    }

    /**
     * Get trash statistics.
     */
    public static function getTrashStats(): array
    {
        if (! static::usesSoftDeletes()) {
            return [
                'total_trashed' => 0,
                'permanently_deletable' => 0,
                'retention_days' => static::$trashRetentionDays,
                'uses_soft_deletes' => false,
            ];
        }

        $trashedQuery = static::newModel()->onlyTrashed();
        $totalTrashed = $trashedQuery->count();

        $permanentlyDeletableQuery = clone $trashedQuery;
        $permanentlyDeletableQuery->where(
            static::getSoftDeleteColumn(),
            '<=',
            now()->subDays(static::$trashRetentionDays),
        );
        $permanentlyDeletable = $permanentlyDeletableQuery->count();

        return [
            'total_trashed' => $totalTrashed,
            'permanently_deletable' => $permanentlyDeletable,
            'retention_days' => static::$trashRetentionDays,
            'uses_soft_deletes' => true,
        ];
    }

    /**
     * Bulk restore resources.
     */
    public static function bulkRestore(array $ids): int
    {
        if (! static::usesSoftDeletes()) {
            return 0;
        }

        return static::newModel()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Bulk force delete resources.
     */
    public static function bulkForceDelete(array $ids): int
    {
        if (! static::usesSoftDeletes()) {
            return 0;
        }

        $models = static::newModel()->onlyTrashed()->whereIn('id', $ids)->get();
        $count = 0;

        foreach ($models as $model) {
            if ($model->forceDelete()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Clean up old trashed resources.
     */
    public static function cleanupOldTrashed(): int
    {
        if (! static::usesSoftDeletes()) {
            return 0;
        }

        $models = static::newModel()
            ->onlyTrashed()
            ->where(
                static::getSoftDeleteColumn(),
                '<=',
                now()->subDays(static::$trashRetentionDays),
            )
            ->get();

        $count = 0;
        foreach ($models as $model) {
            if ($model->forceDelete()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get soft delete actions for the resource.
     */
    public function getSoftDeleteActions(Request $request): array
    {
        $actions = [];

        if (! static::usesSoftDeletes()) {
            return $actions;
        }

        if ($this->isTrashed()) {
            // Trashed resource actions
            if (static::$showRestoreAction && $this->authorizedToRestore($request)) {
                $actions[] = [
                    'name' => 'restore',
                    'label' => 'Restore',
                    'icon' => 'arrow-path',
                    'type' => 'success',
                    'confirmation' => 'Are you sure you want to restore this item?',
                ];
            }

            if (static::$showForceDeleteAction && $this->authorizedToForceDelete($request)) {
                $actions[] = [
                    'name' => 'force-delete',
                    'label' => 'Delete Permanently',
                    'icon' => 'trash',
                    'type' => 'danger',
                    'confirmation' => 'Are you sure you want to permanently delete this item? This action cannot be undone.',
                ];
            }
        } else {
            // Active resource actions
            if ($this->authorizedToDelete($request)) {
                $actions[] = [
                    'name' => 'soft-delete',
                    'label' => 'Move to Trash',
                    'icon' => 'archive-box',
                    'type' => 'warning',
                    'confirmation' => 'Are you sure you want to move this item to trash?',
                ];
            }
        }

        return $actions;
    }

    /**
     * Get the trash status information for the resource.
     */
    public function getTrashStatusInfo(): array
    {
        if (! static::usesSoftDeletes()) {
            return [
                'is_trashed' => false,
                'uses_soft_deletes' => false,
            ];
        }

        return [
            'is_trashed' => $this->isTrashed(),
            'deleted_at' => $this->getDeletedAt(),
            'days_since_deletion' => $this->getDaysSinceDeletion(),
            'is_permanently_deletable' => $this->isPermanentlyDeletable(),
            'retention_days' => static::$trashRetentionDays,
            'uses_soft_deletes' => true,
        ];
    }
}
