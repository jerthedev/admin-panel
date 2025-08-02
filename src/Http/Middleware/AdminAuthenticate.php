<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

/**
 * Admin Authentication Middleware
 *
 * Handles authentication for admin panel routes with configurable
 * guards and redirect behavior.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Http\Middleware
 */
class AdminAuthenticate extends Middleware
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // Use configured admin guard if no guards specified
        if (empty($guards)) {
            $guards = [config('admin-panel.auth.guard', 'web')];
        }

        $this->authenticate($request, $guards);

        // Check if user is authorized to access admin panel
        if (! $this->isAuthorizedForAdmin($request)) {
            // If user is authenticated but not authorized, return 403
            if ($request->user()) {
                abort(403, 'Unauthorized to access admin panel.');
            }

            // If user is not authenticated, throw authentication exception
            throw new AuthenticationException(
                'Unauthorized to access admin panel.',
                $guards,
                $this->redirectTo($request)
            );
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        if (! $request->expectsJson()) {
            return route('admin-panel.login');
        }

        return null;
    }

    /**
     * Check if the authenticated user is authorized for admin panel.
     */
    protected function isAuthorizedForAdmin(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        // Check admin authorization callback
        $callback = config('admin-panel.auth.authorize');

        if ($callback && is_callable($callback)) {
            return call_user_func($callback, $user, $request);
        }

        // Default authorization checks
        return $this->defaultAdminAuthorization($user);
    }

    /**
     * Default admin authorization logic.
     */
    protected function defaultAdminAuthorization($user): bool
    {
        // Check if all authenticated users are allowed
        if (config('admin-panel.auth.allow_all_authenticated', false)) {
            return true;
        }

        // Check for admin role if user has roles
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

        // Check for specific admin user model
        $adminUserModel = config('admin-panel.auth.admin_user_model');
        if ($adminUserModel && $user instanceof $adminUserModel) {
            return true;
        }

        // Fallback: check if user has any admin permissions
        if (method_exists($user, 'can')) {
            return $user->can('access-admin-panel');
        }

        return false;
    }
}
