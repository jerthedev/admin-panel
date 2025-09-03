<template>
  <div class="dashboard-transition-container">
    <!-- Main Content with Transition -->
    <Transition
      :name="transitionName"
      :mode="transitionMode"
      :duration="transitionDuration"
      @before-enter="onBeforeEnter"
      @enter="onEnter"
      @after-enter="onAfterEnter"
      @before-leave="onBeforeLeave"
      @leave="onLeave"
      @after-leave="onAfterLeave"
    >
      <div
        :key="currentKey"
        class="dashboard-content"
        :class="contentClasses"
      >
        <slot :transition-state="transitionState" />
      </div>
    </Transition>

    <!-- Loading Overlay -->
    <DashboardLoading
      :is-visible="showLoading"
      :variant="loadingVariant"
      :message="loadingMessage"
      :progress="transitionProgress"
      :show-progress="showProgress"
      :show-cancel="showCancel"
      :has-error="hasTransitionError"
      :error-message="transitionErrorMessage"
      :theme="theme"
      @retry="handleRetry"
      @cancel="handleCancel"
    />

    <!-- Error Overlay -->
    <DashboardError
      v-if="showError && !showLoading"
      :type="errorType"
      :title="errorTitle"
      :message="errorMessage"
      :details="errorDetails"
      :suggestions="errorSuggestions"
      :error-id="errorId"
      :show-retry="showRetry"
      :show-go-back="showGoBack"
      :show-go-home="showGoHome"
      :show-refresh="showRefresh"
      :is-retrying="isRetrying"
      :variant="errorVariant"
      :theme="theme"
      @retry="handleRetry"
      @go-back="handleGoBack"
      @go-home="handleGoHome"
      @refresh="handleRefresh"
      @report="handleReport"
    />
  </div>
</template>

<script>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useDashboardTransitions } from '@/composables/useDashboardTransitions'
import { useDashboardNavigationStore } from '@/stores/dashboardNavigation'
import DashboardLoading from './DashboardLoading.vue'
import DashboardError from './DashboardError.vue'

