<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Performance;

use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Support\DashboardRegistry;

/**
 * Dashboard Performance Tests
 * 
 * Performance and load testing for dashboard functionality including
 * response times, memory usage, database queries, and scalability.
 */
class DashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true
        ]);
        
        Auth::login($this->adminUser);
    }

    public function test_dashboard_page_load_performance(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = $this->get('/admin');
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB
        
        $response->assertStatus(200);
        
        // Performance assertions
        $this->assertLessThan(1000, $loadTime, 'Dashboard should load in under 1 second');
        $this->assertLessThan(50, $memoryUsage, 'Dashboard should use less than 50MB memory');
    }

    public function test_dashboard_api_response_time(): void
    {
        $startTime = microtime(true);
        
        $response = $this->getJson('/admin/api/dashboards');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime, 'API should respond in under 500ms');
    }

    public function test_dashboard_database_query_efficiency(): void
    {
        // Enable query logging
        DB::enableQueryLog();
        
        $response = $this->get('/admin');
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $totalQueryTime = array_sum(array_column($queries, 'time'));
        
        $response->assertStatus(200);
        
        // Query efficiency assertions
        $this->assertLessThan(20, $queryCount, 'Dashboard should execute fewer than 20 queries');
        $this->assertLessThan(100, $totalQueryTime, 'Total query time should be under 100ms');
        
        // Check for N+1 query problems
        $duplicateQueries = [];
        foreach ($queries as $query) {
            $sql = preg_replace('/\s+/', ' ', trim($query['query']));
            $duplicateQueries[$sql] = ($duplicateQueries[$sql] ?? 0) + 1;
        }
        
        $maxDuplicates = max($duplicateQueries);
        $this->assertLessThan(5, $maxDuplicates, 'No query should be executed more than 5 times');
    }

    public function test_dashboard_caching_performance(): void
    {
        Cache::flush();
        
        // First request (cache miss)
        $startTime = microtime(true);
        $response1 = $this->getJson('/admin/api/dashboards');
        $firstRequestTime = microtime(true) - $startTime;
        
        $response1->assertStatus(200);
        
        // Second request (cache hit)
        $startTime = microtime(true);
        $response2 = $this->getJson('/admin/api/dashboards');
        $secondRequestTime = microtime(true) - $startTime;
        
        $response2->assertStatus(200);
        
        // Cached request should be significantly faster
        $this->assertLessThan($firstRequestTime * 0.5, $secondRequestTime, 
            'Cached request should be at least 50% faster');
    }

    public function test_dashboard_concurrent_request_handling(): void
    {
        $concurrentRequests = 10;
        $promises = [];
        $startTime = microtime(true);
        
        // Simulate concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $promises[] = $this->getJson('/admin/api/dashboards');
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        // All requests should succeed
        foreach ($promises as $response) {
            $response->assertStatus(200);
        }
        
        // Concurrent requests should not take much longer than sequential
        $this->assertLessThan(5000, $totalTime, 
            'Concurrent requests should complete within 5 seconds');
    }

    public function test_dashboard_memory_leak_prevention(): void
    {
        $initialMemory = memory_get_usage();
        
        // Perform multiple operations
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson('/admin/api/dashboards');
            $response->assertStatus(200);
            
            // Force garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024; // MB
        
        // Memory increase should be minimal
        $this->assertLessThan(10, $memoryIncrease, 
            'Memory usage should not increase by more than 10MB');
    }

    public function test_dashboard_large_dataset_performance(): void
    {
        // Create large dataset
        $dashboards = [];
        for ($i = 0; $i < 1000; $i++) {
            $dashboards[] = new class extends Dashboard {
                private int $id;
                
                public function __construct(int $id = 0)
                {
                    $this->id = $id;
                }
                
                public function name(): string
                {
                    return "Dashboard {$this->id}";
                }
                
                public function cards(): array
                {
                    return array_fill(0, 50, [
                        'type' => 'metric',
                        'title' => 'Metric',
                        'value' => rand(1, 1000)
                    ]);
                }
            };
            
            DashboardRegistry::register("dashboard-{$i}", end($dashboards));
        }
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = $this->getJson('/admin/api/dashboards');
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $loadTime = ($endTime - $startTime) * 1000;
        $memoryUsage = ($endMemory - $startMemory) / 1024 / 1024;
        
        $response->assertStatus(200);
        
        // Performance with large dataset
        $this->assertLessThan(2000, $loadTime, 
            'Large dataset should load in under 2 seconds');
        $this->assertLessThan(100, $memoryUsage, 
            'Large dataset should use less than 100MB memory');
    }

    public function test_dashboard_pagination_performance(): void
    {
        // Test pagination efficiency
        $startTime = microtime(true);
        
        $response = $this->getJson('/admin/api/dashboards?page=1&per_page=50');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        
        $this->assertLessThan(300, $responseTime, 
            'Paginated request should respond in under 300ms');
        
        // Test deep pagination
        $startTime = microtime(true);
        
        $response = $this->getJson('/admin/api/dashboards?page=100&per_page=10');
        
        $endTime = microtime(true);
        $deepPageTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        
        $this->assertLessThan(500, $deepPageTime, 
            'Deep pagination should respond in under 500ms');
    }

    public function test_dashboard_search_performance(): void
    {
        $startTime = microtime(true);
        
        $response = $this->getJson('/admin/api/dashboards?search=analytics');
        
        $endTime = microtime(true);
        $searchTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        
        $this->assertLessThan(400, $searchTime, 
            'Search should respond in under 400ms');
    }

    public function test_dashboard_filtering_performance(): void
    {
        $startTime = microtime(true);
        
        $response = $this->getJson('/admin/api/dashboards?category=Analytics&visible=true&sort=name');
        
        $endTime = microtime(true);
        $filterTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        
        $this->assertLessThan(350, $filterTime, 
            'Filtering should respond in under 350ms');
    }

    public function test_dashboard_asset_loading_performance(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for asset optimization
        $this->assertStringContainsString('.min.js', $content, 
            'JavaScript assets should be minified');
        $this->assertStringContainsString('.min.css', $content, 
            'CSS assets should be minified');
        
        // Check for asset compression hints
        preg_match_all('/\.(js|css)/', $content, $assets);
        $assetCount = count($assets[0]);
        
        $this->assertLessThan(20, $assetCount, 
            'Should have fewer than 20 asset files for optimal loading');
    }

    public function test_dashboard_image_optimization(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for optimized image formats
        preg_match_all('/<img[^>]*src="([^"]*)"/', $content, $images);
        
        foreach ($images[1] as $imageSrc) {
            // Images should use optimized formats or have optimization parameters
            $isOptimized = strpos($imageSrc, '.webp') !== false ||
                          strpos($imageSrc, '?w=') !== false ||
                          strpos($imageSrc, '?q=') !== false;
            
            $this->assertTrue($isOptimized, 
                "Image {$imageSrc} should be optimized");
        }
    }

    public function test_dashboard_lazy_loading_performance(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for lazy loading attributes
        $this->assertStringContainsString('loading="lazy"', $content, 
            'Images should have lazy loading enabled');
        $this->assertStringContainsString('data-lazy', $content, 
            'Components should support lazy loading');
    }

    public function test_dashboard_cdn_usage(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Check for CDN headers
        $response->assertHeader('Cache-Control');
        
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl, 
            'Static assets should be publicly cacheable');
    }

    public function test_dashboard_compression_efficiency(): void
    {
        $response = $this->get('/admin', [
            'Accept-Encoding' => 'gzip, deflate'
        ]);
        
        $response->assertStatus(200);
        
        // Check for compression headers
        $contentEncoding = $response->headers->get('Content-Encoding');
        $this->assertNotNull($contentEncoding, 
            'Response should be compressed');
    }

    public function test_dashboard_resource_hints(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for resource hints
        $this->assertStringContainsString('rel="preload"', $content, 
            'Critical resources should be preloaded');
        $this->assertStringContainsString('rel="prefetch"', $content, 
            'Future resources should be prefetched');
        $this->assertStringContainsString('rel="dns-prefetch"', $content, 
            'External domains should be DNS prefetched');
    }

    public function test_dashboard_critical_css_inlining(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check for inlined critical CSS
        $this->assertStringContainsString('<style>', $content, 
            'Critical CSS should be inlined');
        
        // Check that non-critical CSS is loaded asynchronously
        $this->assertStringContainsString('media="print" onload=', $content, 
            'Non-critical CSS should be loaded asynchronously');
    }

    public function test_dashboard_service_worker_caching(): void
    {
        $response = $this->get('/admin/sw.js');
        
        if ($response->getStatusCode() === 200) {
            $content = $response->getContent();
            
            // Check for caching strategies
            $this->assertStringContainsString('cache', $content, 
                'Service worker should implement caching');
            $this->assertStringContainsString('fetch', $content, 
                'Service worker should handle fetch events');
        }
    }

    public function test_dashboard_performance_budget(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        $contentLength = strlen($response->getContent());
        $contentSizeKB = $contentLength / 1024;
        
        // Performance budget assertions
        $this->assertLessThan(500, $contentSizeKB, 
            'Initial page size should be under 500KB');
        
        // Check for performance timing API usage
        $content = $response->getContent();
        $this->assertStringContainsString('performance.', $content, 
            'Should use Performance API for monitoring');
    }
}
