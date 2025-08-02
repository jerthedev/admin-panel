<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Base Admin Policy
 * 
 * Base policy class for admin panel resources providing common
 * authorization patterns and helper methods.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Policies
 */
abstract class AdminPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, $model): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, $model): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, $model): bool
    {
        return $this->isAdmin($user) && ! $this->isSelf($user, $model);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, $model): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, $model): bool
    {
        return $this->isSuperAdmin($user) && ! $this->isSelf($user, $model);
    }

    /**
     * Check if user is an admin.
     */
    protected function isAdmin($user): bool
    {
        if (! $user) {
            return false;
        }

        // Check for admin role
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('super-admin');
        }

        // Check for is_admin field
        if (isset($user->is_admin)) {
            return (bool) $user->is_admin;
        }

        // Check for admin_access field
        if (isset($user->admin_access)) {
            return (bool) $user->admin_access;
        }

        // Check for specific permission
        if (method_exists($user, 'can')) {
            return $user->can('access-admin-panel');
        }

        return false;
    }

    /**
     * Check if user is a super admin.
     */
    protected function isSuperAdmin($user): bool
    {
        if (! $user) {
            return false;
        }

        // Check for super admin role
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('super-admin');
        }

        // Check for is_super_admin field
        if (isset($user->is_super_admin)) {
            return (bool) $user->is_super_admin;
        }

        // Check for specific permission
        if (method_exists($user, 'can')) {
            return $user->can('super-admin');
        }

        return false;
    }

    /**
     * Check if the model is the same as the authenticated user.
     */
    protected function isSelf($user, $model): bool
    {
        if (! $user || ! $model) {
            return false;
        }

        // Check if model is the user
        if ($model === $user) {
            return true;
        }

        // Check by ID if both have getKey method
        if (method_exists($user, 'getKey') && method_exists($model, 'getKey')) {
            return $user->getKey() === $model->getKey();
        }

        return false;
    }

    /**
     * Check if user has specific permission.
     */
    protected function hasPermission($user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'can')) {
            return $user->can($permission);
        }

        return false;
    }

    /**
     * Check if user owns the model.
     */
    protected function owns($user, $model, string $ownerField = 'user_id'): bool
    {
        if (! $user || ! $model) {
            return false;
        }

        if (! isset($model->{$ownerField})) {
            return false;
        }

        return $model->{$ownerField} === $user->getKey();
    }
}
