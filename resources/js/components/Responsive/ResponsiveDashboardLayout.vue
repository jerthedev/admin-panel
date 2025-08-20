<template>
  <div class="responsive-dashboard-layout" :class="layoutClasses">
    <!-- Mobile Header -->
    <header v-if="isMobile" class="mobile-header">
      <MobileDashboardNavigation
        :dashboards="dashboards"
        :current-dashboard="currentDashboard"
        :user="user"
        :notifications="notifications"
        :show-breadcrumbs="showBreadcrumbs"
        :enable-search="enableSearch"
        :show-notifications="showNotifications"
        :show-profile="showProfile"
        :show-bottom-nav="showBottomNav"
        :variant="mobileVariant"
        @dashboard-select="handleDashboardSelect"
        @search="handleSearch"
        @refresh="handleRefresh"
        @settings="handleSettings"
        @logout="handleLogout"
        @favorite-toggle="handleFavoriteToggle"
        @notification-read="handleNotificationRead"
        @bottom-nav-click="handleBottomNavClick"
      />
    </header>

    <!-- Desktop/Tablet Header -->
    <header v-else class="desktop-header">
      <DashboardNavigation
        :dashboards="dashboards"
        :current-dashboard="currentDashboard"
        :show-breadcrumbs="showBreadcrumbs"
        :show-quick-actions="showQuickActions"
        :show-back-button="showBackButton"
        :show-quick-switcher="showQuickSwitcher"
        :show-refresh-button="showRefreshButton"
        :show-settings-button="showSettingsButton"
        :show-history="showHistory"
        :show-keyboard-shortcuts="showKeyboardShortcuts"
        :variant="desktopVariant"
        @dashboard-changed="handleDashboardSelect"
        @refresh="handleRefresh"
        @settings-changed="handleSettingsChanged"
        @navigate-back="handleNavigateBack"
        @navigate-to="handleNavigateTo"
      />
    </header>

    <!-- Main Content Area -->
    <main class="main-content" :class="contentClasses">
      <!-- Loading State -->
      <div v-if="isLoading" class="loading-container">
        <DashboardLoading
          :variant="loadingVariant"
          :message="loadingMessage"
          :show-progress="showLoadingProgress"
          :progress="loadingProgress"
          :show-cancel="showLoadingCancel"
          @cancel="handleLoadingCancel"
        />
      </div>

      <!-- Error State -->
      <div v-else-if="hasError" class="error-container">
        <DashboardError
          :error="error"
          :show-retry="showErrorRetry"
          :show-details="showErrorDetails"
          @retry="handleErrorRetry"
          @dismiss="handleErrorDismiss"
        />
      </div>

      <!-- Dashboard Content -->
      <div v-else class="dashboard-content">
        <!-- Dashboard Header -->
        <div v-if="showDashboardHeader" class="dashboard-header">
          <div class="dashboard-title-section">
            <h1 class="dashboard-title">{{ currentDashboard?.name }}</h1>
            <p v-if="currentDashboard?.description" class="dashboard-description">
              {{ currentDashboard.description }}
            </p>
          </div>

          <!-- Dashboard Actions -->
          <div v-if="showDashboardActions" class="dashboard-actions">
            <button
              v-for="action in dashboardActions"
              :key="action.key"
              @click="handleDashboardAction(action)"
              class="dashboard-action"
              :class="action.classes"
              :disabled="action.disabled"
              :title="action.tooltip"
              type="button"
            >
              <component :is="action.icon" class="action-icon" />
              <span v-if="!isMobile || action.showLabel" class="action-label">
                {{ action.label }}
              </span>
            </button>
          </div>
        </div>

        <!-- Dashboard Filters -->
        <div v-if="showFilters && filters.length > 0" class="dashboard-filters">
          <ResponsiveDashboardFilters
            :filters="filters"
            :values="filterValues"
            :layout="filtersLayout"
            :collapsible="filtersCollapsible"
            @update="handleFiltersUpdate"
            @reset="handleFiltersReset"
          />
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid" :class="gridClasses" :style="gridStyles">
          <div
            v-for="card in dashboardCards"
            :key="card.id"
            class="dashboard-card-container"
            :class="getCardClasses(card)"
            :style="getCardStyles(card)"
          >
            <component
              :is="card.component"
              v-bind="card.props"
              :loading="card.loading"
              :error="card.error"
              :data="card.data"
              @update="handleCardUpdate"
              @error="handleCardError"
              @action="handleCardAction"
            />
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="isEmpty" class="empty-state">
          <DashboardEmptyState
            :title="emptyStateTitle"
            :description="emptyStateDescription"
            :icon="emptyStateIcon"
            :actions="emptyStateActions"
            @action="handleEmptyStateAction"
          />
        </div>
      </div>
    </main>

    <!-- Pull to Refresh Indicator -->
    <div v-if="enablePullToRefresh && isMobile" class="pull-to-refresh-container">
      <div
        class="pull-to-refresh-indicator"
        :class="{ active: isPulling, refreshing: isRefreshing }"
      >
        <ArrowPathIcon class="refresh-icon" />
      </div>
    </div>

    <!-- Floating Action Button (Mobile) -->
    <button
      v-if="showFAB && isMobile"
      @click="handleFABClick"
      class="floating-action-button"
      :class="fabClasses"
      :aria-label="fabLabel"
      type="button"
    >
      <component :is="fabIcon" class="fab-icon" />
    </button>

    <!-- Resize Observer -->
    <div ref="resizeObserver" class="resize-observer"></div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import MobileDashboardNavigation from '@/Components/Mobile/MobileDashboardNavigation.vue'
