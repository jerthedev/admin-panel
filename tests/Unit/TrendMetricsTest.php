<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use JTD\AdminPanel\Metrics\RegistrationTrendMetric;
use JTD\AdminPanel\Metrics\Trend;
use JTD\AdminPanel\Metrics\TrendResult;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Trend Metrics Tests.
 *
 * Comprehensive tests for Trend metrics including the RegistrationTrendMetric example
 * and the base Trend class functionality. Tests cover time-based aggregation,
 * chart data formatting, caching, and Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TrendMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear metric caches
        Cache::flush();

        // Create test users table
        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_users');
        parent::tearDown();
    }

    public function test_registration_trend_metric_basic_functionality(): void
    {
        $metric = new RegistrationTrendMetric;
        $request = new Request;

        // Test basic properties
        $this->assertEquals('Registration Trend', $metric->name);
        $this->assertEquals('registration-trend', $metric->uriKey());
        $this->assertEquals('chart-line', $metric->icon());
        $this->assertIsArray($metric->ranges());
        $this->assertIsInt($metric->cacheFor());
    }

    public function test_registration_trend_metric_ranges(): void
    {
        $metric = new RegistrationTrendMetric;
        $ranges = $metric->ranges();

        $expectedRanges = [
            1 => 'Today',
            7 => '7 Days',
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            365 => '1 Year',
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
        ];

        $this->assertEquals($expectedRanges, $ranges);
    }

    public function test_registration_trend_metric_calculation_with_no_users(): void
    {
        $metric = new RegistrationTrendMetric;
        $metric->userModel(TrendTestUser::class);

        $request = new Request(['range' => 7]);
        $result = $metric->calculate($request);

        $this->assertInstanceOf(TrendResult::class, $result);
        $this->assertTrue($result->hasNoData());
        $this->assertEmpty($result->getTrend());
    }

    public function test_registration_trend_metric_calculation_with_users(): void
    {
        // Create test users across different days
        $now = Carbon::now();

        // Day 1: 2 users
        TrendTestUser::create([
            'name' => 'User 1',
            'email' => 'user1@test.com',
            'created_at' => $now->copy()->subDays(2)->setHour(10),
        ]);

        TrendTestUser::create([
            'name' => 'User 2',
            'email' => 'user2@test.com',
            'created_at' => $now->copy()->subDays(2)->setHour(14),
        ]);

        // Day 2: 1 user
        TrendTestUser::create([
            'name' => 'User 3',
            'email' => 'user3@test.com',
            'created_at' => $now->copy()->subDays(1)->setHour(9),
        ]);

        $metric = new RegistrationTrendMetric;
        $metric->userModel(TrendTestUser::class);

        $request = new Request(['range' => 7]);
        $result = $metric->calculate($request);

        $this->assertInstanceOf(TrendResult::class, $result);
        $this->assertFalse($result->hasNoData());

        $trend = $result->getTrend();
        $this->assertIsArray($trend);
        $this->assertNotEmpty($trend);

        // Should have data for the days with users
        $this->assertContains(2.0, $trend); // Day with 2 users
        $this->assertContains(1.0, $trend); // Day with 1 user
    }

    public function test_trend_result_formatting(): void
    {
        $trendData = [
            '2023-01-01' => 10,
            '2023-01-02' => 15,
            '2023-01-03' => 8,
        ];

        $result = new TrendResult($trendData);

        // Test basic trend data
        $this->assertEquals($trendData, $result->getTrend());
        $this->assertFalse($result->hasNoData());

        // Test current value (latest)
        $this->assertEquals(8, $result->getCurrentValue());

        // Test trend sum
        $this->assertEquals(33, $result->getTrendSum());
    }

    public function test_trend_result_formatting_options(): void
    {
        $trendData = [
            '2023-01-01' => 1234.56,
            '2023-01-02' => 2345.67,
        ];

        $result = new TrendResult($trendData);

        // Test with prefix
        $result->prefix('Total: ');
        $this->assertEquals('Total: 1,235', $result->getFormattedValue(1234.56));

        // Test with suffix
        $result = new TrendResult($trendData);
        $result->suffix(' users');
        $this->assertEquals('1,235 users', $result->getFormattedValue(1234.56));

        // Test with currency
        $result = new TrendResult($trendData);
        $result->currency('$');
        $this->assertEquals('$1,235', $result->getFormattedValue(1234.56));
    }

    public function test_trend_result_transformation(): void
    {
        $trendData = [
            '2023-01-01' => 1000,
            '2023-01-02' => 2000,
        ];

        $result = new TrendResult($trendData);

        // Transform to thousands
        $result->transform(fn ($value) => $value / 1000);

        $transformed = $result->getTrend();
        $this->assertEquals(1, $transformed['2023-01-01']);
        $this->assertEquals(2, $transformed['2023-01-02']);
    }

    public function test_trend_result_chart_data(): void
    {
        $trendData = [
            '2023-01-01' => 10,
            '2023-01-02' => 15,
        ];

        $result = new TrendResult($trendData);
        $result->prefix('Count: ');

        $chartData = $result->getChartData();

        $this->assertIsArray($chartData);
        $this->assertCount(2, $chartData);

        $this->assertEquals([
            'label' => '2023-01-01',
            'value' => 10,
            'formatted_value' => 'Count: 10',
        ], $chartData[0]);

        $this->assertEquals([
            'label' => '2023-01-02',
            'value' => 15,
            'formatted_value' => 'Count: 15',
        ], $chartData[1]);
    }

    public function test_trend_result_json_serialization(): void
    {
        $trendData = [
            '2023-01-01' => 10,
            '2023-01-02' => 15,
        ];

        $result = new TrendResult($trendData);
        $result->showCurrentValue()->showTrendSum();

        $json = $result->jsonSerialize();

        $expectedKeys = [
            'trend',
            'chart_data',
            'has_no_data',
            'current_value',
            'formatted_current_value',
            'trend_sum',
            'formatted_trend_sum',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $json);
        }

        $this->assertEquals($trendData, $json['trend']);
        $this->assertFalse($json['has_no_data']);
        $this->assertEquals(15, $json['current_value']);
        $this->assertEquals(25, $json['trend_sum']);
    }

    public function test_registration_trend_metric_aggregation_units(): void
    {
        $metric = new RegistrationTrendMetric;
        $metric->userModel(TrendTestUser::class);

        // Test different aggregation units
        $units = ['hour', 'day', 'week', 'month'];

        foreach ($units as $unit) {
            $request = new Request(['range' => 7, 'unit' => $unit]);
            $result = $metric->calculate($request);

            $this->assertInstanceOf(TrendResult::class, $result);
        }
    }

    public function test_registration_trend_metric_caching(): void
    {
        $metric = new RegistrationTrendMetric;
        $metric->userModel(TrendTestUser::class);
        $metric->cacheForMinutes(15);

        $request = new Request(['range' => 7]);

        // First call should cache the result
        $result1 = $metric->calculate($request);

        // Second call should return cached result
        $result2 = $metric->calculate($request);

        $this->assertEquals($result1->getTrend(), $result2->getTrend());
        $this->assertEquals(15 * 60, $metric->cacheFor()); // 15 minutes in seconds
    }

    public function test_registration_trend_metric_authorization(): void
    {
        $metric = new RegistrationTrendMetric;
        $request = new Request;

        // Without authenticated user, should return false
        $this->assertFalse($metric->authorize($request));
    }

    public function test_registration_trend_metric_meta_data(): void
    {
        $metric = new RegistrationTrendMetric;
        $meta = $metric->meta();

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('model', $meta);
        $this->assertArrayHasKey('cache_minutes', $meta);
        $this->assertArrayHasKey('default_unit', $meta);
        $this->assertArrayHasKey('help', $meta);
        $this->assertArrayHasKey('icon', $meta);
    }

    public function test_trend_result_show_options(): void
    {
        $trendData = [
            '2023-01-01' => 10,
            '2023-01-02' => 15,
        ];

        $result = new TrendResult($trendData);

        // Test showCurrentValue
        $result->showCurrentValue();
        $json = $result->jsonSerialize();
        $this->assertArrayHasKey('current_value', $json);
        $this->assertEquals(15, $json['current_value']);

        // Test showTrendSum
        $result = new TrendResult($trendData);
        $result->showTrendSum();
        $json = $result->jsonSerialize();
        $this->assertArrayHasKey('trend_sum', $json);
        $this->assertEquals(25, $json['trend_sum']);
    }
}

/**
 * Trend Test User Model for testing purposes.
 */
class TrendTestUser extends Model
{
    protected $table = 'test_users';

    protected $fillable = [
        'name',
        'email',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
