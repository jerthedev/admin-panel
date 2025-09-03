<template>
  <div 
    class="dashboard-card"
    :class="cardClasses"
    :data-testid="`dashboard-card-${card.component || 'unknown'}`"
  >
    <!-- Card Header -->
    <div v-if="hasHeader" class="card-header">
      <div class="card-title-section">
        <h3 v-if="card.title" class="card-title">
          {{ card.title }}
        </h3>
        <p v-if="card.subtitle" class="card-subtitle">
          {{ card.subtitle }}
        </p>
      </div>
      
      <!-- Card Actions -->
      <div v-if="hasActions" class="card-actions">
        <button
          v-for="action in card.actions"
          :key="action.name"
          @click="handleAction(action)"
          :disabled="isLoading || action.disabled"
          :class="getActionClasses(action)"
          :title="action.tooltip"
        >
          <component
            v-if="action.icon"
            :is="action.icon"
            class="w-4 h-4"
          />
          <span v-if="action.label">{{ action.label }}</span>
        </button>
      </div>
    </div>

    <!-- Card Content -->
    <div class="card-content">
      <!-- Loading State -->
      <div v-if="isLoading" class="card-loading">
        <div class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
          <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Loading...</span>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="card-error">
        <div class="text-center py-6">
          <svg class="mx-auto h-8 w-8 text-red-400 mb-2" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
              clip-rule="evenodd"
            />
          </svg>
          <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
          <button
            @click="retryLoad"
            class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline"
          >
            Retry
          </button>
        </div>
      </div>

      <!-- Dynamic Card Component -->
      <component
        v-else-if="cardComponent"
        :is="cardComponent"
        v-bind="cardProps"
        @action="handleCardAction"
        @error="handleCardError"
      />

      <!-- Fallback Content -->
      <div v-else class="card-fallback">
        <div class="text-center py-6">
          <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ card.title || 'Card' }}
          </p>
          <p v-if="card.component" class="text-xs text-gray-500 dark:text-gray-500 mt-1">
            Component: {{ card.component }}
          </p>
        </div>
      </div>
    </div>

    <!-- Card Footer -->
    <div v-if="hasFooter" class="card-footer">
      <div v-if="card.meta" class="card-meta">
        <span
          v-for="(value, key) in card.meta"
          :key="key"
          class="meta-item"
        >
          <span class="meta-key">{{ key }}:</span>
          <span class="meta-value">{{ value }}</span>
        </span>
      </div>
      
      <div v-if="card.links" class="card-links">
        <a
          v-for="link in card.links"
          :key="link.url"
          :href="link.url"
          :target="link.external ? '_blank' : '_self'"
          :rel="link.external ? 'noopener noreferrer' : ''"
          class="card-link"
        >
          {{ link.label }}
          <svg
            v-if="link.external"
            class="w-3 h-3 ml-1"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
            />
          </svg>
        </a>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, defineAsyncComponent } from 'vue'

