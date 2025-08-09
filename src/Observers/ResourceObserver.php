<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Resource Observer Base Class.
 *
 * Base observer class for handling model lifecycle events in admin panel resources.
 * Provides hooks for creating, updating, deleting, and other model events.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class ResourceObserver
{
    /**
     * The resource class this observer is for.
     */
    protected string $resourceClass;

    /**
     * Handle the model "creating" event.
     */
    public function creating(Model $model): void
    {
        $this->beforeCreate($model);
    }

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->afterCreate($model);
    }

    /**
     * Handle the model "updating" event.
     */
    public function updating(Model $model): void
    {
        $this->beforeUpdate($model);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->afterUpdate($model);
    }

    /**
     * Handle the model "saving" event.
     */
    public function saving(Model $model): void
    {
        $this->beforeSave($model);
    }

    /**
     * Handle the model "saved" event.
     */
    public function saved(Model $model): void
    {
        $this->afterSave($model);
    }

    /**
     * Handle the model "deleting" event.
     */
    public function deleting(Model $model): void
    {
        $this->beforeDelete($model);
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->afterDelete($model);
    }

    /**
     * Handle the model "restoring" event.
     */
    public function restoring(Model $model): void
    {
        $this->beforeRestore($model);
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->afterRestore($model);
    }

    /**
     * Handle the model "force deleting" event.
     */
    public function forceDeleting(Model $model): void
    {
        $this->beforeForceDelete($model);
    }

    /**
     * Handle the model "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        $this->afterForceDelete($model);
    }

    /**
     * Handle the model "replicating" event.
     */
    public function replicating(Model $model): void
    {
        $this->beforeReplicate($model);
    }

    /**
     * Called before a model is created.
     */
    protected function beforeCreate(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Called after a model is created.
     */
    protected function afterCreate(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Called before a model is updated.
     */
    protected function beforeUpdate(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Called after a model is updated.
     */
    protected function afterUpdate(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Called before a model is saved (create or update).
     */
    protected function beforeSave(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Called after a model is saved (create or update).
     */
    protected function afterSave(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Called before a model is deleted.
     */
    protected function beforeDelete(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Called after a model is deleted.
     */
    protected function afterDelete(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Called before a model is restored.
     */
    protected function beforeRestore(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Called after a model is restored.
     */
    protected function afterRestore(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Called before a model is force deleted.
     */
    protected function beforeForceDelete(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Called after a model is force deleted.
     */
    protected function afterForceDelete(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Called before a model is replicated.
     */
    protected function beforeReplicate(Model $model): void
    {
        // Override in subclasses
    }

    /**
     * Get the current request context.
     */
    protected function getRequest(): ?Request
    {
        if (app()->has('request')) {
            return app('request');
        }

        return null;
    }

    /**
     * Get the current user.
     */
    protected function getCurrentUser()
    {
        $request = $this->getRequest();

        return $request ? $request->user() : null;
    }

    /**
     * Log an activity for the model.
     */
    protected function logActivity(Model $model, string $action, array $properties = []): void
    {
        // This could integrate with activity logging packages like spatie/laravel-activitylog
        // For now, we'll just provide a hook for subclasses to implement
    }

    /**
     * Send a notification about the model event.
     */
    protected function sendNotification(Model $model, string $event, array $data = []): void
    {
        // This could integrate with notification systems
        // For now, we'll just provide a hook for subclasses to implement
    }

    /**
     * Clear cache related to the model.
     */
    protected function clearCache(Model $model, array $tags = []): void
    {
        // This could integrate with caching systems
        // For now, we'll just provide a hook for subclasses to implement
    }

    /**
     * Update search index for the model.
     */
    protected function updateSearchIndex(Model $model): void
    {
        // This could integrate with search engines like Elasticsearch, Algolia, etc.
        // For now, we'll just provide a hook for subclasses to implement
    }

    /**
     * Validate model data before saving.
     */
    protected function validateModel(Model $model): bool
    {
        // Override in subclasses to add custom validation
        return true;
    }

    /**
     * Transform model data before saving.
     */
    protected function transformModel(Model $model): void
    {
        // Override in subclasses to transform data
    }

    /**
     * Handle model relationships after saving.
     */
    protected function handleRelationships(Model $model): void
    {
        // Override in subclasses to handle relationships
    }

    /**
     * Check if the observer should handle the event.
     */
    protected function shouldHandle(Model $model): bool
    {
        // Override in subclasses to add conditions
        return true;
    }
}
