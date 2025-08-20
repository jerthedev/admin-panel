/**
 * Dashboard Store Integration Examples
 * 
 * This file demonstrates how to use the dashboard stores together
 * for comprehensive state management in dashboard applications.
 */

import { useDashboardNavigationStore } from '@/stores/dashboardNavigation'
import { useDashboardCacheStore } from '@/stores/dashboardCache'
import { useDashboardPreferencesStore } from '@/stores/dashboardPreferences'

// Example 1: Complete Dashboard Navigation with Caching
export class DashboardNavigationService {
  constructor() {
    this.navigationStore = useDashboardNavigationStore()
    this.cacheStore = useDashboardCacheStore()
    this.preferencesStore = useDashboardPreferencesStore()
  }

  async navigateToDashboard(dashboard, options = {}) {
    try {
      // Set loading state
      this.cacheStore.setLoading(dashboard.uriKey, true)
      this.cacheStore.setError(dashboard.uriKey, null)

      // Check cache first
      let dashboardData = this.cacheStore.getCachedDashboard(dashboard.uriKey)
      
      if (!dashboardData) {
        // Load dashboard data from API
        dashboardData = await this.loadDashboardData(dashboard.uriKey)
        
        // Cache the data
        this.cacheStore.setCachedDashboard(dashboard.uriKey, dashboardData)
      }

      // Update navigation state
      this.navigationStore.setCurrentDashboard({
        ...dashboard,
        ...dashboardData
      })

      // Apply user preferences
      this.applyDashboardPreferences(dashboard.uriKey)

      return true
    } catch (error) {
      this.cacheStore.setError(dashboard.uriKey, error)
      this.navigationStore.navigationError = error.message
      return false
    } finally {
      this.cacheStore.setLoading(dashboard.uriKey, false)
    }
  }

  async loadDashboardData(dashboardUriKey) {
    // Simulate API call
    const response = await fetch(`/api/dashboards/${dashboardUriKey}`)
    if (!response.ok) {
      throw new Error(`Failed to load dashboard: ${response.statusText}`)
    }
    return await response.json()
  }

  applyDashboardPreferences(dashboardUriKey) {
    const preferences = this.preferencesStore.preferences
    
    // Apply display preferences
    const displayConfig = {
      layout: preferences.display.layout,
      columns: preferences.display.columns,
      cardSize: preferences.display.cardSize,
      showDescriptions: preferences.display.showDescriptions,
    }

    // Apply behavior preferences
    const behaviorConfig = {
      autoRefresh: preferences.behavior.autoRefresh,
      refreshInterval: preferences.behavior.refreshInterval,
      enableAnimations: preferences.behavior.enableAnimations,
    }

    // Update dashboard configuration
    this.navigationStore.updateConfiguration({
      display: displayConfig,
      behavior: behaviorConfig,
    })
  }
}

// Example 2: Smart Dashboard Preloading
export class DashboardPreloadingService {
  constructor() {
    this.navigationStore = useDashboardNavigationStore()
    this.cacheStore = useDashboardCacheStore()
    this.preferencesStore = useDashboardPreferencesStore()
  }

  async preloadDashboards() {
    const preferences = this.preferencesStore.preferences
    
    if (!preferences.behavior.preloadData) {
      return
    }

    // Preload favorite dashboards
    const favorites = this.navigationStore.favoriteDashboards
    await this.preloadDashboardList(favorites, 'favorite')

    // Preload recent dashboards
    const recent = this.navigationStore.recentDashboards
    await this.preloadDashboardList(recent, 'recent')

    // Preload dashboards in favorite categories
    const favoriteCategories = preferences.personalization.favoriteCategories
    if (favoriteCategories.length > 0) {
      const categoryDashboards = this.navigationStore.availableDashboards
        .filter(dashboard => favoriteCategories.includes(dashboard.category))
      await this.preloadDashboardList(categoryDashboards, 'category')
    }
  }

  async preloadDashboardList(dashboards, type) {
    const maxPreload = type === 'favorite' ? 5 : 3
    const dashboardsToPreload = dashboards.slice(0, maxPreload)

    const preloadPromises = dashboardsToPreload.map(async (dashboard) => {
      // Skip if already cached
      if (this.cacheStore.hasCachedDashboard(dashboard.uriKey)) {
        return
      }

      try {
        const data = await this.loadDashboardData(dashboard.uriKey)
        this.cacheStore.setCachedDashboard(dashboard.uriKey, data)
      } catch (error) {
        console.warn(`Failed to preload dashboard ${dashboard.uriKey}:`, error)
      }
    })

    await Promise.allSettled(preloadPromises)
  }

  async loadDashboardData(dashboardUriKey) {
    const response = await fetch(`/api/dashboards/${dashboardUriKey}`)
    if (!response.ok) {
      throw new Error(`Failed to load dashboard: ${response.statusText}`)
    }
    return await response.json()
  }
}

// Example 3: Dashboard Analytics and Usage Tracking
export class DashboardAnalyticsService {
  constructor() {
    this.navigationStore = useDashboardNavigationStore()
    this.cacheStore = useDashboardCacheStore()
    this.preferencesStore = useDashboardPreferencesStore()
  }

  trackDashboardView(dashboard) {
    if (!this.preferencesStore.preferences.advanced.analyticsTracking) {
      return
    }

    // Track in navigation store
    this.navigationStore.trackDashboardUsage(dashboard)

    // Send to analytics service
    this.sendAnalyticsEvent('dashboard_view', {
      dashboardUriKey: dashboard.uriKey,
      dashboardName: dashboard.name,
      category: dashboard.category,
      timestamp: new Date().toISOString(),
      userPreferences: this.getUserAnalyticsData(),
      cacheHit: this.cacheStore.hasCachedDashboard(dashboard.uriKey),
    })
  }

