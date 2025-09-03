<template>
  <div class="dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
      <div class="dashboard-title">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ dashboard.name }}
        </h1>
        <p v-if="dashboard.description" class="text-gray-600 dark:text-gray-400 mt-1">
          {{ dashboard.description }}
        </p>
      </div>
      
      <!-- Dashboard Actions -->
      <div class="dashboard-actions flex items-center space-x-3">
        <!-- Refresh Button -->
        <button
          v-if="dashboard.showRefreshButton"
          @click="refreshDashboard"
          :disabled="isRefreshing"
          class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          data-testid="refresh-button"
        >
          <svg
            :class="['w-4 h-4 mr-2', { 'animate-spin': isRefreshing }]"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
        </button>

        <!-- Dashboard Menu (if multiple dashboards) -->
        <DashboardSelector
          v-if="availableDashboards && availableDashboards.length > 1"
          :dashboards="availableDashboards"
          :current-dashboard="dashboard"
          @dashboard-changed="handleDashboardChange"
        />
      </div>
    </div>

    <!-- Dashboard Content -->
    <div class="dashboard-content">
      <!-- Cards Grid -->
      <div v-if="cards && cards.length > 0" class="dashboard-cards">
        <DashboardGrid
          :cards="formattedCards"
          :columns="{ mobile: 1, tablet: 2, desktop: 3, wide: 4 }"
          gap="1.5rem"
          auto-rows="minmax(200px, auto)"
          min-card-width="280px"
          @card-click="handleCardClick"
          @card-error="handleCardError"
          @card-refresh="handleCardRefresh"
        />
      </div>

      <!-- Empty State -->
      <div v-else class="dashboard-empty-state">
        <div class="text-center py-12">
          <svg
            class="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
            />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
            No dashboard cards
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            This dashboard doesn't have any cards configured yet.
          </p>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="dashboard-loading">
        <div class="flex items-center justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          <span class="ml-3 text-gray-600 dark:text-gray-400">Loading dashboard...</span>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="dashboard-error">
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                Dashboard Error
              </h3>
              <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                <p>{{ error }}</p>
              </div>
              <div class="mt-4">
                <button
                  @click="retryLoad"
                  class="bg-red-100 dark:bg-red-800 px-3 py-2 rounded-md text-sm font-medium text-red-800 dark:text-red-200 hover:bg-red-200 dark:hover:bg-red-700"
                >
                  Try Again
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import DashboardCard from './DashboardCard.vue'
import DashboardGrid from './DashboardGrid.vue'
import DashboardSelector from './DashboardSelector.vue'