import DashboardNavigation from '@/Components/Dashboard/DashboardNavigation.vue'
import DashboardLoading from '@/Components/Dashboard/DashboardLoading.vue'
import DashboardError from '@/Components/Dashboard/DashboardError.vue'
import ResponsiveDashboardFilters from './ResponsiveDashboardFilters.vue'
import DashboardEmptyState from '@/Components/Dashboard/DashboardEmptyState.vue'
import { useMobileNavigation } from '@/composables/useMobileNavigation'
import { useMobileGestures } from '@/composables/useMobileGestures'
import { useDashboardPreferencesStore } from '@/stores/dashboardPreferences'

export default {
  name: 'ResponsiveDashboardLayout',
  components: {
    ArrowPathIcon,
    MobileDashboardNavigation,
    DashboardNavigation,
    DashboardLoading,
    DashboardError,
    ResponsiveDashboardFilters,
    DashboardEmptyState
  },
  props: {
    dashboards: {
      type: Array,
      default: () => []
    },
    currentDashboard: {
      type: Object,
      default: null
    },
    dashboardCards: {
      type: Array,
      default: () => []
    },
    user: {
      type: Object,
      default: null
    },
    notifications: {
      type: Array,
      default: () => []
    },
    filters: {
      type: Array,
      default: () => []
    },
    filterValues: {
      type: Object,
      default: () => ({})
    },
    isLoading: {
      type: Boolean,
      default: false
    },
    error: {
      type: [String, Object],
      default: null
    },
    // Layout options
    showBreadcrumbs: {
      type: Boolean,
      default: true
    },
    showDashboardHeader: {
      type: Boolean,
      default: true
    },
    showDashboardActions: {
      type: Boolean,
      default: true
    },
    showFilters: {
      type: Boolean,
      default: true
    },
    showBottomNav: {
      type: Boolean,
      default: true
    },
    showFAB: {
      type: Boolean,
      default: false
    },
    enablePullToRefresh: {
      type: Boolean,
      default: true
    },
    // Responsive options
    mobileVariant: {
      type: String,
      default: 'default'
    },
    desktopVariant: {
      type: String,
      default: 'default'
    },
    gridColumns: {
      type: Object,
      default: () => ({
        mobile: 1,
        tablet: 2,
        desktop: 3,
        wide: 4
      })
    }
  },
  emits: [
    'dashboard-select',
    'search',
    'refresh',
    'settings',
    'logout',
    'favorite-toggle',
    'notification-read',
    'bottom-nav-click',
    'filters-update',
    'card-update',
    'card-error',
    'card-action',
    'fab-click'
  ],
  setup(props, { emit }) {
    // Composables
    const mobileNav = useMobileNavigation()
    const gestures = useMobileGestures()
    const preferencesStore = useDashboardPreferencesStore()

    // Refs
    const resizeObserver = ref(null)
    const isPulling = ref(false)
    const isRefreshing = ref(false)

    // Computed properties
    const isMobile = computed(() => mobileNav.isMobile.value)
    const isTablet = computed(() => mobileNav.isTablet.value)
    const isDesktop = computed(() => mobileNav.isDesktop.value)

    const layoutClasses = computed(() => [
      'responsive-dashboard-layout-base',
      {
        'is-mobile': isMobile.value,
        'is-tablet': isTablet.value,
        'is-desktop': isDesktop.value,
        'is-loading': props.isLoading,
        'has-error': hasError.value,
        'is-pulling': isPulling.value,
        'is-refreshing': isRefreshing.value
      }
    ])

    const contentClasses = computed(() => [
      'main-content-base',
      {
        'with-bottom-nav': props.showBottomNav && isMobile.value,
        'with-fab': props.showFAB && isMobile.value
      }
    ])

    const gridClasses = computed(() => [
      'dashboard-grid-base',
      `columns-${getCurrentColumns()}`,
      {
        'compact': preferencesStore.preferences.display.compactMode,
        'dense': preferencesStore.preferences.display.density === 'compact'
      }
    ])

    const gridStyles = computed(() => {
      const columns = getCurrentColumns()
      return {
        gridTemplateColumns: `repeat(${columns}, 1fr)`,
        gap: getGridGap()
      }
    })

    const hasError = computed(() => !!props.error)
    const isEmpty = computed(() => props.dashboardCards.length === 0 && !props.isLoading && !hasError.value)

    // Dashboard actions
    const dashboardActions = computed(() => [
      {
        key: 'refresh',
        label: 'Refresh',
        icon: 'ArrowPathIcon',
        tooltip: 'Refresh dashboard data',
        showLabel: true
      },
      {
        key: 'export',
        label: 'Export',
        icon: 'ArrowDownTrayIcon',
        tooltip: 'Export dashboard data',
        showLabel: false
      },
      {
        key: 'settings',
        label: 'Settings',
        icon: 'CogIcon',
        tooltip: 'Dashboard settings',
        showLabel: false
      }
    ])

    // Methods
    const getCurrentColumns = () => {
      if (isMobile.value) return props.gridColumns.mobile
      if (isTablet.value) return props.gridColumns.tablet
      if (mobileNav.screenWidth.value >= 1536) return props.gridColumns.wide
      return props.gridColumns.desktop
    }

    const getGridGap = () => {
      const density = preferencesStore.preferences.display.density
      const gapMap = {
        compact: '0.5rem',
        comfortable: '1rem',
        spacious: '1.5rem'
      }
      return gapMap[density] || '1rem'
    }

    const getCardClasses = (card) => [
      'dashboard-card-base',
      card.classes,
      {
        'card-loading': card.loading,
        'card-error': card.error,
        'card-span-2': card.span === 2,
        'card-span-3': card.span === 3,
        'card-full-width': card.fullWidth
      }
    ]

    const getCardStyles = (card) => {
      const styles = { ...card.styles }
      
      if (card.span && card.span > 1) {
        styles.gridColumn = `span ${Math.min(card.span, getCurrentColumns())}`
      }
      
      return styles
    }

    // Event handlers
    const handleDashboardSelect = (dashboard) => {
      emit('dashboard-select', dashboard)
    }

    const handleSearch = (query) => {
      emit('search', query)
    }

    const handleRefresh = async () => {
      isRefreshing.value = true
      try {
        await emit('refresh')
      } finally {
        isRefreshing.value = false
        isPulling.value = false
      }
    }

    const handleSettings = () => {
      emit('settings')
    }

    const handleLogout = () => {
      emit('logout')
    }

    const handleFavoriteToggle = (dashboard) => {
      emit('favorite-toggle', dashboard)
    }

    const handleNotificationRead = (notification) => {
      emit('notification-read', notification)
    }

    const handleBottomNavClick = (item) => {
      emit('bottom-nav-click', item)
    }

    const handleFiltersUpdate = (filters) => {
      emit('filters-update', filters)
    }

    const handleFiltersReset = () => {
      emit('filters-update', {})
    }

    const handleCardUpdate = (cardId, data) => {
      emit('card-update', { cardId, data })
    }

    const handleCardError = (cardId, error) => {
      emit('card-error', { cardId, error })
    }

    const handleCardAction = (cardId, action, data) => {
      emit('card-action', { cardId, action, data })
    }

    const handleDashboardAction = (action) => {
      switch (action.key) {
        case 'refresh':
          handleRefresh()
          break
        case 'export':
          // Handle export
          break
        case 'settings':
          handleSettings()
          break
      }
    }

    const handleFABClick = () => {
      emit('fab-click')
    }

    const handleEmptyStateAction = (action) => {
      // Handle empty state actions
    }

    // Pull to refresh
    const setupPullToRefresh = () => {
      if (!props.enablePullToRefresh || !isMobile.value) return

      gestures.on('pull-to-refresh', (data) => {
        isPulling.value = true
      })

      gestures.on('pull-to-refresh-end', (data) => {
        if (data.shouldRefresh) {
          handleRefresh()
        } else {
          isPulling.value = false
        }
      })
    }

    // Lifecycle
    onMounted(() => {
      mobileNav.setup()
      gestures.setup()
      setupPullToRefresh()

      // Setup resize observer
      if (window.ResizeObserver && resizeObserver.value) {
        const observer = new ResizeObserver(() => {
          // Handle resize
        })
        observer.observe(resizeObserver.value)
      }
    })

    onUnmounted(() => {
      mobileNav.cleanup()
      gestures.cleanup()
    })

    return {
      // Refs
      resizeObserver,
      isPulling,
      isRefreshing,

      // Computed
      isMobile,
      isTablet,
      isDesktop,
      layoutClasses,
      contentClasses,
      gridClasses,
      gridStyles,
      hasError,
      isEmpty,
      dashboardActions,

      // Methods
      getCardClasses,
      getCardStyles,
      handleDashboardSelect,
      handleSearch,
      handleRefresh,
      handleSettings,
      handleLogout,
      handleFavoriteToggle,
      handleNotificationRead,
      handleBottomNavClick,
      handleFiltersUpdate,
      handleFiltersReset,
      handleCardUpdate,
      handleCardError,
      handleCardAction,
      handleDashboardAction,
      handleFABClick,
      handleEmptyStateAction,

      // Loading/Error props
      loadingVariant: 'spinner',
      loadingMessage: 'Loading dashboard...',
      showLoadingProgress: false,
      loadingProgress: 0,
      showLoadingCancel: false,
      showErrorRetry: true,
      showErrorDetails: false,
      
      // Empty state props
      emptyStateTitle: 'No data available',
      emptyStateDescription: 'There are no cards to display in this dashboard.',
      emptyStateIcon: 'ChartBarIcon',
      emptyStateActions: [],

      // FAB props
      fabClasses: ['primary'],
      fabLabel: 'Add widget',
      fabIcon: 'PlusIcon',

      // Filter props
      filtersLayout: isMobile.value ? 'vertical' : 'horizontal',
      filtersCollapsible: isMobile.value,

      // Navigation props
      enableSearch: true,
      showNotifications: true,
      showProfile: true,
      showQuickActions: true,
      showBackButton: true,
      showQuickSwitcher: true,
      showRefreshButton: true,
      showSettingsButton: true,
      showHistory: true,
      showKeyboardShortcuts: true
    }
  }
}
</script>

