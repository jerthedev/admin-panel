<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="quick-switcher-overlay"
        @click="close"
        data-testid="quick-switcher-overlay"
      >
        <div
          ref="quickSwitcher"
          class="quick-switcher-modal"
          @click.stop
          @keydown.escape="close"
          @keydown.arrow-down.prevent="focusNext"
          @keydown.arrow-up.prevent="focusPrevious"
          @keydown.enter.prevent="selectFocused"
        >
          <!-- Header -->
          <div class="modal-header">
            <div class="header-content">
              <h2 class="modal-title">Quick Switch Dashboard</h2>
              <p class="modal-subtitle">Navigate between dashboards quickly</p>
            </div>
            <button
              @click="close"
              class="close-button"
              type="button"
              aria-label="Close quick switcher"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <!-- Search Input -->
          <div class="search-container">
            <div class="search-input-wrapper">
              <MagnifyingGlassIcon class="search-icon" />
              <input
                ref="searchInput"
                v-model="searchQuery"
                @input="handleSearch"
                type="text"
                placeholder="Search dashboards..."
                class="search-input"
                autocomplete="off"
              />
              <button
                v-if="searchQuery"
                @click="clearSearch"
                class="clear-search-button"
                type="button"
                aria-label="Clear search"
              >
                <XMarkIcon class="h-4 w-4" />
              </button>
            </div>
          </div>

          <!-- Results -->
          <div class="results-container">
            <!-- No Results -->
            <div v-if="filteredDashboards.length === 0" class="no-results">
              <div class="no-results-content">
                <ExclamationTriangleIcon class="no-results-icon" />
                <p class="no-results-text">
                  {{ searchQuery ? 'No dashboards match your search' : 'No dashboards available' }}
                </p>
                <button
                  v-if="searchQuery"
                  @click="clearSearch"
                  class="clear-search-link"
                  type="button"
                >
                  Clear search
                </button>
              </div>
            </div>

            <!-- Dashboard Groups -->
            <div v-else class="dashboard-groups">
              <!-- Recent Dashboards -->
              <div v-if="recentDashboards.length > 0" class="dashboard-group">
                <h3 class="group-title">
                  <ClockIcon class="group-icon" />
                  Recent
                </h3>
                <div class="group-items">
                  <button
                    v-for="(dashboard, index) in recentDashboards"
                    :key="`recent-${dashboard.uriKey}`"
                    ref="dashboardItems"
                    @click="selectDashboard(dashboard)"
                    :class="getDashboardItemClasses(getGlobalIndex('recent', index))"
                    type="button"
                  >
                    <div class="item-content">
                      <component
                        v-if="dashboard.icon"
                        :is="getIcon(dashboard.icon)"
                        class="item-icon"
                      />
                      <div class="item-info">
                        <div class="item-name">{{ dashboard.name }}</div>
                        <div v-if="dashboard.description" class="item-description">
                          {{ dashboard.description }}
                        </div>
                      </div>
                      <div class="item-indicators">
                        <StarIcon
                          v-if="isFavorite(dashboard.uriKey)"
                          class="favorite-indicator"
                        />
                        <div class="recent-time">
                          {{ formatRecentTime(dashboard.timestamp) }}
                        </div>
                      </div>
                    </div>
                  </button>
                </div>
              </div>

              <!-- Favorite Dashboards -->
              <div v-if="favoriteDashboards.length > 0" class="dashboard-group">
                <h3 class="group-title">
                  <StarIcon class="group-icon" />
                  Favorites
                </h3>
                <div class="group-items">
                  <button
                    v-for="(dashboard, index) in favoriteDashboards"
                    :key="`favorite-${dashboard.uriKey}`"
                    ref="dashboardItems"
                    @click="selectDashboard(dashboard)"
                    :class="getDashboardItemClasses(getGlobalIndex('favorite', index))"
                    type="button"
                  >
                    <div class="item-content">
                      <component
                        v-if="dashboard.icon"
                        :is="getIcon(dashboard.icon)"
                        class="item-icon"
                      />
                      <div class="item-info">
                        <div class="item-name">{{ dashboard.name }}</div>
                        <div v-if="dashboard.description" class="item-description">
                          {{ dashboard.description }}
                        </div>
                      </div>
                      <div class="item-indicators">
                        <StarIcon class="favorite-indicator filled" />
                      </div>
                    </div>
                  </button>
                </div>
              </div>

              <!-- All Dashboards -->
              <div v-if="otherDashboards.length > 0" class="dashboard-group">
                <h3 class="group-title">
                  <Squares2X2Icon class="group-icon" />
                  All Dashboards
                </h3>
                <div class="group-items">
                  <button
                    v-for="(dashboard, index) in otherDashboards"
                    :key="`all-${dashboard.uriKey}`"
                    ref="dashboardItems"
                    @click="selectDashboard(dashboard)"
                    :class="getDashboardItemClasses(getGlobalIndex('all', index))"
                    type="button"
                  >
                    <div class="item-content">
                      <component
                        v-if="dashboard.icon"
                        :is="getIcon(dashboard.icon)"
                        class="item-icon"
                      />
                      <div class="item-info">
                        <div class="item-name">{{ dashboard.name }}</div>
                        <div v-if="dashboard.description" class="item-description">
                          {{ dashboard.description }}
                        </div>
                      </div>
                      <div class="item-indicators">
                        <StarIcon
                          v-if="isFavorite(dashboard.uriKey)"
                          class="favorite-indicator"
                        />
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="modal-footer">
            <div class="keyboard-shortcuts">
              <div class="shortcut-group">
                <kbd>↑</kbd><kbd>↓</kbd>
                <span>Navigate</span>
              </div>
              <div class="shortcut-group">
                <kbd>Enter</kbd>
                <span>Select</span>
              </div>
              <div class="shortcut-group">
                <kbd>Esc</kbd>
                <span>Close</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { useDashboardNavigationStore } from '@/stores/dashboardNavigation'
