# Cards Guide - Complete Documentation

## Overview

Cards are the building blocks of AdminPanel dashboards, providing modular, reusable components that display data, metrics, and interactive content. The AdminPanel card system is 100% compatible with Laravel Nova cards while adding enhanced features and capabilities.

## Table of Contents

1. [Quick Start](#quick-start)
2. [Card Basics](#card-basics)
3. [Creating Custom Cards](#creating-custom-cards)
4. [Card Authorization](#card-authorization)
5. [Card Metadata](#card-metadata)
6. [Vue Components](#vue-components)
7. [Advanced Features](#advanced-features)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)

## Quick Start

### Creating Your First Card

```bash
# Generate a new card
php artisan admin-panel:make-card StatsCard

# Generate with template
php artisan admin-panel:make-card AnalyticsCard --template=analytics
```

### Basic Card Implementation

```php
<?php

namespace App\Admin\Cards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;

class StatsCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'title' => 'Statistics Overview',
            'data' => $this->getData(),
        ]);
    }
    
    protected function getData(): array
    {
        return [
            'users' => User::count(),
            'orders' => Order::count(),
            'revenue' => Order::sum('total'),
        ];
    }
}
```

### Adding to Dashboard

```php
// In your dashboard class
public function cards(): array
{
    return [
        StatsCard::make(),
    ];
}
```

## Card Basics

### Base Card Class

All cards extend the `JTD\AdminPanel\Cards\Card` abstract class:

```php
abstract class Card
{
    // Factory method
    public static function make(): static;
    
    // Meta data management
    public function withMeta(array $meta): static;
    public function meta(): array;
    
    // Authorization
    public function canSee(callable $callback): static;
    public function authorize(Request $request): bool;
    
    // Naming
    public function name(): string;
    public function withName(string $name): static;
    
    // Component mapping
    public function component(): string;
    public function withComponent(string $component): static;
    
    // Serialization
    public function jsonSerialize(): array;
}
```

### Card Lifecycle

1. **Instantiation**: Card is created via `make()` method
2. **Configuration**: Meta data and options are set
3. **Authorization**: `authorize()` checks if user can see card
4. **Serialization**: Card data is serialized for frontend
5. **Rendering**: Vue component renders the card

## Creating Custom Cards

### Step 1: Generate Card Class

```bash
php artisan admin-panel:make-card MyCustomCard
```

This creates:
- `app/Admin/Cards/MyCustomCard.php` - PHP card class
- `resources/js/admin-cards/MyCustomCard.vue` - Vue component

### Step 2: Implement Card Logic

```php
<?php

namespace App\Admin\Cards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;

class MyCustomCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'title' => 'My Custom Card',
            'subtitle' => 'Custom functionality',
            'data' => $this->loadData(),
            'refreshInterval' => 30, // seconds
        ]);
    }
    
    protected function loadData(): array
    {
        // Your custom data loading logic
        return [
            'metric1' => $this->calculateMetric1(),
            'metric2' => $this->calculateMetric2(),
            'chart_data' => $this->getChartData(),
        ];
    }
    
    private function calculateMetric1(): int
    {
        // Custom calculation
        return 42;
    }
    
    private function calculateMetric2(): float
    {
        // Custom calculation
        return 3.14;
    }
    
    private function getChartData(): array
    {
        // Chart data for frontend
        return [
            'labels' => ['Jan', 'Feb', 'Mar'],
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => [100, 200, 150],
                ]
            ]
        ];
    }
}
```

### Step 3: Create Vue Component

```vue
<template>
  <div class="card bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="card-header p-4 border-b border-gray-200">
      <h3 class="text-lg font-semibold text-gray-900">
        {{ card.meta.title }}
      </h3>
      <p class="text-sm text-gray-600">
        {{ card.meta.subtitle }}
      </p>
    </div>
    
    <div class="card-body p-4">
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="metric">
          <div class="text-2xl font-bold text-blue-600">
            {{ card.meta.data.metric1 }}
          </div>
          <div class="text-sm text-gray-600">Metric 1</div>
        </div>
        
        <div class="metric">
          <div class="text-2xl font-bold text-green-600">
            {{ card.meta.data.metric2 }}
          </div>
          <div class="text-sm text-gray-600">Metric 2</div>
        </div>
      </div>
      
      <!-- Chart component -->
      <div class="chart-container">
        <canvas ref="chartCanvas"></canvas>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import Chart from 'chart.js/auto'

export default {
  name: 'MyCustomCard',
  
  props: {
    card: {
      type: Object,
      required: true
    }
  },
  
  setup(props) {
    const chartCanvas = ref(null)
    let chartInstance = null
    
    onMounted(() => {
      initChart()
    })
    
    const initChart = () => {
      if (chartCanvas.value) {
        chartInstance = new Chart(chartCanvas.value, {
          type: 'line',
          data: props.card.meta.data.chart_data,
          options: {
            responsive: true,
            maintainAspectRatio: false,
          }
        })
      }
    }
    
    return {
      chartCanvas
    }
  }
}
</script>
```

## Card Authorization

### Basic Authorization

```php
class AdminOnlyCard extends Card
{
    public static function make(): static
    {
        return parent::make()->canSee(function (Request $request) {
            return $request->user()?->is_admin ?? false;
        });
    }
}
```

### Role-Based Authorization

```php
class ManagerCard extends Card
{
    public static function make(): static
    {
        return parent::make()->canSee(function (Request $request) {
            return $request->user()?->hasRole('manager');
        });
    }
}
```

### Permission-Based Authorization

```php
class ReportsCard extends Card
{
    public static function make(): static
    {
        return parent::make()->canSee(function (Request $request) {
            return $request->user()?->can('view-reports');
        });
    }
}
```

## Card Metadata

### Common Meta Properties

```php
$this->withMeta([
    // Display
    'title' => 'Card Title',
    'subtitle' => 'Card Subtitle',
    'icon' => 'chart-bar', // Heroicon name
    
    // Data
    'data' => $this->getData(),
    'loading' => false,
    'error' => null,
    
    // Behavior
    'refreshInterval' => 30, // seconds
    'autoRefresh' => true,
    'clickable' => true,
    
    // Styling
    'color' => 'blue',
    'variant' => 'default',
    'size' => 'medium',
    
    // Features
    'exportable' => true,
    'printable' => false,
    'fullscreen' => true,
]);
```

### Dynamic Meta Data

```php
public function withMeta(array $meta): static
{
    // Add dynamic data based on current user
    $meta['user_specific_data'] = $this->getUserData();
    
    // Add timestamp
    $meta['last_updated'] = now()->toISOString();
    
    return parent::withMeta($meta);
}
```

## Vue Components

### Component Registration

Cards automatically map to Vue components:
- `MyCard` → `MyCard.vue`
- `AnalyticsCard` → `AnalyticsCard.vue`

### Component Props

All card components receive:

```javascript
props: {
  card: {
    type: Object,
    required: true,
    // Contains: name, component, meta, uriKey
  }
}
```

### Reactive Data

```vue
<script>
import { ref, computed, watch } from 'vue'

export default {
  props: ['card'],
  
  setup(props) {
    const loading = ref(false)
    const error = ref(null)
    
    // Computed properties
    const cardData = computed(() => props.card.meta.data)
    const isRefreshable = computed(() => props.card.meta.autoRefresh)
    
    // Watch for changes
    watch(() => props.card.meta.data, (newData) => {
      // Handle data updates
    })
    
    return {
      loading,
      error,
      cardData,
      isRefreshable
    }
  }
}
</script>
```

## Advanced Features

### Auto-Refresh

```php
class LiveStatsCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'autoRefresh' => true,
            'refreshInterval' => 10, // 10 seconds
            'data' => $this->getLiveData(),
        ]);
    }
}
```

### Export Functionality

```php
class ExportableCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'exportable' => true,
            'exportFormats' => ['csv', 'xlsx', 'pdf'],
            'data' => $this->getExportableData(),
        ]);
    }
    
    public function export(string $format): mixed
    {
        // Handle export logic
        switch ($format) {
            case 'csv':
                return $this->exportToCsv();
            case 'xlsx':
                return $this->exportToExcel();
            case 'pdf':
                return $this->exportToPdf();
        }
    }
}
```

### Interactive Cards

```vue
<template>
  <div class="interactive-card">
    <div class="card-actions">
      <button @click="handleAction('refresh')" class="btn-refresh">
        Refresh
      </button>
      <button @click="handleAction('export')" class="btn-export">
        Export
      </button>
    </div>
    
    <div class="card-content" @click="handleCardClick">
      <!-- Card content -->
    </div>
  </div>
</template>

<script>
export default {
  methods: {
    handleAction(action) {
      this.$emit('card-action', {
        action,
        card: this.card
      })
    },
    
    handleCardClick() {
      if (this.card.meta.clickable) {
        this.$emit('card-click', this.card)
      }
    }
  }
}
</script>
```

## Best Practices

### Performance

1. **Cache expensive operations**:
```php
protected function getData(): array
{
    return Cache::remember(
        "card-data-{$this->uriKey}",
        300, // 5 minutes
        fn() => $this->loadExpensiveData()
    );
}
```

2. **Use lazy loading for large datasets**:
```php
$this->withMeta([
    'lazy' => true,
    'loadUrl' => route('api.card.data', $this->uriKey),
]);
```

3. **Optimize database queries**:
```php
protected function getUsers(): Collection
{
    return User::select(['id', 'name', 'email'])
        ->with('profile:user_id,avatar')
        ->limit(10)
        ->get();
}
```

### Security

1. **Always authorize sensitive data**:
```php
public static function make(): static
{
    return parent::make()->canSee(function (Request $request) {
        return $request->user()->can('view-sensitive-data');
    });
}
```

2. **Sanitize user input**:
```php
protected function processUserInput(array $input): array
{
    return [
        'query' => Str::limit(strip_tags($input['query'] ?? ''), 100),
        'filters' => array_intersect_key(
            $input['filters'] ?? [],
            array_flip(['status', 'category', 'date'])
        ),
    ];
}
```

### Maintainability

1. **Use descriptive names**:
```php
class MonthlyRevenueAnalyticsCard extends Card // Good
class Card1 extends Card                       // Bad
```

2. **Extract complex logic to services**:
```php
class AnalyticsCard extends Card
{
    public function __construct(
        private AnalyticsService $analytics
    ) {
        parent::__construct();
        
        $this->withMeta([
            'data' => $this->analytics->getMonthlyData(),
        ]);
    }
}
```

3. **Document complex cards**:
```php
/**
 * Revenue Analytics Card
 * 
 * Displays monthly revenue trends with year-over-year comparison.
 * Includes export functionality and drill-down capabilities.
 * 
 * @requires permission:view-revenue
 * @refresh-interval 300 seconds
 */
class RevenueAnalyticsCard extends Card
{
    // Implementation
}
```

## Troubleshooting

### Common Issues

#### Card Not Appearing

**Problem**: Card is registered but doesn't appear on dashboard.

**Solutions**:
1. Check authorization:
```php
// Add debug logging
public function authorize(Request $request): bool
{
    $result = parent::authorize($request);
    Log::debug("Card authorization for {$this->name()}: " . ($result ? 'allowed' : 'denied'));
    return $result;
}
```

2. Verify registration:
```php
// In dashboard
public function cards(): array
{
    return [
        MyCard::make(), // Ensure make() is called
    ];
}
```

#### Vue Component Not Loading

**Problem**: Card shows but Vue component doesn't render.

**Solutions**:
1. Check component name matches class name
2. Verify component is in correct directory
3. Check for JavaScript errors in console
4. Ensure component is properly exported

#### Data Not Updating

**Problem**: Card data appears stale.

**Solutions**:
1. Clear cache:
```bash
php artisan cache:clear
```

2. Check refresh interval:
```php
$this->withMeta([
    'refreshInterval' => 30, // Ensure reasonable interval
]);
```

3. Verify data loading logic:
```php
protected function getData(): array
{
    // Add logging to debug
    Log::info('Loading card data', ['timestamp' => now()]);
    return $this->actualDataLoadingMethod();
}
```

### Debug Mode

Enable debug mode for detailed card information:

```php
// config/admin-panel.php
'debug' => [
    'cards' => env('APP_DEBUG', false),
],
```

This will add debug information to card meta data and console logs.

### Performance Debugging

Monitor card performance:

```php
class PerformanceCard extends Card
{
    public function __construct()
    {
        $start = microtime(true);
        
        parent::__construct();
        
        $this->withMeta([
            'data' => $this->getData(),
            'load_time' => microtime(true) - $start,
        ]);
    }
}
```

## Next Steps

- **[Cards API Reference](cards-api-reference.md)** - Complete API documentation
- **[Advanced Card Examples](examples/advanced-card-examples.md)** - Complex implementations
- **[Card Testing Guide](testing/card-testing.md)** - Testing strategies
- **[Performance Optimization](performance/card-optimization.md)** - Performance tips
