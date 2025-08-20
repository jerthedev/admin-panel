<template>
  <div class="metric-card">
    <!-- Metric Value -->
    <div class="metric-value-section">
      <div class="metric-value">
        {{ formattedValue }}
      </div>
      <div v-if="label" class="metric-label">
        {{ label }}
      </div>
    </div>

    <!-- Metric Change Indicator -->
    <div v-if="hasChange" class="metric-change">
      <div :class="changeClasses">
        <svg
          v-if="changeDirection !== 'neutral'"
          :class="['w-4 h-4 mr-1', changeDirection === 'up' ? 'rotate-0' : 'rotate-180']"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            fill-rule="evenodd"
            d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 4.414 6.707 7.707a1 1 0 01-1.414 0z"
            clip-rule="evenodd"
          />
        </svg>
        <span>{{ formattedChange }}</span>
      </div>
      <div v-if="changePeriod" class="change-period">
        {{ changePeriod }}
      </div>
    </div>

    <!-- Metric Description -->
    <div v-if="description" class="metric-description">
      {{ description }}
    </div>

    <!-- Metric Chart/Sparkline (if data provided) -->
    <div v-if="chartData" class="metric-chart">
      <svg
        class="w-full h-12"
        viewBox="0 0 200 48"
        preserveAspectRatio="none"
      >
        <polyline
          :points="chartPoints"
          fill="none"
          :stroke="chartColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
        <circle
          v-if="chartData.length > 0"
          :cx="lastPointX"
          :cy="lastPointY"
          r="3"
          :fill="chartColor"
        />
      </svg>
    </div>

    <!-- Metric Footer -->
    <div v-if="hasFooter" class="metric-footer">
      <div v-if="lastUpdated" class="last-updated">
        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
            clip-rule="evenodd"
          />
        </svg>
        <span>{{ formattedLastUpdated }}</span>
      </div>
      
      <div v-if="target" class="target">
        <span class="target-label">Target:</span>
        <span class="target-value">{{ formattedTarget }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'MetricCard',
  props: {
    dashboard: {
      type: Object,
      required: true
    },
    value: {
      type: [Number, String],
      required: true
    },
    label: {
      type: String,
      default: ''
    },
    description: {
      type: String,
      default: ''
    },
    format: {
      type: String,
      default: 'number', // number, currency, percentage
      validator: (value) => ['number', 'currency', 'percentage'].includes(value)
    },
    currency: {
      type: String,
      default: 'USD'
    },
    change: {
      type: Number,
      default: null
    },
    changePeriod: {
      type: String,
      default: ''
    },
    target: {
      type: Number,
      default: null
    },
    chartData: {
      type: Array,
      default: () => []
    },
    chartColor: {
      type: String,
      default: '#3B82F6'
    },
    lastUpdated: {
      type: [String, Date],
      default: null
    }
  },
  emits: ['action', 'error'],
  setup(props, { emit }) {
    // Computed properties
    const formattedValue = computed(() => {
      const numValue = typeof props.value === 'string' ? parseFloat(props.value) : props.value
      
      if (isNaN(numValue)) return props.value

      switch (props.format) {
        case 'currency':
          return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: props.currency
          }).format(numValue)
        case 'percentage':
          return new Intl.NumberFormat('en-US', {
            style: 'percent',
            minimumFractionDigits: 1,
            maximumFractionDigits: 1
          }).format(numValue / 100)
        default:
          return new Intl.NumberFormat('en-US').format(numValue)
      }
    })

    const hasChange = computed(() => props.change !== null && props.change !== undefined)

    const changeDirection = computed(() => {
      if (!hasChange.value) return 'neutral'
      return props.change > 0 ? 'up' : props.change < 0 ? 'down' : 'neutral'
    })

    const changeClasses = computed(() => [
      'inline-flex items-center text-sm font-medium',
      {
        'text-green-600 dark:text-green-400': changeDirection.value === 'up',
        'text-red-600 dark:text-red-400': changeDirection.value === 'down',
        'text-gray-600 dark:text-gray-400': changeDirection.value === 'neutral'
      }
    ])

    const formattedChange = computed(() => {
      if (!hasChange.value) return ''
      
      const absChange = Math.abs(props.change)
      const sign = props.change >= 0 ? '+' : '-'
      
      if (props.format === 'percentage') {
        return `${sign}${absChange.toFixed(1)}%`
      }
      
      return `${sign}${absChange.toLocaleString()}`
    })

    const formattedTarget = computed(() => {
      if (props.target === null) return ''
      
      switch (props.format) {
        case 'currency':
          return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: props.currency
          }).format(props.target)
        case 'percentage':
          return new Intl.NumberFormat('en-US', {
            style: 'percent',
            minimumFractionDigits: 1,
            maximumFractionDigits: 1
          }).format(props.target / 100)
        default:
          return new Intl.NumberFormat('en-US').format(props.target)
      }
    })

    const formattedLastUpdated = computed(() => {
      if (!props.lastUpdated) return ''
      
      const date = typeof props.lastUpdated === 'string' 
        ? new Date(props.lastUpdated) 
        : props.lastUpdated
      
      return new Intl.RelativeTimeFormat('en', { numeric: 'auto' }).format(
        Math.round((date.getTime() - Date.now()) / (1000 * 60)),
        'minute'
      )
    })

    const chartPoints = computed(() => {
      if (!props.chartData || props.chartData.length === 0) return ''
      
      const maxValue = Math.max(...props.chartData)
      const minValue = Math.min(...props.chartData)
      const range = maxValue - minValue || 1
      
      return props.chartData
        .map((value, index) => {
          const x = (index / (props.chartData.length - 1)) * 200
          const y = 48 - ((value - minValue) / range) * 48
          return `${x},${y}`
        })
        .join(' ')
    })

    const lastPointX = computed(() => {
      if (!props.chartData || props.chartData.length === 0) return 0
      return ((props.chartData.length - 1) / (props.chartData.length - 1)) * 200
    })

    const lastPointY = computed(() => {
      if (!props.chartData || props.chartData.length === 0) return 24
      
      const maxValue = Math.max(...props.chartData)
      const minValue = Math.min(...props.chartData)
      const range = maxValue - minValue || 1
      const lastValue = props.chartData[props.chartData.length - 1]
      
      return 48 - ((lastValue - minValue) / range) * 48
    })

    const hasFooter = computed(() => props.lastUpdated || props.target !== null)

    return {
      formattedValue,
      hasChange,
      changeDirection,
      changeClasses,
      formattedChange,
      formattedTarget,
      formattedLastUpdated,
      chartPoints,
      lastPointX,
      lastPointY,
      hasFooter
    }
  }
}
</script>

<style scoped>
.metric-card {
  @apply space-y-4;
}

.metric-value-section {
  @apply text-center;
}

.metric-value {
  @apply text-3xl font-bold text-gray-900 dark:text-white;
}

.metric-label {
  @apply text-sm font-medium text-gray-600 dark:text-gray-400 mt-1;
}

.metric-change {
  @apply flex items-center justify-center space-x-2;
}

.change-period {
  @apply text-xs text-gray-500 dark:text-gray-500;
}

.metric-description {
  @apply text-sm text-gray-600 dark:text-gray-400 text-center;
}

.metric-chart {
  @apply w-full;
}

.metric-footer {
  @apply flex items-center justify-between text-xs text-gray-500 dark:text-gray-500 pt-2 border-t border-gray-200 dark:border-gray-700;
}

.last-updated {
  @apply flex items-center;
}

.target {
  @apply flex items-center space-x-1;
}

.target-label {
  @apply font-medium;
}

.target-value {
  @apply text-gray-700 dark:text-gray-300;
}
</style>
