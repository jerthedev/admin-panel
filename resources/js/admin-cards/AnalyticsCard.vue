<template>
  <div 
    class="analytics-card bg-white rounded-lg shadow-sm border border-gray-200 p-6"
    :class="{ 'card-loading': isLoading, 'card-error': error }"
  >
    <!-- Card Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-3">
        <div class="flex-shrink-0">
          <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ card.title || 'Analytics Overview' }}</h3>
          <p class="text-sm text-gray-500">{{ card.description || 'Key performance metrics' }}</p>
        </div>
      </div>
      
      <div class="flex items-center space-x-2">
        <span v-if="card.group" class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ card.group }}</span>
        <button 
          v-if="card.refreshable" 
          @click="refresh"
          :disabled="isLoading"
          class="p-2 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 transition-colors"
          title="Refresh data"
        >
          <svg class="w-4 h-4" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
      <div class="flex">
        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error loading analytics data</h3>
          <p class="text-sm text-red-700 mt-1">{{ error }}</p>
        </div>
      </div>
    </div>

    <!-- Main Metrics Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="metric-item">
        <div class="text-2xl font-bold text-blue-600">{{ formatNumber(data.totalUsers) }}</div>
        <div class="text-sm text-gray-500">Total Users</div>
      </div>
      <div class="metric-item">
        <div class="text-2xl font-bold text-green-600">{{ formatNumber(data.activeUsers) }}</div>
        <div class="text-sm text-gray-500">Active Users</div>
      </div>
      <div class="metric-item">
        <div class="text-2xl font-bold text-purple-600">{{ formatNumber(data.pageViews) }}</div>
        <div class="text-sm text-gray-500">Page Views</div>
      </div>
      <div class="metric-item">
        <div class="text-2xl font-bold text-orange-600">{{ formatCurrency(data.revenue) }}</div>
        <div class="text-sm text-gray-500">Revenue</div>
      </div>
    </div>

    <!-- Conversion Rate -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-lg font-semibold text-gray-900">{{ data.conversionRate }}%</div>
          <div class="text-sm text-gray-500">Conversion Rate</div>
        </div>
        <div class="w-16 h-16">
          <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
            <path
              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              fill="none"
              stroke="#e5e7eb"
              stroke-width="3"
            />
            <path
              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              fill="none"
              stroke="#3b82f6"
              stroke-width="3"
              :stroke-dasharray="`${data.conversionRate}, 100`"
            />
          </svg>
        </div>
      </div>
    </div>

    <!-- Top Pages -->
    <div class="mb-6">
      <h4 class="text-sm font-medium text-gray-900 mb-3">Top Pages</h4>
      <div class="space-y-2">
        <div 
          v-for="page in data.topPages" 
          :key="page.path"
          class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-md"
        >
          <div class="flex-1">
            <div class="text-sm font-medium text-gray-900">{{ page.path }}</div>
            <div class="text-xs text-gray-500">{{ formatNumber(page.views) }} views</div>
          </div>
          <div class="text-sm text-gray-600">{{ page.percentage }}%</div>
        </div>
      </div>
    </div>

    <!-- Device Breakdown -->
    <div class="mb-6">
      <h4 class="text-sm font-medium text-gray-900 mb-3">Device Breakdown</h4>
      <div class="grid grid-cols-3 gap-3">
        <div 
          v-for="device in data.deviceBreakdown" 
          :key="device.device"
          class="text-center p-3 bg-gray-50 rounded-md"
        >
          <div class="text-lg font-semibold text-gray-900">{{ formatNumber(device.users) }}</div>
          <div class="text-xs text-gray-500">{{ device.device }}</div>
          <div class="text-xs text-gray-400">{{ device.percentage }}%</div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
      <div class="text-xs text-gray-400">
        Last updated: {{ formatTimestamp(data.lastUpdated) }}
      </div>
      <div class="flex items-center space-x-2">
        <button 
          @click="exportData"
          class="text-xs text-blue-600 hover:text-blue-800 font-medium"
        >
          Export
        </button>
        <button 
          @click="configure"
          class="text-xs text-gray-600 hover:text-gray-800 font-medium"
        >
          Configure
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props validation
const props = defineProps({
  card: {
    type: Object,
    required: true,
    validator: (card) => {
      return card && typeof card === 'object'
    }
  }
})

// Emits for card interactions
const emit = defineEmits(['refresh', 'configure', 'remove', 'export'])

// Reactive data
const isLoading = ref(false)
const error = ref(null)

// Computed properties
const data = computed(() => {
  return props.card.data || {
    totalUsers: 0,
    activeUsers: 0,
    pageViews: 0,
    conversionRate: 0,
    revenue: 0,
    topPages: [],
    deviceBreakdown: [],
    lastUpdated: new Date().toISOString()
  }
})

// Methods
const refresh = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    emit('refresh', props.card)
    // Simulate API call delay
    await new Promise(resolve => setTimeout(resolve, 1000))
  } catch (err) {
    error.value = err.message || 'Failed to refresh analytics data'
  } finally {
    isLoading.value = false
  }
}

const configure = () => {
  emit('configure', props.card)
}

const exportData = () => {
  emit('export', props.card)
}

const formatNumber = (num) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num?.toLocaleString() || '0'
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

const formatTimestamp = (timestamp) => {
  if (!timestamp) return 'Never'
  return new Date(timestamp).toLocaleString()
}

// Expose methods for parent component access
defineExpose({
  refresh,
  configure,
  exportData
})
</script>

<style scoped>
/* Component-specific styles */
.analytics-card {
  transition: all 0.2s ease-in-out;
}

.card-loading {
  opacity: 0.6;
  pointer-events: none;
}

.card-error {
  border-color: #ef4444;
  background-color: #fef2f2;
}

.metric-item {
  text-align: center;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  transition: background-color 0.2s ease-in-out;
}

.metric-item:hover {
  background: #f3f4f6;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .analytics-card {
    background-color: #1f2937;
    border-color: #374151;
    color: #f9fafb;
  }
  
  .metric-item {
    background: #374151;
  }
  
  .metric-item:hover {
    background: #4b5563;
  }
}
</style>
