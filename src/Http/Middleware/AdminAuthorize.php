<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Admin Authorization Middleware
 * 
 * Handles resource-level authorization for admin panel operations
 * with support for policies and custom authorization logic.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Http\Middleware
 */
class AdminAuthorize
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $ability = null)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentication required.');
        }

        // Check resource-specific authorization
        if ($resourceName = $request->route('resource')) {
            $this->authorizeResource($request, $user, $resourceName, $ability);
        }

        // Check general admin panel access
        if (! $this->canAccessAdminPanel($user)) {
            abort(403, 'Unauthorized to access admin panel.');
        }

        return $next($request);
    }

    /**
     * Authorize access to a specific resource.
     */
    protected function authorizeResource(Request $request, $user, string $resourceName, ?string $ability): void
    {
        $adminPanel = app(AdminPanel::class);
        $resource = $adminPanel->findResource($resourceName);

        if (! $resource) {
            abort(404, "Resource [{$resourceName}] not found.");
        }

        // Get the ability from the route action if not provided
        if (! $ability) {
            $ability = $this->getAbilityFromRoute($request);
        }

        // Check resource authorization
        if (! $this->authorizeResourceAction($user, $resource, $ability, $request)) {
            abort(403, "Unauthorized to {$ability} this resource.");
        }
    }

    /**
     * Check if user can access admin panel.
     */
    protected function canAccessAdminPanel($user): bool
    {
        // Use Gate if defined
        if (Gate::has('access-admin-panel')) {
            return Gate::forUser($user)->allows('access-admin-panel');
        }

        // Use custom authorization callback
        $callback = config('admin-panel.auth.authorize');
        if ($callback && is_callable($callback)) {
            return call_user_func($callback, $user);
        }

        return true; // Default to allowing access if authenticated
    }

    /**
     * Authorize a specific resource action.
     */
    protected function authorizeResourceAction($user, $resource, string $ability, Request $request): bool
    {
        $resourceInstance = new $resource();

        // Use resource's authorization method
        switch ($ability) {
            case 'viewAny':
            case 'index':
                return $resourceInstance->authorizedToView($request);
            
            case 'view':
            case 'show':
                return $resourceInstance->authorizedToView($request);
            
            case 'create':
            case 'store':
                return $resourceInstance->authorizedToCreate($request);
            
            case 'update':
            case 'edit':
                return $resourceInstance->authorizedToUpdate($request);
            
            case 'delete':
            case 'destroy':
                return $resourceInstance->authorizedToDelete($request);
            
            default:
                return $resourceInstance->authorizedToView($request);
        }
    }

    /**
     * Get the ability from the current route action.
     */
    protected function getAbilityFromRoute(Request $request): string
    {
        $action = $request->route()->getActionMethod();

        return match ($action) {
            'index' => 'viewAny',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
            default => 'view'
        };
    }
}
