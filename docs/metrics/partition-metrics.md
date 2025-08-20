# Partition Metrics

Partition metrics display categorical data as pie charts, showing how data is distributed across different categories or segments. They are perfect for visualizing proportions, breakdowns, and categorical analysis of your data.

## Overview

Partition metrics in AdminPanel are 100% compatible with Laravel Nova's Partition metrics, providing:

- **Categorical Data Visualization**: Display data distribution with pie charts
- **Custom Labels**: Friendly names for categories with closure support
- **Custom Colors**: Configurable colors for pie chart segments
- **Rich Aggregation Methods**: Count, sum, average, max, min operations
- **Flexible Grouping**: Column-based and custom grouping logic
- **Range-Based Grouping**: Date ranges and numeric ranges
- **Manual Result Building**: Direct partition data construction
- **Rich Formatting**: Prefix, suffix, currency, and custom transformations
- **Percentage Calculations**: Automatic percentage calculation for segments
- **Caching Support**: Performance optimization with configurable cache duration

## Basic Usage

### Creating a Partition Metric

```php
<?php

namespace App\Metrics;

use Illuminate\Http\Request;
use JTD\AdminPanel\Metrics\Partition;
use JTD\AdminPanel\Metrics\PartitionResult;

class UserStatusPartitionMetric extends Partition
{
    /**
     * The metric's display name.
     */
    public string $name = 'User Status Distribution';

    /**
     * Calculate the partition data for the metric.
     */
    public function calculate(Request $request): PartitionResult
    {
        return $this->count($request, User::class, 'status')
            ->labels([
                'active' => 'Active Users',
                'inactive' => 'Inactive Users',
                'pending' => 'Pending Approval',
            ])
            ->colors([
                'active' => '#10B981',
                'inactive' => '#6B7280',
                'pending' => '#F59E0B',
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
            'ALL' => 'All Time',
        ];
    }
}
```

## Aggregation Methods

### Count by Category

```php
public function calculate(Request $request): PartitionResult
{
    return $this->count($request, User::class, 'status');
}
```

### Sum by Category

```php
public function calculate(Request $request): PartitionResult
{
    return $this->sum($request, Order::class, 'total', 'status');
}
```

### Average by Category

```php
public function calculate(Request $request): PartitionResult
{
    return $this->average($request, Order::class, 'total', 'category');
}
```

### Max/Min by Category

```php
public function calculate(Request $request): PartitionResult
{
    return $this->max($request, Order::class, 'total', 'category');
    // return $this->min($request, Order::class, 'total', 'category');
}
```

## Custom Grouping

### Custom Grouping Logic

```php
public function calculate(Request $request): PartitionResult
{
    return $this->countWithCustomGrouping($request, User::class, function ($user) {
        // Group by user activity level
        $daysSinceLogin = now()->diffInDays($user->last_login_at);
        
        return match (true) {
            $daysSinceLogin <= 7 => 'Active',
            $daysSinceLogin <= 30 => 'Moderate',
            default => 'Inactive',
        };
    });
}
```

### Sum with Custom Grouping

```php
public function calculate(Request $request): PartitionResult
{
    return $this->sumWithCustomGrouping($request, Order::class, 'total', function ($order) {
        // Group by order size
        return match (true) {
            $order->total < 100 => 'Small Orders',
            $order->total < 500 => 'Medium Orders',
            default => 'Large Orders',
        };
    });
}
```

## Range-Based Grouping

### Date Range Grouping

```php
public function calculate(Request $request): PartitionResult
{
    $ranges = [
        'This Week' => [now()->startOfWeek(), now()->endOfWeek()],
        'Last Week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
        'This Month' => [now()->startOfMonth(), now()->endOfMonth()],
        'Older' => [now()->subYears(10), now()->startOfMonth()],
    ];

    return $this->countByDateRanges($request, User::class, $ranges);
}
```

### Numeric Range Grouping

```php
public function calculate(Request $request): PartitionResult
{
    $ranges = [
        'Small (0-100)' => [0, 100],
        'Medium (101-500)' => [101, 500],
        'Large (501-1000)' => [501, 1000],
        'Extra Large (1000+)' => [1001, null], // null means no upper limit
    ];

    return $this->countByNumericRanges($request, Order::class, 'total', $ranges);
}
```

## Partition Result Formatting

### Custom Labels

```php
public function calculate(Request $request): PartitionResult
{
    return $this->count($request, User::class, 'status')
        ->label('active', 'Active Users')
        ->label('inactive', 'Inactive Users')
        // Or set multiple at once:
        ->labels([
            'pending' => 'Pending Approval',
            'suspended' => 'Suspended Users',
        ]);
}
```

### Custom Colors

```php
public function calculate(Request $request): PartitionResult
{
    return $this->count($request, User::class, 'status')
        ->color('active', '#10B981')
        ->color('inactive', '#6B7280')
        // Or set multiple at once:
        ->colors([
            'pending' => '#F59E0B',
            'suspended' => '#F97316',
        ]);
}
```

### Value Formatting

```php
public function calculate(Request $request): PartitionResult
{
    return $this->sum($request, Order::class, 'total', 'status')
        ->currency('$')
        ->suffix(' revenue');
}
```

### Value Transformation