export default {
  name: 'Dashboard',
  components: {
    DashboardCard,
    DashboardGrid,
    DashboardSelector,
  },
  props: {
    dashboard: {
      type: Object,
      required: true,
      validator: (dashboard) => {
        return dashboard && 
               typeof dashboard.name === 'string' && 
               typeof dashboard.uriKey === 'string'
      }
    },
    cards: {
      type: Array,
      default: () => []
    },
    availableDashboards: {
      type: Array,
      default: () => []
    }
  },
  setup(props) {
    const isLoading = ref(false)
    const isRefreshing = ref(false)
    const error = ref(null)

    // Computed properties
    const hasCards = computed(() => props.cards && props.cards.length > 0)
    const hasMultipleDashboards = computed(() =>
      props.availableDashboards && props.availableDashboards.length > 1
    )

    // Format cards for DashboardGrid component
    const formattedCards = computed(() => {
      if (!props.cards || !Array.isArray(props.cards)) {
        return []
      }

      return props.cards.map((card, index) => {
        const size = card.size || 'md'

        // Map card sizes to grid area spans
        const sizeToSpan = {
          'sm': { rowSpan: 1, columnSpan: 1 },
          'md': { rowSpan: 1, columnSpan: 1 },
          'lg': { rowSpan: 1, columnSpan: 2 },
          'xl': { rowSpan: 2, columnSpan: 2 },
          'full': { rowSpan: 1, columnSpan: 4 }
        }

        const spans = sizeToSpan[size] || sizeToSpan['md']

        return {
          id: card.id || `card-${index}`,
          component: card.component || 'DashboardCard',
          title: card.title || '',
          gridArea: {
            row: 'auto',
            column: 'auto',
            ...spans
          },
          props: {
            card: card,
            dashboard: props.dashboard
          }
        }
      })
    })

    // Methods
    const refreshDashboard = async () => {
      if (isRefreshing.value) return

      isRefreshing.value = true
      error.value = null

      try {
        // Refresh the current page to reload dashboard data
        await router.reload({
          only: ['dashboard', 'cards'],
          preserveScroll: true
        })
      } catch (err) {
        error.value = 'Failed to refresh dashboard. Please try again.'
        console.error('Dashboard refresh error:', err)
      } finally {
        isRefreshing.value = false
      }
    }

    const handleDashboardChange = (newDashboard) => {
      if (newDashboard.uriKey === props.dashboard.uriKey) return

      isLoading.value = true
      error.value = null

      // Navigate to the new dashboard
      router.visit(`/admin/dashboards/${newDashboard.uriKey}`, {
        preserveState: false,
        preserveScroll: false,
        onError: (errors) => {
          error.value = 'Failed to load dashboard. Please try again.'
          console.error('Dashboard navigation error:', errors)
          isLoading.value = false
        },
        onFinish: () => {
          isLoading.value = false
        }
      })
    }

    const handleCardAction = (action, card) => {
      // Handle card-specific actions
      console.log('Card action:', action, card)
      
      // Emit event for parent components to handle
      // This could be used for analytics, logging, etc.
    }

    const handleCardError = (error, card) => {
      console.error('Card error:', error, card)
      
      // Could show a toast notification or handle card-specific errors
      // For now, we'll just log it
    }

    const retryLoad = () => {
      error.value = null
      isLoading.value = true
      
      // Reload the current page
      router.reload({
        onError: (errors) => {
          error.value = 'Failed to load dashboard. Please check your connection and try again.'
          console.error('Dashboard retry error:', errors)
        },
        onFinish: () => {
          isLoading.value = false
        }
      })
    }

    const handleCardClick = (card, event) => {
      // Handle card click events
      // This can be extended to support card-specific actions
      console.log('Card clicked:', card.title || card.id)
    }

    const handleCardRefresh = async (card) => {
      try {
        await refreshCard(card.id)
      } catch (error) {
        console.error('Failed to refresh card:', error)
        // Could show a notification to the user
      }
    }

    const refreshCard = async (cardId) => {
      try {
        const response = await fetch(`/admin/api/dashboards/${props.dashboard.uriKey}/cards/${cardId}/refresh`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
        })

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()

        // Update the card data in the cards array
        const cardIndex = props.cards.findIndex(card => card.id === cardId)
        if (cardIndex !== -1) {
          // Create a new array with the updated card
          const updatedCards = [...props.cards]
          updatedCards[cardIndex] = { ...updatedCards[cardIndex], ...data.card }

          // Emit an event to notify parent component of the update
          // This would require the parent to handle the update
          console.log('Card refreshed:', data.card)
        }

        return data.card
      } catch (error) {
        console.error('Failed to refresh card:', error)
        throw error
      }
    }

    const refreshAllCards = async () => {
      if (isRefreshing.value) return

      isRefreshing.value = true
      error.value = null

      try {
        const response = await fetch(`/admin/api/dashboards/${props.dashboard.uriKey}/cards`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
        })

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()

        // Emit event to parent to update all cards
        console.log('All cards refreshed:', data.cards)

        // For now, we'll use the existing dashboard refresh
        await refreshDashboard()
      } catch (err) {
        error.value = 'Failed to refresh cards. Please try again.'
        console.error('Cards refresh error:', err)
      } finally {
        isRefreshing.value = false
      }
    }

    // Lifecycle
    onMounted(() => {
      // Dashboard is already loaded via Inertia, no need to fetch
      console.log('Dashboard mounted:', props.dashboard.name)
    })

    // Watch for dashboard changes
    watch(() => props.dashboard, (newDashboard, oldDashboard) => {
      if (oldDashboard && newDashboard.uriKey !== oldDashboard.uriKey) {
        console.log('Dashboard changed:', newDashboard.name)
      }
    })

    return {
      isLoading,
      isRefreshing,
      error,
      hasCards,
      hasMultipleDashboards,
      formattedCards,
      refreshDashboard,
      refreshCard,
      refreshAllCards,
      handleDashboardChange,
      handleCardClick,
      handleCardError,
      handleCardRefresh,
      retryLoad
    }
  }
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.dashboard {
  @apply min-h-full;
}

.dashboard-header {
  @apply flex items-start justify-between mb-8;
}

.dashboard-title h1 {
  @apply text-2xl font-bold text-gray-900 dark:text-white;
}

.dashboard-actions {
  @apply flex items-center space-x-3;
}

.dashboard-content {
  @apply space-y-6;
}

.dashboard-cards {
  @apply w-full;
}

.dashboard-empty-state {
  @apply bg-white dark:bg-gray-800 shadow rounded-lg;
}

.dashboard-loading {
  @apply bg-white dark:bg-gray-800 shadow rounded-lg;
}

.dashboard-error {
  @apply mb-6;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .dashboard-header {
    @apply flex-col space-y-4;
  }
  
  .dashboard-actions {
    @apply w-full justify-end;
  }
}
</style>
