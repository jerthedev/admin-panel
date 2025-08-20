# Table Metrics

Table metrics display tabular data with custom columns, actions, and formatting. They are perfect for showing lists of records, detailed data views, and interactive data tables with sorting and actions.

## Overview

Table metrics in AdminPanel are 100% compatible with Laravel Nova's Table metrics, providing:

- **Tabular Data Display**: Show data in structured table format
- **Custom Column Definitions**: Define columns with labels, formatting, and sorting
- **Row Actions**: Add clickable actions for each row (view, edit, delete, etc.)
- **Custom Formatting**: Format values with closures, currency, dates, etc.
- **Sorting Support**: Enable sorting on specific columns
- **Icons and Links**: Add icons and external links to actions
- **Conditional Actions**: Show/hide actions based on row data
- **Empty State Handling**: Custom messages when no data is available
- **Caching Support**: Performance optimization with configurable cache duration

## Basic Usage

### Creating a Table Metric

```php
<?php

namespace App\Metrics;

use Illuminate\Http\Request;
use JTD\AdminPanel\Metrics\Table;
use JTD\AdminPanel\Metrics\TableResult;

class TopCustomersTableMetric extends Table
{
    /**
     * The metric's display name.
     */
    public string $name = 'Top Customers';

    /**
     * Calculate the table data for the metric.
     */
    public function calculate(Request $request): TableResult
    {
        return $this->recentRecords($request, Customer::class, 10, [
            'name' => 'name',
            'email' => 'email',
            'created_at' => 'created_at',
        ])
            ->columns([
                'name' => 'Customer Name',
                'email' => 'Email Address',
                'created_at' => [
                    'label' => 'Joined',
                    'formatter' => fn($value) => $value->format('M j, Y'),
                ],
            ])
            ->action('view', 'View Customer', [
                'icon' => 'eye',
                'url' => '/admin/customers/{id}',
            ]);
    }

    /**
     * Get the ranges available for this metric.
     */
    public function ranges(): array
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
        ];
    }
}
```

## Data Source Methods

### Recent Records

```php
public function calculate(Request $request): TableResult
{
    return $this->recentRecords($request, User::class, 10, [
        'name' => 'name',
        'email' => 'email',
        'created_at' => 'created_at',
    ]);
}
```

### Custom Query

```php
public function calculate(Request $request): TableResult
{
    return $this->fromCustomQuery($request, function ($request, $range, $timezone) {
        return User::where('status', 'active')
            ->with('orders')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    });
}
```

### Aggregation Data

```php
public function calculate(Request $request): TableResult
{
    return $this->fromAggregation($request, Order::class, 'status', 'count')
        ->columns([
            'group' => 'Status',
            'value' => 'Count',
        ]);
}
```

### Manual Data

```php
public function calculate(Request $request): TableResult
{
    $data = [
        ['name' => 'John Doe', 'orders' => 15, 'revenue' => 2500.00],
        ['name' => 'Jane Smith', 'orders' => 8, 'revenue' => 1200.00],
    ];

    return $this->fromData($data);
}
```

## Column Configuration

### Basic Columns

```php
->columns([
    'name' => 'Customer Name',
    'email' => 'Email Address',
    'created_at' => 'Join Date',
])
```

### Advanced Column Configuration

```php
->columns([
    'name' => [
        'label' => 'Customer Name',
        'sortable' => true,
        'width' => '200px',
        'align' => 'left',
    ],
    'email' => [
        'label' => 'Email',
        'sortable' => true,
        'formatter' => fn($value) => strtolower($value),
    ],
    'revenue' => [
        'label' => 'Total Revenue',
        'sortable' => true,
        'align' => 'right',
        'width' => '150px',
        'formatter' => fn($value) => '$' . number_format($value, 2),
    ],
    'status' => [
        'label' => 'Status',
        'formatter' => fn($value) => ucfirst($value),
        'align' => 'center',
    ],
])
```

### Individual Column Configuration

```php
->column('name', 'Customer Name', [
    'sortable' => true,
    'width' => '200px',
])
->column('email', 'Email', [
    'formatter' => fn($value) => strtolower($value),
])
```

## Actions Configuration

### Basic Actions

```php
->action('view', 'View Customer', [
    'icon' => 'eye',
    'url' => '/admin/customers/{id}',
])
->action('edit', 'Edit Customer', [
    'icon' => 'edit',
    'url' => '/admin/customers/{id}/edit',
])
```

### Advanced Actions