<style scoped>
@import '@/css/responsive.css';

.responsive-dashboard-layout {
  @apply min-h-screen bg-gray-50;
}

.mobile-header,
.desktop-header {
  @apply sticky top-0 z-40;
}

.main-content {
  @apply flex-1 overflow-hidden;
}

.main-content.with-bottom-nav {
  @apply pb-16; /* Space for bottom navigation */
}

.main-content.with-fab {
  @apply pb-20; /* Extra space for FAB */
}

.loading-container,
.error-container {
  @apply flex items-center justify-center min-h-96;
}

.dashboard-content {
  @apply p-4;
}

.dashboard-header {
  @apply flex items-start justify-between mb-6;
}

.dashboard-title-section {
  @apply flex-1 min-w-0;
}

.dashboard-title {
  @apply text-2xl font-bold text-gray-900 mb-1;
}

.dashboard-description {
  @apply text-gray-600;
}

.dashboard-actions {
  @apply flex items-center space-x-2 ml-4;
}

.dashboard-action {
  @apply inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
}

.action-icon {
  @apply w-4 h-4;
}

.action-label {
  @apply ml-2;
}

.dashboard-filters {
  @apply mb-6;
}

.dashboard-grid {
  @apply grid gap-6;
}

.dashboard-card-container {
  @apply min-w-0;
}

