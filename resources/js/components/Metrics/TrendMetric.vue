<template>
  <div 
    class="trend-metric bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
    :class="{ 'dark': darkMode }"
  >
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ title }}
      </h3>
      
      <!-- Range Selector -->
      <select
        v-if="ranges && Object.keys(ranges).length > 0"
        :value="selectedRange"
        @change="handleRangeChange"
        data-testid="range-selector"
        class="text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
      >
        <option
          v-for="(label, value) in ranges"
          :key="value"
          :value="value"
        >
          {{ label }}
        </option>
      </select>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      data-testid="loading-spinner"
      class="flex items-center justify-center h-64"
    >
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      <span class="ml-2 text-gray-600 dark:text-gray-400">Loading...</span>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      data-testid="error-message"
      class="flex items-center justify-center h-64 text-red-600 dark:text-red-400"
    >
      <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      {{ error }}
    </div>

    <!-- No Data State -->
    <div
      v-else-if="!hasData"
      data-testid="no-data"
      class="flex items-center justify-center h-64 text-gray-500 dark:text-gray-400"
    >
      <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
      </svg>
      No data available
    </div>

    <!-- Main Content -->
    <div v-else>
      <!-- Value and Trend Display -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <div class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ formattedValue }}
          </div>
          <div class="flex items-center mt-1">
            <!-- Trend Up -->
            <svg
              v-if="trendDirection === 'up'"
              data-testid="trend-up"
              class="w-4 h-4 text-green-500 mr-1"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7m0 0H7"></path>
            </svg>
            
            <!-- Trend Down -->
            <svg
              v-else-if="trendDirection === 'down'"
              data-testid="trend-down"
              class="w-4 h-4 text-red-500 mr-1"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10m0 0h10"></path>
            </svg>
            
            <!-- Trend Neutral -->
            <svg
              v-else
              data-testid="trend-neutral"
              class="w-4 h-4 text-gray-500 mr-1"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h8"></path>
            </svg>

            <span
              class="text-sm font-medium"
              :class="{
                'text-green-600 dark:text-green-400': trendDirection === 'up',
                'text-red-600 dark:text-red-400': trendDirection === 'down',
                'text-gray-600 dark:text-gray-400': trendDirection === 'neutral'
              }"
            >
              {{ percentageChange }}
            </span>
          </div>
        </div>
      </div>

      <!-- Chart Container -->
      <div class="relative h-64">
        <canvas
          ref="chartCanvas"
          role="img"
          :aria-label="`${title} trend chart showing ${formattedValue}`"
        ></canvas>
      </div>

      <!-- Screen Reader Content -->
      <div data-testid="sr-only" class="sr-only">
        {{ title }} current value is {{ formattedValue }}, 
        {{ trendDirection === 'up' ? 'increased' : trendDirection === 'down' ? 'decreased' : 'unchanged' }} 
        by {{ percentageChange }} compared to previous period.
      </div>
    </div>
  </div>
</template>

<script>
import { Chart, registerables } from 'chart.js'
import 'chartjs-adapter-date-fns'

Chart.register(...registerables)

