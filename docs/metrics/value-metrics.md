# Value Metrics

Value metrics display a single numeric value with optional comparison to a previous time period. They are the most common type of metric and are perfect for showing counts, totals, averages, and other single-value statistics.

## Overview

Value metrics in AdminPanel are 100% compatible with Laravel Nova's Value metrics, providing:

- **Single Value Display**: Show one primary metric value
- **Previous Period Comparison**: Automatic comparison with previous time period
- **Percentage Change Calculation**: Built-in trend analysis
- **Rich Formatting**: Currency, prefixes, suffixes, and custom formatting
- **Caching Support**: Performance optimization with configurable cache duration
- **Range Selection**: Support for various time ranges (30/60/90 days, MTD, QTD, YTD)

## Basic Usage

### Creating a Value Metric

```php
<?php

namespace App\Metrics;

use Illuminate\Http\Request;
use JTD\AdminPanel\Metrics\Value;
use JTD\AdminPanel\Metrics\ValueResult;

class UserGrowthMetric extends Value
{
    /**
     * The metric's display name.
     */
    public string $name = 'User Growth';

    /**
     * Calculate the value of the metric.
     */
    public function calculate(Request $request): ValueResult
    {
        return $this->count($request, User::class);
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
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
        ];
    }
}
```

### Helper Methods

The Value base class provides several helper methods for common operations:

#### Count Records
```php
public function calculate(Request $request): ValueResult
{
    return $this->count($request, User::class);
}
```

#### Sum Values
```php
public function calculate(Request $request): ValueResult
{
    return $this->sum($request, Order::class, 'total');
}
```

#### Calculate Average
```php
public function calculate(Request $request): ValueResult
{
    return $this->average($request, Order::class, 'total');
}
```

#### Find Maximum
```php
public function calculate(Request $request): ValueResult
{
    return $this->max($request, Order::class, 'total');
}
```

#### Find Minimum
```php
public function calculate(Request $request): ValueResult
{
    return $this->min($request, Order::class, 'total');
}
```

## Value Result Formatting

### Basic Formatting

```php
public function calculate(Request $request): ValueResult
{
    $count = User::count();
    
    return $this->result($count)
        ->prefix('Total: ')
        ->suffix(' users');
}
```

### Currency Formatting

```php
public function calculate(Request $request): ValueResult
{
    $revenue = Order::sum('total');
    
    return $this->result($revenue)
        ->currency('$');
}
```

### Custom Formatting

```php
public function calculate(Request $request): ValueResult
{
    $bytes = Storage::size('uploads');
    
    return $this->result($bytes)
        ->transform(fn($value) => $this->formatBytes($value))
        ->suffix(' storage used');
}

private function formatBytes($bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}
```

## Advanced Features

### Custom Date Column

By default, metrics filter by `created_at`. You can customize this:

```php
protected function applyDateRange(Builder $query, string|int $range, string $timezone): void
{
    [$start, $end] = $this->calculateDateRange($range, $timezone);
    
    $query->whereBetween('published_at', [$start, $end]);
}
```

### Complex Queries

```php
public function calculate(Request $request): ValueResult
{
    $query = User::query()
        ->where('status', 'active')
        ->whereHas('orders', function ($q) {
            $q->where('total', '>', 100);
        });
    
    return $this->count($request, $query);
}
```

### Caching

```php
public function calculate(Request $request): ValueResult
{
    return Cache::remember(
        $this->getCacheKey($request),
        $this->cacheFor(),
        fn() => $this->count($request, User::class)
    );
}

public function cacheFor(): int
{
    return 300; // 5 minutes
}
```

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
    return 'user-growth';
}
```

### Help Text

```php
public function help(): ?string
{
    return 'Shows the number of new users registered in the selected time period.';
}
```

## Example Implementation

Here's a complete example of the UserGrowthMetric:

```php
<?php

namespace App\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Metrics\Value;
use JTD\AdminPanel\Metrics\ValueResult;

class UserGrowthMetric extends Value
{
    public string $name = 'User Growth';
    
    protected string $userModel = 'App\Models\User';
    protected int $cacheMinutes = 5;

    public function calculate(Request $request): ValueResult
    {
        $cacheKey = $this->getCacheKey($request);
        
        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            return $this->count($request, $this->userModel);
        });
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

    public function cacheFor(): int
    {
        return $this->cacheMinutes * 60;
    }

    public function uriKey(): string
    {
        return 'user-growth';
    }

    public function authorize(Request $request): bool
    {
        return $request->user()?->can('viewAny', $this->userModel) ?? false;
    }

    public function help(): ?string
    {
        return 'Shows the number of new users registered in the selected time period compared to the previous period.';
    }

    public function icon(): string
    {
        return 'users';
    }
}
```

This implementation provides a complete, production-ready Value metric with caching, authorization, and comprehensive configuration options.
