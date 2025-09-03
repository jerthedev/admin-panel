<template>
  <div 
    class="progress-metric bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
    :class="{ 'dark': darkMode, 'compact': compact }"
  >
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ title }}
      </h3>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      data-testid="loading-spinner"
      class="flex items-center justify-center h-32"
    >
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      <span class="ml-2 text-gray-600 dark:text-gray-400">Loading...</span>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      data-testid="error-message"
      class="flex items-center justify-center h-32 text-red-600 dark:text-red-400"
    >
      <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      {{ error }}
    </div>

    <!-- Main Content -->
    <div v-else>
      <!-- Values Display -->
      <div class="flex items-center justify-between mb-4">
        <!-- Current Value -->
        <div
          v-if="showCurrent && hasValidData"
          data-testid="current-value"
          class="text-left"
        >
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ formattedCurrent }}
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Current
          </div>
        </div>

        <!-- Percentage -->
        <div
          v-if="showPercentage && hasValidData"
          data-testid="percentage"
          class="text-center"
        >
          <div class="text-3xl font-bold" :class="percentageColorClass">
            {{ formattedPercentage }}
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Progress
          </div>
        </div>

        <!-- Target Value -->
        <div
          v-if="showTarget && hasValidData"
          data-testid="target-value"
          class="text-right"
        >
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ formattedTarget }}
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Target
          </div>
        </div>
      </div>

      <!-- No Data State -->
      <div
        v-if="!hasValidData"
        data-testid="no-data"
        class="flex items-center justify-center h-32 text-gray-500 dark:text-gray-400"
      >
        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        No data available
      </div>

      <!-- Progress Bar -->
      <div
        v-if="hasValidData"
        data-testid="progress-bar"
        class="relative w-full h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden"
        role="progressbar"
        :aria-valuenow="Math.round(progressPercentage)"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-label="`${title} progress: ${formattedPercentage} complete`"
      >
        <div
          data-testid="progress-fill"
          class="h-full rounded-full"
          :class="[
            progressColorClass,
            animated ? 'transition-all duration-500 ease-out' : ''
          ]"
          :style="progressBarStyle"
        ></div>
      </div>

      <!-- Screen Reader Content -->
      <div data-testid="sr-only" class="sr-only">
        {{ title }} progress: {{ formattedCurrent }} of {{ formattedTarget }} ({{ formattedPercentage }} complete).
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProgressMetric',
  
  props: {
    title: {
      type: String,
      required: true,
    },
    current: {
      type: [Number, String],
      default: 0,
    },
    target: {
      type: [Number, String],
      default: 100,
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
    formatter: {
      type: Function,
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
    animated: {
      type: Boolean,
      default: true,
    },
    animationDuration: {
      type: Number,
      default: 500,
    },
    showPercentage: {
      type: Boolean,
      default: true,
    },
    showTarget: {
      type: Boolean,
      default: true,
    },
    showCurrent: {
      type: Boolean,
      default: true,
    },
    color: {
      type: String,
      default: null,
    },
    darkMode: {
      type: Boolean,
      default: false,
    },
    compact: {
      type: Boolean,
      default: false,
    },
  },

  computed: {
    hasValidData() {
      return this.current !== null && 
             this.current !== undefined && 
             this.target !== null && 
             this.target !== undefined &&
             !isNaN(Number(this.current)) &&
             !isNaN(Number(this.target))
    },

    numericCurrent() {
      return Number(this.current) || 0
    },

    numericTarget() {
      return Number(this.target) || 1
    },

    progressPercentage() {
      if (!this.hasValidData || this.numericTarget === 0) {
        return 0
      }
      
      const percentage = (this.numericCurrent / this.numericTarget) * 100
      return Math.max(0, percentage) // Don't allow negative percentages
    },

    displayPercentage() {
      // For display purposes, we can show over 100%
      if (!this.hasValidData || this.numericTarget === 0) {
        return 0
      }
      
      return Math.max(0, (this.numericCurrent / this.numericTarget) * 100)
    },

    progressBarWidth() {
      // Cap the visual progress bar at 100%
      return Math.min(100, this.progressPercentage)
    },

    formattedCurrent() {
      if (!this.hasValidData) {
        return 'No data'
      }

      if (this.formatter) {
        return this.formatter(this.numericCurrent)
      }

      return this.formatValue(this.numericCurrent)
    },

    formattedTarget() {
      if (!this.hasValidData) {
        return 'No data'
      }

      if (this.formatter) {
        return this.formatter(this.numericTarget)
      }

      return this.formatValue(this.numericTarget)
    },

    formattedPercentage() {
      if (!this.hasValidData) {
        return '0.0%'
      }

      return `${this.displayPercentage.toFixed(1)}%`
    },

    progressColorClass() {
      if (this.color) {
        return '' // Custom color will be applied via style
      }

      const percentage = this.progressPercentage

      if (percentage >= 100) {
        return 'bg-green-500'
      } else if (percentage >= 75) {
        return 'bg-yellow-500'
      } else if (percentage >= 50) {
        return 'bg-blue-500'
      } else {
        return 'bg-red-500'
      }
    },

    percentageColorClass() {
      const percentage = this.progressPercentage

      if (percentage >= 100) {
        return 'text-green-600 dark:text-green-400'
      } else if (percentage >= 75) {
        return 'text-yellow-600 dark:text-yellow-400'
      } else if (percentage >= 50) {
        return 'text-blue-600 dark:text-blue-400'
      } else {
        return 'text-red-600 dark:text-red-400'
      }
    },

    progressBarStyle() {
      const baseStyle = {
        width: `${this.progressBarWidth}%`,
      }

      if (this.color) {
        baseStyle.backgroundColor = this.color
      }

      if (this.animated && this.animationDuration !== 500) {
        baseStyle.transitionDuration = `${this.animationDuration}ms`
      }

      return baseStyle
    },
  },

  methods: {
    formatValue(value) {
      if (value === null || value === undefined || isNaN(value)) {
        return 'No data'
      }

      const numValue = Number(value)
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
  },
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.progress-metric {
  transition: all 0.2s ease-in-out;
}

.progress-metric:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.compact {
  padding: 1rem;
}

.compact .text-2xl {
  font-size: 1.25rem;
}

.compact .text-3xl {
  font-size: 1.5rem;
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
