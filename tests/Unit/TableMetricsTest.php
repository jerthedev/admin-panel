<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use JTD\AdminPanel\Metrics\Table;
use JTD\AdminPanel\Metrics\TableResult;
use JTD\AdminPanel\Metrics\TopCustomersTableMetric;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Table Metrics Tests.
 *
 * Comprehensive tests for Table metrics including the TopCustomersTableMetric example
 * and the base Table class functionality. Tests cover tabular data formatting,
 * column definitions, actions, sorting, and Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TableMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear metric caches
        Cache::flush();

        // Create test customers table
        Schema::create('table_test_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });

        // Create test orders table
        Schema::create('table_test_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->decimal('total', 10, 2);
            $table->string('status')->default('completed');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('customer_id')->references('id')->on('table_test_customers');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('table_test_orders');
        Schema::dropIfExists('table_test_customers');
        parent::tearDown();
    }

    public function test_top_customers_table_metric_basic_functionality(): void
    {
        $metric = new TopCustomersTableMetric;
        $request = new Request;

        // Test basic properties
        $this->assertEquals('Top Customers', $metric->name);
        $this->assertEquals('top-customers', $metric->uriKey());
        $this->assertEquals('users', $metric->icon());
        $this->assertIsArray($metric->ranges());
        $this->assertIsInt($metric->cacheFor());
    }

    public function test_top_customers_table_metric_ranges(): void
    {
        $metric = new TopCustomersTableMetric;
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

    public function test_top_customers_table_metric_calculation_with_no_data(): void
    {
        $metric = new TopCustomersTableMetric;
        $metric->customerModel(TableTestCustomer::class)
            ->orderModel(TableTestOrder::class);

        $request = new Request(['range' => 30]);
        $result = $metric->calculate($request);

        $this->assertInstanceOf(TableResult::class, $result);
        $this->assertTrue($result->hasNoData());
        $this->assertEmpty($result->getData());
    }

    public function test_top_customers_table_metric_calculation_with_data(): void
    {
        // Create test customers
        $customer1 = TableTestCustomer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => now()->subDays(10),
        ]);

        $customer2 = TableTestCustomer::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'created_at' => now()->subDays(15),
        ]);

        // Create test orders
        TableTestOrder::create([
            'customer_id' => $customer1->id,
            'total' => 1500.00,
            'status' => 'completed',
            'created_at' => now()->subDays(5),
        ]);

        TableTestOrder::create([
            'customer_id' => $customer1->id,
            'total' => 800.00,
            'status' => 'completed',
            'created_at' => now()->subDays(8),
        ]);

        TableTestOrder::create([
            'customer_id' => $customer2->id,
            'total' => 1200.00,
            'status' => 'completed',
            'created_at' => now()->subDays(3),
        ]);

        $metric = new TopCustomersTableMetric;
        $metric->customerModel(TableTestCustomer::class)
            ->orderModel(TableTestOrder::class);

        $request = new Request(['range' => 30]);
        $result = $metric->calculate($request);

        $this->assertInstanceOf(TableResult::class, $result);
        $this->assertFalse($result->hasNoData());

        $data = $result->getData();
        $this->assertNotEmpty($data);

        // Should be sorted by revenue (John Doe has $2300, Jane Smith has $1200)
        $this->assertEquals('John Doe', $data[0]['name']);
        $this->assertEquals('Jane Smith', $data[1]['name']);
    }

    public function test_table_result_basic_functionality(): void
    {
        $data = [
            ['name' => 'John', 'email' => 'john@example.com', 'orders' => 5],
            ['name' => 'Jane', 'email' => 'jane@example.com', 'orders' => 3],
        ];

        $result = new TableResult($data);

        // Test basic data
        $this->assertEquals($data, $result->getData());
        $this->assertFalse($result->hasNoData());
    }

    public function test_table_result_column_configuration(): void
    {
        $result = new TableResult([]);

        $result->column('name', 'Customer Name', [
            'sortable' => true,
            'width' => '200px',
            'align' => 'left',
        ])
            ->column('email', 'Email Address', [
                'sortable' => false,
                'formatter' => fn ($value) => strtolower($value),
            ]);

        $columns = $result->getColumns();

        $this->assertArrayHasKey('name', $columns);
        $this->assertArrayHasKey('email', $columns);

        $this->assertEquals('Customer Name', $columns['name']['label']);
        $this->assertEquals('200px', $columns['name']['width']);
        $this->assertTrue($columns['name']['sortable']);

        $this->assertEquals('Email Address', $columns['email']['label']);
        $this->assertFalse($columns['email']['sortable']);
        $this->assertIsCallable($columns['email']['formatter']);
    }

    public function test_table_result_multiple_columns_configuration(): void
    {
        $result = new TableResult([]);

        $result->columns([
            'name' => 'Customer Name',
            'email' => [
                'label' => 'Email Address',
                'sortable' => false,
                'formatter' => fn ($value) => strtolower($value),
            ],
            'orders' => [
                'label' => 'Order Count',
                'align' => 'center',
                'width' => '100px',
            ],
        ]);

        $columns = $result->getColumns();

        $this->assertCount(3, $columns);
        $this->assertEquals('Customer Name', $columns['name']['label']);
        $this->assertEquals('Email Address', $columns['email']['label']);
        $this->assertEquals('Order Count', $columns['orders']['label']);
    }

    public function test_table_result_actions_configuration(): void
    {
        $result = new TableResult([]);

        $result->action('view', 'View Customer', [
            'icon' => 'eye',
            'color' => 'primary',
            'url' => '/customers/{id}',
            'target' => '_self',
        ])
            ->action('edit', 'Edit Customer', [
                'icon' => 'edit',
                'color' => 'secondary',
                'url' => '/customers/{id}/edit',
                'condition' => fn ($row) => $row['status'] === 'active',
            ]);

        $actions = $result->getActions();

        $this->assertCount(2, $actions);
        $this->assertArrayHasKey('view', $actions);
        $this->assertArrayHasKey('edit', $actions);

        $this->assertEquals('View Customer', $actions['view']['label']);
        $this->assertEquals('eye', $actions['view']['icon']);
        $this->assertEquals('/customers/{id}', $actions['view']['url']);

        $this->assertEquals('Edit Customer', $actions['edit']['label']);
        $this->assertIsCallable($actions['edit']['condition']);
    }

    public function test_table_result_formatted_data(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'JOHN@EXAMPLE.COM', 'revenue' => 1500.50],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'JANE@EXAMPLE.COM', 'revenue' => 2300.75],
        ];

        $result = new TableResult($data);
        $result->columns([
            'name' => 'Customer Name',
            'email' => [
                'label' => 'Email',
                'formatter' => fn ($value) => strtolower($value),
            ],
            'revenue' => [
                'label' => 'Revenue',
                'formatter' => fn ($value) => '$'.number_format($value, 2),
            ],
        ])
            ->action('view', 'View', [
                'url' => '/customers/{id}',
                'icon' => 'eye',
            ]);

        $formatted = $result->getFormattedData();

        $this->assertCount(2, $formatted);

        // Check first row
        $this->assertEquals('John Doe', $formatted[0]['name']);
        $this->assertEquals('john@example.com', $formatted[0]['email']); // Formatted
        $this->assertEquals('$1,500.50', $formatted[0]['revenue']); // Formatted
        $this->assertArrayHasKey('_actions', $formatted[0]);
        $this->assertArrayHasKey('_row_id', $formatted[0]);

        // Check actions
        $actions = $formatted[0]['_actions'];
        $this->assertCount(1, $actions);
        $this->assertEquals('/customers/1', $actions[0]['url']); // URL with ID replaced
    }

    public function test_table_result_sorting_configuration(): void
    {
        $result = new TableResult([]);

        $result->sortBy('revenue', 'desc');

        $json = $result->jsonSerialize();
        $this->assertEquals('revenue', $json['default_sort']);
        $this->assertEquals('desc', $json['default_sort_direction']);
        $this->assertTrue($json['sortable']);

        // Test disabling sorting
        $result->withoutSorting();
        $json = $result->jsonSerialize();
        $this->assertFalse($json['sortable']);
    }

    public function test_table_result_json_serialization(): void
    {
        $data = [
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com'],
        ];

        $result = new TableResult($data);
        $result->columns([
            'name' => 'Name',
            'email' => 'Email',
        ])
            ->action('view', 'View', ['url' => '/users/{id}'])
            ->sortBy('name', 'asc')
            ->emptyText('No customers found');

        $json = $result->jsonSerialize();

        $expectedKeys = [
            'data',
            'columns',
            'actions',
            'empty_text',
            'sortable',
            'default_sort',
            'default_sort_direction',
            'has_no_data',
            'total_rows',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $json);
        }

        $this->assertCount(2, $json['data']);
        $this->assertCount(2, $json['columns']);
        $this->assertCount(1, $json['actions']);
        $this->assertEquals('No customers found', $json['empty_text']);
        $this->assertTrue($json['sortable']);
        $this->assertEquals('name', $json['default_sort']);
        $this->assertEquals('asc', $json['default_sort_direction']);
        $this->assertFalse($json['has_no_data']);
        $this->assertEquals(2, $json['total_rows']);
    }

    public function test_top_customers_table_metric_recent_customers(): void
    {
        // Create test customers
        TableTestCustomer::create([
            'name' => 'Recent Customer',
            'email' => 'recent@example.com',
            'created_at' => now()->subHours(2),
        ]);

        TableTestCustomer::create([
            'name' => 'Old Customer',
            'email' => 'old@example.com',
            'created_at' => now()->subDays(5),
        ]);

        $metric = new TopCustomersTableMetric;
        $metric->customerModel(TableTestCustomer::class);

        $request = new Request(['range' => 7, 'limit' => 5]);
        $result = $metric->getRecentCustomers($request);

        $this->assertInstanceOf(TableResult::class, $result);
        $this->assertFalse($result->hasNoData());

        $data = $result->getData();
        $this->assertCount(2, $data);

        // Should be sorted by created_at desc (most recent first)
        $this->assertEquals('Recent Customer', $data[0]['name']);
        $this->assertEquals('Old Customer', $data[1]['name']);
    }

    public function test_top_customers_table_metric_activity_summary(): void
    {
        // Create test customer
        $customer = TableTestCustomer::create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'created_at' => now()->subDays(10),
        ]);

        // Create orders with different statuses
        TableTestOrder::create([
            'customer_id' => $customer->id,
            'total' => 100.00,
            'status' => 'completed',
            'created_at' => now()->subDays(5),
        ]);

        TableTestOrder::create([
            'customer_id' => $customer->id,
            'total' => 200.00,
            'status' => 'completed',
            'created_at' => now()->subDays(3),
        ]);

        TableTestOrder::create([
            'customer_id' => $customer->id,
            'total' => 150.00,
            'status' => 'pending',
            'created_at' => now()->subDays(1),
        ]);

        $metric = new TopCustomersTableMetric;
        $metric->orderModel(TableTestOrder::class);

        $request = new Request(['range' => 30]);
        $result = $metric->getCustomerActivitySummary($request);

        $this->assertInstanceOf(TableResult::class, $result);
        $this->assertFalse($result->hasNoData());

        $data = $result->getData();
        $this->assertNotEmpty($data);

        // Should have aggregated data by status
        $completedRow = collect($data)->firstWhere('group', 'completed');
        $pendingRow = collect($data)->firstWhere('group', 'pending');

        $this->assertNotNull($completedRow);
        $this->assertNotNull($pendingRow);
        $this->assertEquals(2, $completedRow['value']); // 2 completed orders
        $this->assertEquals(1, $pendingRow['value']); // 1 pending order
    }

    public function test_top_customers_table_metric_caching(): void
    {
        $metric = new TopCustomersTableMetric;
        $metric->customerModel(TableTestCustomer::class)
            ->orderModel(TableTestOrder::class)
            ->cacheForMinutes(25);

        $request = new Request(['range' => 30]);

        // First call should cache the result
        $result1 = $metric->calculate($request);

        // Second call should return cached result
        $result2 = $metric->calculate($request);

        $this->assertEquals($result1->getData(), $result2->getData());
        $this->assertEquals(25 * 60, $metric->cacheFor()); // 25 minutes in seconds
    }

    public function test_top_customers_table_metric_authorization(): void
    {
        $metric = new TopCustomersTableMetric;
        $request = new Request;

        // Without authenticated user, should return false
        $this->assertFalse($metric->authorize($request));
    }

    public function test_top_customers_table_metric_meta_data(): void
    {
        $metric = new TopCustomersTableMetric;
        $meta = $metric->meta();

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('customer_model', $meta);
        $this->assertArrayHasKey('order_model', $meta);
        $this->assertArrayHasKey('default_limit', $meta);
        $this->assertArrayHasKey('cache_minutes', $meta);
        $this->assertArrayHasKey('help', $meta);
        $this->assertArrayHasKey('icon', $meta);
    }

    public function test_table_result_empty_state(): void
    {
        $result = new TableResult([]);

        $this->assertTrue($result->hasNoData());
        $this->assertEmpty($result->getData());

        $json = $result->jsonSerialize();
        $this->assertTrue($json['has_no_data']);
        $this->assertEquals(0, $json['total_rows']);
    }

    public function test_table_result_action_conditions(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Active User', 'status' => 'active'],
            ['id' => 2, 'name' => 'Inactive User', 'status' => 'inactive'],
        ];

        $result = new TableResult($data);
        $result->action('edit', 'Edit', [
            'url' => '/users/{id}/edit',
            'condition' => fn ($row) => $row['status'] === 'active',
        ]);

        $formatted = $result->getFormattedData();

        // Active user should have the edit action
        $this->assertCount(1, $formatted[0]['_actions']);
        $this->assertEquals('Edit', $formatted[0]['_actions'][0]['label']);

        // Inactive user should not have the edit action
        $this->assertCount(0, $formatted[1]['_actions']);
    }

    public function test_top_customers_table_metric_custom_configuration(): void
    {
        $metric = new TopCustomersTableMetric;
        $metric->customerModel('App\Models\CustomCustomer')
            ->orderModel('App\Models\CustomOrder')
            ->defaultLimit(20);

        $meta = $metric->meta();
        $this->assertEquals('App\Models\CustomCustomer', $meta['customer_model']);
        $this->assertEquals('App\Models\CustomOrder', $meta['order_model']);
        $this->assertEquals(20, $meta['default_limit']);
    }
}

/**
 * Table Test Customer Model for testing purposes.
 */
class TableTestCustomer extends Model
{
    protected $table = 'table_test_customers';

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

    public function orders()
    {
        return $this->hasMany(TableTestOrder::class, 'customer_id');
    }
}

/**
 * Table Test Order Model for testing purposes.
 */
class TableTestOrder extends Model
{
    protected $table = 'table_test_orders';

    protected $fillable = [
        'customer_id',
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

    public function customer()
    {
        return $this->belongsTo(TableTestCustomer::class, 'customer_id');
    }
}
