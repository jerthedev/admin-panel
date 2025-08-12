<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Test Only Middleware
 * 
 * Restricts access to routes only in testing environments.
 * Provides security by preventing test data endpoints from being
 * accessible in production or staging environments.
 */
class TestOnlyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only allow access in testing environments
        if (!$this->isTestingEnvironment()) {
            abort(404, 'Test endpoints are not available in this environment');
        }

        // Add security headers for test endpoints
        $response = $next($request);
        
        if ($response instanceof Response) {
            $response->headers->set('X-Test-Environment', 'true');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }

    /**
     * Check if we're in a testing environment.
     */
    protected function isTestingEnvironment(): bool
    {
        $environment = app()->environment();
        
        // Allow in testing, local development, and when explicitly enabled
        return in_array($environment, ['testing', 'local']) || 
               config('admin-panel.enable_test_endpoints', false) ||
               env('ADMIN_PANEL_TEST_ENDPOINTS', false);
    }
}
