<template>
  <div 
    class="partition-metric bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
    :class="{ 'dark': darkMode }"
  >
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ title }}
      </h3>
      
      <!-- Total Value -->
      <div
        v-if="showTotal && !loading && !error && hasData"
        data-testid="total-value"
        class="text-right"
      >
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ formattedTotal }}
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400">
          Total
        </div>
      </div>
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
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Chart Container -->
      <div class="flex items-center justify-center">
        <div class="relative w-64 h-64">
          <canvas
            ref="chartCanvas"
            role="img"
            :aria-label="`${title} partition chart showing ${formattedTotal} total across ${data.length} segments`"
          ></canvas>
        </div>
      </div>

      <!-- Legend -->
      <div
        v-if="showLegend"
        data-testid="legend"
        class="space-y-3"
      >
        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
          Distribution
        </h4>
        <div
          v-for="(item, index) in data"
          :key="index"
          data-testid="legend-item"
          class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        >
          <div class="flex items-center">
            <div
              class="w-4 h-4 rounded-full mr-3 flex-shrink-0"
              :style="{ backgroundColor: getItemColor(item, index) }"
            ></div>
            <span class="text-sm font-medium text-gray-900 dark:text-white">
              {{ item.label }}
            </span>
          </div>
          <div class="text-right">
            <div class="text-sm font-semibold text-gray-900 dark:text-white">
              {{ formatValue(item.value) }}
            </div>
            <div class="text-xs text-gray-600 dark:text-gray-400">
              {{ formatPercentage(item.value) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Screen Reader Content -->
    <div data-testid="sr-only" class="sr-only">
      {{ title }} partition showing {{ data.length }} segments with total of {{ formattedTotal }}.
      <span v-for="(item, index) in data" :key="index">
        {{ item.label }}: {{ formatValue(item.value) }} ({{ formatPercentage(item.value) }}).
      </span>
    </div>
  </div>
</template>

<script>
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

export default {
  name: 'PartitionMetric',
  
  props: {
    title: {
      type: String,
      required: true,
    },
    data: {
      type: Array,
      required: true,
      validator: (value) => {
        return Array.isArray(value) && value.every(item => 
          typeof item === 'object' && 
          'label' in item && 
          'value' in item
        )
      },
    },
    total: {
      type: Number,
      default: null,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    error: {
      type: String,
      default: null,
    },
    showTotal: {
      type: Boolean,
      default: true,
    },
    showLegend: {
      type: Boolean,
      default: true,
    },
    customColors: {
      type: Array,
      default: () => [],
    },
    labelFormatter: {
      type: Function,
      default: null,
    },
    darkMode: {
      type: Boolean,
      default: false,
    },
  },

  emits: ['segment-click'],

  data() {
    return {
      chart: null,
      defaultColors: [
        '#3B82F6', // Blue
        '#EF4444', // Red
        '#F59E0B', // Amber
        '#10B981', // Emerald
        '#8B5CF6', // Violet
        '#F97316', // Orange
        '#06B6D4', // Cyan
        '#84CC16', // Lime
        '#EC4899', // Pink
        '#6366F1', // Indigo
      ],
    }
  },

  computed: {
    hasData() {
      return this.data && this.data.length > 0
    },

    calculatedTotal() {
      if (this.total !== null) {
        return this.total
      }
      return this.data.reduce((sum, item) => sum + (item.value || 0), 0)
    },

    formattedTotal() {
      if (this.labelFormatter) {
        return this.labelFormatter(this.calculatedTotal)
      }
      return this.calculatedTotal.toLocaleString()
    },

    chartData() {
      if (!this.hasData) return null

      return {
        labels: this.data.map(item => item.label),
        datasets: [{
          data: this.data.map(item => item.value),
          backgroundColor: this.data.map((item, index) => this.getItemColor(item, index)),
          borderColor: this.darkMode ? '#374151' : '#ffffff',
          borderWidth: 2,
          hoverBorderWidth: 3,
        }],
      }
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
        type: 'pie',
        data: this.chartData,
        options: this.getChartOptions(),
      })
    },

    updateChart() {
      if (!this.chart) {
        this.initChart()
        return
      }

      this.chart.data = this.chartData
      this.chart.options = this.getChartOptions()
      this.chart.update()
    },

    getChartOptions() {
      const isDark = this.darkMode
      
      return {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: false, // We use custom legend
          },
          tooltip: {
            backgroundColor: isDark ? '#374151' : '#ffffff',
            titleColor: isDark ? '#ffffff' : '#111827',
            bodyColor: isDark ? '#ffffff' : '#111827',
            borderColor: isDark ? '#6B7280' : '#E5E7EB',
            borderWidth: 1,
            callbacks: {
              label: (context) => {
                const value = context.parsed
                const total = context.dataset.data.reduce((a, b) => a + b, 0)
                const percentage = ((value / total) * 100).toFixed(1)
                const formattedValue = this.labelFormatter ? 
                  this.labelFormatter(value) : 
                  value.toLocaleString()
                return `${context.label}: ${formattedValue} (${percentage}%)`
              },
            },
          },
        },
        onClick: (event, elements) => {
          if (elements.length > 0) {
            const index = elements[0].index
            const item = this.data[index]
            this.$emit('segment-click', { item, index })
          }
        },
        onHover: (event, elements) => {
          event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default'
        },
      }
    },

    getItemColor(item, index) {
      // Priority: custom colors > item color > default colors
      if (this.customColors.length > 0) {
        return this.customColors[index % this.customColors.length]
      }
      if (item.color) {
        return item.color
      }
      return this.defaultColors[index % this.defaultColors.length]
    },

    formatValue(value) {
      if (this.labelFormatter) {
        return this.labelFormatter(value)
      }
      return value.toLocaleString()
    },

    formatPercentage(value) {
      const percentage = ((value / this.calculatedTotal) * 100).toFixed(1)
      return `${percentage}%`
    },
  },
}
</script>

<style scoped>
.partition-metric {
  transition: all 0.2s ease-in-out;
}

.partition-metric:hover {
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