export default {
  name: 'DashboardCard',
  props: {
    card: {
      type: Object,
      required: true,
      validator: (card) => {
        return card && typeof card === 'object'
      }
    },
    dashboard: {
      type: Object,
      required: true
    }
  },
  emits: ['card-action', 'card-error'],
  setup(props, { emit }) {
    const isLoading = ref(false)
    const error = ref(null)
    const cardComponent = ref(null)

    // Computed properties
    const hasHeader = computed(() => 
      props.card.title || props.card.subtitle || hasActions.value
    )

    const hasActions = computed(() => 
      props.card.actions && props.card.actions.length > 0
    )

    const hasFooter = computed(() => 
      props.card.meta || props.card.links
    )

    const cardClasses = computed(() => [
      'bg-white dark:bg-gray-800',
      'shadow-sm hover:shadow-md',
      'rounded-lg border border-gray-200 dark:border-gray-700',
      'transition-shadow duration-200',
      {
        'opacity-50': isLoading.value,
        'border-red-300 dark:border-red-600': error.value,
        'cursor-pointer': props.card.clickable,
        'h-full': true // Ensure cards fill their grid cell
      }
    ])

    const cardProps = computed(() => {
      const { component, actions, title, subtitle, meta, links, ...otherProps } = props.card
      return {
        ...otherProps,
        dashboard: props.dashboard
      }
    })

    // Methods
    const loadCardComponent = async () => {
      if (!props.card.component) {
        return
      }

      isLoading.value = true
      error.value = null

      try {
        // Try to load the card component dynamically
        const componentName = props.card.component
        
        // First try to load from Cards directory
        try {
          cardComponent.value = defineAsyncComponent(() => 
            import(`../Cards/${componentName}.vue`)
          )
        } catch (cardsError) {
          // Fallback to Dashboard directory
          try {
            cardComponent.value = defineAsyncComponent(() => 
              import(`./Cards/${componentName}.vue`)
            )
          } catch (dashboardError) {
            // Fallback to a generic card component if available
            console.warn(`Card component ${componentName} not found, using fallback`)
            cardComponent.value = null
          }
        }
      } catch (err) {
        error.value = `Failed to load card: ${err.message}`
        console.error('Card loading error:', err)
        emit('card-error', err, props.card)
      } finally {
        isLoading.value = false
      }
    }

    const handleAction = (action) => {
      if (action.disabled || isLoading.value) return

      emit('card-action', action, props.card)

      // Handle built-in actions
      if (action.type === 'refresh') {
        retryLoad()
      } else if (action.type === 'link' && action.url) {
        if (action.external) {
          window.open(action.url, '_blank', 'noopener,noreferrer')
        } else {
          window.location.href = action.url
        }
      }
    }

    const handleCardAction = (action) => {
      emit('card-action', action, props.card)
    }

    const handleCardError = (error) => {
      emit('card-error', error, props.card)
    }

    const retryLoad = () => {
      loadCardComponent()
    }

    const getActionClasses = (action) => [
      'inline-flex items-center px-2 py-1 text-xs font-medium rounded',
      'transition-colors duration-200',
      'focus:outline-none focus:ring-2 focus:ring-offset-2',
      {
        'text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 focus:ring-gray-500': 
          action.type === 'default' || !action.type,
        'text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900 hover:bg-blue-200 dark:hover:bg-blue-800 focus:ring-blue-500': 
          action.type === 'primary',
        'text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900 hover:bg-red-200 dark:hover:bg-red-800 focus:ring-red-500': 
          action.type === 'danger',
        'opacity-50 cursor-not-allowed': action.disabled || isLoading.value
      }
    ]

    // Lifecycle
    onMounted(() => {
      loadCardComponent()
    })

    return {
      isLoading,
      error,
      cardComponent,
      hasHeader,
      hasActions,
      hasFooter,
      cardClasses,
      cardProps,
      handleAction,
      handleCardAction,
      handleCardError,
      retryLoad,
      getActionClasses
    }
  }
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.dashboard-card {
  @apply flex flex-col h-full;
}

.card-header {
  @apply flex items-start justify-between p-4 pb-2;
}

.card-title-section {
  @apply flex-1 min-w-0;
}

.card-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white truncate;
}

.card-subtitle {
  @apply text-sm text-gray-600 dark:text-gray-400 mt-1;
}

.card-actions {
  @apply flex items-center space-x-2 ml-4;
}

.card-content {
  @apply flex-1 px-4 pb-4;
}

.card-footer {
  @apply px-4 py-3 bg-gray-50 dark:bg-gray-700/50 rounded-b-lg border-t border-gray-200 dark:border-gray-600;
}

.card-meta {
  @apply flex flex-wrap gap-2 text-xs text-gray-600 dark:text-gray-400;
}

.meta-item {
  @apply flex items-center;
}

.meta-key {
  @apply font-medium mr-1;
}

.meta-value {
  @apply text-gray-500 dark:text-gray-500;
}

.card-links {
  @apply flex flex-wrap gap-2 mt-2;
}

.card-link {
  @apply inline-flex items-center text-xs text-blue-600 dark:text-blue-400 hover:underline;
}

.card-loading,
.card-error,
.card-fallback {
  @apply min-h-[120px] flex items-center justify-center;
}
</style>
