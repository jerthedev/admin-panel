# Trend Metrics

Trend metrics display time-series data as line charts, showing how values change over time. They are perfect for visualizing patterns, growth trends, and temporal analysis of your data.

## Overview

Trend metrics in AdminPanel are 100% compatible with Laravel Nova's Trend metrics, providing:

- **Time-Series Visualization**: Display data over time with line charts
- **Multiple Time Units**: Support for minutes, hours, days, weeks, months, and years
- **Database Agnostic**: Works with MySQL, PostgreSQL, SQLite, and SQL Server
- **Rich Aggregation Methods**: Count, sum, average, max, min operations
- **Flexible Formatting**: Prefix, suffix, currency, and custom transformations
- **Current Value Display**: Show the latest data point
- **Sum Display**: Show the total of all data points
- **Caching Support**: Performance optimization with configurable cache duration
- **Range Selection**: Support for various time ranges with automatic unit selection

## Basic Usage

### Creating a Trend Metric

```php
<?php

namespace App\Metrics;

use Illuminate\Http\Request;
use JTD\AdminPanel\Metrics\Trend;
use JTD\AdminPanel\Metrics\TrendResult;

class RegistrationTrendMetric extends Trend
{
    /**
     * The metric's display name.
     */
    public string $name = 'Registration Trend';

    /**
     * Calculate the trend data for the metric.
     */
    public function calculate(Request $request): TrendResult
    {
        return $this->countByDays($request, User::class);
    }

    /**
     * Get the ranges available for this metric.
     */
    public function ranges(): array
    {
        return [
            7 => '7 Days',
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            365 => '1 Year',
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
        ];
    }
}
```

## Time-Based Aggregation Methods

### Count Methods

```php
// Count by different time units
public function calculate(Request $request): TrendResult
{
    return $this->countByDays($request, User::class);
    // return $this->countByWeeks($request, User::class);
    // return $this->countByMonths($request, User::class);
    // return $this->countByHours($request, User::class);
    // return $this->countByMinutes($request, User::class);
}
```

### Sum Methods

```php
public function calculate(Request $request): TrendResult
{
    return $this->sumByDays($request, Order::class, 'total');
    // return $this->sumByWeeks($request, Order::class, 'total');
    // return $this->sumByMonths($request, Order::class, 'total');
}
```

### Average Methods

```php
public function calculate(Request $request): TrendResult
{
    return $this->averageByDays($request, Order::class, 'total');
    // return $this->averageByWeeks($request, Order::class, 'total');
    // return $this->averageByMonths($request, Order::class, 'total');
}
```

### Max/Min Methods

```php
public function calculate(Request $request): TrendResult
{
    return $this->maxByDays($request, Order::class, 'total');
    // return $this->minByDays($request, Order::class, 'total');
}
```

## Trend Result Formatting

### Basic Formatting

```php
public function calculate(Request $request): TrendResult
{
    return $this->countByDays($request, User::class)
        ->prefix('Users: ')
        ->suffix(' registered');
}
```

### Currency Formatting

```php
public function calculate(Request $request): TrendResult
{
    return $this->sumByDays($request, Order::class, 'total')
        ->currency('$');
}
```

### Show Current Value

```php
public function calculate(Request $request): TrendResult
{
    return $this->countByDays($request, User::class)
        ->showCurrentValue(); // Shows the latest data point
}
```

### Show Trend Sum

```php
public function calculate(Request $request): TrendResult
{
    return $this->sumByDays($request, Order::class, 'total')
        ->showTrendSum(); // Shows the sum of all data points
}
```

### Value Transformation

```php
public function calculate(Request $request): TrendResult
{
    return $this->sumByDays($request, Order::class, 'total')
        ->transform(fn($value) => $value / 1000) // Convert to thousands
        ->suffix('K');
}
```

## Advanced Features

### Dynamic Time Unit Selection

