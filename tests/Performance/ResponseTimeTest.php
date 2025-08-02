<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Performance;

use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\Fixtures\UserResource;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Performance Tests
 *
 * Test response times to ensure the admin panel meets performance requirements.
 * Target: Sub-100ms response times for most operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ResponseTimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Register test resource
        $adminPanel = app(AdminPanel::class);
        $adminPanel->register([UserResource::class]);
        
        // Create test data
        User::factory()->count(50)->create();
    }

    public function test_dashboard_loads_within_performance_target(): void
    {
        $admin = $this->createAdminUser();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($admin)
            ->get('/admin');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertOk();
        
        // Target: Sub-100ms for dashboard
        $this->assertLessThan(100, $responseTime, 
            "Dashboard response time ({$responseTime}ms) exceeds 100ms target"
        );
        
        $this->addToAssertionCount(1);
        echo "\n✅ Dashboard response time: " . round($responseTime, 2) . "ms";
    }

    public function test_resource_index_loads_within_performance_target(): void
    {
        $admin = $this->createAdminUser();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($admin)
            ->get('/admin/resources/users');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertOk();
        
        // Target: Sub-150ms for resource index with 50 records
        $this->assertLessThan(150, $responseTime, 
            "Resource index response time ({$responseTime}ms) exceeds 150ms target"
        );
        
        echo "\n✅ Resource index response time: " . round($responseTime, 2) . "ms";
    }

    public function test_resource_show_loads_within_performance_target(): void
    {
        $admin = $this->createAdminUser();
        $user = User::first();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($admin)
            ->get("/admin/resources/users/{$user->id}");
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertOk();
        
        // Target: Sub-50ms for single resource show
        $this->assertLessThan(50, $responseTime, 
            "Resource show response time ({$responseTime}ms) exceeds 50ms target"
        );
        
        echo "\n✅ Resource show response time: " . round($responseTime, 2) . "ms";
    }

    public function test_resource_create_form_loads_within_performance_target(): void
    {
        $admin = $this->createAdminUser();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($admin)
            ->get('/admin/resources/users/create');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertOk();
        
        // Target: Sub-75ms for create form
        $this->assertLessThan(75, $responseTime, 
            "Resource create form response time ({$responseTime}ms) exceeds 75ms target"
        );
        
        echo "\n✅ Resource create form response time: " . round($responseTime, 2) . "ms";
    }

    public function test_resource_edit_form_loads_within_performance_target(): void
    {
        $admin = $this->createAdminUser();
        $user = User::first();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($admin)
            ->get("/admin/resources/users/{$user->id}/edit");
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertOk();
        
        // Target: Sub-75ms for edit form
        $this->assertLessThan(75, $responseTime, 
            "Resource edit form response time ({$responseTime}ms) exceeds 75ms target"
        );
        
        echo "\n✅ Resource edit form response time: " . round($responseTime, 2) . "ms";
    }

    public function test_metrics_calculation_within_performance_target(): void
    {
        $admin = $this->createAdminUser();
        
        // Test metrics calculation time directly
        $startTime = microtime(true);
        
        $response = $this->actingAs($admin)
            ->get('/admin');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertOk();
        
        // Metrics should be calculated quickly (included in dashboard load time)
        $this->assertLessThan(100, $responseTime, 
            "Dashboard with metrics response time ({$responseTime}ms) exceeds 100ms target"
        );
        
        echo "\n✅ Dashboard with metrics response time: " . round($responseTime, 2) . "ms";
    }

    public function test_memory_usage_within_acceptable_limits(): void
    {
        $admin = $this->createAdminUser();
        
        $memoryBefore = memory_get_usage(true);
        
        $response = $this->actingAs($admin)
            ->get('/admin/resources/users');
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB
        
        $response->assertOk();
        
        // Target: Less than 10MB memory usage for resource index
        $this->assertLessThan(10, $memoryUsed, 
            "Memory usage ({$memoryUsed}MB) exceeds 10MB target"
        );
        
        echo "\n✅ Memory usage for resource index: " . round($memoryUsed, 2) . "MB";
    }

    public function test_database_query_count_is_optimized(): void
    {
        $admin = $this->createAdminUser();
        
        // Enable query logging
        \DB::enableQueryLog();
        
        $response = $this->actingAs($admin)
            ->get('/admin/resources/users');
        
        $queries = \DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertOk();
        
        // Target: Less than 10 queries for resource index
        $this->assertLessThan(10, $queryCount, 
            "Query count ({$queryCount}) exceeds 10 queries target"
        );
        
        echo "\n✅ Database queries for resource index: {$queryCount}";
        
        // Disable query logging
        \DB::disableQueryLog();
    }

    public function test_concurrent_request_handling(): void
    {
        $admin = $this->createAdminUser();
        
        $responses = [];
        $startTime = microtime(true);
        
        // Simulate 5 concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->actingAs($admin)
                ->get('/admin/resources/users');
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $averageTime = $totalTime / 5;
        
        // All responses should be successful
        foreach ($responses as $response) {
            $response->assertOk();
        }
        
        // Average response time should still be reasonable
        $this->assertLessThan(200, $averageTime, 
            "Average response time for concurrent requests ({$averageTime}ms) exceeds 200ms target"
        );
        
        echo "\n✅ Average response time for 5 concurrent requests: " . round($averageTime, 2) . "ms";
    }
}
