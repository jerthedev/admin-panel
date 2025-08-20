<template>
  <nav
    class="dashboard-navigation"
    :class="navigationClasses"
    role="navigation"
    aria-label="Dashboard navigation"
  >
    <!-- Breadcrumbs -->
    <div v-if="showBreadcrumbs" class="breadcrumbs-container">
      <DashboardBreadcrumbs
        :breadcrumbs="breadcrumbs"
        :show-home="showHome"
        :show-icons="showBreadcrumbIcons"
        @navigate="handleBreadcrumbNavigation"
      />
    </div>

    <!-- Quick Actions -->
    <div v-if="showQuickActions" class="quick-actions">
      <!-- Back Button -->
      <button
        v-if="showBackButton && canGoBack"
        @click="goBack"
        class="quick-action-button back-button"
        :aria-label="backButtonLabel"
        type="button"
      >
        <ArrowLeftIcon class="action-icon" />
        <span v-if="showActionLabels">Back</span>
      </button>

      <!-- Dashboard Selector -->
      <div class="dashboard-selector-container">
        <DashboardSelector
          :dashboards="dashboards"
          :current-dashboard="currentDashboard"
          :enable-search="enableSearch"
          :group-by="groupBy"
          :sort-by="sortBy"
          :show-favorites="showFavorites"
          :show-recent="showRecent"
          :compact="compactSelector"
          @dashboard-changed="handleDashboardChange"
        />
      </div>

      <!-- Quick Switcher -->
      <button
        v-if="showQuickSwitcher"
        @click="openQuickSwitcher"
        class="quick-action-button switcher-button"
        :aria-label="quickSwitcherLabel"
        type="button"
      >
        <CommandLineIcon class="action-icon" />
        <span v-if="showActionLabels">Quick Switch</span>
      </button>

      <!-- Refresh Button -->
      <button
        v-if="showRefreshButton"
        @click="refreshDashboard"
        class="quick-action-button refresh-button"
        :class="{ 'is-refreshing': isRefreshing }"
        :disabled="isRefreshing"
        :aria-label="refreshButtonLabel"
        type="button"
      >
        <ArrowPathIcon class="action-icon" :class="{ 'animate-spin': isRefreshing }" />
        <span v-if="showActionLabels">Refresh</span>
      </button>

      <!-- Settings Button -->
      <button
        v-if="showSettingsButton"
        @click="openSettings"
        class="quick-action-button settings-button"
        :aria-label="settingsButtonLabel"
        type="button"
      >
        <CogIcon class="action-icon" />
        <span v-if="showActionLabels">Settings</span>
      </button>
    </div>

    <!-- Navigation History -->
    <div v-if="showHistory && hasHistory" class="navigation-history">
      <div class="history-label">Recent:</div>
      <div class="history-items">
        <button
          v-for="item in recentHistory"
          :key="item.uriKey"
          @click="navigateToHistoryItem(item)"
          class="history-item"
          :title="item.name"
          type="button"
        >
          <DashboardIcon
            :icon="item.icon"
            size="xs"
            class="history-icon"
          />
          <span class="history-name">{{ item.name }}</span>
        </button>
      </div>
    </div>

    <!-- Keyboard Shortcuts Indicator -->
    <div v-if="showKeyboardShortcuts" class="keyboard-shortcuts">
      <button
        @click="toggleShortcutsHelp"
        class="shortcuts-button"
        :aria-label="shortcutsButtonLabel"
        type="button"
      >
        <span class="shortcuts-key">?</span>
      </button>
    </div>

    <!-- Quick Switcher Modal -->
    <DashboardQuickSwitcher
      v-if="quickSwitcherOpen"
      :dashboards="dashboards"
      :current-dashboard="currentDashboard"
      @close="closeQuickSwitcher"
      @select="handleQuickSwitcherSelect"
    />

    <!-- Settings Modal -->
    <DashboardSettings
      v-if="settingsOpen"
      :dashboard="currentDashboard"
      :configuration="dashboardConfiguration"
      @close="closeSettings"
      @save="handleSettingsSave"
    />

    <!-- Keyboard Shortcuts Help -->
    <DashboardShortcutsHelp
      v-if="shortcutsHelpOpen"
      @close="closeShortcutsHelp"
    />
  </nav>
</template>

<script>
import { computed, ref, inject } from 'vue'
import {
  ArrowLeftIcon,
  CommandLineIcon,
  ArrowPathIcon,
  CogIcon
} from '@heroicons/vue/24/outline'
import DashboardBreadcrumbs from './DashboardBreadcrumbs.vue'
import DashboardSelector from './DashboardSelector.vue'
import DashboardIcon from './DashboardIcon.vue'
import DashboardQuickSwitcher from './DashboardQuickSwitcher.vue'
import DashboardSettings from './DashboardSettings.vue'
import DashboardShortcutsHelp from './DashboardShortcutsHelp.vue'
import { useDashboardNavigationStore } from '@/stores/dashboardNavigation'
import { useDashboardTransitions } from '@/composables/useDashboardTransitions'

