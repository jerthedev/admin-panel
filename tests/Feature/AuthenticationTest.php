<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Authentication Feature Tests
 *
 * Tests for admin panel authentication functionality including
 * login, logout, and authorization.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class AuthenticationTest extends TestCase
{
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
    }

    public function test_admin_user_can_login(): void
    {
        $admin = $this->createAdminUser([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_admin_user_cannot_login(): void
    {
        $user = $this->createUser([
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_login_validation_errors(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_login_rate_limiting(): void
    {
        $admin = $this->createAdminUser([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_remember_me_functionality(): void
    {
        $admin = $this->createAdminUser([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertRedirect();
        $this->assertNotNull(auth()->user()->getRememberToken());
    }

    public function test_admin_user_can_logout(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $response = $this->post('/admin/logout');

        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect();
    }

    public function test_authenticated_non_admin_user_cannot_access_dashboard(): void
    {
        $user = $this->createUser(['is_admin' => false]);
        $this->actingAs($user);

        $response = $this->get('/admin');

        $response->assertStatus(403);
    }

    public function test_profile_page_is_accessible_to_admin(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $response = $this->get('/admin/profile');

        $response->assertOk();
    }

    public function test_admin_can_update_profile(): void
    {
        $admin = $this->createAdminUser([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);
        $this->actingAs($admin);

        $response = $this->put('/admin/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertRedirect();

        $admin->refresh();
        $this->assertEquals('New Name', $admin->name);
        $this->assertEquals('new@example.com', $admin->email);
    }

    public function test_admin_can_update_password(): void
    {
        $admin = $this->createAdminUser([
            'password' => Hash::make('old-password'),
        ]);
        $this->actingAs($admin);

        $response = $this->put('/admin/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect();

        $admin->refresh();
        $this->assertTrue(Hash::check('new-password', $admin->password));
    }

    public function test_password_update_requires_current_password(): void
    {
        $admin = $this->createAdminUser([
            'password' => Hash::make('old-password'),
        ]);
        $this->actingAs($admin);

        $response = $this->put('/admin/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('login:admin@example.com|127.0.0.1');
        parent::tearDown();
    }
}
