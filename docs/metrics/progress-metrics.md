# Progress Metrics

Progress metrics display progress towards a target value as progress bars, showing completion percentage and remaining amounts. They are perfect for visualizing goals, targets, and completion status of various business metrics.

## Overview

Progress metrics in AdminPanel are 100% compatible with Laravel Nova's Progress metrics, providing:

- **Target-Based Visualization**: Display progress towards specific goals with progress bars
- **Percentage Calculations**: Automatic percentage completion calculation
- **Color-Coded Progress**: Dynamic colors based on progress level (red/yellow/blue/green)
- **Rich Aggregation Methods**: Count, sum, average, max, min operations towards targets
- **Dynamic Target Calculation**: Targets based on historical data or complex logic
- **Flexible Formatting**: Currency, prefixes, suffixes, and custom transformations
- **Completion States**: Track completion, exceeding targets, and remaining amounts
- **Unwanted Progress Control**: Option to cap progress at 100%
- **Caching Support**: Performance optimization with configurable cache duration

## Basic Usage

### Creating a Progress Metric

```php
<?php

namespace App\Metrics;

use Illuminate\Http\Request;
use JTD\AdminPanel\Metrics\Progress;
use JTD\AdminPanel\Metrics\ProgressResult;

class SalesTargetProgressMetric extends Progress
{
    /**
     * The metric's display name.
     */
    public string $name = 'Sales Target Progress';

    /**
     * Calculate the progress data for the metric.
     */
    public function calculate(Request $request): ProgressResult
    {
        $target = 50000; // $50,000 monthly target
        
        return $this->sum($request, Order::class, 'total', $target)
            ->currency('$')
            ->avoidUnwantedProgress();
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

## Aggregation Methods

### Count Towards Target

```php
public function calculate(Request $request): ProgressResult
{
    $target = 100; // 100 new users target
    
    return $this->count($request, User::class, $target);
}
```

### Sum Towards Target

```php
public function calculate(Request $request): ProgressResult
{
    $target = 50000; // $50,000 revenue target
    
    return $this->sum($request, Order::class, 'total', $target);
}
```

### Average Towards Target

```php
public function calculate(Request $request): ProgressResult
{
    $target = 500; // $500 average order value target
    
    return $this->average($request, Order::class, 'total', $target);
}
```

### Max/Min Towards Target

```php
public function calculate(Request $request): ProgressResult
{
    $target = 1000; // $1,000 largest order target
    
    return $this->max($request, Order::class, 'total', $target);
    // return $this->min($request, Order::class, 'total', $target);
}
```

## Dynamic Target Calculation

### Dynamic Target with Callback

```php
public function calculate(Request $request): ProgressResult
{
    return $this->progressWithDynamicTarget(
        $request,
        Order::class,
        fn($query) => $query->sum('total'), // Current value calculation
        fn($request, $range, $timezone) => $this->calculateTarget($request, $range, $timezone) // Target calculation
    );
}

private function calculateTarget(Request $request, $range, $timezone): float
{
    // Calculate target as 110% of last year's performance
    $lastYearSales = Order::whereBetween('created_at', [
        now()->subYear()->startOfMonth(),
        now()->subYear()->endOfMonth()
    ])->sum('total');
    
    return $lastYearSales * 1.10;
}
```

### Percentage-Based Target

```php
public function calculate(Request $request): ProgressResult
{
    // Target is 80% of total possible revenue
    return $this->progressWithPercentageTarget($request, Order::class, 'total', 80);
}
```

### Compare to Previous Period

```php
public function calculate(Request $request): ProgressResult
{
    // Use previous period as the target
    return $this->progressComparedToPrevious($request, Order::class, 'sum', 'total');
}
```

## Progress Result Formatting

### Basic Formatting

```php
public function calculate(Request $request): ProgressResult
{
    return $this->sum($request, Order::class, 'total', 50000)
        ->prefix('Revenue: ')
        ->suffix(' achieved');
}
```

### Currency Formatting

```php
public function calculate(Request $request): ProgressResult
{
    return $this->sum($request, Order::class, 'total', 50000)
        ->currency('$');
}
```

### Avoid Unwanted Progress

```php
public function calculate(Request $request): ProgressResult
{
    return $this->sum($request, Order::class, 'total', 50000)
        ->avoidUnwantedProgress(); // Cap at 100%
}
```

### Value Transformation

```php
public function calculate(Request $request): ProgressResult
{
    return $this->sum($request, Order::class, 'total', 50000)
        ->transform(fn($value) => $value / 1000) // Convert to thousands
        ->suffix('K');
}
```

### Custom Format

```php
public function calculate(Request $request): ProgressResult
{
    return $this->sum($request, Order::class, 'total', 50000)
        ->format([
            'thousandSeparated' => true,
            'mantissa' => 2
        ]);
}
```

## Advanced Features

### Multiple Calculation Methods

```php
class SalesMetrics extends Progress
{
    public function calculateRevenue(Request $request): ProgressResult
    {
        return $this->sum($request, Order::class, 'total', 50000)
            ->currency('$');
    }

