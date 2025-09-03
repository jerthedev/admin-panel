<template>
  <div
    class="metric-card"
    :class="{
      'metric-card-compact': compact,
      'mobile': mobile
    }"
    role="region"
    :aria-labelledby="labelId"
  >
    <!-- Header with Range Selector -->
    <div
      v-if="ranges && Object.keys(ranges).length > 0"
      data-testid="metric-header"
      class="metric-header flex items-center justify-between mb-4"
      :class="{ 'flex-col space-y-2': stackOnMobile }"
    >
      <div v-if="label" class="metric-title">
        {{ label }}
      </div>

      <!-- Range Selector -->
      <select
        :value="selectedRange"
        @change="handleRangeChange"
        data-testid="range-selector"
        :aria-label="`Select time range for ${label || 'metric'}`"
        tabindex="0"
        class="text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
      >
        <option
          v-for="(rangeLabel, rangeValue) in ranges"
          :key="rangeValue"
          :value="rangeValue"
        >
          {{ rangeLabel }}
        </option>
      </select>
    </div>

    <!-- Metric Value -->
    <div class="metric-value-section">
      <div
        :id="labelId"
        class="metric-value"
        :class="{ 'text-2xl': mobile }"
      >
        {{ formattedValue }}
      </div>
      <div v-if="label && (!ranges || Object.keys(ranges).length === 0)" class="metric-label">
        {{ label }}
      </div>
    </div>

    <!-- Enhanced Metric Change Indicator -->
    <div v-if="hasChange" class="metric-change">
      <div
        :class="changeClasses"
        :data-testid="enhancedTrend ? 'enhanced-trend' : 'basic-trend'"
      >
        <svg
          :class="trendIconClasses"
          data-testid="trend-icon"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            fill-rule="evenodd"
            :d="getTrendIconPath()"
            clip-rule="evenodd"
          />
        </svg>
        <span
          :title="trendTooltip"
          data-testid="trend-tooltip"
        >
          {{ formattedChange }}
        </span>
      </div>

      <!-- Previous Value Comparison -->
      <div
        v-if="previousValue !== null && previousValue !== undefined"
        data-testid="trend-comparison"
        class="text-xs text-gray-500 dark:text-gray-400"
      >
        from {{ formattedPreviousValue }}
      </div>

      <div v-if="changePeriod" class="change-period">
        {{ changePeriod }}
      </div>
    </div>

    <!-- Screen Reader Trend Description -->
    <div
      v-if="hasChange"
      data-testid="sr-trend-description"
      class="sr-only"
    >
      {{ label || 'Metric' }} {{ changeDirection === 'up' ? 'increased' : changeDirection === 'down' ? 'decreased' : 'remained unchanged' }} by {{ Math.abs(change) }}% {{ changePeriod }}
    </div>

    <!-- Live Region for Dynamic Updates -->
    <div aria-live="polite" class="sr-only">
      {{ liveRegionText }}
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
      default: 'number', // number, currency, percentage, compact
      validator: (value) => ['number', 'currency', 'percentage', 'compact'].includes(value)
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
    },
    // Enhanced formatting options
    formatter: {
      type: Function,
      default: null
    },
    prefix: {
      type: String,
      default: ''
    },
    suffix: {
      type: String,
      default: ''
    },
    decimalPlaces: {
      type: Number,
      default: null
    },
    // Range selection
    ranges: {
      type: Object,
      default: null
    },
    selectedRange: {
      type: [String, Number],
      default: null
    },
    // Enhanced trend indicators
    enhancedTrend: {
      type: Boolean,
      default: false
    },
    previousValue: {
      type: Number,
      default: null
    },
    trendPrecision: {
      type: Number,
      default: 1
    },
    trendTooltip: {
      type: String,
      default: ''
    },
    // Responsive design
    compact: {
      type: Boolean,
      default: false
    },
    mobile: {
      type: Boolean,
      default: false
    },
    stackOnMobile: {
      type: Boolean,
      default: false
    }
  },
  emits: ['action', 'error', 'range-changed'],
  setup(props, { emit }) {
    // Generate unique ID for accessibility
    const labelId = `metric-${Math.random().toString(36).substr(2, 9)}`

    // Computed properties
    const formattedValue = computed(() => {
      // Use custom formatter if provided
      if (props.formatter && typeof props.formatter === 'function') {
        return props.formatter(props.value)
      }

      const numValue = typeof props.value === 'string' ? parseFloat(props.value) : props.value

      if (isNaN(numValue)) return props.value

      let formatted = ''

      switch (props.format) {
        case 'currency':
          formatted = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: props.currency,
            minimumFractionDigits: props.decimalPlaces !== null ? props.decimalPlaces : 2,
            maximumFractionDigits: props.decimalPlaces !== null ? props.decimalPlaces : 2
          }).format(numValue)
          break
        case 'percentage':
          formatted = new Intl.NumberFormat('en-US', {
            style: 'percent',
            minimumFractionDigits: props.decimalPlaces !== null ? props.decimalPlaces : 1,
            maximumFractionDigits: props.decimalPlaces !== null ? props.decimalPlaces : 1
          }).format(numValue / 100)
          break
        case 'compact':
          formatted = new Intl.NumberFormat('en-US', {
            notation: 'compact',
            maximumFractionDigits: 1
          }).format(numValue)
          break
        default:
          formatted = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: props.decimalPlaces !== null ? props.decimalPlaces : 0,
            maximumFractionDigits: props.decimalPlaces !== null ? props.decimalPlaces : 0
          }).format(numValue)
          break
      }

      return `${props.prefix}${formatted}${props.suffix}`
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
      const precision = props.trendPrecision || 1

      if (props.format === 'percentage') {
        return `${sign}${absChange.toFixed(precision)}%`
      }

      // For regular changes, use the precision for percentage display
      return `${sign}${absChange.toFixed(precision)}%`
    })

    // Enhanced computed properties
    const trendIconClasses = computed(() => {
      const direction = changeDirection.value
      const baseClasses = 'w-4 h-4 mr-1'

      if (direction === 'up') {
        return `${baseClasses} trend-up rotate-0`
      } else if (direction === 'down') {
        return `${baseClasses} trend-down rotate-180`
      } else {
        return `${baseClasses} trend-neutral`
      }
    })

    const formattedPreviousValue = computed(() => {
      if (props.previousValue === null || props.previousValue === undefined) return ''

      // Use same formatting as main value
      if (props.formatter && typeof props.formatter === 'function') {
        return props.formatter(props.previousValue)
      }

      const numValue = props.previousValue
      let formatted = ''

      switch (props.format) {
        case 'currency':
          formatted = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: props.currency,
            minimumFractionDigits: props.decimalPlaces !== null ? props.decimalPlaces : 2,
            maximumFractionDigits: props.decimalPlaces !== null ? props.decimalPlaces : 2
          }).format(numValue)
          break
        case 'percentage':
          formatted = new Intl.NumberFormat('en-US', {
            style: 'percent',
            minimumFractionDigits: props.decimalPlaces !== null ? props.decimalPlaces : 1,
            maximumFractionDigits: props.decimalPlaces !== null ? props.decimalPlaces : 1
          }).format(numValue / 100)
          break
        case 'compact':
          formatted = new Intl.NumberFormat('en-US', {
            notation: 'compact',
            maximumFractionDigits: 1
          }).format(numValue)
          break
        default:
          formatted = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: props.decimalPlaces !== null ? props.decimalPlaces : 0,
            maximumFractionDigits: props.decimalPlaces !== null ? props.decimalPlaces : 0
          }).format(numValue)
          break
      }

      return `${props.prefix}${formatted}${props.suffix}`
    })

    const liveRegionText = computed(() => {
      return `${props.label || 'Metric'} updated to ${formattedValue.value}`
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

    // Methods
    const handleRangeChange = (event) => {
      const newRange = event.target.value
      emit('range-changed', Number(newRange) || newRange)
    }

    const getTrendIconPath = () => {
      const direction = changeDirection.value

      if (direction === 'up') {
        return 'M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 4.414 6.707 7.707a1 1 0 01-1.414 0z'
      } else if (direction === 'down') {
        return 'M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 4.414 6.707 7.707a1 1 0 01-1.414 0z'
      } else {
        return 'M5 12h14'
      }
    }

    return {
      labelId,
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
      hasFooter,
      // Enhanced properties
      trendIconClasses,
      formattedPreviousValue,
      liveRegionText,
      // Methods
      handleRangeChange,
      getTrendIconPath
    }
  }
}
</script>

