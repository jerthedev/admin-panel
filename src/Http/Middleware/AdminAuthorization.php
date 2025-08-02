<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Admin Authorization Middleware
 * 
 * Ensures that only authorized users can access the admin panel.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Http\Middleware
 */
class AdminAuthorization
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth(config('admin-panel.auth.guard', 'web'))->user();
        
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Check if user has admin access
        if (! $this->userCanAccessAdminPanel($user)) {
            abort(403, 'Access denied to admin panel.');
        }

        return $next($request);
    }

    /**
     * Determine if the user can access the admin panel.
     */
    protected function userCanAccessAdminPanel($user): bool
    {
        $authCallback = config('admin-panel.auth.callback');
        
        if ($authCallback && is_callable($authCallback)) {
            return call_user_func($authCallback, $user);
        }

        // Default: check if user has admin role or is_admin attribute
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        if (isset($user->is_admin) && $user->is_admin) {
            return true;
        }

        // Default: allow all authenticated users (can be overridden in config)
        return config('admin-panel.auth.allow_all_authenticated', true);
    }
}
