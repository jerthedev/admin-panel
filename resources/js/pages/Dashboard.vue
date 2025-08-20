<template>
  <AdminLayout :title="dashboard.name">
    <!-- Dashboard Navigation -->
    <DashboardBreadcrumbs
      v-if="navigationPreferences.showBreadcrumbs"
      :show-navigation="navigationPreferences.showNavigationControls"
      :show-quick-actions="navigationPreferences.showQuickActions"
      :show-keyboard-hints="navigationPreferences.showKeyboardHints"
      :enable-keyboard-shortcuts="navigationPreferences.enableKeyboardShortcuts"
      @refresh-dashboard="handleRefreshDashboard"
      @dashboard-switched="handleDashboardSwitched"
    />

    <!-- Dashboard with Seamless Transitions -->
    <DashboardTransition
      :animation="transitionAnimation"
      :duration="transitionDuration"
      :show-loading="showTransitionLoading"
      :loading-variant="loadingVariant"
      :loading-message="loadingMessage"
      :show-progress="showTransitionProgress"
      :show-cancel="allowCancelTransition"
      :show-error="showTransitionErrors"
      :theme="dashboardTheme"
      :enable-gestures="enableGestureNavigation"
      @transition-start="handleTransitionStart"
      @transition-end="handleTransitionEnd"
      @transition-error="handleTransitionError"
    >
      <template #default="{ transitionState }">
        <!-- Dashboard Component -->
        <Dashboard
          :dashboard="dashboard"
          :cards="cards"
          :available-dashboards="navigation.availableDashboards"
          :transition-state="transitionState"
          :class="getDashboardClasses(transitionState)"
        />
      </template>
    </DashboardTransition>

    <!-- Quick Switcher Modal -->
    <DashboardQuickSwitcher
      ref="quickSwitcher"
      :enable-keyboard-shortcuts="navigationPreferences.enableKeyboardShortcuts"
      @dashboard-selected="handleDashboardSwitched"
      @close="handleQuickSwitcherClose"
    />
  </AdminLayout>
</template>

<script setup>
/**
 * Dashboard Page
 *
 * Main dashboard page using the new Dashboard component system
 * with Nova v5 compatibility, extensible dashboard support, and
 * advanced navigation features.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { onMounted, computed, ref } from 'vue'
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import Dashboard from '@/Components/Dashboard/Dashboard.vue'
import DashboardBreadcrumbs from '@/Components/Dashboard/DashboardBreadcrumbs.vue'
import DashboardQuickSwitcher from '@/Components/Dashboard/DashboardQuickSwitcher.vue'
import DashboardTransition from '@/Components/Dashboard/DashboardTransition.vue'
import { useDashboardNavigationStore } from '@/stores/dashboardNavigation'
import { useDashboardTransitions } from '@/composables/useDashboardTransitions'

// Props from Inertia
const props = defineProps({
  dashboard: {
    type: Object,
    required: true,
    validator: (dashboard) => {
      return dashboard &&
             typeof dashboard.name === 'string' &&
             typeof dashboard.uriKey === 'string'
    }
  },
  navigation: {
    type: Object,
    required: true,
    validator: (navigation) => {
      return navigation &&
             navigation.currentDashboard &&
             Array.isArray(navigation.availableDashboards)
    }
  },
  cards: {
    type: Array,
    default: () => []
  },
  // Legacy prop for backward compatibility
  availableDashboards: {
    type: Array,
    default: () => []
  }
})

// Refs
const quickSwitcher = ref(null)

// Stores and Composables
const navigationStore = useDashboardNavigationStore()
const transitions = useDashboardTransitions()

// Computed
const navigationPreferences = computed(() => props.navigation.preferences || {})

// Transition Configuration
const transitionAnimation = computed(() => {
  // Determine animation based on navigation type
  if (transitions.currentTransition.value?.type === 'back') return 'slideRight'
  if (transitions.currentTransition.value?.type === 'forward') return 'slideLeft'
  if (transitions.currentTransition.value?.type === 'switch') return 'slide'
  if (transitions.currentTransition.value?.type === 'refresh') return 'fade'
  return 'fade'
})

const transitionDuration = computed(() => {
  return navigationPreferences.value.transitionDuration || 300
})

const showTransitionLoading = computed(() => {
  return navigationPreferences.value.showTransitionLoading !== false
})

const loadingVariant = computed(() => {
  return navigationPreferences.value.loadingVariant || 'spinner'
})

const loadingMessage = computed(() => {
  const dashboard = transitions.currentTransition.value?.dashboard
  if (dashboard) {
    return `Loading ${dashboard.name}...`
  }
  return 'Loading dashboard...'
})

const showTransitionProgress = computed(() => {
  return navigationPreferences.value.showTransitionProgress !== false
})

const allowCancelTransition = computed(() => {
  return navigationPreferences.value.allowCancelTransition !== false
})

const showTransitionErrors = computed(() => {
  return navigationPreferences.value.showTransitionErrors !== false
})

const dashboardTheme = computed(() => {
  return navigationPreferences.value.theme || 'light'
})

const enableGestureNavigation = computed(() => {
  return navigationPreferences.value.enableGestureNavigation === true
})

// Methods
const handleRefreshDashboard = async () => {
  try {
    await transitions.refreshDashboard({
      animation: 'pulse',
      preserveScroll: false
    })
  } catch (error) {
    console.error('Failed to refresh dashboard:', error)
    // Fallback to page reload
    window.location.reload()
  }
}

const handleDashboardSwitched = async (dashboard) => {
  try {
    await transitions.switchToDashboard(dashboard, {
      animation: 'slide',
      preserveScroll: false,
      showProgress: true
    })
  } catch (error) {
    console.error('Failed to switch dashboard:', error)
  }
}

const handleQuickSwitcherClose = () => {
  console.log('Quick switcher closed')
}

// Transition Event Handlers
const handleTransitionStart = () => {
  console.log('Dashboard transition started')
}

const handleTransitionEnd = () => {
  console.log('Dashboard transition completed')
}

const handleTransitionError = (error) => {
  console.error('Dashboard transition error:', error)
}

// Dashboard Classes
const getDashboardClasses = (transitionState) => {
  return [
    'dashboard-container',
    `transition-${transitionState}`,
    {
      'is-transitioning': transitions.isTransitioning.value,
      'has-error': transitions.hasError.value
    }
  ]
}

// Initialize navigation store
onMounted(() => {
  navigationStore.initialize({
    currentDashboard: props.navigation.currentDashboard,
    availableDashboards: props.navigation.availableDashboards
  })
})
</script>
