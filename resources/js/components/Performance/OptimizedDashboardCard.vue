<template>
  <div
    ref="cardContainer"
    class="optimized-dashboard-card"
    :class="cardClasses"
    :data-lazy="enableLazyLoading"
    :data-component="componentPath"
    :data-priority="priority"
  >
    <!-- Loading State -->
    <div v-if="isLoading" class="card-loading-state">
      <div class="loading-skeleton">
        <div class="skeleton-header"></div>
        <div class="skeleton-content">
          <div class="skeleton-line"></div>
          <div class="skeleton-line short"></div>
          <div class="skeleton-line"></div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="hasError" class="card-error-state">
      <div class="error-content">
        <ExclamationTriangleIcon class="error-icon" />
        <h3 class="error-title">Failed to load card</h3>
        <p class="error-message">{{ errorMessage }}</p>
        <button
          @click="retryLoad"
          class="retry-button"
          :disabled="isRetrying"
        >
          <ArrowPathIcon class="retry-icon" :class="{ 'animate-spin': isRetrying }" />
          {{ isRetrying ? 'Retrying...' : 'Retry' }}
        </button>
      </div>
    </div>

    <!-- Actual Card Component -->
    <Suspense v-else>
      <template #default>
        <component
          :is="cardComponent"
          v-bind="cardProps"
          :data="cardData"
          :loading="dataLoading"
          :error="dataError"
          @update="handleCardUpdate"
          @error="handleCardError"
          @action="handleCardAction"
          @resize="handleCardResize"
        />
      </template>
      
      <template #fallback>
        <div class="card-suspense-fallback">
          <div class="fallback-spinner">
            <div class="spinner"></div>
          </div>
          <p class="fallback-text">Loading card...</p>
        </div>
      </template>
    </Suspense>

    <!-- Performance Overlay (Development Only) -->
    <div v-if="showPerformanceOverlay && isDevelopment" class="performance-overlay">
      <div class="performance-stats">
        <div class="stat">
          <span class="stat-label">Load:</span>
          <span class="stat-value">{{ loadTime }}ms</span>
        </div>
        <div class="stat">
          <span class="stat-label">Render:</span>
          <span class="stat-value">{{ renderTime }}ms</span>
        </div>
        <div class="stat">
          <span class="stat-label">Size:</span>
          <span class="stat-value">{{ componentSize }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch, nextTick, defineAsyncComponent } from 'vue'
import { ExclamationTriangleIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import { usePerformanceOptimization } from '@/composables/usePerformanceOptimization'
import lazyLoadingService from '@/services/LazyLoadingService'
import performanceMonitoringService from '@/services/PerformanceMonitoringService'

export default {
  name: 'OptimizedDashboardCard',
  components: {
    ExclamationTriangleIcon,
    ArrowPathIcon
  },
  props: {
    card: {
      type: Object,
      required: true
    },
    enableLazyLoading: {
      type: Boolean,
      default: true
    },
    enablePerformanceMonitoring: {
      type: Boolean,
      default: true
    },
    showPerformanceOverlay: {
      type: Boolean,
      default: false
    },
    priority: {
      type: Number,
      default: 0
    },
    preload: {
      type: Boolean,
      default: false
    },
    cacheStrategy: {
      type: String,
      default: 'stale-while-revalidate',
      validator: (value) => ['cache-first', 'network-first', 'stale-while-revalidate'].includes(value)
    }
  },
  emits: [
    'card-loaded',
    'card-error',
    'card-update',
    'card-action',
    'performance-metrics'
  ],
  setup(props, { emit }) {
    // Composables
    const performance = usePerformanceOptimization()

    // Refs
    const cardContainer = ref(null)
    const cardComponent = ref(null)
    const isLoading = ref(true)
    const hasError = ref(false)
    const errorMessage = ref('')
    const isRetrying = ref(false)
    const dataLoading = ref(false)
    const dataError = ref(null)
    const cardData = ref(null)
    const loadTime = ref(0)
    const renderTime = ref(0)
    const componentSize = ref('0KB')

    // Computed properties
    const cardClasses = computed(() => [
      'optimized-dashboard-card-base',
      {
        'is-loading': isLoading.value,
        'has-error': hasError.value,
        'is-retrying': isRetrying.value,
        'lazy-enabled': props.enableLazyLoading,
        [`priority-${props.priority}`]: props.priority > 0
      }
    ])

    const cardProps = computed(() => {
      const { component, ...otherProps } = props.card
      return otherProps
    })

    const componentPath = computed(() => {
      return props.card.component || 'DefaultCard'
    })

    const isDevelopment = computed(() => {
      return process.env.NODE_ENV === 'development'
    })

    // Methods
    const loadCardComponent = async () => {
      if (!componentPath.value) {
        throw new Error('No component path specified')
      }

      const startTime = performance.now()
      performanceMonitoringService.startTimer(`card-load-${componentPath.value}`)

      try {
        // Create lazy component with performance monitoring
        cardComponent.value = performance.createLazyComponent(
          () => import(`../Cards/${componentPath.value}.vue`),
          createFallbackComponent()
        )

        const endTime = performance.now()
        loadTime.value = Math.round(endTime - startTime)
        
        performanceMonitoringService.endTimer(`card-load-${componentPath.value}`)
        performanceMonitoringService.incrementCounter('cards-loaded')

        // Estimate component size (rough approximation)
        componentSize.value = estimateComponentSize()

        emit('card-loaded', {
          component: componentPath.value,
          loadTime: loadTime.value,
          size: componentSize.value
        })

        return cardComponent.value
      } catch (error) {
        performanceMonitoringService.incrementCounter('cards-failed')
        throw error
      }
    }

    const loadCardData = async () => {
      if (!props.card.dataUrl) return

      dataLoading.value = true
      dataError.value = null

      try {
        const response = await fetch(props.card.dataUrl, {
          cache: props.cacheStrategy === 'cache-first' ? 'force-cache' : 'default'
        })

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }

        cardData.value = await response.json()
        performanceMonitoringService.incrementCounter('data-requests-success')
      } catch (error) {
        dataError.value = error.message
        performanceMonitoringService.incrementCounter('data-requests-failed')
        console.error('Failed to load card data:', error)
      } finally {
        dataLoading.value = false
      }
    }

    const initializeCard = async () => {
      isLoading.value = true
      hasError.value = false
      errorMessage.value = ''

      try {
        // Measure render time
        const renderStartTime = performance.now()

        // Load component and data concurrently
        const [component] = await Promise.all([
          loadCardComponent(),
          loadCardData()
        ])

        await nextTick()
        
        const renderEndTime = performance.now()
        renderTime.value = Math.round(renderEndTime - renderStartTime)

        // Emit performance metrics
        if (props.enablePerformanceMonitoring) {
          emit('performance-metrics', {
            component: componentPath.value,
            loadTime: loadTime.value,
            renderTime: renderTime.value,
            size: componentSize.value
          })
        }

        isLoading.value = false
      } catch (error) {
        hasError.value = true
        errorMessage.value = error.message
        isLoading.value = false
        
        emit('card-error', {
          component: componentPath.value,
          error: error.message
        })
      }
    }

    const retryLoad = async () => {
      isRetrying.value = true
      
      try {
        await new Promise(resolve => setTimeout(resolve, 1000)) // Brief delay
        await initializeCard()
      } finally {
        isRetrying.value = false
      }
    }

    const createFallbackComponent = () => {
      return {
        template: `
          <div class="card-fallback">
            <div class="fallback-icon">📊</div>
            <h3>Card Unavailable</h3>
            <p>This card component could not be loaded.</p>
          </div>
        `
      }
    }

    const estimateComponentSize = () => {
      // Rough estimation based on component complexity
      const baseSize = 2 // KB
      const propsSize = JSON.stringify(cardProps.value).length / 1024
      const totalSize = baseSize + propsSize
      
      return totalSize < 1 ? `${Math.round(totalSize * 1024)}B` : `${totalSize.toFixed(1)}KB`
    }

    // Event handlers
    const handleCardUpdate = (data) => {
      cardData.value = { ...cardData.value, ...data }
      emit('card-update', data)
    }

    const handleCardError = (error) => {
      dataError.value = error
      emit('card-error', error)
    }

    const handleCardAction = (action, data) => {
      emit('card-action', action, data)
    }

    const handleCardResize = () => {
      // Handle card resize for responsive layouts
      if (cardContainer.value) {
        const rect = cardContainer.value.getBoundingClientRect()
        performanceMonitoringService.recordHistogram('card-width', rect.width)
        performanceMonitoringService.recordHistogram('card-height', rect.height)
      }
    }

    // Intersection observer for lazy loading
    const setupLazyLoading = () => {
      if (!props.enableLazyLoading || !cardContainer.value) return

      lazyLoadingService.observe(cardContainer.value)
      
      // Listen for lazy loading events
      cardContainer.value.addEventListener('component-loaded', (event) => {
        initializeCard()
      })
    }

    // Preloading
    const setupPreloading = () => {
      if (props.preload) {
        lazyLoadingService.preload([{
          type: 'component',
          src: `../Cards/${componentPath.value}.vue`,
          priority: props.priority
        }])
      }
    }

    // Lifecycle
    onMounted(() => {
      if (props.enableLazyLoading) {
        setupLazyLoading()
      } else {
        initializeCard()
      }
      
      setupPreloading()
    })

    onUnmounted(() => {
      // Cleanup performance timers
      performanceMonitoringService.endTimer(`card-load-${componentPath.value}`)
    })

    // Watch for card changes
    watch(() => props.card, () => {
      initializeCard()
    }, { deep: true })

    return {
      // Refs
      cardContainer,
      cardComponent,
      isLoading,
      hasError,
      errorMessage,
      isRetrying,
      dataLoading,
      dataError,
      cardData,
      loadTime,
      renderTime,
      componentSize,

      // Computed
      cardClasses,
      cardProps,
      componentPath,
      isDevelopment,

      // Methods
      retryLoad,
      handleCardUpdate,
      handleCardError,
      handleCardAction,
      handleCardResize
    }
  }
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.optimized-dashboard-card {
  @apply relative bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden;
  min-height: 200px;
  transition: all 0.2s ease;
}

.optimized-dashboard-card:hover {
  @apply shadow-md;
}

.optimized-dashboard-card.is-loading {
  @apply bg-gray-50;
}

.optimized-dashboard-card.has-error {
  @apply border-red-200 bg-red-50;
}

/* Loading State */
.card-loading-state {
  @apply p-6;
}

.loading-skeleton {
  @apply animate-pulse;
}

.skeleton-header {
  @apply h-4 bg-gray-300 rounded mb-4;
  width: 60%;
}

.skeleton-content {
  @apply space-y-3;
}

.skeleton-line {
  @apply h-3 bg-gray-300 rounded;
}

.skeleton-line.short {
  width: 40%;
}

/* Error State */
.card-error-state {
  @apply p-6 text-center;
}

.error-content {
  @apply space-y-4;
}

.error-icon {
  @apply w-12 h-12 text-red-500 mx-auto;
}

.error-title {
  @apply text-lg font-semibold text-red-900;
}

.error-message {
  @apply text-sm text-red-700;
}

.retry-button {
  @apply inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50;
}

.retry-icon {
  @apply w-4 h-4 mr-2;
}

/* Suspense Fallback */
.card-suspense-fallback {
  @apply p-6 text-center;
}

.fallback-spinner {
  @apply mb-4;
}

.spinner {
  @apply w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto;
}

.fallback-text {
  @apply text-sm text-gray-600;
}

/* Performance Overlay */
.performance-overlay {
  @apply absolute top-2 right-2 bg-black/75 text-white text-xs rounded px-2 py-1;
}

.performance-stats {
  @apply space-y-1;
}

.stat {
  @apply flex justify-between;
}

.stat-label {
  @apply mr-2;
}

.stat-value {
  @apply font-mono;
}

/* Card Fallback */
.card-fallback {
  @apply p-6 text-center text-gray-500;
}

.fallback-icon {
  @apply text-4xl mb-2;
}

/* Priority-based styling */
.priority-1 {
  @apply ring-1 ring-blue-200;
}

.priority-2 {
  @apply ring-2 ring-blue-300;
}

.priority-3 {
  @apply ring-2 ring-blue-500;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .optimized-dashboard-card {
    @apply bg-gray-800 border-gray-700;
  }

  .optimized-dashboard-card.is-loading {
    @apply bg-gray-900;
  }

  .skeleton-header,
  .skeleton-line {
    @apply bg-gray-600;
  }

  .fallback-text {
    @apply text-gray-400;
  }
}
</style>
