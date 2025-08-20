<template>
  <nav
    v-if="showBreadcrumbs && breadcrumbs.length > 0"
    class="dashboard-breadcrumbs"
    aria-label="Dashboard breadcrumb"
    data-testid="dashboard-breadcrumbs"
  >
    <div class="breadcrumb-container">
      <!-- Back/Forward Navigation -->
      <div v-if="showNavigation" class="navigation-controls">
        <button
          @click="navigateBack"
          :disabled="!canGoBack"
          :class="getNavigationButtonClasses(canGoBack)"
          type="button"
          title="Go back"
          aria-label="Go back to previous dashboard"
        >
          <ChevronLeftIcon class="h-4 w-4" />
        </button>
        
        <button
          @click="navigateForward"
          :disabled="!canGoForward"
          :class="getNavigationButtonClasses(canGoForward)"
          type="button"
          title="Go forward"
          aria-label="Go forward to next dashboard"
        >
          <ChevronRightIcon class="h-4 w-4" />
        </button>
      </div>

      <!-- Breadcrumb Trail -->
      <ol class="breadcrumb-list">
        <li
          v-for="(breadcrumb, index) in breadcrumbs"
          :key="index"
          class="breadcrumb-item"
        >
          <!-- Separator -->
          <ChevronRightIcon
            v-if="index > 0"
            class="breadcrumb-separator"
          />

          <!-- Breadcrumb Content -->
          <div class="breadcrumb-content">
            <!-- Icon -->
            <component
              v-if="breadcrumb.icon"
              :is="getIcon(breadcrumb.icon)"
              class="breadcrumb-icon"
            />

            <!-- Link or Text -->
            <Link
              v-if="breadcrumb.href && !breadcrumb.isCurrent"
              :href="breadcrumb.href"
              :class="getBreadcrumbLinkClasses()"
              @click="handleBreadcrumbClick(breadcrumb)"
            >
              {{ breadcrumb.label }}
            </Link>
            <span
              v-else
              :class="getBreadcrumbTextClasses(breadcrumb.isCurrent)"
            >
              {{ breadcrumb.label }}
            </span>

            <!-- Current Dashboard Indicator -->
            <div v-if="breadcrumb.isCurrent" class="current-indicator">
              <div class="current-dot"></div>
            </div>
          </div>
        </li>
      </ol>

      <!-- Quick Actions -->
      <div v-if="showQuickActions" class="quick-actions">
        <!-- Dashboard Selector -->
        <button
          v-if="hasMultipleDashboards"
          @click="toggleQuickSwitcher"
          :class="getQuickActionButtonClasses()"
          type="button"
          title="Switch dashboard"
          aria-label="Open dashboard switcher"
        >
          <Squares2X2Icon class="h-4 w-4" />
        </button>

        <!-- Favorite Toggle -->
        <button
          v-if="currentDashboard && currentDashboard.uriKey !== 'main'"
          @click="toggleFavorite"
          :class="getFavoriteButtonClasses()"
          type="button"
          :title="isFavorite ? 'Remove from favorites' : 'Add to favorites'"
          :aria-label="isFavorite ? 'Remove from favorites' : 'Add to favorites'"
        >
          <StarIcon 
            :class="['h-4 w-4', { 'fill-current': isFavorite }]"
          />
        </button>

        <!-- Refresh Dashboard -->
        <button
          v-if="showRefreshButton"
          @click="refreshDashboard"
          :disabled="isRefreshing"
          :class="getRefreshButtonClasses()"
          type="button"
          title="Refresh dashboard"
          aria-label="Refresh dashboard data"
        >
          <ArrowPathIcon 
            :class="['h-4 w-4', { 'animate-spin': isRefreshing }]"
          />
        </button>
      </div>
    </div>

    <!-- Quick Switcher Dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="showQuickSwitcherDropdown"
        ref="quickSwitcherDropdown"
        class="quick-switcher-dropdown"
        @keydown.escape="closeQuickSwitcher"
      >
        <div class="dropdown-header">
          <h3 class="dropdown-title">Switch Dashboard</h3>
          <button
            @click="closeQuickSwitcher"
            class="close-button"
            type="button"
            aria-label="Close dashboard switcher"
          >
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>

        <div class="dropdown-content">
          <!-- Recent Dashboards -->
          <div v-if="recentDashboards.length > 0" class="dashboard-group">
            <h4 class="group-title">Recent</h4>
            <button
              v-for="dashboard in recentDashboards"
              :key="`recent-${dashboard.uriKey}`"
              @click="switchToDashboard(dashboard)"
              class="dashboard-option"
              type="button"
            >
              <component
                v-if="dashboard.icon"
                :is="getIcon(dashboard.icon)"
                class="option-icon"
              />
              <span class="option-label">{{ dashboard.name }}</span>
              <ClockIcon class="option-indicator" />
            </button>
          </div>

          <!-- Favorite Dashboards -->
          <div v-if="favoriteDashboards.length > 0" class="dashboard-group">
            <h4 class="group-title">Favorites</h4>
            <button
              v-for="dashboard in favoriteDashboards"
              :key="`favorite-${dashboard.uriKey}`"
              @click="switchToDashboard(dashboard)"
              class="dashboard-option"
              type="button"
            >
              <component
                v-if="dashboard.icon"
                :is="getIcon(dashboard.icon)"
                class="option-icon"
              />
              <span class="option-label">{{ dashboard.name }}</span>
              <StarIcon class="option-indicator fill-current" />
            </button>
          </div>

          <!-- All Dashboards -->
          <div v-if="otherDashboards.length > 0" class="dashboard-group">
            <h4 class="group-title">All Dashboards</h4>
            <button
              v-for="dashboard in otherDashboards"
              :key="`all-${dashboard.uriKey}`"
              @click="switchToDashboard(dashboard)"
              class="dashboard-option"
              type="button"
            >
              <component
                v-if="dashboard.icon"
                :is="getIcon(dashboard.icon)"
                class="option-icon"
              />
              <span class="option-label">{{ dashboard.name }}</span>
            </button>
          </div>
        </div>

        <!-- Keyboard Shortcuts Hint -->
        <div v-if="showKeyboardHints" class="dropdown-footer">
          <div class="keyboard-hints">
            <span class="hint">
              <kbd>Ctrl</kbd> + <kbd>K</kbd> Quick switch
            </span>
            <span class="hint">
              <kbd>Alt</kbd> + <kbd>←</kbd> / <kbd>→</kbd> Navigate
            </span>
          </div>
        </div>
      </div>
    </Transition>
  </nav>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useDashboardNavigationStore } from '@/stores/dashboardNavigation'
