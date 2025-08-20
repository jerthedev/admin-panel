/**
 * Dashboard Preferences Store
 * 
 * Manages user-specific dashboard preferences including layout,
 * display options, accessibility settings, and personalization.
 */

import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'

export const useDashboardPreferencesStore = defineStore('dashboardPreferences', () => {
  // State
  const preferences = ref({
    // Display preferences
    display: {
      layout: 'grid', // 'grid', 'list', 'cards'
      columns: 3,
      cardSize: 'medium', // 'small', 'medium', 'large'
      showDescriptions: true,
      showIcons: true,
      showCategories: true,
      showMetadata: true,
      showStatus: true,
      compactMode: false,
      density: 'comfortable', // 'compact', 'comfortable', 'spacious'
    },

    // Theme preferences
    theme: {
      mode: 'auto', // 'light', 'dark', 'auto'
      primaryColor: '#3B82F6',
      accentColor: '#10B981',
      borderRadius: 'medium', // 'none', 'small', 'medium', 'large'
      fontFamily: 'system', // 'system', 'inter', 'roboto'
      fontSize: 'medium', // 'small', 'medium', 'large'
    },

    // Behavior preferences
    behavior: {
      autoRefresh: false,
      refreshInterval: 300, // seconds
      lazyLoading: true,
      preloadData: false,
      cacheData: true,
      cacheTTL: 600, // seconds
      enableAnimations: true,
      animationDuration: 300, // milliseconds
      enableSounds: false,
    },

    // Navigation preferences
    navigation: {
      showBreadcrumbs: true,
      showBackButton: true,
      enableKeyboardShortcuts: true,
      enableQuickSwitcher: true,
      rememberLastDashboard: true,
      showNavigationHistory: true,
      maxHistoryItems: 10,
      enableGestures: false, // Touch gestures on mobile
    },

    // Accessibility preferences
    accessibility: {
      highContrast: false,
      largeText: false,
      reduceMotion: false,
      screenReaderOptimized: false,
      keyboardNavigation: true,
      focusIndicators: true,
      announceChanges: true,
    },

    // Personalization
    personalization: {
      favoriteCategories: [],
      hiddenCategories: [],
      customDashboardOrder: [],
      pinnedDashboards: [],
      recentDashboardsCount: 5,
      showWelcomeMessage: true,
      showTips: true,
      showBadges: true,
    },

    // Advanced preferences
    advanced: {
      debugMode: false,
      performanceMonitoring: false,
      errorReporting: true,
      analyticsTracking: true,
      experimentalFeatures: false,
      developerMode: false,
    },
  })

  const userSettings = ref({
    userId: null,
    lastUpdated: null,
    syncEnabled: true,
    backupEnabled: true,
    version: '1.0.0',
  })

  const preferencesHistory = ref([])
  const isDirty = ref(false)
  const isSaving = ref(false)
  const lastSaved = ref(null)

  // Computed properties
  const currentTheme = computed(() => {
    const mode = preferences.value.theme.mode
    if (mode === 'auto') {
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }
    return mode
  })

  const isHighContrast = computed(() => {
    return preferences.value.accessibility.highContrast ||
           window.matchMedia('(prefers-contrast: high)').matches
  })

  const shouldReduceMotion = computed(() => {
    return preferences.value.accessibility.reduceMotion ||
           window.matchMedia('(prefers-reduced-motion: reduce)').matches
  })

  const effectiveAnimationDuration = computed(() => {
    if (shouldReduceMotion.value) return 0
    return preferences.value.behavior.animationDuration
  })

  const displaySettings = computed(() => ({
    ...preferences.value.display,
    theme: currentTheme.value,
    highContrast: isHighContrast.value,
    reduceMotion: shouldReduceMotion.value,
    animationDuration: effectiveAnimationDuration.value,
  }))

  const accessibilitySettings = computed(() => ({
    ...preferences.value.accessibility,
    highContrast: isHighContrast.value,
    reduceMotion: shouldReduceMotion.value,
  }))

  const hasUnsavedChanges = computed(() => isDirty.value)

  // Actions
  const updatePreferences = (section, updates) => {
    if (!preferences.value[section]) {
      console.warn(`Unknown preferences section: ${section}`)
      return
    }

    const oldValue = { ...preferences.value[section] }
    preferences.value[section] = { ...preferences.value[section], ...updates }
    
    // Track change
    addToHistory(section, oldValue, preferences.value[section])
    markDirty()
  }

  const updateDisplayPreferences = (updates) => {
    updatePreferences('display', updates)
  }

  const updateThemePreferences = (updates) => {
    updatePreferences('theme', updates)
    applyTheme()
  }

  const updateBehaviorPreferences = (updates) => {
    updatePreferences('behavior', updates)
  }

  const updateNavigationPreferences = (updates) => {
    updatePreferences('navigation', updates)
  }

  const updateAccessibilityPreferences = (updates) => {
    updatePreferences('accessibility', updates)
    applyAccessibilitySettings()
  }

  const updatePersonalizationPreferences = (updates) => {
    updatePreferences('personalization', updates)
  }

  const updateAdvancedPreferences = (updates) => {
    updatePreferences('advanced', updates)
  }

  const resetPreferences = (section = null) => {
    if (section) {
      // Reset specific section
      const defaultPrefs = getDefaultPreferences()
      if (defaultPrefs[section]) {
        preferences.value[section] = { ...defaultPrefs[section] }
        markDirty()
      }
    } else {
      // Reset all preferences
      preferences.value = getDefaultPreferences()
      markDirty()
    }
  }

  const importPreferences = (importedPreferences) => {
    try {
      // Validate imported preferences
      const validated = validatePreferences(importedPreferences)
      preferences.value = validated
      markDirty()
      return true
    } catch (error) {
      console.error('Failed to import preferences:', error)
      return false
    }
  }

  const exportPreferences = () => {
    return {
      preferences: preferences.value,
      userSettings: userSettings.value,
      exportedAt: new Date().toISOString(),
      version: userSettings.value.version,
    }
  }

  const savePreferences = async () => {
    if (isSaving.value) return

    try {
      isSaving.value = true
      
      // Save to localStorage
      localStorage.setItem('dashboard_preferences', JSON.stringify(preferences.value))
      localStorage.setItem('dashboard_user_settings', JSON.stringify(userSettings.value))
      
      // Save to server if sync is enabled
      if (userSettings.value.syncEnabled && userSettings.value.userId) {
        await syncPreferencesToServer()
      }

      lastSaved.value = new Date().toISOString()
      userSettings.value.lastUpdated = lastSaved.value
      isDirty.value = false

      return true
    } catch (error) {
      console.error('Failed to save preferences:', error)
      return false
    } finally {
      isSaving.value = false
    }
  }

  const loadPreferences = async () => {
    try {
      // Load from localStorage
      const storedPrefs = localStorage.getItem('dashboard_preferences')
      const storedSettings = localStorage.getItem('dashboard_user_settings')

      if (storedPrefs) {
        const parsed = JSON.parse(storedPrefs)
        preferences.value = validatePreferences(parsed)
      }

      if (storedSettings) {
        userSettings.value = { ...userSettings.value, ...JSON.parse(storedSettings) }
      }

      // Load from server if sync is enabled
      if (userSettings.value.syncEnabled && userSettings.value.userId) {
        await syncPreferencesFromServer()
      }

      applyTheme()
      applyAccessibilitySettings()

      return true
    } catch (error) {
      console.error('Failed to load preferences:', error)
      return false
    }
  }

  // Helper functions
  const markDirty = () => {
    isDirty.value = true
  }

  const addToHistory = (section, oldValue, newValue) => {
    preferencesHistory.value.unshift({
      section,
      oldValue,
      newValue,
      timestamp: new Date().toISOString(),
    })

    // Keep only last 50 changes
    if (preferencesHistory.value.length > 50) {
      preferencesHistory.value = preferencesHistory.value.slice(0, 50)
    }
  }

  const getDefaultPreferences = () => ({
    display: {
      layout: 'grid',
      columns: 3,
      cardSize: 'medium',
      showDescriptions: true,
      showIcons: true,
      showCategories: true,
      showMetadata: true,
      showStatus: true,
      compactMode: false,
      density: 'comfortable',
    },
    theme: {
      mode: 'auto',
      primaryColor: '#3B82F6',
      accentColor: '#10B981',
      borderRadius: 'medium',
      fontFamily: 'system',
      fontSize: 'medium',
    },
    behavior: {
      autoRefresh: false,
      refreshInterval: 300,
      lazyLoading: true,
      preloadData: false,
      cacheData: true,
      cacheTTL: 600,
      enableAnimations: true,
      animationDuration: 300,
      enableSounds: false,
    },
    navigation: {
      showBreadcrumbs: true,
      showBackButton: true,
      enableKeyboardShortcuts: true,
      enableQuickSwitcher: true,
      rememberLastDashboard: true,
      showNavigationHistory: true,
      maxHistoryItems: 10,
      enableGestures: false,
    },
    accessibility: {
      highContrast: false,
      largeText: false,
      reduceMotion: false,
      screenReaderOptimized: false,
      keyboardNavigation: true,
      focusIndicators: true,
      announceChanges: true,
    },
    personalization: {
      favoriteCategories: [],
      hiddenCategories: [],
      customDashboardOrder: [],
      pinnedDashboards: [],
      recentDashboardsCount: 5,
      showWelcomeMessage: true,
      showTips: true,
      showBadges: true,
    },
    advanced: {
      debugMode: false,
      performanceMonitoring: false,
      errorReporting: true,
      analyticsTracking: true,
      experimentalFeatures: false,
      developerMode: false,
    },
  })

  const validatePreferences = (prefs) => {
    const defaults = getDefaultPreferences()
    const validated = {}

    // Validate each section
    Object.keys(defaults).forEach(section => {
      validated[section] = { ...defaults[section] }
      
      if (prefs[section] && typeof prefs[section] === 'object') {
        Object.keys(defaults[section]).forEach(key => {
          if (prefs[section][key] !== undefined) {
            validated[section][key] = prefs[section][key]
          }
        })
      }
    })

    return validated
  }

  const applyTheme = () => {
    const theme = currentTheme.value
    const root = document.documentElement

    // Apply theme class
    root.classList.remove('light', 'dark')
    root.classList.add(theme)

    // Apply custom colors
    root.style.setProperty('--primary-color', preferences.value.theme.primaryColor)
    root.style.setProperty('--accent-color', preferences.value.theme.accentColor)

    // Apply border radius
    const radiusMap = {
      none: '0px',
      small: '4px',
      medium: '8px',
      large: '12px',
    }
    root.style.setProperty('--border-radius', radiusMap[preferences.value.theme.borderRadius] || '8px')

    // Apply font settings
    if (preferences.value.theme.fontFamily !== 'system') {
      root.style.setProperty('--font-family', preferences.value.theme.fontFamily)
    }

    const fontSizeMap = {
      small: '14px',
      medium: '16px',
      large: '18px',
    }
    root.style.setProperty('--font-size', fontSizeMap[preferences.value.theme.fontSize] || '16px')
  }

  const applyAccessibilitySettings = () => {
    const root = document.documentElement

    // Apply accessibility classes
    root.classList.toggle('high-contrast', isHighContrast.value)
    root.classList.toggle('large-text', preferences.value.accessibility.largeText)
    root.classList.toggle('reduce-motion', shouldReduceMotion.value)
    root.classList.toggle('screen-reader-optimized', preferences.value.accessibility.screenReaderOptimized)
  }

  const syncPreferencesToServer = async () => {
    // This would sync preferences to the server
    // Implementation depends on your backend API
    console.log('Syncing preferences to server...')
  }

  const syncPreferencesFromServer = async () => {
    // This would load preferences from the server
    // Implementation depends on your backend API
    console.log('Loading preferences from server...')
  }

  // Initialize preferences
  const initialize = async () => {
    await loadPreferences()
    
    // Watch for system theme changes
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    mediaQuery.addEventListener('change', () => {
      if (preferences.value.theme.mode === 'auto') {
        applyTheme()
      }
    })

    // Auto-save preferences when they change
    watch(preferences, () => {
      markDirty()
      // Debounced auto-save
      setTimeout(() => {
        if (isDirty.value) {
          savePreferences()
        }
      }, 1000)
    }, { deep: true })
  }

  // Initialize on store creation
  initialize()

  return {
    // State
    preferences,
    userSettings,
    preferencesHistory,
    isDirty,
    isSaving,
    lastSaved,

    // Computed
    currentTheme,
    isHighContrast,
    shouldReduceMotion,
    effectiveAnimationDuration,
    displaySettings,
    accessibilitySettings,
    hasUnsavedChanges,

    // Actions
    updatePreferences,
    updateDisplayPreferences,
    updateThemePreferences,
    updateBehaviorPreferences,
    updateNavigationPreferences,
    updateAccessibilityPreferences,
    updatePersonalizationPreferences,
    updateAdvancedPreferences,
    resetPreferences,
    importPreferences,
    exportPreferences,
    savePreferences,
    loadPreferences,

    // Utilities
    initialize,
    applyTheme,
    applyAccessibilitySettings,
    getDefaultPreferences,
    validatePreferences
  }
})
