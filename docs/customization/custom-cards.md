# Building Custom Dashboard Cards

## Overview

Custom cards are the primary way to extend AdminPanel dashboards with your own functionality. This guide covers everything from basic card creation to advanced customization techniques.

## Quick Start

### Generate a New Card

```bash
# Basic card
php artisan admin-panel:make-card MyCard

# Card with template
php artisan admin-panel:make-card AnalyticsCard --template=analytics

# Card with specific options
php artisan admin-panel:make-card StatsCard --exportable --refreshable
```

### Basic Card Structure

```php
<?php

namespace App\Admin\Cards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;

class MyCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'title' => 'My Custom Card',
            'data' => $this->loadData(),
        ]);
    }
    
    protected function loadData(): array
    {
        return [
            'metric' => 42,
            'trend' => '+12%',
        ];
    }
}
```

## Card Architecture

### PHP Backend

The PHP card class handles:
- Data loading and processing
- Authorization logic
- Metadata configuration
- API endpoints (optional)

### Vue Frontend

The Vue component handles:
- Data presentation
- User interactions
- Real-time updates
- Responsive design

### Communication Flow

```
Dashboard → Card Class → Vue Component → User Interface
    ↑           ↓            ↓              ↓
Authorization  Data      Rendering    Interactions
```

## Advanced Card Features

### Real-Time Updates

```php
class LiveStatsCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'title' => 'Live Statistics',
            'data' => $this->getLiveData(),
            'autoRefresh' => true,
            'refreshInterval' => 5, // 5 seconds
            'websocket' => [
                'channel' => 'stats-updates',
                'event' => 'stats.updated',
            ],
        ]);
    }
    
    protected function getLiveData(): array
    {
        return [
            'active_users' => $this->getActiveUsers(),
            'current_sales' => $this->getCurrentSales(),
            'server_load' => $this->getServerLoad(),
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

### Interactive Cards

```php
class InteractiveCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'title' => 'Interactive Dashboard',
            'data' => $this->getData(),
            'interactive' => true,
            'actions' => [
                'refresh' => 'Refresh Data',
                'export' => 'Export CSV',
                'filter' => 'Apply Filters',
            ],
        ]);
    }
    
    public function handleAction(string $action, array $params = []): array
    {
        return match($action) {
            'refresh' => $this->refreshData(),
            'export' => $this->exportData($params),
            'filter' => $this->filterData($params),
            default => ['error' => 'Unknown action'],
        };
    }
}
```

### Data Visualization Cards

```php
class ChartCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'title' => 'Sales Analytics',
            'chart' => [
                'type' => 'line',
                'data' => $this->getChartData(),
                'options' => $this->getChartOptions(),
            ],
            'exportable' => true,
            'fullscreen' => true,
        ]);
    }
    
    protected function getChartData(): array
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => [12, 19, 3, 5, 2, 3],
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1,
                ],
            ],
        ];
    }
    
    protected function getChartOptions(): array
    {
        return [
            'responsive' => true,
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'Monthly Sales Data',
                ],
            ],
        ];
    }
}
```

## Vue Component Development

### Basic Component Structure

```vue
<template>
  <div class="custom-card">
    <div class="card-header">
      <h3 class="card-title">{{ card.meta.title }}</h3>
      <div class="card-actions">
        <button @click="refresh" :disabled="loading">
          <RefreshIcon :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>
    
    <div class="card-body">
      <div v-if="error" class="error-message">
        {{ error }}
      </div>
      
      <div v-else-if="loading" class="loading-spinner">
        Loading...
      </div>
      
      <div v-else class="card-content">
        <!-- Your card content here -->
        <div class="metric">
          <span class="value">{{ card.meta.data.metric }}</span>
          <span class="trend">{{ card.meta.data.trend }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import { RefreshIcon } from '@heroicons/vue/24/outline'

export default {
  name: 'MyCard',
  
  components: {
    RefreshIcon
  },
  
  props: {
    card: {
      type: Object,
      required: true
    }
  },
  
  setup(props) {
    const loading = ref(false)
    const error = ref(null)
    let refreshInterval = null
    
    const refresh = async () => {
      loading.value = true
      error.value = null
      
      try {
        // Refresh logic here
        await refreshCardData()
      } catch (err) {
        error.value = err.message
      } finally {
        loading.value = false
      }
    }
    
    const refreshCardData = async () => {
      // Implementation depends on your refresh strategy
    }
    
    onMounted(() => {
      if (props.card.meta.autoRefresh) {
        refreshInterval = setInterval(
          refresh,
          (props.card.meta.refreshInterval || 30) * 1000
        )
      }
    })
    
    onUnmounted(() => {
      if (refreshInterval) {
        clearInterval(refreshInterval)
      }
    })
    
    return {
      loading,
      error,
      refresh
    }
  }
}
</script>

<style scoped>
.custom-card {
  @apply bg-white rounded-lg shadow-sm border border-gray-200;
}

.card-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200;
}

