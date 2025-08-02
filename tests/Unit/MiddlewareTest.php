<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JTD\AdminPanel\Http\Middleware\AdminAuthenticate;
use JTD\AdminPanel\Http\Middleware\AdminAuthorize;
use JTD\AdminPanel\Tests\TestCase;
use Mockery;

/**
 * Middleware Unit Tests
 *
 * Tests for admin panel middleware including authentication
 * and authorization functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MiddlewareTest extends TestCase
{
    public function test_admin_authenticate_redirect_path(): void
    {
        $auth = $this->app['auth'];
        $middleware = new AdminAuthenticate($auth);
        $request = Request::create('/admin');

        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('redirectTo');
        $method->setAccessible(true);

        $redirectPath = $method->invoke($middleware, $request);

        $this->assertEquals(route('admin-panel.login'), $redirectPath);
    }

    public function test_admin_authenticate_json_request(): void
    {
        $auth = $this->app['auth'];
        $middleware = new AdminAuthenticate($auth);
        $request = Request::create('/admin', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('redirectTo');
        $method->setAccessible(true);

        $redirectPath = $method->invoke($middleware, $request);

        $this->assertNull($redirectPath);
    }



    public function test_admin_authenticate_default_authorization_with_role_method(): void
    {
        $auth = $this->app['auth'];
        $middleware = new AdminAuthenticate($auth);

        // Create a mock user with hasRole method
        $user = new class {
            public function hasRole($role) {
                return $role === 'admin';
            }
        };

        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('defaultAdminAuthorization');
        $method->setAccessible(true);

        $result = $method->invoke($middleware, $user);

        $this->assertTrue($result);
    }

    public function test_admin_authenticate_default_authorization_with_is_admin_field(): void
    {
        $auth = $this->app['auth'];
        $middleware = new AdminAuthenticate($auth);

        // Create a mock user with is_admin field
        $user = new class {
            public $is_admin = true;
        };

        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('defaultAdminAuthorization');
        $method->setAccessible(true);

        $result = $method->invoke($middleware, $user);

        $this->assertTrue($result);
    }

    public function test_admin_authenticate_default_authorization_with_admin_access_field(): void
    {
        $auth = $this->app['auth'];
        $middleware = new AdminAuthenticate($auth);

        // Create a mock user with admin_access field
        $user = new class {
            public $admin_access = true;
        };

        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('defaultAdminAuthorization');
        $method->setAccessible(true);

        $result = $method->invoke($middleware, $user);

        $this->assertTrue($result);
    }

    public function test_admin_authenticate_default_authorization_with_permission(): void
    {
        $auth = $this->app['auth'];
        $middleware = new AdminAuthenticate($auth);

        // Create a mock user with can method
        $user = new class {
            public function can($permission) {
                return $permission === 'access-admin-panel';
            }
        };

        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('defaultAdminAuthorization');
        $method->setAccessible(true);

        $result = $method->invoke($middleware, $user);

        $this->assertTrue($result);
    }

    public function test_admin_authenticate_default_authorization_respects_config(): void
    {
        $auth = $this->app['auth'];
        $middleware = new AdminAuthenticate($auth);

        // Create a mock user without admin privileges
        $user = new class {
            public $is_admin = false;
        };

        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('defaultAdminAuthorization');
        $method->setAccessible(true);

        $result = $method->invoke($middleware, $user);

        // Result should match the allow_all_authenticated configuration
        $expectedResult = config('admin-panel.auth.allow_all_authenticated', true);
        $this->assertEquals($expectedResult, $result,
            'Authorization should respect allow_all_authenticated config setting');
    }

    public function test_admin_authenticate_with_custom_authorization_callback(): void
    {
        config(['admin-panel.auth.authorize' => fn($user) => $user->custom_admin === true]);

        $auth = $this->app['auth'];
        $middleware = new AdminAuthenticate($auth);

        // Create a mock user
        $user = new class {
            public $custom_admin = true;
        };

        $request = Request::create('/admin');
        $request->setUserResolver(fn() => $user);

        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('isAuthorizedForAdmin');
        $method->setAccessible(true);

        $result = $method->invoke($middleware, $request);

        $this->assertTrue($result);
    }
}