.card-span-2 {
  @apply col-span-2;
}

.card-span-3 {
  @apply col-span-3;
}

.card-full-width {
  @apply col-span-full;
}

.empty-state {
  @apply flex items-center justify-center min-h-96;
}

.pull-to-refresh-container {
  @apply fixed top-0 left-0 right-0 pointer-events-none z-30;
}

.pull-to-refresh-indicator {
  @apply absolute top-4 left-1/2 transform -translate-x-1/2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center transition-all duration-300 opacity-0;
}

.pull-to-refresh-indicator.active {
  @apply opacity-100;
}

.pull-to-refresh-indicator.refreshing {
  @apply opacity-100;
}

.pull-to-refresh-indicator.refreshing .refresh-icon {
  @apply animate-spin;
}

.refresh-icon {
  @apply w-5 h-5 text-gray-600;
}

.floating-action-button {
  @apply fixed bottom-20 right-4 w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 z-30;
}

.fab-icon {
  @apply w-6 h-6;
}

.resize-observer {
  @apply absolute inset-0 pointer-events-none opacity-0;
}

/* Responsive adjustments */
@media (max-width: 767px) {
  .dashboard-content {
    @apply p-2;
  }

  .dashboard-header {
    @apply flex-col items-start space-y-4 mb-4;
  }

  .dashboard-actions {
    @apply w-full justify-end ml-0;
  }

  .dashboard-action .action-label {
    @apply hidden;
  }

  .dashboard-grid {
    @apply gap-4;
  }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .responsive-dashboard-layout {
    @apply bg-gray-900;
  }

  .dashboard-title {
    @apply text-gray-100;
  }

  .dashboard-description {
    @apply text-gray-400;
  }

  .dashboard-action {
    @apply border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700;
  }

  .pull-to-refresh-indicator {
    @apply bg-gray-800;
  }

  .refresh-icon {
    @apply text-gray-300;
  }
}
</style>
