<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Metrics\ActiveUsersMetric;
use JTD\AdminPanel\Metrics\ResourceCountMetric;
use JTD\AdminPanel\Metrics\SystemHealthMetric;
use JTD\AdminPanel\Metrics\UserCountMetric;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Metrics Tests
 *
 * Test the dashboard metrics system including calculation,
 * formatting, and trend analysis.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MetricsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear metric caches
        Cache::flush();
    }

    public function test_user_count_metric_calculates_correctly(): void
    {
        $metric = new UserCountMetric();
        $request = new Request();

        $this->assertEquals('Total Users', $metric->name());
        $this->assertEquals('UsersIcon', $metric->icon());
        $this->assertEquals('blue', $metric->color());
        $this->assertEquals('number', $metric->format());

        // The metric should return a number (even if 0 when no users exist)
        $value = $metric->calculate($request);
        $this->assertIsInt($value);
        $this->assertGreaterThanOrEqual(0, $value);
    }

    public function test_active_users_metric_calculates_correctly(): void
    {
        $metric = new ActiveUsersMetric();
        $request = new Request();

        $this->assertEquals('Active Users', $metric->name());
        $this->assertEquals('UserGroupIcon', $metric->icon());
        $this->assertEquals('purple', $metric->color());
        $this->assertEquals('number', $metric->format());

        // The metric should return a number
        $value = $metric->calculate($request);
        $this->assertIsInt($value);
        $this->assertGreaterThanOrEqual(0, $value);
    }

    public function test_resource_count_metric_calculates_correctly(): void
    {
        $metric = new ResourceCountMetric();
        $request = new Request();

        $this->assertEquals('Admin Resources', $metric->name());
        $this->assertEquals('CubeIcon', $metric->icon());
        $this->assertEquals('green', $metric->color());
        $this->assertEquals('number', $metric->format());

        // The metric should return a number
        $value = $metric->calculate($request);
        $this->assertIsInt($value);
        $this->assertGreaterThanOrEqual(0, $value);
    }

    public function test_system_health_metric_calculates_correctly(): void
    {
        $metric = new SystemHealthMetric();
        $request = new Request();

        $this->assertEquals('System Health', $metric->name());
        $this->assertEquals('HeartIcon', $metric->icon());
        $this->assertEquals('red', $metric->color());
        $this->assertEquals('percentage', $metric->format());

        // The metric should return a percentage between 0 and 100
        $value = $metric->calculate($request);
        $this->assertIsFloat($value);
        $this->assertGreaterThanOrEqual(0, $value);
        $this->assertLessThanOrEqual(100, $value);
    }

    public function test_metric_authorization_works(): void
    {
        $metric = new UserCountMetric();
        $request = new Request();

        // Default authorization should return true
        $this->assertTrue($metric->authorize($request));
    }

    public function test_metric_trend_calculation(): void
    {
        $metric = new UserCountMetric();
        $request = new Request();

        // Trend might be null if no previous data exists
        $trend = $metric->trend($request);

        if ($trend !== null) {
            $this->assertIsArray($trend);
            $this->assertArrayHasKey('percentage', $trend);
            $this->assertArrayHasKey('direction', $trend);
            $this->assertContains($trend['direction'], ['up', 'down']);
            $this->assertIsFloat($trend['percentage']);
        } else {
            // If trend is null, that's also a valid state
            $this->assertNull($trend);
        }
    }

    public function test_metric_fluent_interface(): void
    {
        $metric = new UserCountMetric();

        $result = $metric
            ->withName('Custom Users')
            ->withIcon('CustomIcon')
            ->withColor('green')
            ->withFormat('currency')
            ->withoutTrend();

        $this->assertSame($metric, $result);
        $this->assertEquals('Custom Users', $metric->name());
        $this->assertEquals('CustomIcon', $metric->icon());
        $this->assertEquals('green', $metric->color());
        $this->assertEquals('currency', $metric->format());
    }

    public function test_active_users_metric_configuration(): void
    {
        $metric = new ActiveUsersMetric();

        $result = $metric
            ->withActivityPeriod(7)
            ->withActivityColumn('last_seen_at')
            ->withUserModel(\App\Models\User::class);

        $this->assertSame($metric, $result);
    }

    public function test_metric_caching(): void
    {
        $metric = new UserCountMetric();
        $request = new Request();

        // First call should calculate and cache
        $value1 = $metric->calculate($request);

        // Second call should use cache
        $value2 = $metric->calculate($request);

        $this->assertEquals($value1, $value2);
    }

    public function test_system_health_metric_authorization(): void
    {
        $metric = new SystemHealthMetric();
        $request = new Request();

        // Without authenticated user, should return false
        $this->assertFalse($metric->authorize($request));
    }

    public function test_resource_count_metric_no_trend(): void
    {
        $metric = new ResourceCountMetric();
        $request = new Request();

        // Resource count metric has trend disabled
        $trend = $metric->trend($request);
        $this->assertNull($trend);
    }
}
