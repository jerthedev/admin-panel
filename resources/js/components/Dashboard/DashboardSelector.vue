<template>
  <div class="dashboard-selector relative" data-testid="dashboard-selector">
    <!-- Dropdown Button -->
    <button
      @click="toggleDropdown"
      @keydown.escape="closeDropdown"
      @keydown.arrow-down.prevent="focusNext"
      @keydown.arrow-up.prevent="focusPrevious"
      :class="buttonClasses"
      :aria-expanded="isOpen"
      aria-haspopup="true"
      type="button"
    >
      <!-- Dashboard Icon -->
      <div v-if="currentDashboard.icon" class="dashboard-button-icon">
        <component
          :is="currentDashboard.icon"
          class="h-4 w-4"
        />
      </div>
      <span class="dashboard-name">{{ currentDashboard.name }}</span>
      <span v-if="currentDashboard.badge" :class="getBadgeClasses(currentDashboard.badgeType)" class="ml-2">
        {{ currentDashboard.badge }}
      </span>
      <svg
        :class="['ml-2 h-4 w-4 transition-transform duration-200', { 'rotate-180': isOpen }]"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </button>

    <!-- Dropdown Menu -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        ref="dropdown"
        class="dashboard-dropdown"
        role="menu"
        aria-orientation="vertical"
        @keydown.escape="closeDropdown"
        @keydown.arrow-down.prevent="focusNext"
        @keydown.arrow-up.prevent="focusPrevious"
        @keydown.enter.prevent="selectFocused"
      >
        <div class="dropdown-header">
          <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
            Available Dashboards
          </h3>

          <!-- Search Input -->
          <div v-if="enableSearch && dashboards.length > searchThreshold" class="search-container">
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                ref="searchInput"
                v-model="searchQuery"
                @keydown.arrow-down.prevent="focusNext"
                @keydown.arrow-up.prevent="focusPrevious"
                @keydown.enter.prevent="selectFocused"
                @keydown.escape="clearSearchAndFocus"
                type="text"
                placeholder="Search dashboards..."
                class="search-input"
              />
              <button
                v-if="searchQuery"
                @click="clearSearch"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
                type="button"
              >
                <svg class="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div class="dropdown-content">
          <!-- No Results Message -->
          <div v-if="filteredDashboards.length === 0" class="no-results">
            <div class="text-center py-6">
              <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ searchQuery ? 'No dashboards match your search' : 'No dashboards available' }}
              </p>
              <button
                v-if="searchQuery"
                @click="clearSearch"
                class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                type="button"
              >
                Clear search
              </button>
            </div>
          </div>

          <!-- Dashboard Groups -->
          <template v-else>
            <div
              v-for="(group, groupKey) in groupedDashboards"
              :key="groupKey"
              class="dashboard-group"
            >
              <!-- Group Header -->
              <div v-if="showGroupHeaders && Object.keys(groupedDashboards).length > 1" class="group-header">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-4 py-2">
                  {{ getGroupDisplayName(groupKey) }}
                </h4>
              </div>

              <!-- Dashboard Items -->
              <button
                v-for="(dashboard, index) in group"
                :key="dashboard.uriKey"
                ref="menuItems"
                @click="selectDashboard(dashboard)"
                :class="getItemClasses(dashboard, getGlobalIndex(dashboard))"
                :aria-selected="dashboard.uriKey === currentDashboard.uriKey"
                role="menuitem"
                type="button"
              >
                <div class="dashboard-item-content">
                  <!-- Dashboard Icon -->
                  <div class="dashboard-icon">
                    <component
                      v-if="dashboard.icon"
                      :is="dashboard.icon"
                      class="h-4 w-4"
                    />
                    <svg
                      v-else
                      class="h-4 w-4"
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
                  </div>

                  <!-- Dashboard Info -->
                  <div class="dashboard-info">
                    <div class="dashboard-name">{{ dashboard.name }}</div>
                    <div v-if="dashboard.description" class="dashboard-description">
                      {{ dashboard.description }}
                    </div>
                    <div v-if="dashboard.category && !showGroupHeaders" class="dashboard-category">
                      {{ dashboard.category }}
                    </div>
                  </div>

                  <!-- Current Indicator -->
                  <div v-if="dashboard.uriKey === currentDashboard.uriKey" class="current-indicator">
                    <svg class="h-4 w-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                      />
                    </svg>
                  </div>

                  <!-- Badge -->
                  <div v-if="dashboard.badge" class="dashboard-badge">
                    <span :class="getBadgeClasses(dashboard.badgeType)">
                      {{ dashboard.badge }}
                    </span>
                  </div>

                  <!-- Favorite Indicator -->
                  <div v-if="dashboard.isFavorite" class="favorite-indicator">
                    <svg class="h-4 w-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  </div>
                </div>
              </button>
            </div>
          </template>
        </div>

        <!-- Dropdown Footer -->
        <div v-if="showFooter" class="dropdown-footer">
          <div class="flex items-center justify-between">
            <div class="text-xs text-gray-500 dark:text-gray-400">
              {{ getFooterText() }}
            </div>
            <div v-if="enableQuickActions" class="flex items-center space-x-2">
              <button
                v-if="canCreateDashboard"
                @click="createDashboard"
                class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                type="button"
              >
                + New
              </button>
              <button
                v-if="canManageDashboards"
                @click="manageDashboards"
                class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"
                type="button"
              >
                Manage
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Backdrop -->
    <div
      v-if="isOpen"
      @click="closeDropdown"
      class="fixed inset-0 z-10"
      aria-hidden="true"
    ></div>
  </div>
