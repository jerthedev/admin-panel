<template>
  <Transition
    enter-active-class="transition-opacity duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-opacity duration-150"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="isVisible"
      class="dashboard-loading-overlay"
      :class="overlayClasses"
      data-testid="dashboard-loading"
    >
      <!-- Progress Bar -->
      <div v-if="showProgress" class="progress-container">
        <div class="progress-bar">
          <div 
            class="progress-fill"
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
        <div class="progress-text">
          {{ progressText }}
        </div>
      </div>

      <!-- Loading Content -->
      <div v-if="!hasError" class="loading-content">
        <!-- Spinner -->
        <div v-if="variant === 'spinner'" class="loading-spinner">
          <div class="spinner-ring">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
          </div>
          <p class="loading-message">{{ message }}</p>
        </div>

        <!-- Skeleton -->
        <div v-else-if="variant === 'skeleton'" class="loading-skeleton">
          <div class="skeleton-header">
            <div class="skeleton-title"></div>
            <div class="skeleton-actions">
              <div class="skeleton-button"></div>
              <div class="skeleton-button"></div>
            </div>
          </div>
          <div class="skeleton-content">
            <div class="skeleton-card" v-for="i in 3" :key="i">
              <div class="skeleton-card-header"></div>
              <div class="skeleton-card-body">
                <div class="skeleton-line"></div>
                <div class="skeleton-line short"></div>
                <div class="skeleton-line medium"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pulse -->
        <div v-else-if="variant === 'pulse'" class="loading-pulse">
          <div class="pulse-circle">
            <div class="pulse-ring"></div>
            <div class="pulse-ring"></div>
            <div class="pulse-ring"></div>
          </div>
          <p class="loading-message">{{ message }}</p>
        </div>

        <!-- Dots -->
        <div v-else-if="variant === 'dots'" class="loading-dots">
          <div class="dots-container">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
          </div>
          <p class="loading-message">{{ message }}</p>
        </div>

        <!-- Default (fade) -->
        <div v-else class="loading-fade">
          <div class="fade-content">
            <div class="fade-icon">
              <ChartBarIcon class="h-8 w-8" />
            </div>
            <p class="loading-message">{{ message }}</p>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="hasError" class="error-content">
        <div class="error-icon">
          <ExclamationTriangleIcon class="h-8 w-8 text-red-500" />
        </div>
        <h3 class="error-title">Loading Failed</h3>
        <p class="error-message">{{ errorMessage }}</p>
        <div class="error-actions">
          <button
            @click="$emit('retry')"
            class="retry-button"
            type="button"
          >
            <ArrowPathIcon class="h-4 w-4 mr-2" />
            Retry
          </button>
          <button
            @click="$emit('cancel')"
            class="cancel-button"
            type="button"
          >
            Cancel
          </button>
        </div>
      </div>

      <!-- Cancel Button -->
      <button
        v-if="showCancel && !hasError"
        @click="$emit('cancel')"
        class="cancel-overlay-button"
        type="button"
        aria-label="Cancel loading"
      >
        <XMarkIcon class="h-5 w-5" />
      </button>
    </div>
  </Transition>
</template>

<script>
import { computed } from 'vue'
import { 
  ChartBarIcon, 
  ExclamationTriangleIcon, 
  ArrowPathIcon, 
  XMarkIcon 
} from '@heroicons/vue/24/outline'

