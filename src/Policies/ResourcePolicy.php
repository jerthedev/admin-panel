<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

/**
 * Resource Policy Base Class.
 *
 * Base policy class for resource-level authorization in the admin panel.
 * Provides granular permission controls for CRUD operations and field access.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class ResourcePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, Model $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, Model $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, Model $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, Model $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, Model $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can attach models to the parent model.
     */
    public function attach($user, Model $model): bool
    {
        return $this->update($user, $model);
    }

    /**
     * Determine whether the user can detach models from the parent model.
     */
    public function detach($user, Model $model): bool
    {
        return $this->update($user, $model);
    }

    /**
     * Determine whether the user can view the field.
     */
    public function viewField($user, Model $model, string $field): bool
    {
        return $this->view($user, $model);
    }

    /**
     * Determine whether the user can update the field.
     */
    public function updateField($user, Model $model, string $field): bool
    {
        return $this->update($user, $model);
    }

    /**
     * Determine whether the user can run actions on the model.
     */
    public function runAction($user, Model $model, string $action): bool
    {
        return $this->update($user, $model);
    }

    /**
     * Determine whether the user can export models.
     */
    public function export($user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can import models.
     */
    public function import($user): bool
    {
        return $this->create($user);
    }

    /**
     * Get the list of fields that should be hidden from the user.
     */
    public function getHiddenFields($user, ?Model $model = null): array
    {
        return [];
    }

    /**
     * Get the list of fields that should be readonly for the user.
     */
    public function getReadonlyFields($user, ?Model $model = null): array
    {
        return [];
    }

    /**
     * Get the list of actions that should be hidden from the user.
     */
    public function getHiddenActions($user, ?Model $model = null): array
    {
        return [];
    }

    /**
     * Determine if the user can see the resource in navigation.
     */
    public function viewInNavigation($user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can see the resource in search results.
     */
    public function viewInSearch($user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Get custom authorization message for failed authorization.
     */
    public function getAuthorizationMessage(string $ability): ?string
    {
        return match ($ability) {
            'viewAny' => 'You are not authorized to view this resource.',
            'view' => 'You are not authorized to view this item.',
            'create' => 'You are not authorized to create new items.',
            'update' => 'You are not authorized to update this item.',
            'delete' => 'You are not authorized to delete this item.',
            'restore' => 'You are not authorized to restore this item.',
            'forceDelete' => 'You are not authorized to permanently delete this item.',
            'attach' => 'You are not authorized to attach items.',
            'detach' => 'You are not authorized to detach items.',
            'export' => 'You are not authorized to export data.',
            'import' => 'You are not authorized to import data.',
            default => null,
        };
    }

    /**
     * Check if the user has a specific role.
     */
    protected function hasRole($user, string $role): bool
    {
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($role);
        }

        if (property_exists($user, 'role')) {
            return $user->role === $role;
        }

        return false;
    }

    /**
     * Check if the user has a specific permission.
     */
    protected function hasPermission($user, string $permission): bool
    {
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }

        if (method_exists($user, 'can')) {
            return $user->can($permission);
        }

        return false;
    }

    /**
     * Check if the user owns the model.
     */
    protected function owns($user, Model $model): bool
    {
        // Check for user_id attribute
        if (isset($model->user_id)) {
            return $model->user_id === $user->id;
        }

        // Check for owner_id attribute
        if (isset($model->owner_id)) {
            return $model->owner_id === $user->id;
        }

        // Check for created_by attribute
        if (isset($model->created_by)) {
            return $model->created_by === $user->id;
        }

        return false;
    }

    /**
     * Check if the user is an admin.
     */
    protected function isAdmin($user): bool
    {
        return $this->hasRole($user, 'admin') ||
               $this->hasRole($user, 'administrator') ||
               $this->hasPermission($user, 'admin.*');
    }

    /**
     * Check if the user is a super admin.
     */
    protected function isSuperAdmin($user): bool
    {
        return $this->hasRole($user, 'super-admin') ||
               $this->hasRole($user, 'superadmin') ||
               $this->hasPermission($user, 'super-admin.*');
    }
}
