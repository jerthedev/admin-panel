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
use JTD\AdminPanel\Metrics\UserGrowthMetric;
use JTD\AdminPanel\Metrics\Value;
use JTD\AdminPanel\Metrics\ValueResult;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Value Metrics Tests.
 *
 * Comprehensive tests for Value metrics including the UserGrowthMetric example
 * and the base Value class functionality. Tests cover calculation, formatting,
 * caching, range selection, and Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ValueMetricsTest extends TestCase
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

    public function test_user_growth_metric_basic_functionality(): void
    {
        $metric = new UserGrowthMetric;
        $request = new Request;

        // Test basic properties
        $this->assertEquals('User Growth', $metric->name);
        $this->assertEquals('user-growth', $metric->uriKey());
        $this->assertEquals('users', $metric->icon());
        $this->assertIsArray($metric->ranges());
        $this->assertIsInt($metric->cacheFor());
    }

    public function test_user_growth_metric_ranges(): void
    {
        $metric = new UserGrowthMetric;
        $ranges = $metric->ranges();

        $expectedRanges = [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
        ];

        $this->assertEquals($expectedRanges, $ranges);
    }

    public function test_user_growth_metric_calculation_with_no_users(): void
    {
        $metric = new UserGrowthMetric;
        $metric->userModel(TestUser::class);

        $request = new Request(['range' => 30]);
        $result = $metric->calculate($request);

        $this->assertInstanceOf(ValueResult::class, $result);
        $this->assertEquals(0, $result->getValue());
        $this->assertEquals(0, $result->getPrevious());
        $this->assertNull($result->getPercentageChange());
    }

    public function test_user_growth_metric_calculation_with_users(): void
    {
        // Create test users - some in current period, some in previous period
        $now = Carbon::now();

        // Current period users (last 30 days)
        TestUser::create([
            'name' => 'Current User 1',
            'email' => 'current1@test.com',
            'created_at' => $now->copy()->subDays(10),
        ]);

        TestUser::create([
            'name' => 'Current User 2',
            'email' => 'current2@test.com',
            'created_at' => $now->copy()->subDays(20),
        ]);

        // Previous period users (30-60 days ago)
        TestUser::create([
            'name' => 'Previous User 1',
            'email' => 'previous1@test.com',
            'created_at' => $now->copy()->subDays(40),
        ]);

        $metric = new UserGrowthMetric;
        $metric->userModel(TestUser::class);

        $request = new Request(['range' => 30]);
        $result = $metric->calculate($request);

        $this->assertInstanceOf(ValueResult::class, $result);
        $this->assertEquals(2, $result->getValue());
        $this->assertEquals(1, $result->getPrevious());
        $this->assertEquals(100.0, $result->getPercentageChange()); // 100% increase
        $this->assertEquals('increase', $result->getChangeDirection());
    }

    public function test_value_result_formatting(): void
    {
        $result = new ValueResult(1234.56);

        // Test basic formatting
        $this->assertEquals('1,235', $result->getFormattedValue());

        // Test with prefix
        $result->prefix('Total: ');
        $this->assertEquals('Total: 1,235', $result->getFormattedValue());

        // Test with suffix
        $result = new ValueResult(42);
        $result->suffix(' users');
        $this->assertEquals('42 users', $result->getFormattedValue());

        // Test with currency
        $result = new ValueResult(99.99);
        $result->currency('$');
        $this->assertEquals('$100', $result->getFormattedValue());
    }

    public function test_value_result_transformation(): void
    {
        $result = new ValueResult(1000);

        // Transform to thousands
        $result->transform(fn ($value) => $value / 1000);

        $this->assertEquals(1, $result->getValue());
        $this->assertEquals('1', $result->getFormattedValue());
    }

    public function test_value_result_percentage_change_calculation(): void
    {
        // Test increase
        $result = new ValueResult(150);
        $result->previous(100);

        $this->assertEquals(50.0, $result->getPercentageChange());
        $this->assertEquals('increase', $result->getChangeDirection());

        // Test decrease
        $result = new ValueResult(75);
        $result->previous(100);

        $this->assertEquals(-25.0, $result->getPercentageChange());
        $this->assertEquals('decrease', $result->getChangeDirection());

        // Test no change
        $result = new ValueResult(100);
        $result->previous(100);

        $this->assertEquals(0.0, $result->getPercentageChange());
        $this->assertEquals('increase', $result->getChangeDirection()); // 0% is considered increase
    }

    public function test_value_result_json_serialization(): void
    {
        $result = new ValueResult(42);
        $result->previous(30);

        $json = $result->jsonSerialize();

        $expectedKeys = [
            'value',
            'formatted_value',
            'has_no_data',
            'previous',
            'percentage_change',
            'change_direction',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $json);
        }

        $this->assertEquals(42, $json['value']);
        $this->assertEquals('42', $json['formatted_value']);
        $this->assertFalse($json['has_no_data']);
        $this->assertEquals(30, $json['previous']);
        $this->assertEquals(40.0, $json['percentage_change']);
        $this->assertEquals('increase', $json['change_direction']);
    }

    public function test_user_growth_metric_caching(): void
    {
        $metric = new UserGrowthMetric;
        $metric->userModel(TestUser::class);
        $metric->cacheForMinutes(10);

        $request = new Request(['range' => 30]);

        // First call should cache the result
        $result1 = $metric->calculate($request);

        // Second call should return cached result
        $result2 = $metric->calculate($request);

        $this->assertEquals($result1->getValue(), $result2->getValue());
        $this->assertEquals(10 * 60, $metric->cacheFor()); // 10 minutes in seconds
    }

    public function test_user_growth_metric_different_ranges(): void
    {
        $metric = new UserGrowthMetric;
        $metric->userModel(TestUser::class);

        $ranges = ['MTD', 'QTD', 'YTD', 30, 60, 90];

        foreach ($ranges as $range) {
            $request = new Request(['range' => $range]);
            $result = $metric->calculate($request);

            $this->assertInstanceOf(ValueResult::class, $result);
            $this->assertIsNumeric($result->getValue());
        }
    }

    public function test_user_growth_metric_authorization(): void
    {
        $metric = new UserGrowthMetric;
        $request = new Request;

        // Without authenticated user, should return false
        $this->assertFalse($metric->authorize($request));
    }

    public function test_user_growth_metric_meta_data(): void
    {
        $metric = new UserGrowthMetric;
        $meta = $metric->meta();

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('model', $meta);
        $this->assertArrayHasKey('cache_minutes', $meta);
        $this->assertArrayHasKey('help', $meta);
        $this->assertArrayHasKey('icon', $meta);
    }
}

/**
 * Test User Model for testing purposes.
 */
class TestUser extends Model
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