.card-title {
  @apply text-lg font-semibold text-gray-900;
}

.card-actions {
  @apply flex space-x-2;
}

.card-body {
  @apply p-4;
}

.metric {
  @apply text-center;
}

.value {
  @apply block text-3xl font-bold text-blue-600;
}

.trend {
  @apply text-sm text-green-600;
}

.error-message {
  @apply text-red-600 text-sm;
}

.loading-spinner {
  @apply text-gray-500 text-center;
}
</style>
```

### Advanced Vue Features

#### Composables for Card Logic

```javascript
// composables/useCardData.js
import { ref, computed } from 'vue'

export function useCardData(card) {
  const data = ref(card.meta.data)
  const loading = ref(false)
  const error = ref(null)
  const lastUpdated = ref(new Date())
  
  const isStale = computed(() => {
    const staleThreshold = 5 * 60 * 1000 // 5 minutes
    return Date.now() - lastUpdated.value.getTime() > staleThreshold
  })
  
  const reload = async () => {
    loading.value = true
    error.value = null
    
    try {
      const response = await fetch(`/api/cards/${card.uriKey}/data`)
      const result = await response.json()
      
      data.value = result.data
      lastUpdated.value = new Date()
    } catch (err) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }
  
  return {
    data,
    loading,
    error,
    lastUpdated,
    isStale,
    reload
  }
}
```

#### WebSocket Integration

```javascript
// composables/useWebSocket.js
import { ref, onMounted, onUnmounted } from 'vue'

export function useWebSocket(card) {
  const connected = ref(false)
  const data = ref(card.meta.data)
  let socket = null
  
  onMounted(() => {
    if (card.meta.websocket) {
      connectWebSocket()
    }
  })
  
  onUnmounted(() => {
    if (socket) {
      socket.close()
    }
  })
  
  const connectWebSocket = () => {
    const { channel, event } = card.meta.websocket
    
    socket = new WebSocket(`ws://localhost:6001/app/${channel}`)
    
    socket.onopen = () => {
      connected.value = true
    }
    
    socket.onmessage = (message) => {
      const payload = JSON.parse(message.data)
      if (payload.event === event) {
        data.value = payload.data
      }
    }
    
    socket.onclose = () => {
      connected.value = false
    }
  }
  
  return {
    connected,
    data
  }
}
```

## Card Templates

### Analytics Template

```bash
php artisan admin-panel:make-card MyAnalytics --template=analytics
```

Creates a comprehensive analytics card with:
- Multiple metrics display
- Chart visualization
- Export functionality
- Real-time updates
- Responsive design

### Stats Template

```bash
php artisan admin-panel:make-card MyStats --template=stats
```

Creates a simple statistics card with:
- Key metrics
- Trend indicators
- Clean layout
- Basic interactions

### Table Template

```bash
php artisan admin-panel:make-card MyTable --template=table
```

Creates a data table card with:
- Sortable columns
- Pagination
- Search functionality
- Export options

## Styling and Theming

### CSS Classes

AdminPanel provides utility classes for consistent styling:

```css
/* Card containers */
.admin-card { /* Base card styles */ }
.admin-card-header { /* Header styles */ }
.admin-card-body { /* Body styles */ }
.admin-card-footer { /* Footer styles */ }

/* Metrics */
.metric-value { /* Large metric numbers */ }
.metric-label { /* Metric descriptions */ }
.metric-trend { /* Trend indicators */ }

/* States */
.loading { /* Loading state */ }
.error { /* Error state */ }
.empty { /* Empty state */ }
```

### Dark Mode Support

```vue
<template>
  <div class="card dark:bg-gray-800 dark:border-gray-700">
    <h3 class="text-gray-900 dark:text-gray-100">
      {{ card.meta.title }}
    </h3>
  </div>
</template>

<style scoped>
.card {
  @apply bg-white border-gray-200;
}

@media (prefers-color-scheme: dark) {
  .card {
    @apply bg-gray-800 border-gray-700;
  }
}
</style>
```

### Custom Themes

```php
// In your card class
$this->withMeta([
    'theme' => [
        'primary' => '#3b82f6',
        'secondary' => '#6b7280',
        'success' => '#10b981',
        'warning' => '#f59e0b',
        'error' => '#ef4444',
    ],
]);
```

## Performance Optimization

### Caching Strategies

```php
class OptimizedCard extends Card
{
    protected function getData(): array
    {
        return Cache::remember(
            $this->getCacheKey(),
            $this->getCacheDuration(),
            fn() => $this->loadExpensiveData()
        );
    }
    