export default {
  name: 'DashboardTransition',
  components: {
    DashboardLoading,
    DashboardError
  },
  props: {
    // Transition Configuration
    animation: {
      type: String,
      default: 'fade',
      validator: (value) => [
        'fade', 'slide', 'slideLeft', 'slideRight', 'slideUp', 'slideDown',
        'scale', 'flip', 'zoom', 'none'
      ].includes(value)
    },
    duration: {
      type: [Number, Object],
      default: 300
    },
    mode: {
      type: String,
      default: 'out-in',
      validator: (value) => ['in-out', 'out-in', 'default'].includes(value)
    },
    
    // Loading Configuration
    showLoading: {
      type: Boolean,
      default: true
    },
    loadingVariant: {
      type: String,
      default: 'spinner'
    },
    loadingMessage: {
      type: String,
      default: 'Loading dashboard...'
    },
    showProgress: {
      type: Boolean,
      default: true
    },
    showCancel: {
      type: Boolean,
      default: true
    },
    
    // Error Configuration
    showError: {
      type: Boolean,
      default: true
    },
    errorVariant: {
      type: String,
      default: 'card'
    },
    showRetry: {
      type: Boolean,
      default: true
    },
    showGoBack: {
      type: Boolean,
      default: true
    },
    showGoHome: {
      type: Boolean,
      default: true
    },
    showRefresh: {
      type: Boolean,
      default: false
    },
    
    // Appearance
    theme: {
      type: String,
      default: 'light'
    },
    
    // Advanced Options
    preserveHeight: {
      type: Boolean,
      default: false
    },
    enableGestures: {
      type: Boolean,
      default: false
    },
    gestureThreshold: {
      type: Number,
      default: 50
    }
  },
  emits: [
    'transition-start', 'transition-end', 'transition-error',
    'before-enter', 'enter', 'after-enter',
    'before-leave', 'leave', 'after-leave'
  ],
  setup(props, { emit }) {
    // Composables
    const transitions = useDashboardTransitions()
    const navigationStore = useDashboardNavigationStore()

    // State
    const transitionState = ref('idle') // idle, entering, leaving, error
    const currentKey = ref(Date.now())
    const isRetrying = ref(false)
    const gestureStartX = ref(0)
    const gestureStartY = ref(0)

    // Computed
    const transitionName = computed(() => {
      if (props.animation === 'none') return ''
      return `dashboard-${props.animation}`
    })

    const transitionMode = computed(() => {
      return props.mode === 'default' ? undefined : props.mode
    })

    const transitionDuration = computed(() => {
      if (typeof props.duration === 'number') {
        return { enter: props.duration, leave: props.duration }
      }
      return props.duration
    })

    const contentClasses = computed(() => [
      {
        'preserve-height': props.preserveHeight,
        'with-gestures': props.enableGestures
      }
    ])

    const showLoading = computed(() => {
      return props.showLoading && transitions.isTransitioning.value
    })

    const hasTransitionError = computed(() => {
      return transitions.hasError.value
    })

    const transitionProgress = computed(() => {
      return transitions.transitionProgress.value
    })

    const transitionErrorMessage = computed(() => {
      return transitions.transitionError.value?.message || 'Transition failed'
    })

    const showError = computed(() => {
      return props.showError && transitions.hasError.value && !transitions.isTransitioning.value
    })

    const errorType = computed(() => {
      const error = transitions.transitionError.value
      if (!error) return 'general'
      
      if (error.message.includes('timeout')) return 'timeout'
      if (error.message.includes('network') || error.message.includes('fetch')) return 'network'
      if (error.message.includes('permission') || error.message.includes('403')) return 'permission'
      if (error.message.includes('404') || error.message.includes('not found')) return 'not-found'
      if (error.message.includes('500') || error.message.includes('server')) return 'server'
      
      return 'general'
    })

    const errorTitle = computed(() => {
      return transitions.transitionError.value?.title || ''
    })

    const errorMessage = computed(() => {
      return transitions.transitionError.value?.message || ''
    })

    const errorDetails = computed(() => {
      return transitions.transitionError.value?.stack || ''
    })

    const errorSuggestions = computed(() => {
      return transitions.transitionError.value?.suggestions || []
    })

    const errorId = computed(() => {
      return transitions.currentTransition.value?.id || ''
    })

    // Methods
    const updateKey = () => {
      currentKey.value = Date.now()
    }

    const handleRetry = async () => {
      isRetrying.value = true
      transitions.clearError()
      
      try {
        const currentDashboard = navigationStore.currentDashboard
        if (currentDashboard) {
          await transitions.refreshDashboard()
        }
      } catch (error) {
        console.error('Retry failed:', error)
      } finally {
        isRetrying.value = false
      }
    }

    const handleCancel = () => {
      transitions.cancelTransition()
      emit('transition-error', new Error('Transition cancelled by user'))
    }

    const handleGoBack = () => {
      transitions.navigateBack()
    }

    const handleGoHome = () => {
      const mainDashboard = { uriKey: 'main', name: 'Dashboard' }
      transitions.navigateToDashboard(mainDashboard)
    }

    const handleRefresh = () => {
      window.location.reload()
    }

    const handleReport = () => {
      // Implement error reporting logic
      console.log('Report error:', transitions.transitionError.value)
    }

    // Transition Event Handlers
    const onBeforeEnter = (el) => {
      transitionState.value = 'entering'
      emit('before-enter', el)
    }

    const onEnter = (el, done) => {
      emit('enter', el, done)
      done()
    }

    const onAfterEnter = (el) => {
      transitionState.value = 'idle'
      emit('after-enter', el)
      emit('transition-end')
    }

    const onBeforeLeave = (el) => {
      transitionState.value = 'leaving'
      emit('before-leave', el)
    }

    const onLeave = (el, done) => {
      emit('leave', el, done)
      done()
    }

    const onAfterLeave = (el) => {
      emit('after-leave', el)
    }

    // Gesture Handling
    const handleTouchStart = (event) => {
      if (!props.enableGestures) return
      
      const touch = event.touches[0]
      gestureStartX.value = touch.clientX
      gestureStartY.value = touch.clientY
    }

    const handleTouchEnd = (event) => {
      if (!props.enableGestures) return
      
      const touch = event.changedTouches[0]
      const deltaX = touch.clientX - gestureStartX.value
      const deltaY = touch.clientY - gestureStartY.value
      
      // Horizontal swipe
      if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > props.gestureThreshold) {
        if (deltaX > 0) {
          // Swipe right - go back
          if (navigationStore.canGoBack) {
            transitions.navigateBack()
          }
        } else {
          // Swipe left - go forward
          if (navigationStore.canGoForward) {
            transitions.navigateForward()
          }
        }
      }
    }

    // Watchers
    watch(() => navigationStore.currentDashboard, () => {
      updateKey()
    })

    watch(() => transitions.isTransitioning.value, (isTransitioning) => {
      if (isTransitioning) {
        emit('transition-start')
      }
    })

    watch(() => transitions.transitionError.value, (error) => {
      if (error) {
        transitionState.value = 'error'
        emit('transition-error', error)
      }
    })

    // Lifecycle
    onMounted(() => {
      if (props.enableGestures) {
        document.addEventListener('touchstart', handleTouchStart, { passive: true })
        document.addEventListener('touchend', handleTouchEnd, { passive: true })
      }
    })

    onUnmounted(() => {
      if (props.enableGestures) {
        document.removeEventListener('touchstart', handleTouchStart)
        document.removeEventListener('touchend', handleTouchEnd)
      }
    })

    return {
      // State
      transitionState,
      currentKey,
      isRetrying,

      // Computed
      transitionName,
      transitionMode,
      transitionDuration,
      contentClasses,
      showLoading,
      hasTransitionError,
      transitionProgress,
      transitionErrorMessage,
      showError,
      errorType,
      errorTitle,
      errorMessage,
      errorDetails,
      errorSuggestions,
      errorId,

      // Methods
      handleRetry,
      handleCancel,
      handleGoBack,
      handleGoHome,
      handleRefresh,
      handleReport,
      onBeforeEnter,
      onEnter,
      onAfterEnter,
      onBeforeLeave,
      onLeave,
      onAfterLeave
    }
  }
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.dashboard-transition-container {
  @apply relative;
}