export default {
  name: 'DashboardNavigation',
  components: {
    ArrowLeftIcon,
    CommandLineIcon,
    ArrowPathIcon,
    CogIcon,
    DashboardBreadcrumbs,
    DashboardSelector,
    DashboardIcon,
    DashboardQuickSwitcher,
    DashboardSettings,
    DashboardShortcutsHelp
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
    showBreadcrumbs: {
      type: Boolean,
      default: true
    },
    showQuickActions: {
      type: Boolean,
      default: true
    },
    showBackButton: {
      type: Boolean,
      default: true
    },
    showQuickSwitcher: {
      type: Boolean,
      default: true
    },
    showRefreshButton: {
      type: Boolean,
      default: true
    },
    showSettingsButton: {
      type: Boolean,
      default: true
    },
    showHistory: {
      type: Boolean,
      default: true
    },
    showKeyboardShortcuts: {
      type: Boolean,
      default: true
    },
    showActionLabels: {
      type: Boolean,
      default: false
    },
    showHome: {
      type: Boolean,
      default: true
    },
    showBreadcrumbIcons: {
      type: Boolean,
      default: true
    },
    enableSearch: {
      type: Boolean,
      default: true
    },
    groupBy: {
      type: String,
      default: 'category'
    },
    sortBy: {
      type: String,
      default: 'priority'
    },
    showFavorites: {
      type: Boolean,
      default: true
    },
    showRecent: {
      type: Boolean,
      default: true
    },
    compactSelector: {
      type: Boolean,
      default: false
    },
    maxHistoryItems: {
      type: Number,
      default: 5
    },
    variant: {
      type: String,
      default: 'default',
      validator: (value) => ['default', 'compact', 'minimal'].includes(value)
    }
  },
  emits: [
    'dashboard-changed',
    'refresh',
    'settings-changed',
    'navigate-back',
    'navigate-to'
  ],
  setup(props, { emit }) {
    // Composables
    const navigationStore = useDashboardNavigationStore()
    const transitions = useDashboardTransitions()

    // Reactive state
    const isRefreshing = ref(false)
    const quickSwitcherOpen = ref(false)
    const settingsOpen = ref(false)
    const shortcutsHelpOpen = ref(false)

    // Computed properties
    const navigationClasses = computed(() => [
      'dashboard-navigation-base',
      `variant-${props.variant}`,
      {
        'has-breadcrumbs': props.showBreadcrumbs,
        'has-quick-actions': props.showQuickActions,
        'has-history': props.showHistory && hasHistory.value,
        'compact': props.compactSelector
      }
    ])

    const breadcrumbs = computed(() => {
      return navigationStore.breadcrumbs || []
    })

    const canGoBack = computed(() => {
      return navigationStore.canGoBack
    })

    const hasHistory = computed(() => {
      return navigationStore.history.length > 0
    })

    const recentHistory = computed(() => {
      return navigationStore.history.slice(0, props.maxHistoryItems)
    })

    const dashboardConfiguration = computed(() => {
      return navigationStore.currentConfiguration || {}
    })

    const backButtonLabel = computed(() => {
      const previousDashboard = navigationStore.previousDashboard
      return previousDashboard 
        ? `Go back to ${previousDashboard.name}`
        : 'Go back'
    })

    const quickSwitcherLabel = computed(() => {
      return 'Open quick dashboard switcher (Ctrl+K)'
    })

    const refreshButtonLabel = computed(() => {
      return isRefreshing.value ? 'Refreshing...' : 'Refresh dashboard'
    })

    const settingsButtonLabel = computed(() => {
      return 'Open dashboard settings'
    })

    const shortcutsButtonLabel = computed(() => {
      return 'Show keyboard shortcuts'
    })

    // Methods
    const handleDashboardChange = async (dashboard) => {
      try {
        await transitions.switchToDashboard(dashboard, {
          animation: 'slide',
          preserveScroll: false,
          showProgress: true
        })

        navigationStore.setCurrentDashboard(dashboard)
        emit('dashboard-changed', dashboard)
      } catch (error) {
        console.error('Failed to change dashboard:', error)
      }
    }

    const handleBreadcrumbNavigation = async (breadcrumb) => {
      if (breadcrumb.dashboard) {
        await handleDashboardChange(breadcrumb.dashboard)
      } else if (breadcrumb.url) {
        emit('navigate-to', breadcrumb.url)
      }
    }

    const goBack = async () => {
      const previousDashboard = navigationStore.previousDashboard
      if (previousDashboard) {
        await handleDashboardChange(previousDashboard)
        emit('navigate-back', previousDashboard)
      }
    }

    const refreshDashboard = async () => {
      if (isRefreshing.value) return

      isRefreshing.value = true
      try {
        await transitions.refreshDashboard(props.currentDashboard)
        emit('refresh', props.currentDashboard)
      } catch (error) {
        console.error('Failed to refresh dashboard:', error)
      } finally {
        isRefreshing.value = false
      }
    }

    const navigateToHistoryItem = async (item) => {
      await handleDashboardChange(item)
    }

    const openQuickSwitcher = () => {
      quickSwitcherOpen.value = true
    }

    const closeQuickSwitcher = () => {
      quickSwitcherOpen.value = false
    }

    const handleQuickSwitcherSelect = async (dashboard) => {
      closeQuickSwitcher()
      await handleDashboardChange(dashboard)
    }

    const openSettings = () => {
      settingsOpen.value = true
    }

    const closeSettings = () => {
      settingsOpen.value = false
    }

    const handleSettingsSave = (settings) => {
      navigationStore.updateConfiguration(settings)
      emit('settings-changed', settings)
      closeSettings()
    }

    const toggleShortcutsHelp = () => {
      shortcutsHelpOpen.value = !shortcutsHelpOpen.value
    }

    const closeShortcutsHelp = () => {
      shortcutsHelpOpen.value = false
    }

    return {
      isRefreshing,
      quickSwitcherOpen,
      settingsOpen,
      shortcutsHelpOpen,
      navigationClasses,
      breadcrumbs,
      canGoBack,
      hasHistory,
      recentHistory,
      dashboardConfiguration,
      backButtonLabel,
      quickSwitcherLabel,
      refreshButtonLabel,
      settingsButtonLabel,
      shortcutsButtonLabel,
      handleDashboardChange,
      handleBreadcrumbNavigation,
      goBack,
      refreshDashboard,
      navigateToHistoryItem,
      openQuickSwitcher,
      closeQuickSwitcher,
      handleQuickSwitcherSelect,
      openSettings,
      closeSettings,
      handleSettingsSave,
      toggleShortcutsHelp,
      closeShortcutsHelp
    }
  }
}
</script>