import {
  XMarkIcon,
  MagnifyingGlassIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  StarIcon,
  Squares2X2Icon
} from '@heroicons/vue/24/outline'

export default {
  name: 'DashboardQuickSwitcher',
  components: {
    XMarkIcon,
    MagnifyingGlassIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    StarIcon,
    Squares2X2Icon
  },
  props: {
    enableKeyboardShortcuts: {
      type: Boolean,
      default: true
    }
  },
  emits: ['dashboard-selected', 'close'],
  setup(props, { emit }) {
    const navigationStore = useDashboardNavigationStore()
    
    // Refs
    const isOpen = ref(false)
    const searchQuery = ref('')
    const focusedIndex = ref(0)
    const quickSwitcher = ref(null)
    const searchInput = ref(null)
    const dashboardItems = ref([])

    // Computed
    const currentDashboard = computed(() => navigationStore.currentDashboard)
    
    const filteredDashboards = computed(() => {
      let dashboards = navigationStore.availableDashboards
        .filter(dashboard => dashboard.uriKey !== currentDashboard.value?.uriKey)

      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        dashboards = dashboards.filter(dashboard =>
          dashboard.name.toLowerCase().includes(query) ||
          (dashboard.description && dashboard.description.toLowerCase().includes(query)) ||
          (dashboard.category && dashboard.category.toLowerCase().includes(query))
        )
      }

      return dashboards
    })

    const recentDashboards = computed(() => {
      return navigationStore.recentlyViewed
        .filter(dashboard => 
          dashboard.uriKey !== currentDashboard.value?.uriKey &&
          filteredDashboards.value.some(d => d.uriKey === dashboard.uriKey)
        )
        .slice(0, 3)
    })

    const favoriteDashboards = computed(() => {
      return filteredDashboards.value
        .filter(dashboard => navigationStore.favorites.includes(dashboard.uriKey))
        .filter(dashboard => !recentDashboards.value.some(d => d.uriKey === dashboard.uriKey))
    })

    const otherDashboards = computed(() => {
      const recentUriKeys = recentDashboards.value.map(d => d.uriKey)
      const favoriteUriKeys = favoriteDashboards.value.map(d => d.uriKey)
      
      return filteredDashboards.value
        .filter(dashboard => 
          !recentUriKeys.includes(dashboard.uriKey) &&
          !favoriteUriKeys.includes(dashboard.uriKey)
        )
    })

    const allVisibleDashboards = computed(() => {
      return [
        ...recentDashboards.value,
        ...favoriteDashboards.value,
        ...otherDashboards.value
      ]
    })

    // Methods
    const open = () => {
      isOpen.value = true
      searchQuery.value = ''
      focusedIndex.value = 0
      
      nextTick(() => {
        if (searchInput.value) {
          searchInput.value.focus()
        }
      })
    }

    const close = () => {
      isOpen.value = false
      searchQuery.value = ''
      focusedIndex.value = 0
      emit('close')
    }

    const handleSearch = () => {
      focusedIndex.value = 0
    }

    const clearSearch = () => {
      searchQuery.value = ''
      focusedIndex.value = 0
      if (searchInput.value) {
        searchInput.value.focus()
      }
    }

    const selectDashboard = (dashboard) => {
      navigationStore.navigateToDashboard(dashboard)
      emit('dashboard-selected', dashboard)
      close()
    }

    const selectFocused = () => {
      const dashboard = allVisibleDashboards.value[focusedIndex.value]
      if (dashboard) {
        selectDashboard(dashboard)
      }
    }

    const focusNext = () => {
      if (focusedIndex.value < allVisibleDashboards.value.length - 1) {
        focusedIndex.value++
        scrollToFocused()
      }
    }

    const focusPrevious = () => {
      if (focusedIndex.value > 0) {
        focusedIndex.value--
        scrollToFocused()
      }
    }

    const scrollToFocused = () => {
      nextTick(() => {
        const focusedElement = dashboardItems.value[focusedIndex.value]
        if (focusedElement) {
          focusedElement.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
          })
        }
      })
    }

    const isFavorite = (dashboardUriKey) => {
      return navigationStore.favorites.includes(dashboardUriKey)
    }

    const formatRecentTime = (timestamp) => {
      if (!timestamp) return ''
      
      const now = Date.now()
      const diff = now - timestamp
      const minutes = Math.floor(diff / (1000 * 60))
      const hours = Math.floor(diff / (1000 * 60 * 60))
      const days = Math.floor(diff / (1000 * 60 * 60 * 24))

      if (minutes < 1) return 'Just now'
      if (minutes < 60) return `${minutes}m ago`
      if (hours < 24) return `${hours}h ago`
      return `${days}d ago`
    }

    const getGlobalIndex = (groupType, localIndex) => {
      let globalIndex = 0
      
      if (groupType === 'recent') {
        return localIndex
      }
      
      globalIndex += recentDashboards.value.length
      
      if (groupType === 'favorite') {
        return globalIndex + localIndex
      }
      
      globalIndex += favoriteDashboards.value.length
      
      if (groupType === 'all') {
        return globalIndex + localIndex
      }
      
      return globalIndex
    }

    const getDashboardItemClasses = (index) => [
      'dashboard-item',
      index === focusedIndex.value ? 'focused' : ''
    ]

    const getIcon = (iconName) => {
      // Simple icon mapping - replace with your icon system
      return 'div'
    }

    // Keyboard shortcuts
    const handleGlobalKeydown = (event) => {
      if (!props.enableKeyboardShortcuts) return

      // Ctrl/Cmd + K: Open quick switcher
      if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault()
        if (!isOpen.value) {
          open()
        }
        return
      }
    }

    // Watchers
    watch(searchQuery, () => {
      focusedIndex.value = 0
    })

    watch(isOpen, (newValue) => {
      if (newValue) {
        document.body.style.overflow = 'hidden'
      } else {
        document.body.style.overflow = ''
      }
    })

    // Lifecycle
    onMounted(() => {
      document.addEventListener('keydown', handleGlobalKeydown)
    })

    onUnmounted(() => {
      document.removeEventListener('keydown', handleGlobalKeydown)
      document.body.style.overflow = ''
    })

    return {
      // Refs
      isOpen,
      searchQuery,
      focusedIndex,
      quickSwitcher,
      searchInput,
      dashboardItems,

      // Computed
      currentDashboard,
      filteredDashboards,
      recentDashboards,
      favoriteDashboards,
      otherDashboards,
      allVisibleDashboards,

      // Methods
      open,
      close,
      handleSearch,
      clearSearch,
      selectDashboard,
      selectFocused,
      focusNext,
      focusPrevious,
      isFavorite,
      formatRecentTime,
      getGlobalIndex,
      getDashboardItemClasses,
      getIcon
    }
  }
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.quick-switcher-overlay {
  @apply fixed inset-0 bg-black/50 flex items-start justify-center pt-20 z-50;
}

