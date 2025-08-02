<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Admin Authentication Middleware
 * 
 * Ensures that only authenticated users can access the admin panel.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Http\Middleware
 */
class AdminAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = config('admin-panel.auth.guard', 'web');
        
        if (! auth($guard)->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(
                config('admin-panel.auth.login_route', route('login'))
            );
        }

        return $next($request);
    }
}
