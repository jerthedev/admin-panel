# Complete Metrics Examples

This document provides comprehensive examples for all metric types in JTD AdminPanel, demonstrating real-world implementations that serve as reference for developers.

## Overview

JTD AdminPanel provides 5 metric types, each with complete example implementations:

1. **Value Metrics** - Single numeric values with trend comparison
2. **Trend Metrics** - Time-series data with line chart visualization  
3. **Partition Metrics** - Categorical data with pie chart visualization
4. **Progress Metrics** - Progress towards targets with progress bars
5. **Table Metrics** - Tabular data with sorting and actions

All examples include:
- ✅ **Comprehensive functionality** - Real-world scenarios and use cases
- ✅ **Performance optimization** - Caching strategies and query optimization
- ✅ **Nova compatibility** - 100% alignment with Laravel Nova patterns
- ✅ **Complete testing** - Unit tests with full coverage
- ✅ **Documentation** - Detailed usage examples and API reference

## 1. Value Metrics Example

### UserGrowthMetric

**File**: `src/Metrics/UserGrowthMetric.php`

Demonstrates user growth tracking with trend comparison, showing total users registered in selected time periods.

```php
<?php

namespace JTD\AdminPanel\Metrics;

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
}
```

**Key Features:**
- Count aggregation with time-based filtering
- Previous period comparison for trend analysis
- Caching for performance optimization
- Configurable user model support
- Range selection (30, 60, 90 days, MTD, QTD, YTD)

## 2. Trend Metrics Example

### RegistrationTrendMetric

**File**: `src/Metrics/RegistrationTrendMetric.php`

Demonstrates user registration trends over time with line chart visualization and multiple aggregation units.

```php
<?php

namespace JTD\AdminPanel\Metrics;

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
}
```

**Key Features:**
- Time-based aggregation (countByDays, countByWeeks, countByMonths)
- Chart data formatting for frontend consumption
- Current value and trend sum display options
- Multiple aggregation units (hour, day, week, month)
- Range selection with appropriate time units

## 3. Partition Metrics Example

### UserStatusPartitionMetric

**File**: `src/Metrics/UserStatusPartitionMetric.php`

Demonstrates user status distribution with pie chart visualization and custom labels/colors.

```php
<?php

namespace JTD\AdminPanel\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Metrics\Partition;
use JTD\AdminPanel\Metrics\PartitionResult;

class UserStatusPartitionMetric extends Partition
{
    public string $name = 'User Status Distribution';
    protected string $userModel = 'App\Models\User';
    protected int $cacheMinutes = 15;
    protected string $statusColumn = 'status';

    public function calculate(Request $request): PartitionResult
    {
        $cacheKey = $this->getCacheKey($request);

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            return $this->count($request, $this->userModel, $this->statusColumn)
                ->labels($this->getStatusLabels())
                ->colors($this->getStatusColors())
                ->suffix(' users');
        });
    }

    protected function getStatusLabels(): array
    {
        return [
            'active' => 'Active Users',
            'inactive' => 'Inactive Users', 
            'pending' => 'Pending Approval',
            'suspended' => 'Suspended Users',
        ];
    }

    protected function getStatusColors(): array
    {
        return [
            'active' => '#10B981',
            'inactive' => '#6B7280',
            'pending' => '#F59E0B',
            'suspended' => '#EF4444',
        ];
    }
}
```

**Key Features:**
- Categorical data visualization with pie charts
- Custom labels and colors for segments
- Multiple calculation methods (by status, registration periods, activity levels)
- Flexible grouping logic
- Rich formatting options

## 4. Progress Metrics Example

### SalesTargetProgressMetric

**File**: `src/Metrics/SalesTargetProgressMetric.php`

Demonstrates sales progress towards targets with progress bar visualization and dynamic target calculation.

```php
<?php

namespace JTD\AdminPanel\Metrics;

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

    protected function getTargetForRange(Request $request): float
    {
        $range = $request->get('range', 30);
        
        return match ($range) {
            7 => $this->monthlyTarget * 0.25,
            30, 'MTD' => $this->monthlyTarget,
            90, 'QTD' => $this->monthlyTarget * 3,
            365, 'YTD' => $this->monthlyTarget * 12,
            default => $this->monthlyTarget,
        };
    }
}
```

**Key Features:**
- Target-based visualization with progress bars
- Dynamic target calculation based on time periods
- Color-coded progress (red/yellow/blue/green)
- Multiple progress types (count, sum, average)
- Currency formatting and custom transformations

## 5. Table Metrics Example

### TopCustomersTableMetric

**File**: `src/Metrics/TopCustomersTableMetric.php`

Demonstrates customer data display with tabular format, custom columns, actions, and sorting.

```php
<?php

namespace JTD\AdminPanel\Metrics;

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

    protected function getTopCustomersByRevenue(Request $request): TableResult
    {
        $limit = $request->get('limit', 10);

        return $this->fromCustomQuery($request, function ($request, $range, $timezone) use ($limit) {
            $customerTable = (new ($this->customerModel))->getTable();
            $orderTable = (new ($this->orderModel))->getTable();

            return $this->buildQuery($this->customerModel)
                ->select([
                    $customerTable.'.id',
                    $customerTable.'.name', 
                    $customerTable.'.email',
                ])
                ->selectRaw("COUNT({$orderTable}.id) as order_count")
                ->selectRaw("COALESCE(SUM({$orderTable}.total), 0) as total_revenue")
                ->leftJoin($orderTable, $customerTable.'.id', '=', $orderTable.'.customer_id')
                ->groupBy($customerTable.'.id')
                ->orderByDesc('total_revenue')
                ->limit($limit)
                ->get();
        });
    }
}
```

**Key Features:**
- Interactive data tables with sortable columns
- Row actions and clickable links
- Custom column definitions with formatting
- Pagination for large datasets
- Icons and custom formatting support

## Testing Coverage

All example metrics include comprehensive unit tests:

- **ValueMetricsTest.php** - Tests for UserGrowthMetric
- **TrendMetricsTest.php** - Tests for RegistrationTrendMetric  
- **PartitionMetricsTest.php** - Tests for UserStatusPartitionMetric
- **ProgressMetricsTest.php** - Tests for SalesTargetProgressMetric
- **TableMetricsTest.php** - Tests for TopCustomersTableMetric

## Usage in Dashboards

```php
use JTD\AdminPanel\Metrics\UserGrowthMetric;
use JTD\AdminPanel\Metrics\RegistrationTrendMetric;
use JTD\AdminPanel\Metrics\UserStatusPartitionMetric;
use JTD\AdminPanel\Metrics\SalesTargetProgressMetric;
use JTD\AdminPanel\Metrics\TopCustomersTableMetric;

class AnalyticsDashboard extends Dashboard
{
    public function cards(Request $request): array
    {
        return [
            new UserGrowthMetric,
            new RegistrationTrendMetric,
            new UserStatusPartitionMetric,
            new SalesTargetProgressMetric,
            new TopCustomersTableMetric,
        ];
    }
}
```

## Next Steps

These examples serve as the foundation for creating custom metrics in your application. Each example demonstrates best practices for:

- Performance optimization with caching
- Nova compatibility and patterns
- Real-world data scenarios
- Comprehensive testing approaches
- Documentation standards

For detailed API documentation, see the individual metric type documentation in `docs/metrics/`.