    protected function getCacheKey(): string
    {
        return "card-{$this->uriKey}-" . auth()->id();
    }
    
    protected function getCacheDuration(): int
    {
        return 300; // 5 minutes
    }
}
```

### Lazy Loading

```php
$this->withMeta([
    'lazy' => true,
    'loadUrl' => route('api.card.data', $this->uriKey),
    'placeholder' => 'Loading card data...',
]);
```

### Database Optimization

```php
protected function getUsers(): Collection
{
    return User::select(['id', 'name', 'email', 'created_at'])
        ->with('profile:user_id,avatar')
        ->whereActive()
        ->limit(50)
        ->get();
}
```

## Testing Custom Cards

### Unit Tests

```php
use Tests\TestCase;
use App\Admin\Cards\MyCard;

class MyCardTest extends TestCase
{
    public function test_card_loads_data()
    {
        $card = MyCard::make();
        $data = $card->meta()['data'];
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('metric', $data);
    }
    
    public function test_card_authorization()
    {
        $user = User::factory()->admin()->create();
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn() => $user);
        
        $card = MyCard::make();
        
        $this->assertTrue($card->authorize($request));
    }
}
```

### Vue Component Tests

```javascript
import { mount } from '@vue/test-utils'
import MyCard from '@/components/Cards/MyCard.vue'

describe('MyCard', () => {
  it('renders card title', () => {
    const card = {
      meta: {
        title: 'Test Card',
        data: { metric: 42 }
      }
    }
    
    const wrapper = mount(MyCard, {
      props: { card }
    })
    
    expect(wrapper.text()).toContain('Test Card')
  })
  
  it('displays metric value', () => {
    const card = {
      meta: {
        title: 'Test Card',
        data: { metric: 42 }
      }
    }
    
    const wrapper = mount(MyCard, {
      props: { card }
    })
    
    expect(wrapper.text()).toContain('42')
  })
})
```

## Best Practices

### Security

1. **Always validate user input**
2. **Implement proper authorization**
3. **Sanitize data before display**
4. **Use CSRF protection for actions**

### Performance

1. **Cache expensive operations**
2. **Use database indexes**
3. **Implement lazy loading**
4. **Optimize Vue components**

### Maintainability

1. **Use descriptive names**
2. **Document complex logic**
3. **Follow consistent patterns**
4. **Write comprehensive tests**

### User Experience

1. **Provide loading states**
2. **Handle errors gracefully**
3. **Use responsive design**
4. **Include helpful tooltips**

## Common Patterns

### Master-Detail Cards

```php
class MasterDetailCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'title' => 'Sales Overview',
            'master' => $this->getMasterData(),
            'detail' => null, // Loaded on demand
            'detailUrl' => route('api.card.detail', $this->uriKey),
        ]);
    }
}
```

### Filter Cards

```php
class FilterableCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'title' => 'Filtered Data',
            'filters' => $this->getAvailableFilters(),
            'data' => $this->getFilteredData(),
        ]);
    }
    
    public function applyFilters(array $filters): array
    {
        return [
            'data' => $this->getFilteredData($filters),
            'meta' => [
                'total' => $this->getTotal($filters),
                'filtered' => true,
            ],
        ];
    }
}
```

### Export Cards

```php
class ExportableCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'title' => 'Exportable Data',
            'data' => $this->getData(),
            'exportable' => true,
            'exportFormats' => ['csv', 'xlsx', 'pdf'],
        ]);
    }
    
    public function export(string $format): mixed
    {
        return match($format) {
            'csv' => $this->exportToCsv(),
            'xlsx' => $this->exportToExcel(),
            'pdf' => $this->exportToPdf(),
        };
    }
}
```

## Troubleshooting

### Common Issues

1. **Card not appearing**: Check authorization and registration
2. **Vue component not loading**: Verify component name and path
3. **Data not updating**: Check cache settings and refresh logic
4. **Styling issues**: Verify CSS classes and theme configuration

### Debug Tools

Enable debug mode for detailed information:

```php
// config/admin-panel.php
'debug' => [
    'cards' => true,
],
```

This adds debug information to card metadata and browser console.

## Next Steps

- **[Cards API Reference](../cards-api-reference.md)** - Complete API documentation
- **[Advanced Examples](../examples/advanced-card-examples.md)** - Complex implementations
- **[Performance Guide](../performance/card-optimization.md)** - Optimization techniques
- **[Testing Guide](../testing/card-testing.md)** - Testing strategies
