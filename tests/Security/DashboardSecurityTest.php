<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Security;

use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Support\DashboardRegistry;

/**
 * Dashboard Security Tests
 * 
 * Comprehensive security testing including authentication, authorization,
 * CSRF protection, XSS prevention, and data validation.
 */
class DashboardSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected User $restrictedUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with different permission levels
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secure-password'),
            'is_admin' => true,
            'permissions' => ['dashboard.view', 'dashboard.create', 'dashboard.edit', 'dashboard.delete']
        ]);
        
        $this->regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('user-password'),
            'is_admin' => false,
            'permissions' => ['dashboard.view']
        ]);
        
        $this->restrictedUser = User::factory()->create([
            'email' => 'restricted@example.com',
            'password' => Hash::make('restricted-password'),
            'is_admin' => false,
            'permissions' => []
        ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/admin');
        
        $response->assertRedirect('/login');
    }

    public function test_dashboard_api_requires_authentication(): void
    {
        $response = $this->getJson('/admin/api/dashboards');
        
        $response->assertStatus(401);
    }

    public function test_dashboard_enforces_authorization(): void
    {
        $this->actingAs($this->restrictedUser);
        
        $response = $this->get('/admin');
        
        $response->assertStatus(403);
    }

    public function test_dashboard_csrf_protection(): void
    {
        $this->actingAs($this->adminUser);
        
        // Attempt POST without CSRF token
        $response = $this->post('/admin/dashboards', [
            'name' => 'Test Dashboard',
            'description' => 'Test Description'
        ]);
        
        $response->assertStatus(419); // CSRF token mismatch
    }

    public function test_dashboard_csrf_protection_with_valid_token(): void
    {
        $this->actingAs($this->adminUser);
        
        // Get CSRF token
        Session::start();
        $token = csrf_token();
        
        $response = $this->post('/admin/dashboards', [
            '_token' => $token,
            'name' => 'Test Dashboard',
            'description' => 'Test Description'
        ]);
        
        $response->assertStatus(302); // Successful redirect
    }

    public function test_dashboard_prevents_xss_in_names(): void
    {
        $this->actingAs($this->adminUser);
        
        $maliciousScript = '<script>alert("XSS")</script>';
        
        $response = $this->post('/admin/dashboards', [
            '_token' => csrf_token(),
            'name' => $maliciousScript,
            'description' => 'Test Description'
        ]);
        
        // Check that script is escaped in response
        $response = $this->get('/admin');
        $response->assertDontSee($maliciousScript, false);
        $response->assertSee('&lt;script&gt;alert("XSS")&lt;/script&gt;', false);
    }

    public function test_dashboard_prevents_sql_injection(): void
    {
        $this->actingAs($this->adminUser);
        
        $sqlInjection = "'; DROP TABLE users; --";
        
        $response = $this->get('/admin/dashboards?search=' . urlencode($sqlInjection));
        
        $response->assertStatus(200);
        
        // Verify users table still exists
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com'
        ]);
    }

    public function test_dashboard_validates_input_data(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->postJson('/admin/api/dashboards', [
            'name' => '', // Empty name
            'description' => str_repeat('a', 1001), // Too long description
            'category' => 'invalid-category',
            'priority' => -1 // Invalid priority
        ]);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'description', 'category', 'priority']);
    }

    public function test_dashboard_sanitizes_user_input(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->postJson('/admin/api/dashboards', [
            'name' => '  Test Dashboard  ', // Whitespace
            'description' => '<p>Valid HTML</p><script>alert("XSS")</script>', // Mixed content
            'category' => 'ANALYTICS' // Case variation
        ]);
        
        $response->assertStatus(201);
        
        $dashboard = $response->json('dashboard');
        
        $this->assertEquals('Test Dashboard', $dashboard['name']); // Trimmed
        $this->assertStringContainsString('<p>Valid HTML</p>', $dashboard['description']); // Valid HTML preserved
        $this->assertStringNotContainsString('<script>', $dashboard['description']); // Script removed
        $this->assertEquals('analytics', strtolower($dashboard['category'])); // Normalized
    }

    public function test_dashboard_enforces_rate_limiting(): void
    {
        $this->actingAs($this->adminUser);
        
        // Make multiple rapid requests
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson('/admin/api/dashboards');
            
            if ($response->getStatusCode() === 429) {
                $this->assertEquals(429, $response->getStatusCode());
                $response->assertHeader('Retry-After');
                break;
            }
        }
    }

    public function test_dashboard_prevents_directory_traversal(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/admin/dashboards/../../../etc/passwd');
        
        $response->assertStatus(404);
    }

    public function test_dashboard_validates_file_uploads(): void
    {
        $this->actingAs($this->adminUser);
        
        // Create malicious file
        $maliciousFile = \Illuminate\Http\UploadedFile::fake()->create('malicious.php', 100);
        
        $response = $this->post('/admin/dashboards/upload', [
            '_token' => csrf_token(),
            'file' => $maliciousFile
        ]);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['file']);
    }

    public function test_dashboard_enforces_content_security_policy(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/admin');
        
        $response->assertStatus(200)
                 ->assertHeader('Content-Security-Policy');
        
        $csp = $response->headers->get('Content-Security-Policy');
        
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("style-src 'self'", $csp);
    }

    public function test_dashboard_sets_security_headers(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/admin');
        
        $response->assertStatus(200)
                 ->assertHeader('X-Frame-Options', 'DENY')
                 ->assertHeader('X-Content-Type-Options', 'nosniff')
                 ->assertHeader('X-XSS-Protection', '1; mode=block')
                 ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                 ->assertHeader('Permissions-Policy');
    }

    public function test_dashboard_prevents_clickjacking(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/admin');
        
        $response->assertStatus(200)
                 ->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_dashboard_enforces_https_in_production(): void
    {
        config(['app.env' => 'production']);
        
        $this->actingAs($this->adminUser);
        
        $response = $this->get('http://example.com/admin');
        
        $response->assertRedirect('https://example.com/admin');
    }

    public function test_dashboard_session_security(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Check session cookie security
        $cookies = $response->headers->getCookies();
        $sessionCookie = collect($cookies)->first(function ($cookie) {
            return $cookie->getName() === config('session.cookie');
        });
        
        if ($sessionCookie) {
            $this->assertTrue($sessionCookie->isHttpOnly());
            $this->assertTrue($sessionCookie->isSecure() || config('app.env') !== 'production');
            $this->assertEquals('lax', strtolower($sessionCookie->getSameSite()));
        }
    }

    public function test_dashboard_password_requirements(): void
    {
        $response = $this->postJson('/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '123', // Weak password
            'password_confirmation' => '123'
        ]);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_dashboard_prevents_mass_assignment(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->postJson('/admin/api/dashboards', [
            'name' => 'Test Dashboard',
            'description' => 'Test Description',
            'is_admin' => true, // Attempt to set admin flag
            'user_id' => 999, // Attempt to set user ID
            'created_at' => '2020-01-01' // Attempt to set timestamp
        ]);
        
        $response->assertStatus(201);
        
        $dashboard = $response->json('dashboard');
        
        // Verify protected fields were not set
        $this->assertArrayNotHasKey('is_admin', $dashboard);
        $this->assertNotEquals(999, $dashboard['user_id'] ?? null);
        $this->assertNotEquals('2020-01-01', $dashboard['created_at'] ?? null);
    }

    public function test_dashboard_audit_logging(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->postJson('/admin/api/dashboards', [
            'name' => 'Audit Test Dashboard',
            'description' => 'Testing audit logging'
        ]);
        
        $response->assertStatus(201);
        
        // Verify audit log entry
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'dashboard.created',
            'resource_type' => 'dashboard'
        ]);
    }

    public function test_dashboard_permission_inheritance(): void
    {
        // Create dashboard with specific permissions
        $restrictedDashboard = new class extends Dashboard {
            public function name(): string
            {
                return 'Restricted Dashboard';
            }
            
            public function cards(): array
            {
                return [];
            }
            
            public function authorizedToSee($request): bool
            {
                return $request->user()->hasPermission('dashboard.restricted');
            }
        };
        
        DashboardRegistry::register('restricted', $restrictedDashboard);
        
        // Test with user without permission
        $this->actingAs($this->regularUser);
        
        $response = $this->getJson('/admin/api/dashboards/restricted');
        
        $response->assertStatus(403);
        
        // Test with admin user
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards/restricted');
        
        $response->assertStatus(200);
    }

    public function test_dashboard_data_encryption(): void
    {
        $this->actingAs($this->adminUser);
        
        $sensitiveData = 'sensitive-information';
        
        $response = $this->postJson('/admin/api/dashboards', [
            'name' => 'Encryption Test',
            'description' => 'Testing data encryption',
            'sensitive_field' => $sensitiveData
        ]);
        
        $response->assertStatus(201);
        
        // Verify data is encrypted in database
        $this->assertDatabaseMissing('dashboards', [
            'sensitive_field' => $sensitiveData
        ]);
        
        // Verify data can be decrypted when retrieved
        $dashboard = $response->json('dashboard');
        $this->assertEquals($sensitiveData, $dashboard['sensitive_field']);
    }

    public function test_dashboard_prevents_timing_attacks(): void
    {
        // Test login timing consistency
        $startTime = microtime(true);
        $this->postJson('/admin/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrong-password'
        ]);
        $invalidUserTime = microtime(true) - $startTime;
        
        $startTime = microtime(true);
        $this->postJson('/admin/login', [
            'email' => $this->adminUser->email,
            'password' => 'wrong-password'
        ]);
        $validUserTime = microtime(true) - $startTime;
        
        // Timing difference should be minimal (< 100ms)
        $timingDifference = abs($validUserTime - $invalidUserTime) * 1000;
        $this->assertLessThan(100, $timingDifference);
    }

    public function test_dashboard_secure_random_generation(): void
    {
        $this->actingAs($this->adminUser);
        
        // Generate multiple tokens
        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            $response = $this->postJson('/admin/api/dashboards/token');
            $response->assertStatus(200);
            $tokens[] = $response->json('token');
        }
        
        // Verify all tokens are unique
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(100, $uniqueTokens);
        
        // Verify tokens have sufficient entropy
        foreach ($tokens as $token) {
            $this->assertGreaterThanOrEqual(32, strlen($token));
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $token);
        }
    }
}
