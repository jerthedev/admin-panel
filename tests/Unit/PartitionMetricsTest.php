<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use JTD\AdminPanel\Metrics\Partition;
use JTD\AdminPanel\Metrics\PartitionResult;
use JTD\AdminPanel\Metrics\UserStatusPartitionMetric;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Partition Metrics Tests.
 *
 * Comprehensive tests for Partition metrics including the UserStatusPartitionMetric example
 * and the base Partition class functionality. Tests cover categorical aggregation,
 * pie chart data formatting, custom labels/colors, and Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class PartitionMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear metric caches
        Cache::flush();

        // Create test users table
        Schema::create('partition_test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('status')->default('active');
            $table->decimal('score', 8, 2)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('partition_test_users');
        parent::tearDown();
    }

    public function test_user_status_partition_metric_basic_functionality(): void
    {
        $metric = new UserStatusPartitionMetric;
        $request = new Request;

        // Test basic properties
        $this->assertEquals('User Status Distribution', $metric->name);
        $this->assertEquals('user-status-distribution', $metric->uriKey());
        $this->assertEquals('chart-pie', $metric->icon());
        $this->assertIsArray($metric->ranges());
        $this->assertIsInt($metric->cacheFor());
    }

    public function test_user_status_partition_metric_ranges(): void
    {
        $metric = new UserStatusPartitionMetric;
        $ranges = $metric->ranges();

        $expectedRanges = [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            365 => '1 Year',
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
            'ALL' => 'All Time',
        ];

        $this->assertEquals($expectedRanges, $ranges);
    }

    public function test_user_status_partition_metric_calculation_with_no_users(): void
    {
        $metric = new UserStatusPartitionMetric;
        $metric->userModel(PartitionTestUser::class);

        $request = new Request(['range' => 30]);
        $result = $metric->calculate($request);

        $this->assertInstanceOf(PartitionResult::class, $result);
        $this->assertTrue($result->hasNoData());
        $this->assertEmpty($result->getPartitions());
    }

    public function test_user_status_partition_metric_calculation_with_users(): void
    {
        // Create test users with different statuses
        PartitionTestUser::create([
            'name' => 'Active User 1',
            'email' => 'active1@test.com',
            'status' => 'active',
            'created_at' => now()->subDays(5),
        ]);

        PartitionTestUser::create([
            'name' => 'Active User 2',
            'email' => 'active2@test.com',
            'status' => 'active',
            'created_at' => now()->subDays(10),
        ]);

        PartitionTestUser::create([
            'name' => 'Inactive User',
            'email' => 'inactive@test.com',
            'status' => 'inactive',
            'created_at' => now()->subDays(15),
        ]);

        PartitionTestUser::create([
            'name' => 'Pending User',
            'email' => 'pending@test.com',
            'status' => 'pending',
            'created_at' => now()->subDays(3),
        ]);

        $metric = new UserStatusPartitionMetric;
        $metric->userModel(PartitionTestUser::class);

        $request = new Request(['range' => 30]);
        $result = $metric->calculate($request);

        $this->assertInstanceOf(PartitionResult::class, $result);
        $this->assertFalse($result->hasNoData());

        $partitions = $result->getPartitions();
        $this->assertIsArray($partitions);
        $this->assertNotEmpty($partitions);

        // Should have data for different statuses
        $this->assertEquals(2, $partitions['active']); // 2 active users
        $this->assertEquals(1, $partitions['inactive']); // 1 inactive user
        $this->assertEquals(1, $partitions['pending']); // 1 pending user

        // Total should be 4
        $this->assertEquals(4, $result->getTotal());
    }

    public function test_partition_result_basic_functionality(): void
    {
        $partitionData = [
            'active' => 10,
            'inactive' => 5,
            'pending' => 3,
        ];

        $result = new PartitionResult($partitionData);

        // Test basic partition data
        $this->assertEquals($partitionData, $result->getPartitions());
        $this->assertFalse($result->hasNoData());

        // Test total calculation
        $this->assertEquals(18, $result->getTotal());
    }

    public function test_partition_result_custom_labels(): void
    {
        $partitionData = [
            'active' => 10,
            'inactive' => 5,
        ];

        $result = new PartitionResult($partitionData);
        $result->labels([
            'active' => 'Active Users',
            'inactive' => 'Inactive Users',
        ]);

        $chartData = $result->getChartData();

        $this->assertEquals('Active Users', $chartData[0]['label']);
        $this->assertEquals('Inactive Users', $chartData[1]['label']);
    }

    public function test_partition_result_custom_colors(): void
    {
        $partitionData = [
            'active' => 10,
            'inactive' => 5,
        ];

        $result = new PartitionResult($partitionData);
        $result->colors([
            'active' => '#10B981',
            'inactive' => '#6B7280',
        ]);

        $chartData = $result->getChartData();

        $this->assertEquals('#10B981', $chartData[0]['color']);
        $this->assertEquals('#6B7280', $chartData[1]['color']);
    }

    public function test_partition_result_formatting_options(): void
    {
        $partitionData = [
            'category1' => 1234.56,
            'category2' => 2345.67,
        ];

        $result = new PartitionResult($partitionData);

        // Test with prefix
        $result->prefix('Count: ');
        $this->assertEquals('Count: 1,235', $result->getFormattedValue(1234.56));

        // Test with suffix
        $result = new PartitionResult($partitionData);
        $result->suffix(' items');
        $this->assertEquals('1,235 items', $result->getFormattedValue(1234.56));

        // Test with currency
        $result = new PartitionResult($partitionData);
        $result->currency('$');
        $this->assertEquals('$1,235', $result->getFormattedValue(1234.56));
    }

    public function test_partition_result_transformation(): void
    {
        $partitionData = [
            'category1' => 1000,
            'category2' => 2000,
        ];

        $result = new PartitionResult($partitionData);

        // Transform to thousands
        $result->transform(fn ($value) => $value / 1000);

        $transformed = $result->getPartitions();
        $this->assertEquals(1, $transformed['category1']);
        $this->assertEquals(2, $transformed['category2']);
    }

    public function test_partition_result_chart_data(): void
    {
        $partitionData = [
            'active' => 10,
            'inactive' => 5,
        ];

        $result = new PartitionResult($partitionData);
        $result->labels(['active' => 'Active Users', 'inactive' => 'Inactive Users'])
            ->colors(['active' => '#10B981', 'inactive' => '#6B7280'])
            ->suffix(' users');

        $chartData = $result->getChartData();

        $this->assertIsArray($chartData);
        $this->assertCount(2, $chartData);

        // Check first item
        $this->assertEquals([
            'key' => 'active',
            'label' => 'Active Users',
            'value' => 10,
            'formatted_value' => '10 users',
            'percentage' => 66.7, // 10/15 * 100
            'color' => '#10B981',
        ], $chartData[0]);

        // Check second item
        $this->assertEquals([
            'key' => 'inactive',
            'label' => 'Inactive Users',
            'value' => 5,
            'formatted_value' => '5 users',
            'percentage' => 33.3, // 5/15 * 100
            'color' => '#6B7280',
        ], $chartData[1]);
    }

    public function test_partition_result_json_serialization(): void
    {
        $partitionData = [
            'active' => 10,
            'inactive' => 5,
        ];

        $result = new PartitionResult($partitionData);
        $result->suffix(' users');

        $json = $result->jsonSerialize();

        $expectedKeys = [
            'partitions',
            'chart_data',
            'total',
            'formatted_total',
            'has_no_data',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $json);
        }

        $this->assertEquals($partitionData, $json['partitions']);
        $this->assertFalse($json['has_no_data']);
        $this->assertEquals(15, $json['total']);
        $this->assertEquals('15 users', $json['formatted_total']);
    }

    public function test_user_status_partition_metric_custom_grouping(): void
    {
        // Create test users with different activity levels
        PartitionTestUser::create([
            'name' => 'Recent User',
            'email' => 'recent@test.com',
            'status' => 'active',
            'last_login_at' => now(),
            'created_at' => now()->subDays(1),
        ]);

        PartitionTestUser::create([
            'name' => 'Old User',
            'email' => 'old@test.com',
            'status' => 'active',
            'last_login_at' => now()->subDays(60),
            'created_at' => now()->subDays(100),
        ]);

        $metric = new UserStatusPartitionMetric;
        $metric->userModel(PartitionTestUser::class);

        $request = new Request(['range' => 'ALL']);
        $result = $metric->calculateByActivityLevel($request);

        $this->assertInstanceOf(PartitionResult::class, $result);
        $this->assertFalse($result->hasNoData());

        $partitions = $result->getPartitions();
        $this->assertIsArray($partitions);
        $this->assertNotEmpty($partitions);
    }

    public function test_user_status_partition_metric_caching(): void
    {
        $metric = new UserStatusPartitionMetric;
        $metric->userModel(PartitionTestUser::class);
        $metric->cacheForMinutes(20);

        $request = new Request(['range' => 30]);

        // First call should cache the result
        $result1 = $metric->calculate($request);

        // Second call should return cached result
        $result2 = $metric->calculate($request);

        $this->assertEquals($result1->getPartitions(), $result2->getPartitions());
        $this->assertEquals(20 * 60, $metric->cacheFor()); // 20 minutes in seconds
    }

    public function test_user_status_partition_metric_authorization(): void
    {
        $metric = new UserStatusPartitionMetric;
        $request = new Request;

        // Without authenticated user, should return false
        $this->assertFalse($metric->authorize($request));
    }

    public function test_user_status_partition_metric_meta_data(): void
    {
        $metric = new UserStatusPartitionMetric;
        $meta = $metric->meta();

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('model', $meta);
        $this->assertArrayHasKey('status_column', $meta);
        $this->assertArrayHasKey('cache_minutes', $meta);
        $this->assertArrayHasKey('help', $meta);
        $this->assertArrayHasKey('icon', $meta);
    }

    public function test_partition_result_default_colors(): void
    {
        $partitionData = [
            'cat1' => 10,
            'cat2' => 5,
            'cat3' => 3,
        ];

        $result = new PartitionResult($partitionData);
        $chartData = $result->getChartData();

        // Should use default colors when no custom colors are set
        $this->assertEquals('#3B82F6', $chartData[0]['color']); // Blue
        $this->assertEquals('#10B981', $chartData[1]['color']); // Green
        $this->assertEquals('#F59E0B', $chartData[2]['color']); // Yellow
    }

    public function test_partition_result_empty_data(): void
    {
        $result = new PartitionResult([]);

        $this->assertTrue($result->hasNoData());
        $this->assertEquals(0, $result->getTotal());
        $this->assertEmpty($result->getChartData());
    }

    public function test_user_status_partition_metric_all_time_range(): void
    {
        // Create users with old dates
        PartitionTestUser::create([
            'name' => 'Very Old User',
            'email' => 'veryold@test.com',
            'status' => 'active',
            'created_at' => now()->subYears(2),
        ]);

        $metric = new UserStatusPartitionMetric;
        $metric->userModel(PartitionTestUser::class);

        $request = new Request(['range' => 'ALL']);
        $result = $metric->calculate($request);

        $this->assertInstanceOf(PartitionResult::class, $result);
        $this->assertFalse($result->hasNoData());

        // Should include the very old user when using ALL range
        $partitions = $result->getPartitions();
        $this->assertArrayHasKey('active', $partitions);
        $this->assertEquals(1, $partitions['active']);
    }
}

/**
 * Partition Test User Model for testing purposes.
 */
class PartitionTestUser extends Model
{
    protected $table = 'partition_test_users';

    protected $fillable = [
        'name',
        'email',
        'status',
        'score',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
