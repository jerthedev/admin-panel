import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'

export const useDashboardNavigationStore = defineStore('dashboardNavigation', () => {
  // State
  const currentDashboard = ref(null)
  const previousDashboard = ref(null)
  const availableDashboards = ref([])
  const navigationHistory = ref([])
  const favorites = ref([])
  const recentlyViewed = ref([])
  const isNavigating = ref(false)
  const navigationError = ref(null)
  const lastNavigationTime = ref(null)

  // Enhanced preferences
  const navigationPreferences = ref({
    showBreadcrumbs: true,
    showQuickSwitcher: true,
    maxHistoryItems: 10,
    maxRecentItems: 5,
    enableKeyboardShortcuts: true,
    persistState: true,
    rememberLastDashboard: true,
    animationDuration: 300,
    preserveScrollPosition: false,
    autoRefreshInterval: 0, // 0 = disabled
    enableUsageTracking: true
  })

  // Configuration state
  const currentConfiguration = ref({})
  const globalConfiguration = ref({})
  const userPreferences = ref({})

  // Enhanced computed properties
  const hasMultipleDashboards = computed(() => availableDashboards.value.length > 1)

  const canGoBack = computed(() => {
    return previousDashboard.value !== null || navigationHistory.value.length > 1
  })

  const canGoForward = computed(() => {
    const currentIndex = getCurrentHistoryIndex()
    return currentIndex < navigationHistory.value.length - 1
  })

  const nextDashboard = computed(() => {
    const currentIndex = getCurrentHistoryIndex()
    if (currentIndex === -1 || currentIndex >= navigationHistory.value.length - 1) return null
    return navigationHistory.value[currentIndex + 1]
  })

  const navigationState = computed(() => ({
    current: currentDashboard.value,
    previous: previousDashboard.value,
    canGoBack: canGoBack.value,
    canGoForward: canGoForward.value,
    isNavigating: isNavigating.value,
    error: navigationError.value,
    lastNavigationTime: lastNavigationTime.value
  }))

  const recentDashboards = computed(() => {
    return recentlyViewed.value
      .filter(item => item.uriKey !== currentDashboard.value?.uriKey)
      .slice(0, navigationPreferences.value.maxRecentItems)
  })

  const favoriteDashboards = computed(() => {
    return favorites.value.filter(dashboard =>
      availableDashboards.value.some(available => available.uriKey === dashboard.uriKey)
    )
  })

  const currentBreadcrumb = computed(() => {
    if (!currentDashboard.value) return null

    return {
      name: currentDashboard.value.name,
      dashboard: currentDashboard.value,
      isActive: true
    }
  })

  const breadcrumbs = computed(() => {
    const crumbs = []
    
    // Always start with Dashboard home
    crumbs.push({
      label: 'Dashboards',
      href: route('admin-panel.dashboard'),
      icon: 'HomeIcon',
      isHome: true
    })

    // Add current dashboard if not the main dashboard
    if (currentDashboard.value && currentDashboard.value.uriKey !== 'main') {
      crumbs.push({
        label: currentDashboard.value.name,
        href: null,
        icon: currentDashboard.value.icon || 'ChartBarIcon',
        isCurrent: true
      })
    }

    return crumbs
  })

  const quickSwitchOptions = computed(() => {
    return availableDashboards.value
      .filter(dashboard => dashboard.uriKey !== currentDashboard.value?.uriKey)
      .map(dashboard => ({
        ...dashboard,
        isFavorite: favorites.value.includes(dashboard.uriKey),
        isRecent: recentlyViewed.value.some(recent => recent.uriKey === dashboard.uriKey),
        lastViewed: getLastViewedTime(dashboard.uriKey)
      }))
      .sort((a, b) => {
        // Sort by: favorites first, then recent, then alphabetical
        if (a.isFavorite && !b.isFavorite) return -1
        if (!a.isFavorite && b.isFavorite) return 1
        if (a.isRecent && !b.isRecent) return -1
        if (!a.isRecent && b.isRecent) return 1
        return a.name.localeCompare(b.name)
      })
  })

  // Enhanced Actions
  const setCurrentDashboard = (dashboard) => {
    if (!dashboard) return

    // Store previous dashboard
    if (currentDashboard.value && currentDashboard.value.uriKey !== dashboard.uriKey) {
      previousDashboard.value = currentDashboard.value
    }

    const oldDashboard = currentDashboard.value
    currentDashboard.value = dashboard
    lastNavigationTime.value = new Date().toISOString()
    navigationError.value = null

    // Add to navigation history
    addToHistory(dashboard)

    // Add to recently viewed
    addToRecentlyViewed(dashboard)

    // Update breadcrumbs
    updateBreadcrumbs()

    // Track usage if enabled
    if (navigationPreferences.value.enableUsageTracking) {
      trackDashboardUsage(dashboard)
    }

    // Persist state if enabled
    if (navigationPreferences.value.persistState) {
      persistNavigationState()
    }

    // Emit navigation event
    window.dispatchEvent(new CustomEvent('dashboard-navigation', {
      detail: {
        from: oldDashboard,
        to: dashboard,
        timestamp: Date.now()
      }
    }))
  }

  const setAvailableDashboards = (dashboards) => {
    availableDashboards.value = dashboards || []
  }

  const addToHistory = (dashboard) => {
    if (!dashboard) return

    // Remove if already exists to avoid duplicates
    const existingIndex = navigationHistory.value.findIndex(
      item => item.uriKey === dashboard.uriKey
    )
    if (existingIndex !== -1) {
      navigationHistory.value.splice(existingIndex, 1)
    }

    // Add to end of history
    navigationHistory.value.push({
      ...dashboard,
      timestamp: Date.now(),
      url: getDashboardUrl(dashboard)
    })

    // Limit history size
    const maxItems = navigationPreferences.value.maxHistoryItems
    if (navigationHistory.value.length > maxItems) {
      navigationHistory.value = navigationHistory.value.slice(-maxItems)
    }
  }

  const addToRecentlyViewed = (dashboard) => {
    if (!dashboard) return

    // Remove if already exists
    const existingIndex = recentlyViewed.value.findIndex(
      item => item.uriKey === dashboard.uriKey
    )
    if (existingIndex !== -1) {
      recentlyViewed.value.splice(existingIndex, 1)
    }

    // Add to beginning of recent list
    recentlyViewed.value.unshift({
      ...dashboard,
      timestamp: Date.now(),
      url: getDashboardUrl(dashboard)
    })

    // Limit recent items
    const maxItems = navigationPreferences.value.maxRecentItems
    if (recentlyViewed.value.length > maxItems) {
      recentlyViewed.value = recentlyViewed.value.slice(0, maxItems)
    }
  }

  const toggleFavorite = (dashboardUriKey) => {
    const index = favorites.value.indexOf(dashboardUriKey)
    if (index === -1) {
      favorites.value.push(dashboardUriKey)
    } else {
      favorites.value.splice(index, 1)
    }

    if (navigationPreferences.value.persistState) {
      persistNavigationState()
    }
  }

  const navigateBack = () => {
    if (!canGoBack.value) return false

    const currentIndex = getCurrentHistoryIndex()
    if (currentIndex > 0) {
      const targetDashboard = navigationHistory.value[currentIndex - 1]
      navigateToDashboard(targetDashboard)
      return true
    }
    return false
  }

  const navigateForward = () => {
    if (!canGoForward.value) return false

    const currentIndex = getCurrentHistoryIndex()
    if (currentIndex < navigationHistory.value.length - 1) {
      const targetDashboard = navigationHistory.value[currentIndex + 1]
      navigateToDashboard(targetDashboard)
      return true
    }
    return false
  }

  const navigateToDashboard = (dashboard) => {
    if (!dashboard) return

    const url = getDashboardUrl(dashboard)
    window.location.href = url
  }

  const clearHistory = () => {
    navigationHistory.value = []
    if (navigationPreferences.value.persistState) {
      persistNavigationState()
    }
  }

  const clearRecentlyViewed = () => {
    recentlyViewed.value = []
    if (navigationPreferences.value.persistState) {
      persistNavigationState()
    }
  }

  const updatePreferences = (newPreferences) => {
    navigationPreferences.value = {
      ...navigationPreferences.value,
      ...newPreferences
    }

    if (navigationPreferences.value.persistState) {
      persistNavigationState()
    }
  }

  // Helper functions
  const getCurrentHistoryIndex = () => {
    if (!currentDashboard.value) return -1
    return navigationHistory.value.findIndex(
      item => item.uriKey === currentDashboard.value.uriKey
    )
  }

  const getLastViewedTime = (dashboardUriKey) => {
    const recent = recentlyViewed.value.find(item => item.uriKey === dashboardUriKey)
    return recent ? recent.timestamp : null
  }

  const getDashboardUrl = (dashboard) => {
    if (!dashboard) return '#'
    
    if (dashboard.uriKey === 'main') {
      return route('admin-panel.dashboard')
    }
    
    return route('admin-panel.dashboards.show', { uriKey: dashboard.uriKey })
  }

  const persistNavigationState = () => {
    try {
      const state = {
        favorites: favorites.value,
        recentlyViewed: recentlyViewed.value,
        navigationHistory: navigationHistory.value,
        preferences: navigationPreferences.value,
        timestamp: Date.now()
      }

      const storage = typeof window !== 'undefined' ? window.localStorage : global.window?.localStorage
      if (storage) {
        storage.setItem('admin-panel-dashboard-navigation', JSON.stringify(state))
      }
    } catch (error) {
      console.warn('Failed to persist dashboard navigation state:', error)
    }
  }

  const restoreNavigationState = () => {
    try {
      const storage = typeof window !== 'undefined' ? window.localStorage : global.window?.localStorage
      if (!storage) return

      const stored = storage.getItem('admin-panel-dashboard-navigation')
      if (!stored) return

      const state = JSON.parse(stored)

      // Check if state is not too old (7 days)
      const maxAge = 7 * 24 * 60 * 60 * 1000 // 7 days in milliseconds
      if (Date.now() - state.timestamp > maxAge) {
        storage.removeItem('admin-panel-dashboard-navigation')
        return
      }

      favorites.value = state.favorites || []
      recentlyViewed.value = state.recentlyViewed || []
      navigationHistory.value = state.navigationHistory || []

      if (state.preferences) {
        navigationPreferences.value = {
          ...navigationPreferences.value,
          ...state.preferences
        }
      }
    } catch (error) {
      console.warn('Failed to restore dashboard navigation state:', error)
      const storage = typeof window !== 'undefined' ? window.localStorage : global.window?.localStorage
      if (storage) {
        storage.removeItem('admin-panel-dashboard-navigation')
      }
    }
  }

  // Initialize store
  const initialize = (initialData = {}) => {
    if (initialData.currentDashboard) {
      currentDashboard.value = initialData.currentDashboard
    }
    
    if (initialData.availableDashboards) {
      setAvailableDashboards(initialData.availableDashboards)
    }

    // Restore persisted state
    if (navigationPreferences.value.persistState) {
      restoreNavigationState()
    }

    // Add current dashboard to history if provided
    if (currentDashboard.value) {
      addToHistory(currentDashboard.value)
    }

    // Initialize enhanced features
    hydrate()
  }

  // Enhanced navigation methods
  const navigateToDashboardAsync = async (dashboard, options = {}) => {
    if (isNavigating.value) return false

    try {
      isNavigating.value = true
      navigationError.value = null

      if (!dashboard || !dashboard.uriKey) {
        throw new Error('Invalid dashboard provided')
      }

      if (currentDashboard.value?.uriKey === dashboard.uriKey && !options.force) {
        return true
      }

      const url = getDashboardUrl(dashboard)

      if (options.replace) {
        await router.replace(url)
      } else {
        await router.visit(url, {
          preserveScroll: options.preserveScroll || navigationPreferences.value.preserveScrollPosition,
          preserveState: options.preserveState || false
        })
      }

      setCurrentDashboard(dashboard)
      return true
    } catch (error) {
      navigationError.value = error.message || 'Navigation failed'
      console.error('Dashboard navigation failed:', error)
      return false
    } finally {
      isNavigating.value = false
    }
  }

  const trackDashboardUsage = (dashboard) => {
    if (!navigationPreferences.value.enableUsageTracking) return

    const usageData = {
      dashboardUriKey: dashboard.uriKey,
      dashboardName: dashboard.name,
      category: dashboard.category,
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent
    }

    const usage = JSON.parse(localStorage.getItem('dashboard_usage') || '[]')
    usage.unshift(usageData)

    if (usage.length > 100) {
      usage.splice(100)
    }

    localStorage.setItem('dashboard_usage', JSON.stringify(usage))
  }

  const updateConfiguration = (config) => {
    currentConfiguration.value = { ...currentConfiguration.value, ...config }
    persistConfiguration()
  }

  const setGlobalConfiguration = (config) => {
    globalConfiguration.value = { ...globalConfiguration.value, ...config }
    persistGlobalConfiguration()
  }

  const updateUserPreferences = (prefs) => {
    userPreferences.value = { ...userPreferences.value, ...prefs }
    persistUserPreferences()
  }

  const persistConfiguration = () => {
    try {
      localStorage.setItem('dashboard_configuration', JSON.stringify(currentConfiguration.value))
    } catch (error) {
      console.warn('Failed to persist dashboard configuration:', error)
    }
  }

  const persistGlobalConfiguration = () => {
    try {
      localStorage.setItem('global_dashboard_configuration', JSON.stringify(globalConfiguration.value))
    } catch (error) {
      console.warn('Failed to persist global dashboard configuration:', error)
    }
  }

  const persistUserPreferences = () => {
    try {
      localStorage.setItem('dashboard_user_preferences', JSON.stringify(userPreferences.value))
    } catch (error) {
      console.warn('Failed to persist user preferences:', error)
    }
  }

  const hydrate = () => {
    hydrateConfiguration()
    hydrateUserPreferences()
  }

  const hydrateConfiguration = () => {
    try {
      const stored = localStorage.getItem('dashboard_configuration')
      if (stored) {
        currentConfiguration.value = JSON.parse(stored)
      }

      const globalStored = localStorage.getItem('global_dashboard_configuration')
      if (globalStored) {
        globalConfiguration.value = JSON.parse(globalStored)
      }
    } catch (error) {
      console.warn('Failed to hydrate dashboard configuration:', error)
    }
  }

  const hydrateUserPreferences = () => {
    try {
      const stored = localStorage.getItem('dashboard_user_preferences')
      if (stored) {
        userPreferences.value = JSON.parse(stored)
      }
    } catch (error) {
      console.warn('Failed to hydrate user preferences:', error)
    }
  }

  const clearAllData = () => {
    currentDashboard.value = null
    previousDashboard.value = null
    navigationHistory.value = []
    favorites.value = []
    recentlyViewed.value = []
    currentConfiguration.value = {}
    globalConfiguration.value = {}
    userPreferences.value = {}
    navigationError.value = null

    localStorage.removeItem('dashboard_configuration')
    localStorage.removeItem('global_dashboard_configuration')
    localStorage.removeItem('dashboard_user_preferences')
    localStorage.removeItem('dashboard_usage')
  }

  const reset = () => {
    clearAllData()
    navigationPreferences.value = {
      showBreadcrumbs: true,
      showQuickSwitcher: true,
      maxHistoryItems: 10,
      maxRecentItems: 5,
      enableKeyboardShortcuts: true,
      persistState: true,
      rememberLastDashboard: true,
      animationDuration: 300,
      preserveScrollPosition: false,
      autoRefreshInterval: 0,
      enableUsageTracking: true
    }
  }

  // Watch for changes and persist
  watch(currentConfiguration, persistConfiguration, { deep: true })
  watch(globalConfiguration, persistGlobalConfiguration, { deep: true })
  watch(userPreferences, persistUserPreferences, { deep: true })

  return {
    // Enhanced State
    currentDashboard,
    previousDashboard,
    availableDashboards,
    navigationHistory,
    favorites,
    recentlyViewed,
    navigationPreferences,
    isNavigating,
    navigationError,
    lastNavigationTime,
    currentConfiguration,
    globalConfiguration,
    userPreferences,

    // Enhanced Computed
    hasMultipleDashboards,
    canGoBack,
    canGoForward,
    nextDashboard,
    breadcrumbs,
    quickSwitchOptions,
    navigationState,
    recentDashboards,
    favoriteDashboards,
    currentBreadcrumb,

    // Enhanced Actions
    setCurrentDashboard,
    setAvailableDashboards,
    addToHistory,
    addToRecentlyViewed,
    toggleFavorite,
    navigateBack,
    navigateForward,
    navigateToDashboard,
    navigateToDashboardAsync,
    clearHistory,
    clearRecentlyViewed,
    updatePreferences,
    updateConfiguration,
    setGlobalConfiguration,
    updateUserPreferences,
    trackDashboardUsage,
    initialize,
    hydrate,
    clearAllData,
    reset,

    // Helper functions
    getDashboardUrl,
    persistNavigationState,
    restoreNavigationState,
    persistConfiguration,
    persistGlobalConfiguration,
    persistUserPreferences,
    hydrateConfiguration,
    hydrateUserPreferences
  }
})