```php
->action('view', 'View Details', [
    'icon' => 'eye',
    'color' => 'primary',
    'url' => '/admin/customers/{id}',
    'target' => '_self',
])
->action('email', 'Send Email', [
    'icon' => 'mail',
    'color' => 'success',
    'url' => 'mailto:{email}',
    'target' => '_blank',
    'condition' => fn($row) => !empty($row['email']),
])
->action('delete', 'Delete Customer', [
    'icon' => 'trash',
    'color' => 'danger',
    'url' => '/admin/customers/{id}/delete',
    'condition' => fn($row) => $row['status'] !== 'active',
])
```

### Action Colors

Available action colors:
- `primary` - Blue
- `secondary` - Gray
- `success` - Green
- `danger` - Red
- `warning` - Yellow
- `info` - Light blue

## Formatting Options

### Date Formatting

```php
'created_at' => [
    'label' => 'Created',
    'formatter' => fn($value) => $value->format('M j, Y H:i'),
]
```

### Currency Formatting

```php
'revenue' => [
    'label' => 'Revenue',
    'formatter' => fn($value) => '$' . number_format($value, 2),
]
```

### Text Truncation

```php
'description' => [
    'label' => 'Description',
    'formatter' => fn($value) => Str::limit($value, 50),
]
```

### Status Badges

```php
'status' => [
    'label' => 'Status',
    'formatter' => function($value) {
        $colors = [
            'active' => 'success',
            'inactive' => 'secondary',
            'pending' => 'warning',
        ];
        
        return "<span class=\"badge badge-{$colors[$value]}\">" . ucfirst($value) . "</span>";
    },
]
```

### Custom Calculations

```php
'profit_margin' => [
    'label' => 'Profit Margin',
    'formatter' => function($value, $row) {
        $margin = ($row['revenue'] - $row['cost']) / $row['revenue'] * 100;
        return number_format($margin, 1) . '%';
    },
]
```

## Sorting Configuration

### Default Sorting

```php
->sortBy('created_at', 'desc')
```

### Disable Sorting

```php
->withoutSorting()
```

### Column-Specific Sorting

```php
->columns([
    'name' => [
        'label' => 'Name',
        'sortable' => true,
    ],
    'email' => [
        'label' => 'Email',
        'sortable' => false, // Disable sorting for this column
    ],
])
```

## Advanced Features

### Complex Custom Query

```php
public function calculate(Request $request): TableResult
{
    return $this->fromCustomQuery($request, function ($request, $range, $timezone) {
        $customerTable = (new Customer)->getTable();
        $orderTable = (new Order)->getTable();
        
        return Customer::select([
                $customerTable . '.id',
                $customerTable . '.name',
                $customerTable . '.email',
                $customerTable . '.created_at',
            ])
            ->selectRaw("COUNT({$orderTable}.id) as order_count")
            ->selectRaw("COALESCE(SUM({$orderTable}.total), 0) as total_revenue")
            ->leftJoin($orderTable, $customerTable . '.id', '=', $orderTable . '.customer_id')
            ->whereBetween($orderTable . '.created_at', [$start, $end])
            ->groupBy($customerTable . '.id', $customerTable . '.name', $customerTable . '.email', $customerTable . '.created_at')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();
    });
}
```

### Multiple Calculation Methods

```php
class CustomerMetrics extends Table
{
    public function getTopCustomers(Request $request): TableResult
    {
        return $this->fromCustomQuery($request, function ($request, $range, $timezone) {
            // Complex query for top customers by revenue
        });
    }

    public function getRecentCustomers(Request $request): TableResult
    {
        return $this->recentRecords($request, Customer::class, 10, [
            'name' => 'name',
            'email' => 'email',
            'created_at' => 'created_at',
        ]);
    }

    public function getCustomerActivity(Request $request): TableResult
    {
        return $this->fromAggregation($request, Order::class, 'status', 'count');
    }
}
```

### Caching

```php
public function calculate(Request $request): TableResult
{
    return Cache::remember(
        $this->getCacheKey($request),
        $this->cacheFor(),
        fn() => $this->recentRecords($request, Customer::class, 10, ['name' => 'name'])
    );
}

public function cacheFor(): int
{
    return 900; // 15 minutes
}
```

## Configuration Options

### Available Ranges

```php
public function ranges(): array
{
    return [
        7 => '7 Days',
        30 => '30 Days',
        60 => '60 Days',
        90 => '90 Days',
        'MTD' => 'Month To Date',
        'QTD' => 'Quarter To Date',
        'YTD' => 'Year To Date',
    ];
}
```

### Authorization