<style scoped>
.dashboard-navigation {
  @apply flex flex-col space-y-4 p-4 bg-white border-b border-gray-200;
}

.dashboard-navigation-base {
  @apply transition-all duration-200;
}

/* Variant styles */
.dashboard-navigation.variant-compact {
  @apply p-2 space-y-2;
}

.dashboard-navigation.variant-minimal {
  @apply p-1 space-y-1 border-none;
}

/* Breadcrumbs */
.breadcrumbs-container {
  @apply flex-shrink-0;
}

/* Quick Actions */
.quick-actions {
  @apply flex items-center justify-between space-x-4;
}

.dashboard-selector-container {
  @apply flex-1 max-w-md;
}

.quick-action-button {
  @apply inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200;
}

.quick-action-button:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.quick-action-button.is-refreshing {
  @apply bg-blue-50 border-blue-300 text-blue-700;
}

.action-icon {
  @apply h-4 w-4;
}

.quick-action-button span {
  @apply ml-2;
}

.variant-compact .quick-action-button {
  @apply px-2 py-1 text-xs;
}

.variant-compact .action-icon {
  @apply h-3 w-3;
}

.variant-minimal .quick-action-button {
  @apply border-none shadow-none px-2 py-1;
}

/* Navigation History */
.navigation-history {
  @apply flex items-center space-x-3;
}

.history-label {
  @apply text-xs font-medium text-gray-500 uppercase tracking-wide;
}

.history-items {
  @apply flex items-center space-x-2;
}

.history-item {
  @apply inline-flex items-center px-2 py-1 rounded text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors duration-200;
}

.history-icon {
  @apply mr-1;
}

.history-name {
  @apply truncate max-w-20;
}

/* Keyboard Shortcuts */
.keyboard-shortcuts {
  @apply flex items-center;
}

.shortcuts-button {
  @apply inline-flex items-center justify-center w-6 h-6 rounded border border-gray-300 text-xs font-mono text-gray-500 hover:text-gray-700 hover:border-gray-400 transition-colors duration-200;
}

.shortcuts-key {
  @apply font-bold;
}

/* Responsive */
@media (max-width: 768px) {
  .dashboard-navigation {
    @apply p-2 space-y-2;
  }

  .quick-actions {
    @apply flex-wrap space-x-2 space-y-2;
  }

  .dashboard-selector-container {
    @apply w-full max-w-none order-first;
  }

  .navigation-history {
    @apply hidden;
  }

  .quick-action-button span {
    @apply hidden;
  }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .dashboard-navigation {
    @apply bg-gray-800 border-gray-700;
  }

  .quick-action-button {
    @apply border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700;
  }

  .quick-action-button.is-refreshing {
    @apply bg-blue-900 border-blue-700 text-blue-300;
  }

  .history-label {
    @apply text-gray-400;
  }

  .history-item {
    @apply text-gray-400 hover:text-gray-200 hover:bg-gray-700;
  }

  .shortcuts-button {
    @apply border-gray-600 text-gray-400 hover:text-gray-200 hover:border-gray-500;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .dashboard-navigation-base {
    @apply transition-none;
  }

  .quick-action-button {
    @apply transition-none;
  }

  .history-item {
    @apply transition-none;
  }
}
</style>
