# Basic Dashboard Example

This example demonstrates how to create a basic dashboard with Phase 3 features.

## Dashboard Class

```php
<?php

namespace App\Dashboards;

use JTD\AdminPanel\Dashboards\Dashboard;
use App\Cards\UserMetrics;
use App\Cards\RevenueChart;
use App\Cards\RecentOrders;

class SalesDashboard extends Dashboard
{
    /**
     * Dashboard name displayed in the UI
     */
    public function name(): string
    {
        return 'Sales Dashboard';
    }

    /**
     * Dashboard description for tooltips and metadata
     */
    public function description(): string
    {
        return 'Track sales performance, revenue, and customer metrics';
    }

    /**
     * Dashboard icon (Heroicons name)
     */
    public function icon(): string
    {
        return 'currency-dollar';
    }

    /**
     * Dashboard category for grouping
     */
    public function category(): string
    {
        return 'Sales';
    }

    /**
     * Dashboard cards/widgets
     */
    public function cards(): array
    {
        return [
            new UserMetrics(),
            new RevenueChart(),
            new RecentOrders(),
        ];
    }

    /**
     * Dashboard metadata for enhanced features
     */
    public function meta(): array
    {
        return [
            'refreshInterval' => 60000, // 1 minute
            'priority' => 5,
            'tags' => ['sales', 'revenue', 'customers'],
            'color' => '#10B981',
            'responsive' => true,
            'mobile' => true,
            'autoRefresh' => true
        ];
    }

    /**
     * Check if user is authorized to see this dashboard
     */
    public function authorizedToSee($request): bool
    {
        return $request->user()->can('view-sales-dashboard');
    }

    /**
     * Dashboard caching options
     */
    public function cacheOptions(): array
    {
        return [
            'enabled' => true,
            'ttl' => 300, // 5 minutes
            'strategy' => 'stale-while-revalidate',
            'tags' => ['dashboard', 'sales'],
            'key' => fn($request) => "dashboard.sales.{$request->user()->id}"
        ];
    }

    /**
     * Dashboard switching options
     */
    public function switchingOptions(): array
    {
        return [
            'transition' => 'slide-fade',
            'duration' => 300,
            'preload' => true,
            'persistState' => true,
            'showLoadingIndicator' => true
        ];
    }

    /**
     * Dashboard selector options
     */
    public function selectorOptions(): array
    {
        return [
            'showInSelector' => true,
            'selectorIcon' => 'currency-dollar',
            'selectorColor' => '#10B981',
            'selectorBadge' => null,
            'selectorOrder' => 5
        ];
    }
}
```

## Dashboard Registration

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use JTD\AdminPanel\Support\DashboardRegistry;
use App\Dashboards\SalesDashboard;

class DashboardServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register the dashboard
        DashboardRegistry::register('sales', new SalesDashboard());
    }
}
```

## Nova Menu Integration

```php
<?php

namespace App\Providers;

use Laravel\Nova\NovaApplicationServiceProvider;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    public function boot()
    {
        parent::boot();

        Nova::mainMenu(function (Request $request) {
            return [
                MenuSection::dashboard('Sales Dashboard', 'sales')
                    ->icon('currency-dollar')
                    ->badge('Live'),

                MenuSection::make('Analytics', [
                    MenuItem::dashboard('Sales Dashboard', 'sales')
                        ->icon('currency-dollar'),
                    MenuItem::dashboard('User Analytics', 'users')
                        ->icon('users'),
                    MenuItem::dashboard('Performance', 'performance')
                        ->icon('lightning-bolt')
                ])->icon('chart-bar')->collapsible(),
            ];
        });
    }
}
```

## Vue Component Usage

```vue
<template>
  <div class="dashboard-container">
    <!-- Enhanced Dashboard Selector -->
    <DashboardSelector
      :dashboards="dashboards"
      :current-dashboard="currentDashboard"
      :loading="loading"
      :show-search="true"
      :show-categories="true"
      :show-favorites="true"
      :show-recent="true"
      :max-recent="5"
      @dashboard-selected="handleDashboardSelect"
      @dashboard-favorited="handleFavoriteToggle"
    />

    <!-- Dashboard Navigation -->
    <DashboardNavigation
      :current-dashboard="currentDashboard"
      :navigation-history="navigationHistory"
      :show-breadcrumbs="true"
      :show-back-button="true"
      :show-refresh-button="true"
      @navigate-back="handleBack"
      @refresh="handleRefresh"
    />

    <!-- Dashboard Content with Switcher -->
    <DashboardSwitcher
      :current-dashboard="currentDashboard"
      :transition-name="'slide-fade'"
      :loading="loading"
      :error="error"
      @before-switch="handleBeforeSwitch"
      @after-switch="handleAfterSwitch"
    >
      <component :is="dashboardComponent" />
    </DashboardSwitcher>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import { useDashboardNavigation } from '@/composables/useDashboardNavigation'
import DashboardSelector from '@/Components/Dashboard/DashboardSelector.vue'
import DashboardNavigation from '@/Components/Dashboard/DashboardNavigation.vue'
import DashboardSwitcher from '@/Components/Dashboard/DashboardSwitcher.vue'