    public function calculateOrderCount(Request $request): ProgressResult
    {
        return $this->count($request, Order::class, 100)
            ->suffix(' orders');
    }

    public function calculateAverageOrderValue(Request $request): ProgressResult
    {
        return $this->average($request, Order::class, 'total', 500)
            ->currency('$')
            ->suffix(' avg');
    }
}
```

### Range-Based Targets

```php
public function calculate(Request $request): ProgressResult
{
    $target = $this->getTargetForRange($request);
    
    return $this->sum($request, Order::class, 'total', $target)
        ->currency('$');
}

private function getTargetForRange(Request $request): float
{
    $range = $request->get('range', 30);
    
    return match ($range) {
        'MTD' => 50000,    // Monthly target
        'QTD' => 150000,   // Quarterly target
        'YTD' => 600000,   // Yearly target
        7 => 12500,        // Weekly target
        30 => 50000,       // Monthly target
        60 => 100000,      // 2-month target
        90 => 150000,      // Quarterly target
        default => 50000 * ((int) $range / 30), // Pro-rated target
    };
}
```

### Caching

```php
public function calculate(Request $request): ProgressResult
{
    return Cache::remember(
        $this->getCacheKey($request),
        $this->cacheFor(),
        fn() => $this->sum($request, Order::class, 'total', 50000)
    );
}

public function cacheFor(): int
{
    return 600; // 10 minutes
}
```

## Progress States and Colors

The ProgressResult automatically assigns colors based on completion percentage:

- **0-49%**: Red (`#EF4444`) - Low progress
- **50-74%**: Yellow (`#F59E0B`) - Medium progress  
- **75-99%**: Blue (`#3B82F6`) - High progress
- **100%+**: Green (`#10B981`) - Complete/Exceeded

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
    return $request->user()->can('view-sales-metrics');
}
```

### URI Key

```php
public function uriKey(): string
{
    return 'sales-target-progress';
}
```

### Help Text

```php
public function help(): ?string
{
    return 'Shows current sales progress towards the monthly target.';
}
```

## Progress Data Format

The ProgressResult automatically formats data for frontend consumption:

```json
{
    "value": 37500,
    "target": 50000,
    "remaining": 12500,
    "formatted_value": "$37,500",
    "formatted_target": "$50,000",
    "formatted_remaining": "$12,500",
    "percentage": 75.0,
    "is_complete": false,
    "exceeds_target": false,
    "progress_color": "#3B82F6",
    "has_no_data": false
}
```

## Example Implementation

Here's a complete example of the SalesTargetProgressMetric:

```php
<?php

namespace App\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Metrics\Progress;
use JTD\AdminPanel\Metrics\ProgressResult;

class SalesTargetProgressMetric extends Progress
{
    public string $name = 'Sales Target Progress';
    
    protected string $orderModel = 'App\Models\Order';
    protected int $cacheMinutes = 10;
    protected float $monthlyTarget = 50000.00;

    public function calculate(Request $request): ProgressResult
    {
        $cacheKey = $this->getCacheKey($request);
        
        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            $target = $this->getTargetForRange($request);
            
            return $this->sum($request, $this->orderModel, 'total', $target)
                ->currency('$')
                ->avoidUnwantedProgress();
        });
    }

    public function calculateWithDynamicTarget(Request $request): ProgressResult
    {
        return $this->progressWithDynamicTarget(
            $request,
            $this->orderModel,
            fn($query) => $query->sum('total'),
            fn($request, $range, $timezone) => $this->calculateDynamicTarget($request, $range, $timezone)
        )->currency('$');
    }

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

    private function getTargetForRange(Request $request): float
    {
        $range = $request->get('range', 30);

        return match ($range) {
            'MTD' => $this->monthlyTarget,
            'QTD' => $this->monthlyTarget * 3,
            'YTD' => $this->monthlyTarget * 12,
            7 => $this->monthlyTarget * (7 / 30),
            30 => $this->monthlyTarget,
            60 => $this->monthlyTarget * 2,
            90 => $this->monthlyTarget * 3,
            default => $this->monthlyTarget * ((int) $range / 30),
        };
    }

    private function calculateDynamicTarget($request, $range, $timezone): float
    {
        // Target is 110% of last year's performance for the same period
        $query = $this->buildQuery($this->orderModel);
        [$start, $end] = $this->calculateDateRange($range, $timezone);
        
        $lastYearStart = $start->copy()->subYear();
        $lastYearEnd = $end->copy()->subYear();
        
        $query->whereBetween('created_at', [$lastYearStart, $lastYearEnd]);
        $lastYearSales = $query->sum('total');
        
        return $lastYearSales * 1.10;
    }

    public function uriKey(): string
    {
        return 'sales-target-progress';
    }

    public function authorize(Request $request): bool
    {
        return $request->user()?->can('viewAny', $this->orderModel) ?? false;
    }

    public function help(): ?string
    {
        return 'Shows current sales progress towards the target with progress bar visualization.';
    }
}
```

This implementation provides a complete, production-ready Progress metric with dynamic targets, caching, authorization, and comprehensive configuration options.