.dashboard-content {
  @apply w-full;
}

.dashboard-content.preserve-height {
  @apply min-h-screen;
}

.dashboard-content.with-gestures {
  @apply touch-pan-y;
}

/* Fade Transition */
.dashboard-fade-enter-active,
.dashboard-fade-leave-active {
  transition: opacity 0.3s ease;
}

.dashboard-fade-enter-from,
.dashboard-fade-leave-to {
  opacity: 0;
}

/* Slide Transitions */
.dashboard-slide-enter-active,
.dashboard-slide-leave-active {
  transition: transform 0.3s ease;
}

.dashboard-slide-enter-from {
  transform: translateX(100%);
}

.dashboard-slide-leave-to {
  transform: translateX(-100%);
}

.dashboard-slideLeft-enter-active,
.dashboard-slideLeft-leave-active {
  transition: transform 0.3s ease;
}

.dashboard-slideLeft-enter-from {
  transform: translateX(100%);
}

.dashboard-slideLeft-leave-to {
  transform: translateX(-100%);
}

.dashboard-slideRight-enter-active,
.dashboard-slideRight-leave-active {
  transition: transform 0.3s ease;
}

.dashboard-slideRight-enter-from {
  transform: translateX(-100%);
}

.dashboard-slideRight-leave-to {
  transform: translateX(100%);
}

.dashboard-slideUp-enter-active,
.dashboard-slideUp-leave-active {
  transition: transform 0.3s ease;
}

.dashboard-slideUp-enter-from {
  transform: translateY(100%);
}

.dashboard-slideUp-leave-to {
  transform: translateY(-100%);
}

.dashboard-slideDown-enter-active,
.dashboard-slideDown-leave-active {
  transition: transform 0.3s ease;
}

.dashboard-slideDown-enter-from {
  transform: translateY(-100%);
}

.dashboard-slideDown-leave-to {
  transform: translateY(100%);
}

/* Scale Transition */
.dashboard-scale-enter-active,
.dashboard-scale-leave-active {
  transition: all 0.3s ease;
}

.dashboard-scale-enter-from,
.dashboard-scale-leave-to {
  opacity: 0;
  transform: scale(0.9);
}

/* Flip Transition */
.dashboard-flip-enter-active,
.dashboard-flip-leave-active {
  transition: all 0.6s ease;
}

.dashboard-flip-enter-from {
  opacity: 0;
  transform: rotateY(-90deg);
}

.dashboard-flip-leave-to {
  opacity: 0;
  transform: rotateY(90deg);
}

/* Zoom Transition */
.dashboard-zoom-enter-active,
.dashboard-zoom-leave-active {
  transition: all 0.3s ease;
}

.dashboard-zoom-enter-from,
.dashboard-zoom-leave-to {
  opacity: 0;
  transform: scale(1.1);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .dashboard-slide-enter-active,
  .dashboard-slide-leave-active,
  .dashboard-slideLeft-enter-active,
  .dashboard-slideLeft-leave-active,
  .dashboard-slideRight-enter-active,
  .dashboard-slideRight-leave-active {
    transition-duration: 0.2s;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .dashboard-fade-enter-active,
  .dashboard-fade-leave-active,
  .dashboard-slide-enter-active,
  .dashboard-slide-leave-active,
  .dashboard-slideLeft-enter-active,
  .dashboard-slideLeft-leave-active,
  .dashboard-slideRight-enter-active,
  .dashboard-slideRight-leave-active,
  .dashboard-slideUp-enter-active,
  .dashboard-slideUp-leave-active,
  .dashboard-slideDown-enter-active,
  .dashboard-slideDown-leave-active,
  .dashboard-scale-enter-active,
  .dashboard-scale-leave-active,
  .dashboard-flip-enter-active,
  .dashboard-flip-leave-active,
  .dashboard-zoom-enter-active,
  .dashboard-zoom-leave-active {
    transition: none;
  }
}
</style>