export default {
  name: 'TrendMetric',
  
  props: {
    title: {
      type: String,
      required: true,
    },
    data: {
      type: Object,
      required: true,
      validator: (value) => {
        return value && typeof value === 'object' && 'labels' in value && 'datasets' in value
      },
    },
    value: {
      type: [Number, String],
      default: null,
    },
    previousValue: {
      type: [Number, String],
      default: null,
    },
    format: {
      type: String,
      default: 'decimal',
      validator: (value) => ['currency', 'percentage', 'decimal'].includes(value),
    },
    prefix: {
      type: String,
      default: '',
    },
    suffix: {
      type: String,
      default: '',
    },
    loading: {
      type: Boolean,
      default: false,
    },
    error: {
      type: String,
      default: null,
    },
    ranges: {
      type: Object,
      default: () => ({}),
    },
    selectedRange: {
      type: [String, Number],
      default: null,
    },
    darkMode: {
      type: Boolean,
      default: false,
    },
  },

  emits: ['range-changed'],

  data() {
    return {
      chart: null,
    }
  },

  computed: {
    hasData() {
      if (!this.data || !this.data.datasets) return false
      if (this.data.datasets.length === 0) return false
      if (!this.data.labels || this.data.labels.length === 0) return false
      return this.value !== null && this.value !== undefined
    },

    formattedValue() {
      if (this.value === null || this.value === undefined) {
        return 'No data'
      }

      const numValue = Number(this.value)
      if (isNaN(numValue)) return 'No data'

      let formatted = ''

      switch (this.format) {
        case 'currency':
          formatted = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
          }).format(numValue)
          break
        case 'percentage':
          formatted = new Intl.NumberFormat('en-US', {
            style: 'percent',
            minimumFractionDigits: 0,
            maximumFractionDigits: 1,
          }).format(numValue)
          return formatted // Percentage format includes the % symbol
        case 'decimal':
        default:
          formatted = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
          }).format(numValue)
          break
      }

      return `${this.prefix}${formatted}${this.suffix}`
    },

    trendDirection() {
      if (this.value === null || this.value === undefined || 
          this.previousValue === null || this.previousValue === undefined) {
        return 'neutral'
      }

      const current = Number(this.value)
      const previous = Number(this.previousValue)

      if (isNaN(current) || isNaN(previous)) return 'neutral'

      if (current > previous) return 'up'
      if (current < previous) return 'down'
      return 'neutral'
    },

    percentageChange() {
      if (this.value === null || this.value === undefined || 
          this.previousValue === null || this.previousValue === undefined) {
        return '0.0%'
      }

      const current = Number(this.value)
      const previous = Number(this.previousValue)

      if (isNaN(current) || isNaN(previous) || previous === 0) {
        return '0.0%'
      }

      const change = ((current - previous) / Math.abs(previous)) * 100
      return `${change >= 0 ? '+' : ''}${change.toFixed(1)}%`
    },
  },

  watch: {
    data: {
      handler() {
        this.updateChart()
      },
      deep: true,
    },
    darkMode() {
      this.updateChart()
    },
  },

  mounted() {
    this.initChart()
  },

  beforeUnmount() {
    if (this.chart) {
      this.chart.destroy()
    }
  },

  methods: {
    initChart() {
      if (!this.$refs.chartCanvas || !this.hasData) return

      const ctx = this.$refs.chartCanvas.getContext('2d')
      
      this.chart = new Chart(ctx, {
        type: 'line',
        data: this.data,
        options: this.getChartOptions(),
      })
    },

    updateChart() {
      if (!this.chart) {
        this.initChart()
        return
      }

      this.chart.data = this.data
      this.chart.options = this.getChartOptions()
      this.chart.update()
    },

    getChartOptions() {
      const isDark = this.darkMode
      
      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            backgroundColor: isDark ? '#374151' : '#ffffff',
            titleColor: isDark ? '#ffffff' : '#111827',
            bodyColor: isDark ? '#ffffff' : '#111827',
            borderColor: isDark ? '#6B7280' : '#E5E7EB',
            borderWidth: 1,
          },
        },
        scales: {
          x: {
            grid: {
              color: isDark ? '#374151' : '#F3F4F6',
            },
            ticks: {
              color: isDark ? '#9CA3AF' : '#6B7280',
            },
          },
          y: {
            grid: {
              color: isDark ? '#374151' : '#F3F4F6',
            },
            ticks: {
              color: isDark ? '#9CA3AF' : '#6B7280',
            },
          },
        },
        elements: {
          point: {
            radius: 4,
            hoverRadius: 6,
          },
          line: {
            tension: 0.4,
          },
        },
      }
    },

    handleRangeChange(event) {
      const newRange = event.target.value
      this.$emit('range-changed', Number(newRange) || newRange)
    },
  },
}
</script>

<style scoped>
.trend-metric {
  transition: all 0.2s ease-in-out;
}

.trend-metric:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

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
</style>