.quick-switcher-modal {
  @apply bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-96 flex flex-col;
}

.modal-header {
  @apply flex items-center justify-between p-6 border-b border-gray-200;
}

.header-content {
  @apply flex-1;
}

.modal-title {
  @apply text-lg font-semibold text-gray-900;
}

.modal-subtitle {
  @apply text-sm text-gray-500 mt-1;
}

.close-button {
  @apply p-2 text-gray-400 hover:text-gray-600 rounded-md transition-colors;
}

.search-container {
  @apply p-4 border-b border-gray-200;
}

.search-input-wrapper {
  @apply relative;
}

.search-icon {
  @apply absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400;
}

.search-input {
  @apply w-full pl-10 pr-10 py-3 border border-gray-300 rounded-md text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.clear-search-button {
  @apply absolute right-3 top-1/2 transform -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 rounded;
}

.results-container {
  @apply flex-1 overflow-y-auto;
}

.no-results {
  @apply p-8;
}

.no-results-content {
  @apply text-center;
}

.no-results-icon {
  @apply mx-auto h-12 w-12 text-gray-400 mb-4;
}

.no-results-text {
  @apply text-gray-500 mb-4;
}

.clear-search-link {
  @apply text-blue-600 hover:text-blue-800 text-sm;
}

.dashboard-groups {
  @apply p-2;
}

.dashboard-group {
  @apply mb-6 last:mb-0;
}

.group-title {
  @apply flex items-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide;
}

.group-icon {
  @apply h-4 w-4 mr-2;
}

.group-items {
  @apply space-y-1;
}

.dashboard-item {
  @apply w-full p-3 rounded-md text-left transition-colors duration-150;
}

.dashboard-item:hover,
.dashboard-item.focused {
  @apply bg-gray-100;
}

.item-content {
  @apply flex items-center;
}

.item-icon {
  @apply flex-shrink-0 h-6 w-6 mr-3 text-gray-400;
}

.item-info {
  @apply flex-1 min-w-0;
}

.item-name {
  @apply font-medium text-gray-900 truncate;
}

.item-description {
  @apply text-sm text-gray-500 truncate;
}

.item-indicators {
  @apply flex items-center space-x-2;
}

.favorite-indicator {
  @apply h-4 w-4 text-gray-400;
}

.favorite-indicator.filled {
  @apply text-yellow-500;
}

.recent-time {
  @apply text-xs text-gray-400;
}

.modal-footer {
  @apply p-4 border-t border-gray-200 bg-gray-50;
}

.keyboard-shortcuts {
  @apply flex items-center justify-center space-x-6;
}

.shortcut-group {
  @apply flex items-center space-x-2 text-sm text-gray-500;
}

.shortcut-group kbd {
  @apply px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs font-mono;
}

/* Dark theme support */
@media (prefers-color-scheme: dark) {
  .quick-switcher-modal {
    @apply bg-gray-800;
  }

  .modal-header {
    @apply border-gray-700;
  }

  .modal-title {
    @apply text-gray-200;
  }

  .modal-subtitle {
    @apply text-gray-400;
  }

  .close-button {
    @apply text-gray-500 hover:text-gray-300;
  }

  .search-container {
    @apply border-gray-700;
  }

  .search-input {
    @apply border-gray-600 bg-gray-700 text-gray-200 placeholder-gray-400 focus:ring-blue-400 focus:border-blue-400;
  }

  .clear-search-button {
    @apply text-gray-500 hover:text-gray-300;
  }

  .no-results-text {
    @apply text-gray-400;
  }

  .clear-search-link {
    @apply text-blue-400 hover:text-blue-300;
  }

  .group-title {
    @apply text-gray-500;
  }

  .dashboard-item:hover,
  .dashboard-item.focused {
    @apply bg-gray-700;
  }

  .item-name {
    @apply text-gray-200;
  }

  .item-description {
    @apply text-gray-400;
  }

  .modal-footer {
    @apply border-gray-700 bg-gray-700;
  }

  .keyboard-shortcuts {
    @apply text-gray-400;
  }

  .shortcut-group kbd {
    @apply bg-gray-600 text-gray-300;
  }
}
</style>