<style scoped>
@import '../../../../css/admin.css' reference;

.metric-card {
  @apply space-y-4 transition-all duration-200 ease-in-out;
}

.metric-card:hover {
  @apply shadow-md;
}

.metric-card-compact {
  @apply space-y-2 p-4;
}

.metric-card-compact .metric-value {
  @apply text-2xl;
}

.mobile .metric-value {
  @apply text-2xl;
}

.metric-header {
  @apply transition-all duration-200;
}

.metric-title {
  @apply text-sm font-medium text-gray-600 dark:text-gray-400;
}

.metric-value-section {
  @apply text-center;
}

.metric-value {
  @apply text-3xl font-bold text-gray-900 dark:text-white transition-all duration-200;
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

/* Enhanced trend indicators */
.trend-up {
  @apply text-green-600 dark:text-green-400 transition-colors duration-200;
}

.trend-down {
  @apply text-red-600 dark:text-red-400 transition-colors duration-200;
}

.trend-neutral {
  @apply text-gray-600 dark:text-gray-400 transition-colors duration-200;
}

/* Screen reader only content */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* Responsive design */
@media (max-width: 640px) {
  .metric-card {
    @apply space-y-2;
  }

  .metric-header {
    @apply flex-col space-y-2;
  }

  .metric-value {
    @apply text-2xl;
  }
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
