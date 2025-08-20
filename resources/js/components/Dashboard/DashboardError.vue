<template>
  <div
    class="dashboard-error"
    :class="containerClasses"
    data-testid="dashboard-error"
  >
    <!-- Error Icon -->
    <div class="error-icon-container">
      <component
        :is="getErrorIcon()"
        class="error-icon"
        :class="iconClasses"
      />
    </div>

    <!-- Error Content -->
    <div class="error-content">
      <h2 class="error-title">{{ title }}</h2>
      <p class="error-message">{{ message }}</p>
      
      <!-- Error Details (collapsible) -->
      <div v-if="showDetails && details" class="error-details">
        <button
          @click="toggleDetails"
          class="details-toggle"
          type="button"
        >
          <span>{{ detailsVisible ? 'Hide' : 'Show' }} Details</span>
          <ChevronDownIcon 
            class="details-chevron"
            :class="{ 'rotate-180': detailsVisible }"
          />
        </button>
        
        <Transition
          enter-active-class="transition-all duration-200"
          enter-from-class="opacity-0 max-h-0"
          enter-to-class="opacity-100 max-h-96"
          leave-active-class="transition-all duration-200"
          leave-from-class="opacity-100 max-h-96"
          leave-to-class="opacity-0 max-h-0"
        >
          <div v-if="detailsVisible" class="details-content">
            <pre class="details-text">{{ details }}</pre>
          </div>
        </Transition>
      </div>

      <!-- Suggestions -->
      <div v-if="suggestions.length > 0" class="error-suggestions">
        <h3 class="suggestions-title">Try these solutions:</h3>
        <ul class="suggestions-list">
          <li 
            v-for="(suggestion, index) in suggestions" 
            :key="index"
            class="suggestion-item"
          >
            {{ suggestion }}
          </li>
        </ul>
      </div>
    </div>

    <!-- Actions -->
    <div class="error-actions">
      <button
        v-if="showRetry"
        @click="$emit('retry')"
        class="action-button primary"
        type="button"
        :disabled="isRetrying"
      >
        <ArrowPathIcon 
          class="action-icon"
          :class="{ 'animate-spin': isRetrying }"
        />
        {{ isRetrying ? 'Retrying...' : 'Retry' }}
      </button>

      <button
        v-if="showGoBack"
        @click="$emit('go-back')"
        class="action-button secondary"
        type="button"
      >
        <ArrowLeftIcon class="action-icon" />
        Go Back
      </button>

      <button
        v-if="showGoHome"
        @click="$emit('go-home')"
        class="action-button secondary"
        type="button"
      >
        <HomeIcon class="action-icon" />
        Go Home
      </button>

      <button
        v-if="showRefresh"
        @click="$emit('refresh')"
        class="action-button secondary"
        type="button"
      >
        <ArrowPathIcon class="action-icon" />
        Refresh Page
      </button>

      <button
        v-if="showReport"
        @click="$emit('report')"
        class="action-button tertiary"
        type="button"
      >
        <ExclamationTriangleIcon class="action-icon" />
        Report Issue
      </button>
    </div>

    <!-- Footer -->
    <div v-if="showFooter" class="error-footer">
      <p class="footer-text">
        If this problem persists, please contact support.
      </p>
      <p v-if="errorId" class="error-id">
        Error ID: {{ errorId }}
      </p>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import {
  ExclamationTriangleIcon,
  XCircleIcon,
  WifiIcon,
  ClockIcon,
  ShieldExclamationIcon,
  ArrowPathIcon,
  ArrowLeftIcon,
  HomeIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'DashboardError',
  components: {
    ExclamationTriangleIcon,
    XCircleIcon,
    WifiIcon,
    ClockIcon,
    ShieldExclamationIcon,
    ArrowPathIcon,
    ArrowLeftIcon,
    HomeIcon,
    ChevronDownIcon
  },
  props: {
    type: {
      type: String,
      default: 'general',
      validator: (value) => [
        'general', 'network', 'timeout', 'permission', 
        'not-found', 'server', 'validation'
      ].includes(value)
    },
    title: {
      type: String,
      default: ''
    },
    message: {
      type: String,
      default: ''
    },
    details: {
      type: String,
      default: ''
    },
    suggestions: {
      type: Array,
      default: () => []
    },
    errorId: {
      type: String,
      default: ''
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
    showReport: {
      type: Boolean,
      default: false
    },
    showDetails: {
      type: Boolean,
      default: true
    },
    showFooter: {
      type: Boolean,
      default: true
    },
    isRetrying: {
      type: Boolean,
      default: false
    },
    variant: {
      type: String,
      default: 'card',
      validator: (value) => ['card', 'inline', 'fullscreen'].includes(value)
    },
    theme: {
      type: String,
      default: 'light',
      validator: (value) => ['light', 'dark'].includes(value)
    }
  },
  emits: ['retry', 'go-back', 'go-home', 'refresh', 'report'],
  setup(props) {
    const detailsVisible = ref(false)

    const containerClasses = computed(() => [
      `variant-${props.variant}`,
      `theme-${props.theme}`,
      `type-${props.type}`
    ])

    const iconClasses = computed(() => [
      {
        'text-red-500': props.type === 'general' || props.type === 'server',
        'text-orange-500': props.type === 'network' || props.type === 'timeout',
        'text-yellow-500': props.type === 'permission',
        'text-blue-500': props.type === 'not-found',
        'text-purple-500': props.type === 'validation'
      }
    ])

    const computedTitle = computed(() => {
      if (props.title) return props.title

      const titles = {
        general: 'Something went wrong',
        network: 'Connection problem',
        timeout: 'Request timed out',
        permission: 'Access denied',
        'not-found': 'Dashboard not found',
        server: 'Server error',
        validation: 'Invalid request'
      }

      return titles[props.type] || 'Error occurred'
    })

    const computedMessage = computed(() => {
      if (props.message) return props.message

      const messages = {
        general: 'An unexpected error occurred while loading the dashboard.',
        network: 'Unable to connect to the server. Please check your internet connection.',
        timeout: 'The request took too long to complete. Please try again.',
        permission: 'You don\'t have permission to access this dashboard.',
        'not-found': 'The requested dashboard could not be found.',
        server: 'The server encountered an error while processing your request.',
        validation: 'The request contains invalid data or parameters.'
      }

      return messages[props.type] || 'Please try again or contact support if the problem persists.'
    })

    const computedSuggestions = computed(() => {
      if (props.suggestions.length > 0) return props.suggestions

      const suggestions = {
        general: [
          'Refresh the page and try again',
          'Clear your browser cache',
          'Try a different browser'
        ],
        network: [
          'Check your internet connection',
          'Try refreshing the page',
          'Contact your network administrator'
        ],
        timeout: [
          'Try again in a few moments',
          'Check your internet connection',
          'Contact support if this continues'
        ],
        permission: [
          'Contact your administrator for access',
          'Try logging out and back in',
          'Verify your account permissions'
        ],
        'not-found': [
          'Check the dashboard URL',
          'Try going back to the main dashboard',
          'Contact support if the dashboard should exist'
        ],
        server: [
          'Try again in a few minutes',
          'Contact support if this continues',
          'Check the system status page'
        ],
        validation: [
          'Check your input data',
          'Try refreshing the page',
          'Contact support for assistance'
        ]
      }

      return suggestions[props.type] || []
    })

    const getErrorIcon = () => {
      const icons = {
        general: ExclamationTriangleIcon,
        network: WifiIcon,
        timeout: ClockIcon,
        permission: ShieldExclamationIcon,
        'not-found': XCircleIcon,
        server: ExclamationTriangleIcon,
        validation: ExclamationTriangleIcon
      }

      return icons[props.type] || ExclamationTriangleIcon
    }

    const toggleDetails = () => {
      detailsVisible.value = !detailsVisible.value
    }

    return {
      detailsVisible,
      containerClasses,
      iconClasses,
      title: computedTitle,
      message: computedMessage,
      suggestions: computedSuggestions,
      getErrorIcon,
      toggleDetails
    }
  }
}
</script>

<style scoped>
.dashboard-error {
  @apply flex flex-col items-center text-center;
}

/* Variants */
.variant-card {
  @apply bg-white border border-gray-200 rounded-lg shadow-sm p-8 max-w-lg mx-auto;
}

.variant-inline {
  @apply p-6;
}

.variant-fullscreen {
  @apply min-h-screen justify-center p-8;
}

/* Themes */
.theme-dark.variant-card {
  @apply bg-gray-800 border-gray-700;
}

.theme-dark .error-title {
  @apply text-gray-100;
}

.theme-dark .error-message,
.theme-dark .footer-text {
  @apply text-gray-300;
}

.theme-dark .error-id {
  @apply text-gray-500;
}

/* Error Icon */
.error-icon-container {
  @apply mb-6;
}

.error-icon {
  @apply h-16 w-16;
}

/* Content */
.error-content {
  @apply mb-8;
}

.error-title {
  @apply text-2xl font-bold text-gray-900 mb-4;
}

.error-message {
  @apply text-gray-600 mb-6 leading-relaxed;
}

/* Details */
.error-details {
  @apply mt-6 text-left;
}

.details-toggle {
  @apply inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition-colors;
}

.details-chevron {
  @apply h-4 w-4 ml-1 transition-transform duration-200;
}

.details-content {
  @apply mt-3 overflow-hidden;
}

.details-text {
  @apply text-xs text-gray-500 bg-gray-50 p-3 rounded border overflow-x-auto;
}

.theme-dark .details-text {
  @apply text-gray-400 bg-gray-700;
}

/* Suggestions */
.error-suggestions {
  @apply mt-6 text-left;
}

.suggestions-title {
  @apply text-sm font-semibold text-gray-700 mb-3;
}

.suggestions-list {
  @apply space-y-2;
}

.suggestion-item {
  @apply text-sm text-gray-600 flex items-start;
}

.suggestion-item::before {
  content: "•";
  @apply text-gray-400 mr-2 mt-0.5;
}

.theme-dark .suggestions-title {
  @apply text-gray-300;
}

.theme-dark .suggestion-item {
  @apply text-gray-400;
}

/* Actions */
.error-actions {
  @apply flex flex-wrap gap-3 justify-center;
}

.action-button {
  @apply inline-flex items-center px-4 py-2 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.action-button.primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
}

.action-button.secondary {
  @apply bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-500;
}

.action-button.tertiary {
  @apply bg-transparent text-gray-500 hover:text-gray-700 border border-gray-300 hover:border-gray-400 focus:ring-gray-500;
}

.action-button:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.action-icon {
  @apply h-4 w-4 mr-2;
}

.theme-dark .action-button.secondary {
  @apply bg-gray-700 text-gray-300 hover:bg-gray-600;
}

.theme-dark .action-button.tertiary {
  @apply text-gray-400 hover:text-gray-300 border-gray-600 hover:border-gray-500;
}

/* Footer */
.error-footer {
  @apply mt-8 pt-6 border-t border-gray-200;
}

.footer-text {
  @apply text-sm text-gray-500 mb-2;
}

.error-id {
  @apply text-xs text-gray-400 font-mono;
}

.theme-dark .error-footer {
  @apply border-gray-700;
}

/* Responsive */
@media (max-width: 640px) {
  .variant-card {
    @apply p-6;
  }
  
  .error-title {
    @apply text-xl;
  }
  
  .error-actions {
    @apply flex-col;
  }
  
  .action-button {
    @apply w-full justify-center;
  }
}
</style>