import { 
  ChevronLeftIcon, 
  ChevronRightIcon, 
  StarIcon, 
  ArrowPathIcon,
  Squares2X2Icon,
  XMarkIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'DashboardBreadcrumbs',
  components: {
    Link,
    ChevronLeftIcon,
    ChevronRightIcon,
    StarIcon,
    ArrowPathIcon,
    Squares2X2Icon,
    XMarkIcon,
    ClockIcon
  },
  props: {
    showBreadcrumbs: {
      type: Boolean,
      default: true
    },
    showNavigation: {
      type: Boolean,
      default: true
    },
    showQuickActions: {
      type: Boolean,
      default: true
    },
    showRefreshButton: {
      type: Boolean,
      default: true
    },
    showKeyboardHints: {
      type: Boolean,
      default: true
    },
    enableKeyboardShortcuts: {
      type: Boolean,
      default: true
    }
  },
  emits: ['refresh-dashboard', 'dashboard-switched'],
  setup(props, { emit }) {
    const navigationStore = useDashboardNavigationStore()
    const showQuickSwitcherDropdown = ref(false)
    const quickSwitcherDropdown = ref(null)
    const isRefreshing = ref(false)

    // Computed properties
    const breadcrumbs = computed(() => navigationStore.breadcrumbs)
    const canGoBack = computed(() => navigationStore.canGoBack)
    const canGoForward = computed(() => navigationStore.canGoForward)
    const hasMultipleDashboards = computed(() => navigationStore.hasMultipleDashboards)
    const currentDashboard = computed(() => navigationStore.currentDashboard)
    
    const isFavorite = computed(() => {
      if (!currentDashboard.value) return false
      return navigationStore.favorites.includes(currentDashboard.value.uriKey)
    })

    const recentDashboards = computed(() => {
      return navigationStore.recentlyViewed
        .filter(dashboard => dashboard.uriKey !== currentDashboard.value?.uriKey)
        .slice(0, 3)
    })

    const favoriteDashboards = computed(() => {
      return navigationStore.availableDashboards
        .filter(dashboard => 
          navigationStore.favorites.includes(dashboard.uriKey) &&
          dashboard.uriKey !== currentDashboard.value?.uriKey
        )
    })

    const otherDashboards = computed(() => {
      const recentUriKeys = recentDashboards.value.map(d => d.uriKey)
      const favoriteUriKeys = favoriteDashboards.value.map(d => d.uriKey)
      
      return navigationStore.availableDashboards
        .filter(dashboard => 
          dashboard.uriKey !== currentDashboard.value?.uriKey &&
          !recentUriKeys.includes(dashboard.uriKey) &&
          !favoriteUriKeys.includes(dashboard.uriKey)
        )
    })

    // Methods
    const navigateBack = () => {
      navigationStore.navigateBack()
    }

    const navigateForward = () => {
      navigationStore.navigateForward()
    }

    const toggleFavorite = () => {
      if (currentDashboard.value) {
        navigationStore.toggleFavorite(currentDashboard.value.uriKey)
      }
    }

    const refreshDashboard = async () => {
      isRefreshing.value = true
      try {
        emit('refresh-dashboard')
        // Simulate refresh delay
        await new Promise(resolve => setTimeout(resolve, 1000))
      } finally {
        isRefreshing.value = false
      }
    }

    const toggleQuickSwitcher = () => {
      showQuickSwitcherDropdown.value = !showQuickSwitcherDropdown.value
    }

    const closeQuickSwitcher = () => {
      showQuickSwitcherDropdown.value = false
    }

    const switchToDashboard = (dashboard) => {
      navigationStore.navigateToDashboard(dashboard)
      closeQuickSwitcher()
      emit('dashboard-switched', dashboard)
    }

    const handleBreadcrumbClick = (breadcrumb) => {
      if (breadcrumb.isHome) {
        // Track navigation to home
        emit('dashboard-switched', { uriKey: 'main', name: 'Dashboard' })
      }
    }

    // Style methods
    const getNavigationButtonClasses = (enabled) => [
      'navigation-button',
      enabled ? 'enabled' : 'disabled'
    ]

    const getBreadcrumbLinkClasses = () => [
      'breadcrumb-link'
    ]

    const getBreadcrumbTextClasses = (isCurrent) => [
      'breadcrumb-text',
      isCurrent ? 'current' : 'inactive'
    ]

    const getQuickActionButtonClasses = () => [
      'quick-action-button'
    ]

    const getFavoriteButtonClasses = () => [
      'quick-action-button',
      'favorite-button',
      isFavorite.value ? 'favorited' : 'not-favorited'
    ]

    const getRefreshButtonClasses = () => [
      'quick-action-button',
      'refresh-button',
      isRefreshing.value ? 'refreshing' : ''
    ]

    const getIcon = (iconName) => {
      // Simple icon mapping - you might want to use a more sophisticated icon system
      const iconMap = {
        'HomeIcon': 'div', // Replace with actual icon components
        'ChartBarIcon': 'div',
        // Add more icon mappings as needed
      }
      return iconMap[iconName] || 'div'
    }

    // Keyboard shortcuts
    const handleKeyboardShortcuts = (event) => {
      if (!props.enableKeyboardShortcuts) return

      // Ctrl/Cmd + K: Quick switcher
      if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault()
        toggleQuickSwitcher()
        return
      }

      // Alt + Left Arrow: Go back
      if (event.altKey && event.key === 'ArrowLeft') {
        event.preventDefault()
        if (canGoBack.value) {
          navigateBack()
        }
        return
      }

      // Alt + Right Arrow: Go forward
      if (event.altKey && event.key === 'ArrowRight') {
        event.preventDefault()
        if (canGoForward.value) {
          navigateForward()
        }
        return
      }

      // Escape: Close quick switcher
      if (event.key === 'Escape' && showQuickSwitcherDropdown.value) {
        closeQuickSwitcher()
        return
      }
    }

    // Click outside handler
    const handleClickOutside = (event) => {
      if (quickSwitcherDropdown.value && !quickSwitcherDropdown.value.contains(event.target)) {
        closeQuickSwitcher()
      }
    }

    // Lifecycle
    onMounted(() => {
      document.addEventListener('keydown', handleKeyboardShortcuts)
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('keydown', handleKeyboardShortcuts)
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      // Refs
      showQuickSwitcherDropdown,
      quickSwitcherDropdown,
      isRefreshing,

      // Computed
      breadcrumbs,
      canGoBack,
      canGoForward,
      hasMultipleDashboards,
      currentDashboard,
      isFavorite,
      recentDashboards,
      favoriteDashboards,
      otherDashboards,

      // Methods
      navigateBack,
      navigateForward,
      toggleFavorite,
      refreshDashboard,
      toggleQuickSwitcher,
      closeQuickSwitcher,
      switchToDashboard,
      handleBreadcrumbClick,
      getNavigationButtonClasses,
      getBreadcrumbLinkClasses,
      getBreadcrumbTextClasses,
      getQuickActionButtonClasses,
      getFavoriteButtonClasses,
      getRefreshButtonClasses,
      getIcon
    }
  }
}
</script>

