/**
 * Dashboard Stores Tests
 * 
 * Tests for the Pinia dashboard stores including navigation,
 * cache, and preferences management.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useDashboardNavigationStore } from '@/stores/dashboardNavigation'
import { useDashboardCacheStore } from '@/stores/dashboardCache'
import { useDashboardPreferencesStore } from '@/stores/dashboardPreferences'

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}
global.localStorage = localStorageMock

// Mock router
vi.mock('@inertiajs/vue3', () => ({
  router: {
    visit: vi.fn(),
    replace: vi.fn(),
  }
}))

describe('Dashboard Navigation Store', () => {
  let navigationStore

  beforeEach(() => {
    setActivePinia(createPinia())
    navigationStore = useDashboardNavigationStore()
    localStorageMock.getItem.mockClear()
    localStorageMock.setItem.mockClear()
  })

  it('initializes with default state', () => {
    expect(navigationStore.currentDashboard).toBeNull()
    expect(navigationStore.previousDashboard).toBeNull()
    expect(navigationStore.navigationHistory).toEqual([])
    expect(navigationStore.favorites).toEqual([])
    expect(navigationStore.isNavigating).toBe(false)
    expect(navigationStore.navigationError).toBeNull()
  })

  it('sets current dashboard correctly', () => {
    const dashboard = {
      uriKey: 'test-dashboard',
      name: 'Test Dashboard',
      category: 'Analytics'
    }

    navigationStore.setCurrentDashboard(dashboard)

    expect(navigationStore.currentDashboard).toEqual(dashboard)
    expect(navigationStore.lastNavigationTime).toBeTruthy()
    expect(navigationStore.navigationError).toBeNull()
  })

  it('tracks previous dashboard when switching', () => {
    const dashboard1 = {
      uriKey: 'dashboard-1',
      name: 'Dashboard 1'
    }
    const dashboard2 = {
      uriKey: 'dashboard-2',
      name: 'Dashboard 2'
    }

    navigationStore.setCurrentDashboard(dashboard1)
    navigationStore.setCurrentDashboard(dashboard2)

    expect(navigationStore.currentDashboard).toEqual(dashboard2)
    expect(navigationStore.previousDashboard).toEqual(dashboard1)
  })

  it('manages navigation history correctly', () => {
    const dashboard1 = { uriKey: 'dash-1', name: 'Dashboard 1' }
    const dashboard2 = { uriKey: 'dash-2', name: 'Dashboard 2' }

    navigationStore.setCurrentDashboard(dashboard1)
    navigationStore.setCurrentDashboard(dashboard2)

    expect(navigationStore.navigationHistory.length).toBeGreaterThan(0)
    expect(navigationStore.canGoBack).toBe(true)
  })

  it('computes navigation state correctly', () => {
    const dashboard = {
      uriKey: 'test',
      name: 'Test Dashboard'
    }

    navigationStore.setCurrentDashboard(dashboard)

    const state = navigationStore.navigationState
    expect(state.current).toEqual(dashboard)
    expect(state.isNavigating).toBe(false)
    expect(state.error).toBeNull()
    expect(state.lastNavigationTime).toBeTruthy()
  })

  it('manages favorites correctly', () => {
    const dashboard = {
      uriKey: 'favorite-dash',
      name: 'Favorite Dashboard'
    }

    navigationStore.toggleFavorite(dashboard)
    expect(navigationStore.favorites).toContainEqual(dashboard)

    navigationStore.toggleFavorite(dashboard)
    expect(navigationStore.favorites).not.toContainEqual(dashboard)
  })

  it('updates preferences correctly', () => {
    const newPreferences = {
      maxHistoryItems: 15,
      enableKeyboardShortcuts: false
    }

    navigationStore.updatePreferences(newPreferences)

    expect(navigationStore.navigationPreferences.maxHistoryItems).toBe(15)
    expect(navigationStore.navigationPreferences.enableKeyboardShortcuts).toBe(false)
  })

  it('tracks dashboard usage when enabled', () => {
    navigationStore.navigationPreferences.enableUsageTracking = true
    
    const dashboard = {
      uriKey: 'analytics',
      name: 'Analytics Dashboard',
      category: 'Analytics'
    }

    navigationStore.setCurrentDashboard(dashboard)

    expect(localStorageMock.setItem).toHaveBeenCalledWith(
      'dashboard_usage',
      expect.stringContaining('analytics')
    )
  })
})

describe('Dashboard Cache Store', () => {
  let cacheStore

  beforeEach(() => {
    setActivePinia(createPinia())
    cacheStore = useDashboardCacheStore()
    localStorageMock.getItem.mockClear()
    localStorageMock.setItem.mockClear()
  })

  it('initializes with empty cache', () => {
    expect(cacheStore.cacheSize).toBe(0)
    expect(cacheStore.cacheHitRate).toBe(0)
  })

  it('caches dashboard data correctly', () => {
    const dashboardData = {
      name: 'Test Dashboard',
      cards: ['card1', 'card2']
    }

    cacheStore.setCachedDashboard('test-dash', dashboardData)

    expect(cacheStore.cacheSize).toBe(1)
    expect(cacheStore.hasCachedDashboard('test-dash')).toBe(true)
  })

  it('retrieves cached dashboard data', () => {
    const dashboardData = {
      name: 'Test Dashboard',
      cards: ['card1', 'card2']
    }

    cacheStore.setCachedDashboard('test-dash', dashboardData)
    const retrieved = cacheStore.getCachedDashboard('test-dash')

    expect(retrieved.data).toEqual(dashboardData)
  })

  it('handles cache misses correctly', () => {
    const result = cacheStore.getCachedDashboard('non-existent')
    expect(result).toBeNull()
  })

  it('manages cache expiration', () => {
    const dashboardData = { name: 'Test Dashboard' }
    const shortTTL = 100 // 100ms

    cacheStore.setCachedDashboard('test-dash', dashboardData, shortTTL)

    // Should be cached initially
    expect(cacheStore.hasCachedDashboard('test-dash')).toBe(true)

    // Wait for expiration
    setTimeout(() => {
      expect(cacheStore.hasCachedDashboard('test-dash')).toBe(false)
    }, 150)
  })

  it('evicts oldest entries when cache is full', () => {
    // Set small cache size
    cacheStore.updateCacheConfig({ maxCacheSize: 2 })

    cacheStore.setCachedDashboard('dash-1', { name: 'Dashboard 1' })
    cacheStore.setCachedDashboard('dash-2', { name: 'Dashboard 2' })
    cacheStore.setCachedDashboard('dash-3', { name: 'Dashboard 3' })

    expect(cacheStore.cacheSize).toBe(2)
    expect(cacheStore.hasCachedDashboard('dash-1')).toBe(false) // Evicted
    expect(cacheStore.hasCachedDashboard('dash-3')).toBe(true) // Latest
  })

  it('tracks cache statistics', () => {
    const dashboardData = { name: 'Test Dashboard' }

    cacheStore.setCachedDashboard('test-dash', dashboardData)
    
    // Cache hit
    cacheStore.getCachedDashboard('test-dash')
    
    // Cache miss
    cacheStore.getCachedDashboard('non-existent')

    const stats = cacheStore.cacheStats
    expect(stats.totalHits).toBe(1)
    expect(stats.totalMisses).toBe(1)
    expect(stats.hitRate).toBe(50)
  })

  it('manages loading states', () => {
    expect(cacheStore.isLoadingDashboard('test-dash')).toBe(false)

    cacheStore.setLoading('test-dash', true)
    expect(cacheStore.isLoadingDashboard('test-dash')).toBe(true)

    cacheStore.setLoading('test-dash', false)
    expect(cacheStore.isLoadingDashboard('test-dash')).toBe(false)
  })

  it('manages error states', () => {
    const error = new Error('Test error')

    cacheStore.setError('test-dash', error)
    const retrievedError = cacheStore.getError('test-dash')

    expect(retrievedError.message).toBe('Test error')
    expect(retrievedError.timestamp).toBeTruthy()
  })
})

describe('Dashboard Preferences Store', () => {
  let preferencesStore

  beforeEach(() => {
    setActivePinia(createPinia())
    preferencesStore = useDashboardPreferencesStore()
    localStorageMock.getItem.mockClear()
    localStorageMock.setItem.mockClear()
  })

  it('initializes with default preferences', () => {
    expect(preferencesStore.preferences.display.layout).toBe('grid')
    expect(preferencesStore.preferences.theme.mode).toBe('auto')
    expect(preferencesStore.preferences.behavior.autoRefresh).toBe(false)
    expect(preferencesStore.preferences.accessibility.highContrast).toBe(false)
  })

  it('updates display preferences', () => {
    preferencesStore.updateDisplayPreferences({
      layout: 'list',
      columns: 2,
      compactMode: true
    })

    expect(preferencesStore.preferences.display.layout).toBe('list')
    expect(preferencesStore.preferences.display.columns).toBe(2)
    expect(preferencesStore.preferences.display.compactMode).toBe(true)
  })

  it('updates theme preferences and applies them', () => {
    const applySpy = vi.spyOn(preferencesStore, 'applyTheme')

    preferencesStore.updateThemePreferences({
      mode: 'dark',
      primaryColor: '#FF0000'
    })

    expect(preferencesStore.preferences.theme.mode).toBe('dark')
    expect(preferencesStore.preferences.theme.primaryColor).toBe('#FF0000')
    expect(applySpy).toHaveBeenCalled()
  })

  it('computes current theme correctly', () => {
    // Test auto mode
    preferencesStore.preferences.theme.mode = 'auto'
    expect(['light', 'dark']).toContain(preferencesStore.currentTheme)

    // Test explicit mode
    preferencesStore.preferences.theme.mode = 'dark'
    expect(preferencesStore.currentTheme).toBe('dark')
  })

  it('handles accessibility preferences', () => {
    preferencesStore.updateAccessibilityPreferences({
      highContrast: true,
      largeText: true,
      reduceMotion: true
    })

    expect(preferencesStore.preferences.accessibility.highContrast).toBe(true)
    expect(preferencesStore.preferences.accessibility.largeText).toBe(true)
    expect(preferencesStore.preferences.accessibility.reduceMotion).toBe(true)
  })

  it('computes effective animation duration based on reduce motion', () => {
    // Normal animation
    preferencesStore.preferences.accessibility.reduceMotion = false
    preferencesStore.preferences.behavior.animationDuration = 300
    expect(preferencesStore.effectiveAnimationDuration).toBe(300)

    // Reduced motion
    preferencesStore.preferences.accessibility.reduceMotion = true
    expect(preferencesStore.effectiveAnimationDuration).toBe(0)
  })

  it('tracks dirty state correctly', () => {
    expect(preferencesStore.hasUnsavedChanges).toBe(false)

    preferencesStore.updateDisplayPreferences({ layout: 'list' })
    expect(preferencesStore.hasUnsavedChanges).toBe(true)
  })

  it('exports and imports preferences correctly', () => {
    preferencesStore.updateDisplayPreferences({ layout: 'list' })
    
    const exported = preferencesStore.exportPreferences()
    expect(exported.preferences.display.layout).toBe('list')
    expect(exported.exportedAt).toBeTruthy()
    expect(exported.version).toBeTruthy()

    // Reset and import
    preferencesStore.resetPreferences()
    expect(preferencesStore.preferences.display.layout).toBe('grid')

    const imported = preferencesStore.importPreferences(exported.preferences)
    expect(imported).toBe(true)
    expect(preferencesStore.preferences.display.layout).toBe('list')
  })

  it('resets preferences correctly', () => {
    preferencesStore.updateDisplayPreferences({ layout: 'list' })
    preferencesStore.updateThemePreferences({ mode: 'dark' })

    preferencesStore.resetPreferences()

    expect(preferencesStore.preferences.display.layout).toBe('grid')
    expect(preferencesStore.preferences.theme.mode).toBe('auto')
  })

  it('resets specific preference sections', () => {
    preferencesStore.updateDisplayPreferences({ layout: 'list' })
    preferencesStore.updateThemePreferences({ mode: 'dark' })

    preferencesStore.resetPreferences('display')

    expect(preferencesStore.preferences.display.layout).toBe('grid')
    expect(preferencesStore.preferences.theme.mode).toBe('dark') // Unchanged
  })

  it('validates preferences on import', () => {
    const invalidPreferences = {
      display: {
        layout: 'invalid-layout',
        columns: 'not-a-number'
      },
      unknownSection: {
        someValue: true
      }
    }

    const result = preferencesStore.importPreferences(invalidPreferences)
    expect(result).toBe(true) // Should still import valid parts

    // Invalid values should be ignored, defaults used
    expect(preferencesStore.preferences.display.layout).toBe('grid')
    expect(preferencesStore.preferences.display.columns).toBe(3)
    expect(preferencesStore.preferences.unknownSection).toBeUndefined()
  })
})