```php
public function authorize(Request $request): bool
{
    return $request->user()->can('view-customers');
}
```

### URI Key

```php
public function uriKey(): string
{
    return 'top-customers';
}
```

### Help Text

```php
public function help(): ?string
{
    return 'Shows the top customers by revenue with detailed information.';
}
```

### Empty State

```php
->emptyText('No customers found for the selected period')
```

## Table Data Format

The TableResult automatically formats data for frontend consumption:

```json
{
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "revenue": "$2,500.00",
            "_row_id": "1",
            "_actions": [
                {
                    "key": "view",
                    "label": "View Customer",
                    "icon": "eye",
                    "color": "primary",
                    "url": "/admin/customers/1",
                    "target": "_self"
                }
            ]
        }
    ],
    "columns": {
        "name": {
            "label": "Customer Name",
            "sortable": true,
            "width": "200px",
            "align": "left"
        },
        "email": {
            "label": "Email",
            "sortable": true
        }
    },
    "actions": {
        "view": {
            "label": "View Customer",
            "icon": "eye",
            "color": "primary"
        }
    },
    "empty_text": "No customers found",
    "sortable": true,
    "default_sort": "created_at",
    "default_sort_direction": "desc",
    "has_no_data": false,
    "total_rows": 25
}
```

## Example Implementation

Here's a complete example of the TopCustomersTableMetric:

```php
<?php

namespace App\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Metrics\Table;
use JTD\AdminPanel\Metrics\TableResult;

class TopCustomersTableMetric extends Table
{
    public string $name = 'Top Customers';
    
    protected string $customerModel = 'App\Models\Customer';
    protected string $orderModel = 'App\Models\Order';
    protected int $cacheMinutes = 15;

    public function calculate(Request $request): TableResult
    {
        $cacheKey = $this->getCacheKey($request);
        
        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            return $this->getTopCustomersByRevenue($request);
        });
    }

    public function getTopCustomersByRevenue(Request $request): TableResult
    {
        $limit = $request->get('limit', 10);
        
        return $this->fromCustomQuery($request, function ($request, $range, $timezone) use ($limit) {
            $customerTable = (new ($this->customerModel))->getTable();
            $orderTable = (new ($this->orderModel))->getTable();
            
            return $this->buildQuery($this->customerModel)
                ->select([
                    $customerTable . '.id',
                    $customerTable . '.name',
                    $customerTable . '.email',
                    $customerTable . '.created_at',
                ])
                ->selectRaw("COUNT({$orderTable}.id) as order_count")
                ->selectRaw("COALESCE(SUM({$orderTable}.total), 0) as total_revenue")
                ->leftJoin($orderTable, $customerTable . '.id', '=', $orderTable . '.customer_id')
                ->whereBetween($orderTable . '.created_at', [$start, $end])
                ->groupBy($customerTable . '.id', $customerTable . '.name', $customerTable . '.email', $customerTable . '.created_at')
                ->orderByDesc('total_revenue')
                ->limit($limit)
                ->get();
        })
            ->columns([
                'name' => [
                    'label' => 'Customer Name',
                    'sortable' => true,
                    'width' => '200px',
                ],
                'email' => [
                    'label' => 'Email',
                    'sortable' => true,
                ],
                'order_count' => [
                    'label' => 'Orders',
                    'sortable' => true,
                    'align' => 'center',
                    'formatter' => fn($value) => number_format($value),
                ],
                'total_revenue' => [
                    'label' => 'Revenue',
                    'sortable' => true,
                    'align' => 'right',
                    'formatter' => fn($value) => '$' . number_format($value, 2),
                ],
            ])
            ->action('view', 'View Customer', [
                'icon' => 'eye',
                'color' => 'primary',
                'url' => '/admin/customers/{id}',
            ])
            ->action('email', 'Send Email', [
                'icon' => 'mail',
                'color' => 'success',
                'url' => 'mailto:{email}',
                'target' => '_blank',
                'condition' => fn($row) => !empty($row['email']),
            ])
            ->sortBy('total_revenue', 'desc')
            ->emptyText('No customers found for the selected period');
    }

    public function ranges(): array
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
        ];
    }

    public function uriKey(): string
    {
        return 'top-customers';
    }

    public function authorize(Request $request): bool
    {
        return $request->user()?->can('viewAny', $this->customerModel) ?? false;
    }

    public function help(): ?string
    {
        return 'Shows the top customers by revenue with detailed information and actions.';
    }
}
```

This implementation provides a complete, production-ready Table metric with custom queries, column formatting, actions, caching, authorization, and comprehensive configuration options.