export default {
  name: 'DashboardLoading',
  components: {
    ChartBarIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    XMarkIcon
  },
  props: {
    isVisible: {
      type: Boolean,
      default: false
    },
    variant: {
      type: String,
      default: 'fade',
      validator: (value) => ['spinner', 'skeleton', 'pulse', 'dots', 'fade'].includes(value)
    },
    message: {
      type: String,
      default: 'Loading dashboard...'
    },
    progress: {
      type: Number,
      default: 0,
      validator: (value) => value >= 0 && value <= 100
    },
    showProgress: {
      type: Boolean,
      default: false
    },
    showCancel: {
      type: Boolean,
      default: false
    },
    overlay: {
      type: Boolean,
      default: true
    },
    hasError: {
      type: Boolean,
      default: false
    },
    errorMessage: {
      type: String,
      default: 'Failed to load dashboard. Please try again.'
    },
    theme: {
      type: String,
      default: 'light',
      validator: (value) => ['light', 'dark'].includes(value)
    }
  },
  emits: ['retry', 'cancel'],
  setup(props) {
    const overlayClasses = computed(() => [
      {
        'with-overlay': props.overlay,
        'without-overlay': !props.overlay,
        'dark-theme': props.theme === 'dark',
        'light-theme': props.theme === 'light'
      }
    ])

    const progressText = computed(() => {
      if (props.progress === 0) return 'Initializing...'
      if (props.progress < 25) return 'Loading...'
      if (props.progress < 50) return 'Fetching data...'
      if (props.progress < 75) return 'Rendering...'
      if (props.progress < 100) return 'Almost done...'
      return 'Complete!'
    })

    return {
      overlayClasses,
      progressText
    }
  }
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.dashboard-loading-overlay {
  @apply fixed inset-0 z-50 flex items-center justify-center;
}

.dashboard-loading-overlay.with-overlay {
  @apply bg-white/90 backdrop-blur-sm;
}

.dashboard-loading-overlay.without-overlay {
  @apply bg-transparent pointer-events-none;
}

.dashboard-loading-overlay.dark-theme.with-overlay {
  @apply bg-gray-900/90;
}

.progress-container {
  @apply absolute top-0 left-0 right-0 p-6;
}

.progress-bar {
  @apply w-full h-1 bg-gray-200 rounded-full overflow-hidden;
}

.progress-fill {
  @apply h-full bg-blue-500 transition-all duration-300 ease-out;
}

.progress-text {
  @apply text-sm text-gray-600 mt-2 text-center;
}

.dark-theme .progress-bar {
  @apply bg-gray-700;
}

.dark-theme .progress-text {
  @apply text-gray-400;
}

.loading-content {
  @apply flex flex-col items-center justify-center p-8;
}

/* Spinner Variant */
.loading-spinner {
  @apply flex flex-col items-center;
}

.spinner-ring {
  @apply relative w-12 h-12;
}

.spinner-ring div {
  @apply absolute border-4 border-gray-200 rounded-full;
  width: 48px;
  height: 48px;
  animation: spinner 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
  border-color: #3b82f6 transparent transparent transparent;
}

.spinner-ring div:nth-child(1) { animation-delay: -0.45s; }
.spinner-ring div:nth-child(2) { animation-delay: -0.3s; }
.spinner-ring div:nth-child(3) { animation-delay: -0.15s; }

@keyframes spinner {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Skeleton Variant */
.loading-skeleton {
  @apply w-full max-w-4xl;
}

.skeleton-header {
  @apply flex items-center justify-between mb-6;
}

.skeleton-title {
  @apply h-8 w-64 bg-gray-200 rounded animate-pulse;
}

.skeleton-actions {
  @apply flex space-x-3;
}

.skeleton-button {
  @apply h-8 w-20 bg-gray-200 rounded animate-pulse;
}

.skeleton-content {
  @apply grid grid-cols-1 md:grid-cols-3 gap-6;
}

.skeleton-card {
  @apply bg-white border border-gray-200 rounded-lg p-6;
}

.skeleton-card-header {
  @apply h-6 w-32 bg-gray-200 rounded animate-pulse mb-4;
}

.skeleton-line {
  @apply h-4 bg-gray-200 rounded animate-pulse mb-2;
}

.skeleton-line.short {
  @apply w-3/4;
}

.skeleton-line.medium {
  @apply w-1/2;
}

.dark-theme .skeleton-title,
.dark-theme .skeleton-button,
.dark-theme .skeleton-card-header,
.dark-theme .skeleton-line {
  @apply bg-gray-700;
}

.dark-theme .skeleton-card {
  @apply bg-gray-800 border-gray-700;
}

/* Pulse Variant */
.loading-pulse {
  @apply flex flex-col items-center;
}

.pulse-circle {
  @apply relative w-16 h-16 mb-4;
}

.pulse-ring {
  @apply absolute inset-0 border-4 border-blue-500 rounded-full;
  animation: pulse-ring 1.5s ease-out infinite;
}

.pulse-ring:nth-child(2) { animation-delay: 0.5s; }
.pulse-ring:nth-child(3) { animation-delay: 1s; }

@keyframes pulse-ring {
  0% {
    transform: scale(0.8);
    opacity: 1;
  }
  100% {
    transform: scale(1.4);
    opacity: 0;
  }
}

/* Dots Variant */
.loading-dots {
  @apply flex flex-col items-center;
}

.dots-container {
  @apply flex space-x-2 mb-4;
}

.dot {
  @apply w-3 h-3 bg-blue-500 rounded-full;
  animation: dot-bounce 1.4s ease-in-out infinite both;
}

.dot:nth-child(1) { animation-delay: -0.32s; }
.dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes dot-bounce {
  0%, 80%, 100% {
    transform: scale(0);
  }
  40% {
    transform: scale(1);
  }
}

/* Fade Variant */
.loading-fade {
  @apply flex flex-col items-center;
}

.fade-content {
  @apply flex flex-col items-center;
}

.fade-icon {
  @apply text-blue-500 mb-4;
  animation: fade-pulse 2s ease-in-out infinite;
}

@keyframes fade-pulse {
  0%, 100% { opacity: 0.4; }
  50% { opacity: 1; }
}

/* Common Styles */
.loading-message {
  @apply text-gray-600 text-center mt-4;
}

.dark-theme .loading-message {
  @apply text-gray-400;
}

/* Error State */
.error-content {
  @apply flex flex-col items-center text-center p-8;
}

.error-icon {
  @apply mb-4;
}

.error-title {
  @apply text-lg font-semibold text-gray-900 mb-2;
}

.error-message {
  @apply text-gray-600 mb-6;
}

.error-actions {
  @apply flex space-x-3;
}

.retry-button {
  @apply inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors;
}

.cancel-button {
  @apply inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors;
}

.dark-theme .error-title {
  @apply text-gray-100;
}

.dark-theme .error-message {
  @apply text-gray-400;
}

.dark-theme .cancel-button {
  @apply bg-gray-600 text-gray-300 hover:bg-gray-500;
}

/* Cancel Button */
.cancel-overlay-button {
  @apply absolute top-4 right-4 p-2 text-gray-500 hover:text-gray-700 bg-white rounded-full shadow-md hover:shadow-lg transition-all;
}

.dark-theme .cancel-overlay-button {
  @apply bg-gray-800 text-gray-400 hover:text-gray-200;
}

/* Responsive */
@media (max-width: 768px) {
  .loading-content {
    @apply p-4;
  }
  
  .skeleton-content {
    @apply grid-cols-1;
  }
  
  .skeleton-header {
    @apply flex-col items-start space-y-3;
  }
  
  .skeleton-actions {
    @apply w-full justify-start;
  }
}
</style>