```php
public function calculate(Request $request): TrendResult
{
    $unit = $this->getAggregationUnit($request);
    
    return match ($unit) {
        'hour' => $this->countByHours($request, User::class),
        'day' => $this->countByDays($request, User::class),
        'week' => $this->countByWeeks($request, User::class),
        'month' => $this->countByMonths($request, User::class),
        default => $this->countByDays($request, User::class),
    };
}

protected function getAggregationUnit(Request $request): string
{
    $range = $request->get('range', 30);
    
    return match (true) {
        $range === 1 => 'hour',
        $range <= 7 => 'day',
        $range <= 90 => 'day',
        $range <= 365 => 'week',
        default => 'month',
    };
}
```

### Custom Date Column

```php
protected function getDateColumn(): string
{
    return 'published_at'; // Instead of default 'created_at'
}
```

### Complex Queries

```php
public function calculate(Request $request): TrendResult
{
    $query = User::query()
        ->where('status', 'active')
        ->whereHas('orders', function ($q) {
            $q->where('total', '>', 100);
        });
    
    return $this->countByDays($request, $query);
}
```

### Caching

```php
public function calculate(Request $request): TrendResult
{
    return Cache::remember(
        $this->getCacheKey($request),
        $this->cacheFor(),
        fn() => $this->countByDays($request, User::class)
    );
}

public function cacheFor(): int
{
    return 600; // 10 minutes
}
```

## Database Compatibility

The Trend metrics system automatically detects your database driver and uses the appropriate date functions:

- **MySQL**: `DATE_FORMAT()`
- **PostgreSQL**: `TO_CHAR()`
- **SQLite**: `strftime()`
- **SQL Server**: `FORMAT()`

This ensures your metrics work consistently across different database systems.

## Configuration Options

### Available Ranges

```php
public function ranges(): array
{
    return [
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
    return 'registration-trend';
}
```

### Help Text

```php
public function help(): ?string
{
    return 'Shows the trend of new user registrations over time.';
}
```

## Chart Data Format

The TrendResult automatically formats data for frontend chart consumption:

```json
{
    "trend": {
        "2023-01-01": 10,
        "2023-01-02": 15,
        "2023-01-03": 8
    },
    "chart_data": [
        {
            "label": "2023-01-01",
            "value": 10,
            "formatted_value": "10 users"
        },
        {
            "label": "2023-01-02", 
            "value": 15,
            "formatted_value": "15 users"
        }
    ],
    "has_no_data": false,
    "current_value": 8,
    "formatted_current_value": "8 users",
    "trend_sum": 33,
    "formatted_trend_sum": "33 users"
}
```

## Example Implementation

Here's a complete example of the RegistrationTrendMetric:

```php
<?php

namespace App\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Metrics\Trend;
use JTD\AdminPanel\Metrics\TrendResult;

class RegistrationTrendMetric extends Trend
{
    public string $name = 'Registration Trend';
    
    protected string $userModel = 'App\Models\User';
    protected int $cacheMinutes = 10;

    public function calculate(Request $request): TrendResult
    {
        $cacheKey = $this->getCacheKey($request);
        
        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            $unit = $this->getAggregationUnit($request);
            
            return match ($unit) {
                'hour' => $this->countByHours($request, $this->userModel),
                'day' => $this->countByDays($request, $this->userModel),
                'week' => $this->countByWeeks($request, $this->userModel),
                'month' => $this->countByMonths($request, $this->userModel),
                default => $this->countByDays($request, $this->userModel),
            };
        });
    }

    public function ranges(): array
    {
        return [
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
    }

    protected function getAggregationUnit(Request $request): string
    {
        $range = $request->get('range', 30);
        
        return match (true) {
            $range === 1 => 'hour',
            is_numeric($range) && $range <= 7 => 'day',
            is_numeric($range) && $range <= 90 => 'day',
            is_numeric($range) && $range <= 365 => 'week',
            $range === 'MTD' => 'day',
            $range === 'QTD' => 'week',
            $range === 'YTD' => 'month',
            default => 'day',
        };
    }

    public function uriKey(): string
    {
        return 'registration-trend';
    }

    public function authorize(Request $request): bool
    {
        return $request->user()?->can('viewAny', $this->userModel) ?? false;
    }

    public function help(): ?string
    {
        return 'Shows the trend of new user registrations over time with line chart visualization.';
    }
}
```

This implementation provides a complete, production-ready Trend metric with database compatibility, caching, authorization, and comprehensive configuration options.
