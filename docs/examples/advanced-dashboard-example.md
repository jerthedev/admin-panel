# Advanced Dashboard Example

This example demonstrates advanced Phase 3 features including real-time updates, custom filters, advanced caching, and performance optimization.

## Advanced Dashboard Class

```php
<?php

namespace App\Dashboards;

use JTD\AdminPanel\Dashboards\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Cards\AdvancedMetrics;
use App\Cards\RealTimeChart;
use App\Cards\FilterableTable;
use App\Cards\CustomWidget;

class AdvancedAnalyticsDashboard extends Dashboard
{
    public function name(): string
    {
        return 'Advanced Analytics';
    }

    public function description(): string
    {
        return 'Real-time analytics with advanced filtering and customization';
    }

    public function icon(): string
    {
        return 'chart-pie';
    }

    public function category(): string
    {
        return 'Analytics';
    }

    public function cards(): array
    {
        return [
            new AdvancedMetrics(),
            new RealTimeChart(),
            new FilterableTable(),
            new CustomWidget(),
        ];
    }

    public function meta(): array
    {
        return [
            'refreshInterval' => 15000, // 15 seconds for real-time
            'priority' => 10,
            'tags' => ['analytics', 'real-time', 'advanced'],
            'color' => '#8B5CF6',
            'responsive' => true,
            'mobile' => true,
            'autoRefresh' => true,
            'realTime' => true,
            'customizable' => true,
            'exportable' => true,
            'filters' => [
                'date_range' => [
                    'type' => 'daterange',
                    'default' => 'last_30_days',
                    'options' => ['today', 'yesterday', 'last_7_days', 'last_30_days', 'custom']
                ],
                'category' => [
                    'type' => 'select',
                    'default' => 'all',
                    'options' => $this->getCategoryOptions()
                ],
                'status' => [
                    'type' => 'multiselect',
                    'default' => ['active', 'pending'],
                    'options' => ['active', 'pending', 'completed', 'cancelled']
                ]
            ]
        ];
    }

    public function authorizedToSee($request): bool
    {
        return $request->user()->hasAnyRole(['admin', 'analyst', 'manager']);
    }

    public function cacheOptions(): array
    {
        return [
            'enabled' => true,
            'ttl' => 60, // 1 minute for real-time data
            'strategy' => 'stale-while-revalidate',
            'tags' => ['dashboard', 'analytics', 'real-time'],
            'key' => function($request) {
                $filters = $request->get('filters', []);
                $filterHash = md5(serialize($filters));
                return "dashboard.analytics.{$request->user()->id}.{$filterHash}";
            },
            'invalidation' => [
                'events' => ['order.created', 'user.updated', 'analytics.updated'],
                'models' => ['App\Models\Order', 'App\Models\User']
            ]
        ];
    }

    public function switchingOptions(): array
    {
        return [
            'transition' => 'fade-slide',
            'duration' => 400,
            'preload' => true,
            'persistState' => true,
            'showLoadingIndicator' => true,
            'loadingMessage' => 'Loading advanced analytics...',
            'errorRetryAttempts' => 3,
            'errorRetryDelay' => 1000
        ];
    }

    public function selectorOptions(): array
    {
        return [
            'showInSelector' => true,
            'selectorIcon' => 'chart-pie',
            'selectorColor' => '#8B5CF6',
            'selectorBadge' => 'Pro',
            'selectorOrder' => 1,
            'selectorDescription' => 'Advanced real-time analytics',
            'selectorTags' => ['real-time', 'advanced']
        ];
    }

    /**
     * Get real-time data for WebSocket updates
     */
    public function getRealTimeData(Request $request): array
    {
        $filters = $request->get('filters', []);
        
        return [
            'timestamp' => now()->toISOString(),
            'metrics' => $this->getMetricsData($filters),
            'chart_data' => $this->getChartData($filters),
            'alerts' => $this->getAlerts($filters)
        ];
    }

    /**
     * Export dashboard data
     */
    public function export(Request $request, string $format = 'csv'): mixed
    {
        $filters = $request->get('filters', []);
        $data = $this->getExportData($filters);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($data);
            case 'excel':
                return $this->exportToExcel($data);
            case 'pdf':
                return $this->exportToPdf($data);
            default:
                return $data;
        }
    }

    /**
     * Get dashboard configuration for customization
     */
    public function getConfiguration(Request $request): array
    {
        $userConfig = Cache::get("dashboard.config.{$request->user()->id}", []);
        
        return array_merge([
            'layout' => 'grid',
            'columns' => 3,
            'card_sizes' => [
                'metrics' => 'small',
                'chart' => 'large',
                'table' => 'medium'
            ],
            'theme' => 'light',
            'auto_refresh' => true,
            'refresh_interval' => 15000
        ], $userConfig);
    }

    /**
     * Save dashboard configuration
     */
    public function saveConfiguration(Request $request, array $config): void
    {
        Cache::put(
            "dashboard.config.{$request->user()->id}",
            $config,
            now()->addDays(30)
        );
    }

    private function getCategoryOptions(): array
    {
        return Cache::remember('dashboard.category.options', 3600, function() {
            return \App\Models\Category::pluck('name', 'id')->toArray();
        });
    }

    private function getMetricsData(array $filters): array
    {
        // Implementation for filtered metrics
        return [
            'total_revenue' => 125000,
            'total_orders' => 1250,
            'conversion_rate' => 3.2,
            'avg_order_value' => 100
        ];
    }

    private function getChartData(array $filters): array
    {
        // Implementation for filtered chart data
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => [10000, 15000, 12000, 18000, 20000]
                ]
            ]
        ];
    }

    private function getAlerts(array $filters): array
    {
        return [
            [
                'type' => 'warning',
                'message' => 'Conversion rate dropped by 15% in the last hour',
                'timestamp' => now()->subMinutes(5)->toISOString()
            ]
        ];
    }
}
```