</template>

<script>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useDashboardNavigationStore } from '@/stores/dashboardNavigation'
import { useDashboardTransitions } from '@/composables/useDashboardTransitions'

export default {
  name: 'DashboardSelector',
  props: {
    dashboards: {
      type: Array,
      required: true,
      validator: (dashboards) => {
        return Array.isArray(dashboards) && dashboards.every(d =>
          d && typeof d.name === 'string' && typeof d.uriKey === 'string'
        )
      }
    },
    currentDashboard: {
      type: Object,
      required: true,
      validator: (dashboard) => {
        return dashboard &&
               typeof dashboard.name === 'string' &&
               typeof dashboard.uriKey === 'string'
      }
    },
    showFooter: {
      type: Boolean,
      default: true
    },
    enableSearch: {
      type: Boolean,
      default: true
    },
    searchThreshold: {
      type: Number,
      default: 5
    },
    groupBy: {
      type: String,
      default: 'category',
      validator: (value) => ['category', 'type', 'none'].includes(value)
    },
    showGroupHeaders: {
      type: Boolean,
      default: true
    },
    enableQuickActions: {
      type: Boolean,
      default: false
    },
    canCreateDashboard: {
      type: Boolean,
      default: false
    },
    canManageDashboards: {
      type: Boolean,
      default: false
    },
    sortBy: {
      type: String,
      default: 'name',
      validator: (value) => ['name', 'category', 'recent', 'favorite'].includes(value)
    }
  },
  emits: ['dashboard-changed', 'create-dashboard', 'manage-dashboards'],
  setup(props, { emit }) {
    // Composables
    const navigationStore = useDashboardNavigationStore()
    const transitions = useDashboardTransitions()

    const isOpen = ref(false)
    const dropdown = ref(null)
    const searchInput = ref(null)
    const menuItems = ref([])
    const focusedIndex = ref(-1)
    const searchQuery = ref('')

    // Computed properties
    const buttonClasses = computed(() => [
      'inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600',
      'rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300',
      'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700',
      'focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
      'transition-colors duration-200'
    ])

    const visibleDashboards = computed(() =>
      props.dashboards.filter(d => d.visible !== false)
    )

    const filteredDashboards = computed(() => {
      let dashboards = visibleDashboards.value

      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        dashboards = dashboards.filter(dashboard =>
          dashboard.name.toLowerCase().includes(query) ||
          (dashboard.description && dashboard.description.toLowerCase().includes(query)) ||
          (dashboard.category && dashboard.category.toLowerCase().includes(query)) ||
          (dashboard.tags && dashboard.tags.some(tag => tag.toLowerCase().includes(query)))
        )
      }

      // Apply sorting
      return sortDashboards(dashboards)
    })

    const groupedDashboards = computed(() => {
      if (props.groupBy === 'none') {
        return { 'all': filteredDashboards.value }
      }

      const groups = {}
      filteredDashboards.value.forEach(dashboard => {
        let groupKey = 'Other'

        if (props.groupBy === 'category' && dashboard.category) {
          groupKey = dashboard.category
        } else if (props.groupBy === 'type' && dashboard.type) {
          groupKey = dashboard.type
        }

        if (!groups[groupKey]) {
          groups[groupKey] = []
        }
        groups[groupKey].push(dashboard)
      })

      // Sort groups by name, but put favorites first if sorting by favorite
      const sortedGroups = {}
      const groupKeys = Object.keys(groups).sort()

      if (props.sortBy === 'favorite') {
        // Put groups with favorites first
        const favGroups = groupKeys.filter(key =>
          groups[key].some(d => d.isFavorite)
        )
        const otherGroups = groupKeys.filter(key =>
          !groups[key].some(d => d.isFavorite)
        )

        const orderedKeys = favGroups.concat(otherGroups)
        orderedKeys.forEach(key => {
          sortedGroups[key] = groups[key]
        })
      } else {
        groupKeys.forEach(key => {
          sortedGroups[key] = groups[key]
        })
      }

      return sortedGroups
    })

    // Helper methods
    const sortDashboards = (dashboards) => {
      return [...dashboards].sort((a, b) => {
        switch (props.sortBy) {
          case 'favorite':
            if (a.isFavorite && !b.isFavorite) return -1
            if (!a.isFavorite && b.isFavorite) return 1
            return a.name.localeCompare(b.name)
          case 'category':
            const catA = a.category || 'ZZZ'
            const catB = b.category || 'ZZZ'
            if (catA !== catB) return catA.localeCompare(catB)
            return a.name.localeCompare(b.name)
          case 'recent':
            const recentA = a.lastAccessed || 0
            const recentB = b.lastAccessed || 0
            if (recentA !== recentB) return recentB - recentA
            return a.name.localeCompare(b.name)
          default:
            return a.name.localeCompare(b.name)
        }
      })
    }

    const getGroupDisplayName = (groupKey) => {
      if (groupKey === 'all') return 'All Dashboards'
      if (groupKey === 'Other') return 'Other'
      return groupKey
    }

    const getGlobalIndex = (dashboard) => {
      return filteredDashboards.value.findIndex(d => d.uriKey === dashboard.uriKey)
    }

    const getFooterText = () => {
      const total = props.dashboards.length
      const visible = filteredDashboards.value.length

      if (searchQuery.value) {
        return `${visible} of ${total} dashboard${total !== 1 ? 's' : ''}`
      }
      return `${total} dashboard${total !== 1 ? 's' : ''} available`
    }

    // Methods
    const toggleDropdown = () => {
      if (isOpen.value) {
        closeDropdown()
      } else {
        openDropdown()
      }
    }

    const openDropdown = async () => {
      isOpen.value = true
      focusedIndex.value = filteredDashboards.value.findIndex(
        d => d.uriKey === props.currentDashboard.uriKey
      )

      await nextTick()

      // Focus search input if available, otherwise focus dropdown
      if (props.enableSearch && props.dashboards.length > props.searchThreshold && searchInput.value) {
        searchInput.value.focus()
      } else if (dropdown.value) {
        dropdown.value.focus()
      }
    }

    const closeDropdown = () => {
      isOpen.value = false
      focusedIndex.value = -1
      searchQuery.value = ''
    }

    const selectDashboard = async (dashboard) => {
      if (dashboard.uriKey === props.currentDashboard.uriKey) {
        closeDropdown()
        return
      }

      closeDropdown()

      try {
        // Use the transition system for smooth navigation
        await transitions.switchToDashboard(dashboard, {
          animation: 'slide',
          preserveScroll: false,
          showProgress: true
        })

        // Update navigation store
        navigationStore.setCurrentDashboard(dashboard)

        // Emit event for parent components
        emit('dashboard-changed', dashboard)
      } catch (error) {
        console.error('Failed to switch dashboard:', error)
        // Fallback to direct navigation
        emit('dashboard-changed', dashboard)
      }
    }

    const clearSearch = () => {
      searchQuery.value = ''
      if (searchInput.value) {
        searchInput.value.focus()
      }
    }

    const clearSearchAndFocus = () => {
      if (searchQuery.value) {
        clearSearch()
      } else {
        closeDropdown()
      }
    }

    const createDashboard = () => {
      emit('create-dashboard')
      closeDropdown()
    }

    const manageDashboards = () => {
      emit('manage-dashboards')
      closeDropdown()
    }

    const focusNext = () => {
      if (focusedIndex.value < filteredDashboards.value.length - 1) {
        focusedIndex.value++
        focusMenuItem()
      }
    }

    const focusPrevious = () => {
      if (focusedIndex.value > 0) {
        focusedIndex.value--
        focusMenuItem()
      }
    }

    const focusMenuItem = async () => {
      await nextTick()
      if (menuItems.value[focusedIndex.value]) {
        menuItems.value[focusedIndex.value].focus()
      }
    }

    const selectFocused = () => {
      if (focusedIndex.value >= 0 && focusedIndex.value < filteredDashboards.value.length) {
        selectDashboard(filteredDashboards.value[focusedIndex.value])
      }
    }

    const getItemClasses = (dashboard, index) => [
      'w-full text-left px-4 py-3 text-sm transition-colors duration-200',
      'focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700',
      {
        'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300': 
          dashboard.uriKey === props.currentDashboard.uriKey,
        'text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700': 
          dashboard.uriKey !== props.currentDashboard.uriKey,
        'bg-gray-100 dark:bg-gray-700': focusedIndex.value === index
      }
    ]

    const getBadgeClasses = (badgeType) => [
      'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
      {
        'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200': 
          !badgeType || badgeType === 'default',
        'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200': 
          badgeType === 'info',
        'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200': 
          badgeType === 'success',
        'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200': 
          badgeType === 'warning',
        'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200': 
          badgeType === 'danger'
      }
    ]

    // Handle clicks outside
    const handleClickOutside = (event) => {
      if (dropdown.value && !dropdown.value.contains(event.target)) {
        closeDropdown()
      }
    }

    // Watchers
    watch(searchQuery, () => {
      // Reset focus when search changes
      focusedIndex.value = -1
    })

    watch(isOpen, (newValue) => {
      if (!newValue) {
        // Clear search when dropdown closes
        searchQuery.value = ''
      }
    })

    // Lifecycle
    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      isOpen,
      dropdown,
      searchInput,
      menuItems,
      focusedIndex,
      searchQuery,
      buttonClasses,
      visibleDashboards,
      filteredDashboards,
      groupedDashboards,
      sortDashboards,
      getGroupDisplayName,
      getGlobalIndex,
      getFooterText,
      toggleDropdown,
      openDropdown,
      closeDropdown,
      selectDashboard,
      clearSearch,
      clearSearchAndFocus,
      createDashboard,
      manageDashboards,
      focusNext,
      focusPrevious,
      selectFocused,
      getItemClasses,
      getBadgeClasses
    }
  }
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.dashboard-selector {
  @apply relative inline-block text-left z-20;
}

