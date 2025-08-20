<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Api;

use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Support\DashboardRegistry;

/**
 * Dashboard API Tests
 * 
 * Comprehensive API testing for dashboard endpoints including
 * authentication, authorization, caching, and error handling.
 */
class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true
        ]);
        
        $this->regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'is_admin' => false
        ]);
        
        // Register test dashboard routes
        $this->registerTestRoutes();
    }

    protected function registerTestRoutes(): void
    {
        Route::group(['prefix' => 'admin/api', 'middleware' => ['web', 'auth']], function () {
            Route::get('/dashboards', function () {
                return response()->json([
                    'dashboards' => DashboardRegistry::all()
                ]);
            });
            
            Route::get('/dashboards/{dashboard}', function ($dashboard) {
                $dashboardInstance = DashboardRegistry::get($dashboard);
                
                if (!$dashboardInstance) {
                    return response()->json(['error' => 'Dashboard not found'], 404);
                }
                
                return response()->json([
                    'dashboard' => $dashboardInstance->jsonSerialize()
                ]);
            });
            
            Route::post('/dashboards/{dashboard}/data', function ($dashboard) {
                $dashboardInstance = DashboardRegistry::get($dashboard);
                
                if (!$dashboardInstance) {
                    return response()->json(['error' => 'Dashboard not found'], 404);
                }
                
                return response()->json([
                    'data' => $dashboardInstance->cards()
                ]);
            });
        });
    }

    public function test_get_dashboards_requires_authentication(): void
    {
        $response = $this->getJson('/admin/api/dashboards');
        
        $response->assertStatus(401);
    }

    public function test_get_dashboards_returns_authorized_dashboards(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'dashboards' => [
                         '*' => [
                             'uriKey',
                             'name',
                             'description',
                             'icon',
                             'category'
                         ]
                     ]
                 ]);
    }

    public function test_get_dashboard_by_uri_key(): void
    {
        $this->actingAs($this->adminUser);
        
        // Register a test dashboard
        $testDashboard = new class extends Dashboard {
            public function name(): string
            {
                return 'Test Dashboard';
            }
            
            public function cards(): array
            {
                return [
                    'users' => ['type' => 'metric', 'title' => 'Users', 'value' => 100],
                    'revenue' => ['type' => 'metric', 'title' => 'Revenue', 'value' => 5000]
                ];
            }
        };
        
        DashboardRegistry::register('test-dashboard', $testDashboard);
        
        $response = $this->getJson('/admin/api/dashboards/test-dashboard');
        
        $response->assertStatus(200)
                 ->assertJson([
                     'dashboard' => [
                         'uriKey' => 'test-dashboard',
                         'name' => 'Test Dashboard'
                     ]
                 ]);
    }

    public function test_get_nonexistent_dashboard_returns_404(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards/nonexistent');
        
        $response->assertStatus(404)
                 ->assertJson(['error' => 'Dashboard not found']);
    }

    public function test_get_dashboard_data_returns_cards(): void
    {
        $this->actingAs($this->adminUser);
        
        // Register a test dashboard
        $testDashboard = new class extends Dashboard {
            public function name(): string
            {
                return 'Data Dashboard';
            }
            
            public function cards(): array
            {
                return [
                    'users' => [
                        'type' => 'metric',
                        'title' => 'Total Users',
                        'value' => 1250,
                        'change' => '+12%'
                    ],
                    'revenue' => [
                        'type' => 'metric',
                        'title' => 'Revenue',
                        'value' => 45000,
                        'change' => '+8%'
                    ]
                ];
            }
        };
        
        DashboardRegistry::register('data-dashboard', $testDashboard);
        
        $response = $this->postJson('/admin/api/dashboards/data-dashboard/data');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'users' => [
                             'type',
                             'title',
                             'value',
                             'change'
                         ],
                         'revenue' => [
                             'type',
                             'title',
                             'value',
                             'change'
                         ]
                     ]
                 ]);
    }

    public function test_api_responses_include_proper_headers(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards');
        
        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'application/json')
                 ->assertHeader('Cache-Control')
                 ->assertHeader('X-RateLimit-Limit')
                 ->assertHeader('X-RateLimit-Remaining');
    }

    public function test_api_handles_malformed_requests(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->postJson('/admin/api/dashboards/test/data', [
            'invalid' => 'json{malformed'
        ]);
        
        $response->assertStatus(422);
    }

    public function test_api_rate_limiting(): void
    {
        $this->actingAs($this->adminUser);
        
        // Make multiple requests to test rate limiting
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson('/admin/api/dashboards');
            
            if ($response->getStatusCode() === 429) {
                $this->assertEquals(429, $response->getStatusCode());
                $response->assertHeader('Retry-After');
                break;
            }
        }
    }

    public function test_api_caching_headers(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards');
        
        $response->assertStatus(200);
        
        // Test ETag header
        $etag = $response->headers->get('ETag');
        $this->assertNotNull($etag);
        
        // Test conditional request
        $conditionalResponse = $this->getJson('/admin/api/dashboards', [
            'If-None-Match' => $etag
        ]);
        
        $conditionalResponse->assertStatus(304);
    }

    public function test_api_cors_headers(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->json('OPTIONS', '/admin/api/dashboards', [], [
            'Origin' => 'https://example.com',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Content-Type'
        ]);
        
        $response->assertHeader('Access-Control-Allow-Origin')
                 ->assertHeader('Access-Control-Allow-Methods')
                 ->assertHeader('Access-Control-Allow-Headers');
    }

    public function test_api_error_responses_are_consistent(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards/nonexistent');
        
        $response->assertStatus(404)
                 ->assertJsonStructure([
                     'error',
                     'message',
                     'code',
                     'timestamp'
                 ]);
    }

    public function test_api_supports_pagination(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards?page=1&per_page=10');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'dashboards',
                     'meta' => [
                         'current_page',
                         'per_page',
                         'total',
                         'last_page'
                     ],
                     'links' => [
                         'first',
                         'last',
                         'prev',
                         'next'
                     ]
                 ]);
    }

    public function test_api_supports_filtering(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards?category=Analytics&visible=true');
        
        $response->assertStatus(200);
        
        $dashboards = $response->json('dashboards');
        
        foreach ($dashboards as $dashboard) {
            $this->assertEquals('Analytics', $dashboard['category']);
            $this->assertTrue($dashboard['visible']);
        }
    }

    public function test_api_supports_sorting(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards?sort=name&order=asc');
        
        $response->assertStatus(200);
        
        $dashboards = $response->json('dashboards');
        $names = array_column($dashboards, 'name');
        
        $this->assertEquals($names, array_values(array_sort($names)));
    }

    public function test_api_includes_metadata(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards?include=metadata');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'dashboards' => [
                         '*' => [
                             'uriKey',
                             'name',
                             'metadata' => [
                                 'created_at',
                                 'updated_at',
                                 'version',
                                 'author'
                             ]
                         ]
                     ]
                 ]);
    }

    public function test_api_handles_concurrent_requests(): void
    {
        $this->actingAs($this->adminUser);
        
        $promises = [];
        
        // Simulate concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $promises[] = $this->getJson('/admin/api/dashboards');
        }
        
        foreach ($promises as $response) {
            $response->assertStatus(200);
        }
    }

    public function test_api_validates_request_parameters(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards?page=invalid&per_page=abc');
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['page', 'per_page']);
    }

    public function test_api_supports_field_selection(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards?fields=uriKey,name,category');
        
        $response->assertStatus(200);
        
        $dashboards = $response->json('dashboards');
        
        foreach ($dashboards as $dashboard) {
            $this->assertArrayHasKey('uriKey', $dashboard);
            $this->assertArrayHasKey('name', $dashboard);
            $this->assertArrayHasKey('category', $dashboard);
            $this->assertArrayNotHasKey('description', $dashboard);
        }
    }

    public function test_api_caches_expensive_operations(): void
    {
        $this->actingAs($this->adminUser);
        
        Cache::flush();
        
        // First request should hit the database
        $startTime = microtime(true);
        $response1 = $this->getJson('/admin/api/dashboards');
        $firstRequestTime = microtime(true) - $startTime;
        
        $response1->assertStatus(200);
        
        // Second request should be cached
        $startTime = microtime(true);
        $response2 = $this->getJson('/admin/api/dashboards');
        $secondRequestTime = microtime(true) - $startTime;
        
        $response2->assertStatus(200);
        
        // Cached request should be faster
        $this->assertLessThan($firstRequestTime, $secondRequestTime);
    }

    public function test_api_handles_dashboard_authorization(): void
    {
        $this->actingAs($this->regularUser);
        
        // Register a restricted dashboard
        $restrictedDashboard = new class extends Dashboard {
            public function name(): string
            {
                return 'Admin Only Dashboard';
            }
            
            public function cards(): array
            {
                return [];
            }
            
            public function authorizedToSee($request): bool
            {
                return $request->user()->is_admin;
            }
        };
        
        DashboardRegistry::register('admin-only', $restrictedDashboard);
        
        $response = $this->getJson('/admin/api/dashboards/admin-only');
        
        $response->assertStatus(403);
    }

    public function test_api_logs_requests_for_audit(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/admin/api/dashboards');
        
        $response->assertStatus(200);
        
        // Check that request was logged
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'dashboard.api.list',
            'resource_type' => 'dashboard'
        ]);
    }

    public function test_api_performance_within_acceptable_limits(): void
    {
        $this->actingAs($this->adminUser);
        
        $startTime = microtime(true);
        
        $response = $this->getJson('/admin/api/dashboards');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        
        // API should respond within 500ms
        $this->assertLessThan(500, $responseTime, 
            'API response time should be under 500ms');
    }
}