## Advanced Vue Component

```vue
<template>
  <div class="advanced-dashboard">
    <!-- Dashboard Header with Filters -->
    <div class="dashboard-header">
      <div class="header-content">
        <h1 class="dashboard-title">{{ dashboard.name }}</h1>
        <div class="dashboard-actions">
          <DashboardFilters
            :filters="availableFilters"
            :active-filters="activeFilters"
            @filter-change="handleFilterChange"
          />
          <DashboardExport
            :formats="['csv', 'excel', 'pdf']"
            @export="handleExport"
          />
          <DashboardSettings
            :configuration="configuration"
            @config-change="handleConfigChange"
          />
        </div>
      </div>
    </div>

    <!-- Real-time Status -->
    <div class="realtime-status" v-if="dashboard.meta.realTime">
      <div class="status-indicator" :class="{ 'connected': isConnected }">
        <div class="pulse"></div>
        {{ isConnected ? 'Live' : 'Disconnected' }}
      </div>
      <div class="last-update">
        Last update: {{ formatTime(lastUpdate) }}
      </div>
    </div>

    <!-- Dashboard Alerts -->
    <DashboardAlerts
      :alerts="alerts"
      @alert-dismiss="handleAlertDismiss"
    />

    <!-- Customizable Dashboard Grid -->
    <DashboardGrid
      :cards="dashboardCards"
      :configuration="configuration"
      :loading="loading"
      :real-time-data="realTimeData"
      @card-resize="handleCardResize"
      @card-move="handleCardMove"
      @card-configure="handleCardConfigure"
    />

    <!-- Performance Metrics (Development) -->
    <PerformanceOverlay
      v-if="showPerformanceOverlay"
      :metrics="performanceMetrics"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import { useWebSocket } from '@/composables/useWebSocket'
import { usePerformanceOptimization } from '@/composables/usePerformanceOptimization'
import DashboardFilters from '@/Components/Dashboard/DashboardFilters.vue'
import DashboardExport from '@/Components/Dashboard/DashboardExport.vue'
import DashboardSettings from '@/Components/Dashboard/DashboardSettings.vue'
import DashboardAlerts from '@/Components/Dashboard/DashboardAlerts.vue'
import DashboardGrid from '@/Components/Dashboard/DashboardGrid.vue'
import PerformanceOverlay from '@/Components/Performance/PerformanceOverlay.vue'

export default {
  name: 'AdvancedAnalyticsDashboard',
  components: {
    DashboardFilters,
    DashboardExport,
    DashboardSettings,
    DashboardAlerts,
    DashboardGrid,
    PerformanceOverlay
  },
  props: {
    dashboard: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const dashboardStore = useDashboardStore()
    const performance = usePerformanceOptimization()
    
    // State
    const loading = ref(false)
    const activeFilters = ref({})
    const configuration = ref({})
    const alerts = ref([])
    const realTimeData = ref({})
    const lastUpdate = ref(new Date())

    // WebSocket for real-time updates
    const { 
      isConnected, 
      connect, 
      disconnect, 
      on, 
      emit 
    } = useWebSocket(`/ws/dashboard/${props.dashboard.uriKey}`)

    // Computed
    const availableFilters = computed(() => props.dashboard.meta.filters || {})
    const dashboardCards = computed(() => props.dashboard.cards || [])
    const showPerformanceOverlay = computed(() => 
      process.env.NODE_ENV === 'development' && configuration.value.showPerformance
    )
    const performanceMetrics = computed(() => performance.getMetrics())

    // Methods
    const handleFilterChange = async (filters) => {
      activeFilters.value = filters
      await refreshDashboard()
    }

    const handleExport = async (format) => {
      loading.value = true
      try {
        const response = await fetch(`/admin/api/dashboards/${props.dashboard.uriKey}/export`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({
            format,
            filters: activeFilters.value
          })
        })
        
        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `dashboard-export.${format}`
          a.click()
          window.URL.revokeObjectURL(url)
        }
      } catch (error) {
        console.error('Export failed:', error)
      } finally {
        loading.value = false
      }
    }

    const handleConfigChange = async (newConfig) => {
      configuration.value = { ...configuration.value, ...newConfig }
      
      // Save configuration
      await fetch(`/admin/api/dashboards/${props.dashboard.uriKey}/config`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(configuration.value)
      })
    }

    const handleCardResize = (cardId, newSize) => {
      const updatedConfig = { ...configuration.value }
      updatedConfig.card_sizes = { ...updatedConfig.card_sizes, [cardId]: newSize }
      handleConfigChange(updatedConfig)
    }

    const handleCardMove = (cardId, newPosition) => {
      // Handle card reordering
      emit('card-moved', { cardId, newPosition })
    }

    const handleCardConfigure = (cardId, cardConfig) => {
      // Handle individual card configuration
      emit('card-configured', { cardId, config: cardConfig })
    }

    const handleAlertDismiss = (alertId) => {
      alerts.value = alerts.value.filter(alert => alert.id !== alertId)
    }

    const refreshDashboard = async () => {
      loading.value = true
      try {
        const response = await fetch(`/admin/api/dashboards/${props.dashboard.uriKey}/data`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({
            filters: activeFilters.value
          })
        })
        
        if (response.ok) {
          const data = await response.json()
          realTimeData.value = data
          lastUpdate.value = new Date()
        }
      } catch (error) {
        console.error('Dashboard refresh failed:', error)
      } finally {
        loading.value = false
      }
    }

    const formatTime = (date) => {
      return new Intl.DateTimeFormat('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      }).format(date)
    }

    // WebSocket event handlers
    const setupWebSocketHandlers = () => {
      on('dashboard-update', (data) => {
        realTimeData.value = { ...realTimeData.value, ...data }
        lastUpdate.value = new Date()
      })

      on('dashboard-alert', (alert) => {
        alerts.value.push({
          id: Date.now(),
          ...alert
        })
      })

      on('dashboard-config-update', (config) => {
        configuration.value = { ...configuration.value, ...config }
      })
    }

    // Lifecycle
    onMounted(async () => {
      // Start performance monitoring
      performance.startTimer('dashboard-mount')
      
      // Load configuration
      try {
        const configResponse = await fetch(`/admin/api/dashboards/${props.dashboard.uriKey}/config`)
        if (configResponse.ok) {
          configuration.value = await configResponse.json()
        }
      } catch (error) {
        console.error('Failed to load configuration:', error)
      }

      // Connect WebSocket for real-time updates
      if (props.dashboard.meta.realTime) {
        connect()
        setupWebSocketHandlers()
      }

      // Initial data load
      await refreshDashboard()
      
      // End performance monitoring
      const mountTime = performance.endTimer('dashboard-mount')
      console.log(`Dashboard mounted in ${mountTime}ms`)
    })

    onUnmounted(() => {
      if (props.dashboard.meta.realTime) {
        disconnect()
      }
      performance.cleanup()
    })

    // Watch for filter changes
    watch(activeFilters, (newFilters) => {
      if (props.dashboard.meta.realTime) {
        emit('filters-changed', newFilters)
      }
    }, { deep: true })

    return {
      // State
      loading,
      activeFilters,
      configuration,
      alerts,
      realTimeData,
      lastUpdate,
      isConnected,

      // Computed
      availableFilters,
      dashboardCards,
      showPerformanceOverlay,
      performanceMetrics,

      // Methods
      handleFilterChange,
      handleExport,
      handleConfigChange,
      handleCardResize,
      handleCardMove,
      handleCardConfigure,
      handleAlertDismiss,
      formatTime
    }
  }
}
</script>

<style scoped>
.advanced-dashboard {
  @apply min-h-screen bg-gray-50;
}

.dashboard-header {
  @apply bg-white shadow-sm border-b border-gray-200 px-6 py-4;
}

.header-content {
  @apply flex items-center justify-between;
}

.dashboard-title {
  @apply text-2xl font-bold text-gray-900;
}

.dashboard-actions {
  @apply flex items-center space-x-4;
}

.realtime-status {
  @apply bg-green-50 border-b border-green-200 px-6 py-2 flex items-center justify-between;
}

.status-indicator {
  @apply flex items-center space-x-2 text-sm font-medium;
}

.status-indicator.connected {
  @apply text-green-700;
}

.pulse {
  @apply w-2 h-2 bg-green-500 rounded-full animate-pulse;
}

.last-update {
  @apply text-sm text-gray-600;
}
</style>
```