.dashboard-dropdown {
  @apply absolute right-0 mt-2 w-96 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black/5 focus:outline-none z-30;
}

.dropdown-header {
  @apply px-4 py-3 border-b border-gray-200 dark:border-gray-700;
}

.search-container {
  @apply mt-3;
}

.search-input {
  @apply block w-full pl-10 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm placeholder-gray-500 dark:placeholder-gray-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.dropdown-content {
  @apply py-1 max-h-80 overflow-y-auto;
}

.no-results {
  @apply border-b border-gray-200 dark:border-gray-700;
}

.dashboard-group:not(:last-child) {
  @apply border-b border-gray-200 dark:border-gray-700;
}

.group-header {
  @apply bg-gray-50 dark:bg-gray-700/30;
}

.dropdown-footer {
  @apply px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50;
}

.dashboard-button-icon {
  @apply flex-shrink-0 mr-2 text-gray-400 dark:text-gray-500;
}

.dashboard-item-content {
  @apply flex items-center space-x-3;
}

.dashboard-icon {
  @apply flex-shrink-0 text-gray-400 dark:text-gray-500;
}

.dashboard-info {
  @apply flex-1 min-w-0;
}

.dashboard-name {
  @apply font-medium truncate;
}

.dashboard-description {
  @apply text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5;
}

.dashboard-category {
  @apply text-xs text-blue-600 dark:text-blue-400 truncate mt-0.5 font-medium;
}

.current-indicator {
  @apply flex-shrink-0;
}

.dashboard-badge {
  @apply flex-shrink-0;
}

.favorite-indicator {
  @apply flex-shrink-0;
}

/* Custom scrollbar for dropdown */
.dropdown-content::-webkit-scrollbar {
  @apply w-2;
}

.dropdown-content::-webkit-scrollbar-track {
  @apply bg-gray-100 dark:bg-gray-700;
}

.dropdown-content::-webkit-scrollbar-thumb {
  @apply bg-gray-300 dark:bg-gray-600 rounded;
}

.dropdown-content::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-400 dark:bg-gray-500;
}
</style>