  trackDashboardInteraction(dashboard, interaction) {
    if (!this.preferencesStore.preferences.advanced.analyticsTracking) {
      return
    }

    this.sendAnalyticsEvent('dashboard_interaction', {
      dashboardUriKey: dashboard.uriKey,
      interaction: interaction.type,
      element: interaction.element,
      timestamp: new Date().toISOString(),
    })
  }

  getUserAnalyticsData() {
    const preferences = this.preferencesStore.preferences
    
    return {
      theme: preferences.theme.mode,
      layout: preferences.display.layout,
      accessibility: {
        highContrast: preferences.accessibility.highContrast,
        largeText: preferences.accessibility.largeText,
        reduceMotion: preferences.accessibility.reduceMotion,
      },
      behavior: {
        autoRefresh: preferences.behavior.autoRefresh,
        enableAnimations: preferences.behavior.enableAnimations,
      },
    }
  }

  async sendAnalyticsEvent(eventType, data) {
    try {
      await fetch('/api/analytics/events', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          type: eventType,
          data,
          timestamp: new Date().toISOString(),
        }),
      })
    } catch (error) {
      console.warn('Failed to send analytics event:', error)
    }
  }

  generateUsageReport() {
    const navigationStore = this.navigationStore
    const cacheStore = this.cacheStore
    
    return {
      navigation: {
        totalDashboards: navigationStore.availableDashboards.length,
        favoriteDashboards: navigationStore.favorites.length,
        historySize: navigationStore.navigationHistory.length,
        currentDashboard: navigationStore.currentDashboard?.name,
      },
      cache: {
        cacheSize: cacheStore.cacheSize,
        hitRate: cacheStore.cacheHitRate,
        stats: cacheStore.cacheStats,
      },
      preferences: {
        theme: this.preferencesStore.currentTheme,
        layout: this.preferencesStore.preferences.display.layout,
        accessibility: this.preferencesStore.accessibilitySettings,
      },
    }
  }
}

// Example 4: Dashboard State Synchronization
export class DashboardSyncService {
  constructor() {
    this.navigationStore = useDashboardNavigationStore()
    this.cacheStore = useDashboardCacheStore()
    this.preferencesStore = useDashboardPreferencesStore()
    this.syncInterval = null
  }

  startSync() {
    // Sync every 5 minutes
    this.syncInterval = setInterval(() => {
      this.syncToServer()
    }, 5 * 60 * 1000)

    // Sync on page visibility change
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        this.syncFromServer()
      }
    })

    // Initial sync
    this.syncFromServer()
  }

  stopSync() {
    if (this.syncInterval) {
      clearInterval(this.syncInterval)
      this.syncInterval = null
    }
  }

  async syncToServer() {
    try {
      const syncData = {
        navigation: {
          favorites: this.navigationStore.favorites,
          recentlyViewed: this.navigationStore.recentlyViewed,
          preferences: this.navigationStore.navigationPreferences,
        },
        preferences: this.preferencesStore.exportPreferences(),
        timestamp: new Date().toISOString(),
      }

      await fetch('/api/user/dashboard-state', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(syncData),
      })
    } catch (error) {
      console.warn('Failed to sync to server:', error)
    }
  }

  async syncFromServer() {
    try {
      const response = await fetch('/api/user/dashboard-state')
      if (!response.ok) return

      const serverData = await response.json()

      // Update navigation state
      if (serverData.navigation) {
        this.navigationStore.favorites = serverData.navigation.favorites || []
        this.navigationStore.recentlyViewed = serverData.navigation.recentlyViewed || []
        this.navigationStore.updatePreferences(serverData.navigation.preferences || {})
      }

      // Update preferences
      if (serverData.preferences) {
        this.preferencesStore.importPreferences(serverData.preferences.preferences)
      }
    } catch (error) {
      console.warn('Failed to sync from server:', error)
    }
  }
}

// Example 5: Complete Dashboard Application Setup
export function setupDashboardStores() {
  const navigationStore = useDashboardNavigationStore()
  const cacheStore = useDashboardCacheStore()
  const preferencesStore = useDashboardPreferencesStore()

  // Initialize stores
  navigationStore.initialize()
  cacheStore.initialize()
  preferencesStore.initialize()

  // Setup services
  const navigationService = new DashboardNavigationService()
  const preloadingService = new DashboardPreloadingService()
  const analyticsService = new DashboardAnalyticsService()
  const syncService = new DashboardSyncService()

  // Start services
  syncService.startSync()
  preloadingService.preloadDashboards()

  // Setup event listeners
  window.addEventListener('beforeunload', () => {
    // Save state before page unload
    preferencesStore.savePreferences()
    syncService.syncToServer()
  })

  // Return services for use in components
  return {
    navigationStore,
    cacheStore,
    preferencesStore,
    navigationService,
    preloadingService,
    analyticsService,
    syncService,
  }
}

// Example usage in a Vue component
export const useDashboardServices = () => {
  const services = setupDashboardStores()

  const navigateTo = async (dashboard) => {
    const success = await services.navigationService.navigateToDashboard(dashboard)
    if (success) {
      services.analyticsService.trackDashboardView(dashboard)
    }
    return success
  }

  const updatePreferences = (section, updates) => {
    services.preferencesStore.updatePreferences(section, updates)
    services.analyticsService.trackDashboardInteraction(
      services.navigationStore.currentDashboard,
      { type: 'preferences_update', element: section }
    )
  }

  const getCachedData = (dashboardUriKey) => {
    return services.cacheStore.getCachedDashboard(dashboardUriKey)
  }

  return {
    ...services,
    navigateTo,
    updatePreferences,
    getCachedData,
  }
}