## WebSocket Integration

```javascript
// resources/js/composables/useWebSocket.js
import { ref, onUnmounted } from 'vue'

export function useWebSocket(url) {
  const socket = ref(null)
  const isConnected = ref(false)
  const reconnectAttempts = ref(0)
  const maxReconnectAttempts = 5
  const reconnectDelay = 1000

  const eventHandlers = new Map()

  const connect = () => {
    try {
      socket.value = new WebSocket(`ws://${window.location.host}${url}`)
      
      socket.value.onopen = () => {
        isConnected.value = true
        reconnectAttempts.value = 0
        console.log('WebSocket connected')
      }
      
      socket.value.onmessage = (event) => {
        const data = JSON.parse(event.data)
        const handlers = eventHandlers.get(data.type) || []
        handlers.forEach(handler => handler(data.payload))
      }
      
      socket.value.onclose = () => {
        isConnected.value = false
        console.log('WebSocket disconnected')
        
        // Attempt reconnection
        if (reconnectAttempts.value < maxReconnectAttempts) {
          setTimeout(() => {
            reconnectAttempts.value++
            connect()
          }, reconnectDelay * reconnectAttempts.value)
        }
      }
      
      socket.value.onerror = (error) => {
        console.error('WebSocket error:', error)
      }
    } catch (error) {
      console.error('Failed to connect WebSocket:', error)
    }
  }

  const disconnect = () => {
    if (socket.value) {
      socket.value.close()
      socket.value = null
    }
  }

  const emit = (type, payload) => {
    if (socket.value && isConnected.value) {
      socket.value.send(JSON.stringify({ type, payload }))
    }
  }

  const on = (event, handler) => {
    if (!eventHandlers.has(event)) {
      eventHandlers.set(event, [])
    }
    eventHandlers.get(event).push(handler)
    
    // Return unsubscribe function
    return () => {
      const handlers = eventHandlers.get(event) || []
      const index = handlers.indexOf(handler)
      if (index > -1) {
        handlers.splice(index, 1)
      }
    }
  }

  onUnmounted(() => {
    disconnect()
  })

  return {
    isConnected,
    connect,
    disconnect,
    emit,
    on
  }
}
```

This advanced example demonstrates:

1. **Real-time Updates**: WebSocket integration for live data
2. **Advanced Filtering**: Complex filter system with multiple types
3. **Export Functionality**: Multiple export formats
4. **Customization**: User-configurable dashboard layouts
5. **Performance Monitoring**: Built-in performance tracking
6. **Caching Strategies**: Advanced caching with invalidation
7. **Error Handling**: Robust error handling and retry logic
8. **Alerts System**: Real-time alert notifications

For more examples, see:
- [Mobile Dashboard Example](./mobile-dashboard-example.md)
- [Performance Dashboard Example](./performance-dashboard-example.md)