export default {
  name: 'DashboardContainer',
  components: {
    DashboardSelector,
    DashboardNavigation,
    DashboardSwitcher
  },
  setup() {
    const dashboardStore = useDashboardStore()
    const navigation = useDashboardNavigation()

    // Reactive state
    const loading = ref(false)
    const error = ref(null)

    // Computed properties
    const dashboards = computed(() => dashboardStore.dashboards)
    const currentDashboard = computed(() => dashboardStore.currentDashboard)
    const navigationHistory = computed(() => dashboardStore.navigationHistory)
    const dashboardComponent = computed(() => {
      if (!currentDashboard.value) return null
      return defineAsyncComponent(() => 
        import(`@/Dashboards/${currentDashboard.value.component}.vue`)
      )
    })

    // Event handlers
    const handleDashboardSelect = async (dashboard) => {
      loading.value = true
      error.value = null
      
      try {
        await dashboardStore.switchToDashboard(dashboard.uriKey)
      } catch (err) {
        error.value = err.message
      } finally {
        loading.value = false
      }
    }

    const handleFavoriteToggle = (dashboard) => {
      dashboardStore.toggleFavorite(dashboard.uriKey)
    }

    const handleBack = () => {
      navigation.goBack()
    }

    const handleRefresh = async () => {
      loading.value = true
      try {
        await dashboardStore.refreshCurrentDashboard()
      } catch (err) {
        error.value = err.message
      } finally {
        loading.value = false
      }
    }

    const handleBeforeSwitch = (from, to) => {
      console.log('Switching from', from?.name, 'to', to?.name)
    }

    const handleAfterSwitch = (dashboard) => {
      console.log('Switched to', dashboard?.name)
      // Update page title
      document.title = `${dashboard?.name} - Admin Panel`
    }

    // Initialize
    onMounted(async () => {
      loading.value = true
      try {
        await dashboardStore.loadDashboards()
        
        // Load default dashboard if none selected
        if (!currentDashboard.value && dashboards.value.length > 0) {
          await dashboardStore.switchToDashboard(dashboards.value[0].uriKey)
        }
      } catch (err) {
        error.value = err.message
      } finally {
        loading.value = false
      }
    })

    return {
      // State
      loading,
      error,
      
      // Computed
      dashboards,
      currentDashboard,
      navigationHistory,
      dashboardComponent,
      
      // Methods
      handleDashboardSelect,
      handleFavoriteToggle,
      handleBack,
      handleRefresh,
      handleBeforeSwitch,
      handleAfterSwitch
    }
  }
}
</script>

<style scoped>
.dashboard-container {
  @apply min-h-screen bg-gray-50;
}

/* Dashboard transitions */
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-fade-enter-from {
  transform: translateX(30px);
  opacity: 0;
}

.slide-fade-leave-to {
  transform: translateX(-30px);
  opacity: 0;
}
</style>
```

## Dashboard Cards

### User Metrics Card

```php
<?php

namespace App\Cards;

use Laravel\Nova\Cards\Card;
use App\Models\User;

class UserMetrics extends Card
{
    public function component()
    {
        return 'user-metrics-card';
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'title' => 'User Metrics',
            'data' => [
                'total_users' => User::count(),
                'active_users' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
                'new_users_today' => User::whereDate('created_at', today())->count(),
                'growth_rate' => $this->calculateGrowthRate()
            ]
        ]);
    }

    private function calculateGrowthRate()
    {
        $thisMonth = User::whereMonth('created_at', now()->month)->count();
        $lastMonth = User::whereMonth('created_at', now()->subMonth()->month)->count();
        
        if ($lastMonth === 0) return 0;
        
        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }
}
```

### Revenue Chart Card

```php
<?php

namespace App\Cards;

use Laravel\Nova\Cards\Card;
use App\Models\Order;

class RevenueChart extends Card
{
    public function component()
    {
        return 'revenue-chart-card';
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'title' => 'Revenue Chart',
            'data' => [
                'total_revenue' => Order::sum('total'),
                'monthly_revenue' => $this->getMonthlyRevenue(),
                'chart_data' => $this->getChartData()
            ]
        ]);
    }

    private function getMonthlyRevenue()
    {
        return Order::whereMonth('created_at', now()->month)
                   ->whereYear('created_at', now()->year)
                   ->sum('total');
    }

    private function getChartData()
    {
        return Order::selectRaw('DATE(created_at) as date, SUM(total) as revenue')
                   ->where('created_at', '>=', now()->subDays(30))
                   ->groupBy('date')
                   ->orderBy('date')
                   ->get()
                   ->map(function ($item) {
                       return [
                           'date' => $item->date,
                           'revenue' => (float) $item->revenue
                       ];
                   });
    }
}
```

## Usage in Blade Templates

```blade
{{-- resources/views/admin/dashboard.blade.php --}}
@extends('admin-panel::layouts.app')

@section('content')
<div id="dashboard-app">
    <dashboard-container></dashboard-container>
</div>
@endsection

@push('scripts')
<script>
// Initialize dashboard app
const { createApp } = Vue
const { createPinia } = Pinia

const app = createApp({})
const pinia = createPinia()

app.use(pinia)
app.mount('#dashboard-app')
</script>
@endpush
```

## Configuration

```php
// config/admin-panel.php
return [
    'dashboards' => [
        'default' => 'sales',
        'cache_ttl' => 300,
        'enable_favorites' => true,
        'enable_recent' => true,
        'max_recent' => 10,
        'enable_search' => true,
        'enable_categories' => true,
        'enable_keyboard_shortcuts' => true,
        'transition_duration' => 300,
        'preload_dashboards' => true,
        'performance_monitoring' => true
    ]
];
```

This basic example demonstrates:

1. **Dashboard Class Creation**: Complete dashboard with metadata and options
2. **Registration**: How to register dashboards in service providers
3. **Nova Integration**: Menu integration with Nova
4. **Vue Components**: Using enhanced dashboard components
5. **State Management**: Pinia store integration
6. **Navigation**: Dashboard switching and history
7. **Cards**: Creating dashboard cards with data
8. **Configuration**: Basic configuration options

For more advanced examples, see:
- [Advanced Dashboard Example](./advanced-dashboard-example.md)
- [Mobile Dashboard Example](./mobile-dashboard-example.md)
- [Performance Optimized Dashboard](./performance-dashboard-example.md)
