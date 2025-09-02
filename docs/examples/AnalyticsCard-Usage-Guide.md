# AnalyticsCard Usage Guide

This guide walks you through integrating the AnalyticsCard into your Laravel application, from basic setup to advanced customization.

## Quick Start

### 1. Install the AdminPanel Package

```bash
composer require jerthedev/admin-panel
```

### 2. Publish the Configuration

```bash
php artisan vendor:publish --provider="JTD\AdminPanel\AdminPanelServiceProvider" --tag="config"
```

### 3. Register the AnalyticsCard

In your `AdminServiceProvider` or `AppServiceProvider`:

```php
use JTD\AdminPanel\Cards\Examples\AnalyticsCard;
use JTD\AdminPanel\Support\AdminPanel;

public function boot()
{
    AdminPanel::cards([
        AnalyticsCard::class,
    ]);
}
```

### 4. Add to Dashboard

In your dashboard configuration:

```php
// config/admin-panel.php
'dashboards' => [
    'default' => [
        'cards' => [
            AnalyticsCard::class,
        ],
    ],
],
```

## Step-by-Step Integration

### Step 1: Basic Integration

Start with the simplest integration:

```php
// app/Providers/AdminServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use JTD\AdminPanel\Cards\Examples\AnalyticsCard;
use JTD\AdminPanel\Support\AdminPanel;

class AdminServiceProvider extends ServiceProvider
{
    public function boot()
    {
        AdminPanel::cards([
            AnalyticsCard::class,
        ]);
    }
}
```

### Step 2: Add Authorization

Restrict access to specific users:

```php
AdminPanel::cards([
    AnalyticsCard::adminOnly(),
]);

// Or role-based
AdminPanel::cards([
    AnalyticsCard::forRole('manager'),
]);

// Or custom logic
AdminPanel::cards([
    AnalyticsCard::make()->canSee(function ($request) {
        return $request->user()->hasPermission('view-analytics');
    }),
]);
```

### Step 3: Configure Date Ranges

Set up date range filtering:

```php
AdminPanel::cards([
    AnalyticsCard::withDateRange(
        now()->subDays(30)->toDateString(),
        now()->toDateString()
    ),
]);
```

### Step 4: Customize Metrics

Select specific metrics to display:

```php
AdminPanel::cards([
    AnalyticsCard::withMetrics([
        'totalUsers',
        'activeUsers',
        'revenue',
    ]),
]);
```

## Real-World Implementation

### Connecting to Google Analytics

Replace mock data with real Google Analytics data:

```php
<?php

namespace App\Admin\Cards;

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Examples\AnalyticsCard as BaseAnalyticsCard;

class GoogleAnalyticsCard extends BaseAnalyticsCard
{
    protected BetaAnalyticsDataClient $analyticsClient;

    public function __construct()
    {
        parent::__construct();
        
        $this->analyticsClient = new BetaAnalyticsDataClient([
            'credentials' => config('services.google.analytics.credentials'),
        ]);
    }

    protected function getTotalUsers(): int
    {
        $response = $this->analyticsClient->runReport([
            'property' => 'properties/' . config('services.google.analytics.property_id'),
            'dateRanges' => [
                [
                    'startDate' => '30daysAgo',
                    'endDate' => 'today',
                ],
            ],
            'metrics' => [
                ['name' => 'totalUsers'],
            ],
        ]);

        return (int) $response->getRows()[0]->getMetricValues()[0]->getValue();
    }

    protected function getPageViews(): int
    {
        $response = $this->analyticsClient->runReport([
            'property' => 'properties/' . config('services.google.analytics.property_id'),
            'dateRanges' => [
                [
                    'startDate' => '30daysAgo',
                    'endDate' => 'today',
                ],
            ],
            'metrics' => [
                ['name' => 'screenPageViews'],
            ],
        ]);

        return (int) $response->getRows()[0]->getMetricValues()[0]->getValue();
    }
}
```

### Database-Driven Analytics

Connect to your application's database:

```php
<?php

namespace App\Admin\Cards;

use App\Models\Order;
use App\Models\PageView;
use App\Models\User;
use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Examples\AnalyticsCard as BaseAnalyticsCard;

class DatabaseAnalyticsCard extends BaseAnalyticsCard
{
    protected function getTotalUsers(): int
    {
        return User::count();
    }

    protected function getActiveUsers(): int
    {
        return User::where('last_login_at', '>=', now()->subDays(30))->count();
    }

    protected function getPageViews(): int
    {
        return PageView::where('created_at', '>=', now()->subDays(30))->count();
    }

    protected function getRevenue(): float
    {
        return Order::where('created_at', '>=', now()->subDays(30))
            ->where('status', 'completed')
            ->sum('total');
    }

    protected function getTopPages(): array
    {
        return PageView::select('path')
            ->selectRaw('COUNT(*) as views')
            ->selectRaw('(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM page_views WHERE created_at >= ?)) as percentage', [now()->subDays(30)])
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(5)
            ->get()
            ->map(function ($page) {
                return [
                    'path' => $page->path,
                    'views' => $page->views,
                    'percentage' => round($page->percentage, 1),
                ];
            })
            ->toArray();
    }
}
```

### Caching for Performance

Add caching to improve performance:

```php
<?php

namespace App\Admin\Cards;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Cards\Examples\AnalyticsCard as BaseAnalyticsCard;

class CachedAnalyticsCard extends BaseAnalyticsCard
{
    public function data(Request $request): array
    {
        return Cache::remember(
            'analytics-card-data',
            now()->addMinutes(15),
            function () use ($request) {
                return parent::data($request);
            }
        );
    }

    protected function getTotalUsers(): int
    {
        return Cache::remember('analytics-total-users', now()->addHour(), function () {
            return User::count();
        });
    }

    protected function getActiveUsers(): int
    {
        return Cache::remember('analytics-active-users', now()->addMinutes(30), function () {
            return User::where('last_login_at', '>=', now()->subDays(30))->count();
        });
    }
}
```

## Advanced Customization

### Custom Vue Component

Create a custom Vue component for unique visualizations:

```vue
<!-- resources/js/admin-cards/CustomAnalyticsCard.vue -->
<template>
  <div class="custom-analytics-card">
    <div class="card-header">
      <h3>{{ card.title }}</h3>
      <button @click="refresh" :disabled="loading">
        <RefreshIcon :class="{ 'animate-spin': loading }" />
      </button>
    </div>
    
    <div class="metrics-grid">
      <MetricCard
        v-for="metric in metrics"
        :key="metric.key"
        :title="metric.title"
        :value="metric.value"
        :change="metric.change"
        :trend="metric.trend"
      />
    </div>
    
    <div class="charts-section">
      <LineChart :data="card.data.userGrowth" />
      <PieChart :data="card.data.deviceBreakdown" />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import LineChart from '@/components/charts/LineChart.vue'
import PieChart from '@/components/charts/PieChart.vue'
import MetricCard from '@/components/cards/MetricCard.vue'
import RefreshIcon from '@/components/icons/RefreshIcon.vue'

const props = defineProps(['card'])
const emit = defineEmits(['refresh'])

const loading = ref(false)

const metrics = computed(() => [
  {
    key: 'totalUsers',
    title: 'Total Users',
    value: props.card.data.totalUsers,
    change: '+12%',
    trend: 'up'
  },
  {
    key: 'activeUsers',
    title: 'Active Users',
    value: props.card.data.activeUsers,
    change: '+8%',
    trend: 'up'
  },
  // ... more metrics
])

const refresh = async () => {
  loading.value = true
  try {
    emit('refresh')
  } finally {
    loading.value = false
  }
}
</script>
```

### Multiple Card Variants

Create different variants for different use cases:

```php
// In your AdminServiceProvider
AdminPanel::cards([
    // Executive dashboard
    AnalyticsCard::make()
        ->withMeta(['title' => 'Executive Summary'])
        ->withMetrics(['revenue', 'conversionRate'])
        ->canSee(fn($request) => $request->user()->hasRole('executive')),
    
    // Marketing dashboard
    AnalyticsCard::make()
        ->withMeta(['title' => 'Marketing Metrics'])
        ->withMetrics(['totalUsers', 'activeUsers', 'topPages'])
        ->canSee(fn($request) => $request->user()->hasRole('marketing')),
    
    // Operations dashboard
    AnalyticsCard::make()
        ->withMeta(['title' => 'Operations Overview'])
        ->withMetrics(['pageViews', 'deviceBreakdown'])
        ->canSee(fn($request) => $request->user()->hasRole('operations')),
]);
```

## Testing Your Implementation

### Unit Tests

```php
<?php

namespace Tests\Unit\Cards;

use App\Admin\Cards\DatabaseAnalyticsCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseAnalyticsCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_correct_user_count()
    {
        // Create test users
        User::factory()->count(10)->create();
        
        $card = new DatabaseAnalyticsCard;
        $data = $card->data(request());
        
        $this->assertEquals(10, $data['totalUsers']);
    }
}
```

### Feature Tests

```php
<?php

namespace Tests\Feature\Cards;

use App\Admin\Cards\DatabaseAnalyticsCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseAnalyticsCardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_displays_on_dashboard()
    {
        $this->actingAs($this->createAdminUser())
            ->get('/admin/dashboard')
            ->assertSee('Analytics Overview')
            ->assertSee('Total Users');
    }
}
```

## Deployment Considerations

### Environment Configuration

```env
# .env
ANALYTICS_CACHE_TTL=900
ANALYTICS_REFRESH_INTERVAL=30
GOOGLE_ANALYTICS_PROPERTY_ID=123456789
GOOGLE_ANALYTICS_CREDENTIALS_PATH=/path/to/credentials.json
```

### Performance Optimization

1. **Enable caching** for expensive queries
2. **Use database indexes** on frequently queried columns
3. **Implement pagination** for large datasets
4. **Use queue jobs** for heavy analytics processing

### Security

1. **Validate all inputs** in custom data methods
2. **Implement proper authorization** for sensitive data
3. **Use environment variables** for API credentials
4. **Log access** to analytics data

## Troubleshooting

### Common Issues

**Card not showing data**
- Check database connections
- Verify data method implementations
- Review authorization logic

**Performance issues**
- Enable caching
- Optimize database queries
- Use database indexes

**Authorization problems**
- Check user roles and permissions
- Verify canSee callback logic
- Review middleware configuration

### Debug Tips

```php
// Enable debug mode
$card = new AnalyticsCard;
$card->withMeta(['debug' => true]);

// Log data for debugging
Log::info('Analytics Card Data', $card->data(request()));
```

## Next Steps

1. **Customize the data sources** to match your application
2. **Add more metrics** specific to your business
3. **Create custom Vue components** for unique visualizations
4. **Implement real-time updates** with WebSockets
5. **Add export functionality** for reports

For more advanced features and customization options, refer to the main AdminPanel documentation.