<style scoped>
.dashboard-breadcrumbs {
  @apply bg-white border-b border-gray-200 px-6 py-3 relative;
}

.breadcrumb-container {
  @apply flex items-center justify-between;
}

.navigation-controls {
  @apply flex items-center space-x-1 mr-4;
}

.navigation-button {
  @apply p-1.5 rounded-md transition-colors duration-200;
}

.navigation-button.enabled {
  @apply text-gray-600 hover:text-gray-900 hover:bg-gray-100;
}

.navigation-button.disabled {
  @apply text-gray-300 cursor-not-allowed;
}

.breadcrumb-list {
  @apply flex items-center space-x-2 flex-1;
}

.breadcrumb-item {
  @apply flex items-center;
}

.breadcrumb-separator {
  @apply flex-shrink-0 h-4 w-4 text-gray-400 mr-2;
}

.breadcrumb-content {
  @apply flex items-center relative;
}

.breadcrumb-icon {
  @apply flex-shrink-0 h-4 w-4 mr-1.5 text-gray-400;
}

.breadcrumb-link {
  @apply text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors duration-150;
}

.breadcrumb-text {
  @apply text-sm font-medium;
}

.breadcrumb-text.current {
  @apply text-gray-900;
}

.breadcrumb-text.inactive {
  @apply text-gray-500;
}

