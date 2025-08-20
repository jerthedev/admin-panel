<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use JTD\AdminPanel\Metrics\Progress;
use JTD\AdminPanel\Metrics\ProgressResult;
use JTD\AdminPanel\Metrics\SalesTargetProgressMetric;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Progress Metrics Tests.
 *
 * Comprehensive tests for Progress metrics including the SalesTargetProgressMetric example
 * and the base Progress class functionality. Tests cover target-based calculations,
 * progress bar data formatting, percentage calculations, and Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ProgressMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear metric caches
        Cache::flush();

        // Create test orders table
        Schema::create('progress_test_orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->decimal('total', 10, 2);
            $table->string('status')->default('completed');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('progress_test_orders');
        parent::tearDown();
    }

    public function test_sales_target_progress_metric_basic_functionality(): void
    {
        $metric = new SalesTargetProgressMetric;
        $request = new Request;

        // Test basic properties
        $this->assertEquals('Sales Target Progress', $metric->name);
        $this->assertEquals('sales-target-progress', $metric->uriKey());
        $this->assertEquals('chart-bar', $metric->icon());
        $this->assertIsArray($metric->ranges());
        $this->assertIsInt($metric->cacheFor());
    }

    public function test_sales_target_progress_metric_ranges(): void
    {
        $metric = new SalesTargetProgressMetric;
        $ranges = $metric->ranges();

        $expectedRanges = [
            7 => '7 Days',
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
        ];

        $this->assertEquals($expectedRanges, $ranges);
    }

    public function test_sales_target_progress_metric_calculation_with_no_orders(): void
    {
        $metric = new SalesTargetProgressMetric;
        $metric->orderModel(ProgressTestOrder::class);

        $request = new Request(['range' => 30]);
        $result = $metric->calculate($request);

        $this->assertInstanceOf(ProgressResult::class, $result);
        $this->assertEquals(0, $result->getValue());
        $this->assertEquals(50000.00, $result->getTarget()); // Default monthly target
        $this->assertEquals(0.0, $result->getPercentage());
        $this->assertFalse($result->isComplete());
    }

    public function test_sales_target_progress_metric_calculation_with_orders(): void
    {
        // Create test orders
        ProgressTestOrder::create([
            'customer_name' => 'Customer 1',
            'total' => 15000.00,
            'created_at' => now()->subDays(5),
        ]);

        ProgressTestOrder::create([
            'customer_name' => 'Customer 2',
            'total' => 10000.00,
            'created_at' => now()->subDays(10),
        ]);

        ProgressTestOrder::create([
            'customer_name' => 'Customer 3',
            'total' => 5000.00,
            'created_at' => now()->subDays(15),
        ]);

        $metric = new SalesTargetProgressMetric;
        $metric->orderModel(ProgressTestOrder::class);

        $request = new Request(['range' => 30]);
        $result = $metric->calculate($request);

        $this->assertInstanceOf(ProgressResult::class, $result);
        $this->assertEquals(30000.00, $result->getValue()); // Total sales
        $this->assertEquals(50000.00, $result->getTarget()); // Default monthly target
        $this->assertEquals(60.0, $result->getPercentage()); // 30000/50000 * 100
        $this->assertFalse($result->isComplete());
        $this->assertEquals(20000.00, $result->getRemaining()); // 50000 - 30000
    }

    public function test_progress_result_basic_functionality(): void
    {
        $result = new ProgressResult(750, 1000);

        // Test basic values
        $this->assertEquals(750, $result->getValue());
        $this->assertEquals(1000, $result->getTarget());
        $this->assertEquals(75.0, $result->getPercentage());
        $this->assertFalse($result->isComplete());
        $this->assertEquals(250, $result->getRemaining());
    }

    public function test_progress_result_completion_states(): void
    {
        // Test incomplete progress
        $incomplete = new ProgressResult(750, 1000);
        $this->assertFalse($incomplete->isComplete());
        $this->assertFalse($incomplete->exceedsTarget());

        // Test complete progress
        $complete = new ProgressResult(1000, 1000);
        $this->assertTrue($complete->isComplete());
        $this->assertFalse($complete->exceedsTarget());

        // Test exceeded progress
        $exceeded = new ProgressResult(1200, 1000);
        $this->assertTrue($exceeded->isComplete());
        $this->assertTrue($exceeded->exceedsTarget());
    }

    public function test_progress_result_formatting_options(): void
    {
        $result = new ProgressResult(1234.56, 2000.00);

        // Test with prefix
        $result->prefix('Sales: ');
        $this->assertEquals('Sales: 1,235', $result->getFormattedValue(1234.56));

        // Test with suffix
        $result = new ProgressResult(1234.56, 2000.00);
        $result->suffix(' revenue');
        $this->assertEquals('1,235 revenue', $result->getFormattedValue(1234.56));

        // Test with currency
        $result = new ProgressResult(1234.56, 2000.00);
        $result->currency('$');
        $this->assertEquals('$1,235', $result->getFormattedValue(1234.56));
    }

    public function test_progress_result_transformation(): void
    {
        $result = new ProgressResult(1000, 2000);

        // Transform to thousands
        $result->transform(fn ($value) => $value / 1000);

        $this->assertEquals(1, $result->getValue());
        $this->assertEquals(2, $result->getTarget());
        $this->assertEquals(50.0, $result->getPercentage());
    }

    public function test_progress_result_avoid_unwanted_progress(): void
    {
        $result = new ProgressResult(1200, 1000);

        // Without avoiding unwanted progress
        $this->assertEquals(120.0, $result->getPercentage());

        // With avoiding unwanted progress
        $result->avoidUnwantedProgress();
        $this->assertEquals(100.0, $result->getPercentage());
    }

    public function test_progress_result_color_calculation(): void
    {
        // Test different progress levels
        $low = new ProgressResult(25, 100);
        $this->assertEquals('#EF4444', $low->getProgressColor()); // Red

        $medium = new ProgressResult(60, 100);
        $this->assertEquals('#F59E0B', $medium->getProgressColor()); // Yellow

        $high = new ProgressResult(80, 100);
        $this->assertEquals('#3B82F6', $high->getProgressColor()); // Blue

        $complete = new ProgressResult(100, 100);
        $this->assertEquals('#10B981', $complete->getProgressColor()); // Green
    }

    public function test_progress_result_json_serialization(): void
    {
        $result = new ProgressResult(750, 1000);
        $result->currency('$');

        $json = $result->jsonSerialize();

        $expectedKeys = [
            'value',
            'target',
            'remaining',
            'formatted_value',
            'formatted_target',
            'formatted_remaining',
            'percentage',
            'is_complete',
            'exceeds_target',
            'progress_color',
            'has_no_data',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $json);
        }

        $this->assertEquals(750, $json['value']);
        $this->assertEquals(1000, $json['target']);
        $this->assertEquals(250, $json['remaining']);
        $this->assertEquals('$750', $json['formatted_value']);
        $this->assertEquals('$1,000', $json['formatted_target']);
        $this->assertEquals('$250', $json['formatted_remaining']);
        $this->assertEquals(75.0, $json['percentage']);
        $this->assertFalse($json['is_complete']);
        $this->assertFalse($json['exceeds_target']);
        $this->assertEquals('#3B82F6', $json['progress_color']); // Blue for 75%
        $this->assertFalse($json['has_no_data']);
    }

    public function test_sales_target_progress_metric_different_ranges(): void
    {
        // Create test orders
        ProgressTestOrder::create([
            'customer_name' => 'Customer 1',
            'total' => 10000.00,
            'created_at' => now()->subDays(3),
        ]);

        $metric = new SalesTargetProgressMetric;
        $metric->orderModel(ProgressTestOrder::class);

        // Test different ranges
        $ranges = [
            ['range' => 7, 'expectedTarget' => 11666.67], // Weekly pro-rated
            ['range' => 30, 'expectedTarget' => 50000.00], // Monthly
            ['range' => 'MTD', 'expectedTarget' => 50000.00], // Month to date
            ['range' => 'QTD', 'expectedTarget' => 150000.00], // Quarter to date
        ];

        foreach ($ranges as $rangeTest) {
            $request = new Request(['range' => $rangeTest['range']]);
            $result = $metric->calculate($request);

            $this->assertInstanceOf(ProgressResult::class, $result);
            $this->assertEquals(10000.00, $result->getValue());
            $this->assertEqualsWithDelta($rangeTest['expectedTarget'], $result->getTarget(), 0.01);
        }
    }

    public function test_sales_target_progress_metric_order_count_progress(): void
    {
        // Create test orders
        for ($i = 1; $i <= 25; $i++) {
            ProgressTestOrder::create([
                'customer_name' => "Customer {$i}",
                'total' => 100.00,
                'created_at' => now()->subDays(rand(1, 15)),
            ]);
        }

        $metric = new SalesTargetProgressMetric;
        $metric->orderModel(ProgressTestOrder::class);

        $request = new Request(['range' => 30]);
        $result = $metric->calculateOrderCountProgress($request);

        $this->assertInstanceOf(ProgressResult::class, $result);
        $this->assertEquals(25, $result->getValue()); // 25 orders
        $this->assertEquals(100, $result->getTarget()); // Monthly target
        $this->assertEquals(25.0, $result->getPercentage());
    }

    public function test_sales_target_progress_metric_average_order_value_progress(): void
    {
        // Create test orders with specific values for average calculation
        ProgressTestOrder::create([
            'customer_name' => 'Customer 1',
            'total' => 400.00,
            'created_at' => now()->subDays(5),
        ]);

        ProgressTestOrder::create([
            'customer_name' => 'Customer 2',
            'total' => 600.00,
            'created_at' => now()->subDays(10),
        ]);

        $metric = new SalesTargetProgressMetric;
        $metric->orderModel(ProgressTestOrder::class);

        $request = new Request(['range' => 30]);
        $result = $metric->calculateAverageOrderValueProgress($request);

        $this->assertInstanceOf(ProgressResult::class, $result);
        $this->assertEquals(500.00, $result->getValue()); // Average of 400 and 600
        $this->assertEquals(500.00, $result->getTarget()); // Target AOV
        $this->assertEquals(100.0, $result->getPercentage());
        $this->assertTrue($result->isComplete());
    }

    public function test_sales_target_progress_metric_caching(): void
    {
        $metric = new SalesTargetProgressMetric;
        $metric->orderModel(ProgressTestOrder::class);
        $metric->cacheForMinutes(20);

        $request = new Request(['range' => 30]);

        // First call should cache the result
        $result1 = $metric->calculate($request);

        // Second call should return cached result
        $result2 = $metric->calculate($request);

        $this->assertEquals($result1->getValue(), $result2->getValue());
        $this->assertEquals($result1->getTarget(), $result2->getTarget());
        $this->assertEquals(20 * 60, $metric->cacheFor()); // 20 minutes in seconds
    }

    public function test_sales_target_progress_metric_authorization(): void
    {
        $metric = new SalesTargetProgressMetric;
        $request = new Request;

        // Without authenticated user, should return false
        $this->assertFalse($metric->authorize($request));
    }

    public function test_sales_target_progress_metric_meta_data(): void
    {
        $metric = new SalesTargetProgressMetric;
        $meta = $metric->meta();

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('model', $meta);
        $this->assertArrayHasKey('monthly_target', $meta);
        $this->assertArrayHasKey('quarterly_target', $meta);
        $this->assertArrayHasKey('yearly_target', $meta);
        $this->assertArrayHasKey('cache_minutes', $meta);
        $this->assertArrayHasKey('help', $meta);
        $this->assertArrayHasKey('icon', $meta);
    }

    public function test_progress_result_no_data_state(): void
    {
        $result = new ProgressResult(null, 1000);
        $this->assertTrue($result->hasNoData());

        $result = new ProgressResult(500, null);
        $this->assertTrue($result->hasNoData());

        $result = new ProgressResult(500, 1000);
        $this->assertFalse($result->hasNoData());
    }

    public function test_progress_result_zero_target_handling(): void
    {
        $result = new ProgressResult(100, 0);
        $this->assertEquals(0.0, $result->getPercentage());
        $this->assertFalse($result->isComplete());
    }

    public function test_sales_target_progress_metric_custom_targets(): void
    {
        $metric = new SalesTargetProgressMetric;
        $metric->monthlyTarget(75000.00)
            ->quarterlyTarget(225000.00)
            ->yearlyTarget(900000.00);

        $meta = $metric->meta();
        $this->assertEquals(75000.00, $meta['monthly_target']);
        $this->assertEquals(225000.00, $meta['quarterly_target']);
        $this->assertEquals(900000.00, $meta['yearly_target']);
    }

    public function test_progress_result_custom_format(): void
    {
        $result = new ProgressResult(1234.567, 2000.000);

        // Test custom format with mantissa
        $result->format(['thousandSeparated' => true, 'mantissa' => 2]);
        $this->assertEquals('1,234.57', $result->getFormattedValue(1234.567));
    }
}

/**
 * Progress Test Order Model for testing purposes.
 */
class ProgressTestOrder extends Model
{
    protected $table = 'progress_test_orders';

    protected $fillable = [
        'customer_name',
        'total',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