```php
public function calculate(Request $request): PartitionResult
{
    return $this->sum($request, Order::class, 'total', 'category')
        ->transform(fn($value) => $value / 1000) // Convert to thousands
        ->suffix('K');
}
```

## Manual Result Building

### Direct Partition Creation

```php
public function calculate(Request $request): PartitionResult
{
    // Build partitions manually from complex logic
    $partitions = [
        'High Priority' => 25,
        'Medium Priority' => 45,
        'Low Priority' => 30,
    ];

    return $this->partitionResult($partitions)
        ->colors([
            'High Priority' => '#EF4444',
            'Medium Priority' => '#F59E0B',
            'Low Priority' => '#10B981',
        ]);
}
```

## Advanced Features

### Caching

```php
public function calculate(Request $request): PartitionResult
{
    return Cache::remember(
        $this->getCacheKey($request),
        $this->cacheFor(),
        fn() => $this->count($request, User::class, 'status')
    );
}

public function cacheFor(): int
{
    return 900; // 15 minutes
}
```

### Multiple Calculation Methods

```php
class UserMetrics extends Partition
{
    public function calculateByStatus(Request $request): PartitionResult
    {
        return $this->count($request, User::class, 'status');
    }

    public function calculateByRole(Request $request): PartitionResult
    {
        return $this->countWithCustomGrouping($request, User::class, function ($user) {
            return $user->roles->pluck('name')->join(', ') ?: 'No Role';
        });
    }

    public function calculateByActivity(Request $request): PartitionResult
    {
        return $this->countWithCustomGrouping($request, User::class, function ($user) {
            $daysSinceLogin = now()->diffInDays($user->last_login_at);
            
            return match (true) {
                $daysSinceLogin <= 1 => 'Very Active',
                $daysSinceLogin <= 7 => 'Active',
                $daysSinceLogin <= 30 => 'Moderate',
                default => 'Inactive',
            };
        });
    }
}
```

## Configuration Options

### Available Ranges

```php
public function ranges(): array
{
    return [
        30 => '30 Days',
        60 => '60 Days',
        90 => '90 Days',
        365 => '1 Year',
        'MTD' => 'Month To Date',
        'QTD' => 'Quarter To Date',
        'YTD' => 'Year To Date',
        'ALL' => 'All Time',
    ];
}
```

### Authorization

```php
public function authorize(Request $request): bool
{
    return $request->user()->can('view-analytics');
}
```

### URI Key

```php
public function uriKey(): string
{
    return 'user-status-distribution';
}
```

### Help Text

```php
public function help(): ?string
{
    return 'Shows the distribution of users by their current status.';
}
```

## Chart Data Format

The PartitionResult automatically formats data for frontend chart consumption:

```json
{
    "partitions": {
        "active": 150,
        "inactive": 75,
        "pending": 25
    },
    "chart_data": [
        {
            "key": "active",
            "label": "Active Users",
            "value": 150,
            "formatted_value": "150 users",
            "percentage": 60.0,
            "color": "#10B981"
        },
        {
            "key": "inactive",
            "label": "Inactive Users", 
            "value": 75,
            "formatted_value": "75 users",
            "percentage": 30.0,
            "color": "#6B7280"
        },
        {
            "key": "pending",
            "label": "Pending Approval",
            "value": 25,
            "formatted_value": "25 users",
            "percentage": 10.0,
            "color": "#F59E0B"
        }
    ],
    "total": 250,
    "formatted_total": "250 users",
    "has_no_data": false
}
```

## Default Colors

When no custom colors are specified, the system uses a predefined color palette:

- `#3B82F6` (Blue)
- `#10B981` (Green)
- `#F59E0B` (Yellow)
- `#EF4444` (Red)
- `#8B5CF6` (Purple)
- `#F97316` (Orange)
- `#06B6D4` (Cyan)
- `#84CC16` (Lime)
- `#EC4899` (Pink)
- `#6B7280` (Gray)

## Example Implementation

Here's a complete example of the UserStatusPartitionMetric:

```php
<?php

namespace App\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Metrics\Partition;
use JTD\AdminPanel\Metrics\PartitionResult;

class UserStatusPartitionMetric extends Partition
{
    public string $name = 'User Status Distribution';
    
    protected string $userModel = 'App\Models\User';
    protected int $cacheMinutes = 15;

    public function calculate(Request $request): PartitionResult
    {
        $cacheKey = $this->getCacheKey($request);
        
        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            return $this->count($request, $this->userModel, 'status')
                ->labels([
                    'active' => 'Active Users',
                    'inactive' => 'Inactive Users',
                    'pending' => 'Pending Approval',
                    'suspended' => 'Suspended Users',
                ])
                ->colors([
                    'active' => '#10B981',
                    'inactive' => '#6B7280',
                    'pending' => '#F59E0B',
                    'suspended' => '#F97316',
                ])
                ->suffix(' users');
        });
    }

    public function ranges(): array
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            365 => '1 Year',
            'ALL' => 'All Time',
        ];
    }

    public function uriKey(): string
    {
        return 'user-status-distribution';
    }

    public function authorize(Request $request): bool
    {
        return $request->user()?->can('viewAny', $this->userModel) ?? false;
    }

    public function help(): ?string
    {
        return 'Shows the distribution of users by their status with pie chart visualization.';
    }
}
```

This implementation provides a complete, production-ready Partition metric with custom labels, colors, caching, authorization, and comprehensive configuration options.