.current-indicator {
  @apply ml-2;
}

.current-dot {
  @apply w-2 h-2 bg-blue-500 rounded-full;
}

.quick-actions {
  @apply flex items-center space-x-2;
}

.quick-action-button {
  @apply p-1.5 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors duration-200;
}

.favorite-button.favorited {
  @apply text-yellow-500 hover:text-yellow-600;
}

.refresh-button.refreshing {
  @apply text-blue-500;
}

.quick-switcher-dropdown {
  @apply absolute top-full right-0 mt-2 w-80 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50;
}

.dropdown-header {
  @apply flex items-center justify-between px-4 py-3 border-b border-gray-200;
}

.dropdown-title {
  @apply text-sm font-semibold text-gray-900;
}

.close-button {
  @apply p-1 text-gray-400 hover:text-gray-600 rounded-md;
}

.dropdown-content {
  @apply py-2 max-h-64 overflow-y-auto;
}

.dashboard-group {
  @apply mb-4 last:mb-0;
}

.group-title {
  @apply px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide;
}

.dashboard-option {
  @apply w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150;
}

.option-icon {
  @apply flex-shrink-0 h-4 w-4 mr-3 text-gray-400;
}

.option-label {
  @apply flex-1 text-left;
}

.option-indicator {
  @apply flex-shrink-0 h-4 w-4 text-gray-400;
}

.dropdown-footer {
  @apply px-4 py-3 border-t border-gray-200 bg-gray-50;
}

.keyboard-hints {
  @apply flex items-center justify-between text-xs text-gray-500;
}

.hint {
  @apply flex items-center space-x-1;
}

.hint kbd {
  @apply px-1.5 py-0.5 bg-gray-200 text-gray-700 rounded text-xs font-mono;
}

/* Dark theme support */
@media (prefers-color-scheme: dark) {
  .dashboard-breadcrumbs {
    @apply bg-gray-800 border-gray-700;
  }

  .navigation-button.enabled {
    @apply text-gray-400 hover:text-gray-200 hover:bg-gray-700;
  }

  .navigation-button.disabled {
    @apply text-gray-600;
  }

  .breadcrumb-link {
    @apply text-gray-400 hover:text-gray-200;
  }

  .breadcrumb-text.current {
    @apply text-gray-200;
  }

  .breadcrumb-text.inactive {
    @apply text-gray-400;
  }

  .quick-action-button {
    @apply text-gray-400 hover:text-gray-200 hover:bg-gray-700;
  }

  .quick-switcher-dropdown {
    @apply bg-gray-800 ring-gray-700;
  }

  .dropdown-header {
    @apply border-gray-700;
  }

  .dropdown-title {
    @apply text-gray-200;
  }

  .close-button {
    @apply text-gray-500 hover:text-gray-300;
  }

  .group-title {
    @apply text-gray-500;
  }

  .dashboard-option {
    @apply text-gray-300 hover:bg-gray-700;
  }

  .dropdown-footer {
    @apply border-gray-700 bg-gray-700;
  }

  .keyboard-hints {
    @apply text-gray-400;
  }

  .hint kbd {
    @apply bg-gray-600 text-gray-300;
  }
}
</style>
