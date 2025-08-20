/**
 * Dashboard Navigation Store Tests
 * 
 * Tests for the dashboard navigation store including state management,
 * history tracking, favorites, and navigation preferences.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useDashboardNavigationStore } from '../../../resources/js/stores/dashboardNavigation'

// Mock global functions
global.route = vi.fn((name, params) => {
  if (name === 'admin-panel.dashboard') return '/admin'
  if (name === 'admin-panel.dashboards.show') return `/admin/dashboards/${params.uriKey}`
  return '/admin'
})

global.window = {
  location: { href: '' },
  dispatchEvent: vi.fn(),
  localStorage: {
    getItem: vi.fn(),
    setItem: vi.fn(),
    removeItem: vi.fn()
  }
}

describe('Dashboard Navigation Store', () => {
  let store

  const mockDashboards = [
    {
      name: 'Main Dashboard',
      uriKey: 'main',
      description: 'Main application dashboard',
      icon: 'HomeIcon',
      category: 'Overview'
    },
    {
      name: 'Analytics Dashboard',
      uriKey: 'analytics',
      description: 'Analytics and metrics dashboard',
      icon: 'ChartBarIcon',
      category: 'Analytics'
    },
    {
      name: 'Reports Dashboard',
      uriKey: 'reports',
      description: 'Reports and data dashboard',
      icon: 'DocumentTextIcon',
      category: 'Analytics'
    },
    {
      name: 'Settings Dashboard',
      uriKey: 'settings',
      description: 'Application settings',
      icon: 'CogIcon',
      category: 'Configuration'
    }
  ]

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useDashboardNavigationStore()
    
    // Clear localStorage mocks
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('Initialization', () => {
    it('initializes with default state', () => {
      expect(store.currentDashboard).toBeNull()
      expect(store.availableDashboards).toEqual([])
      expect(store.navigationHistory).toEqual([])
      expect(store.favorites).toEqual([])
      expect(store.recentlyViewed).toEqual([])
    })

    it('initializes with provided data', () => {
      store.initialize({
        currentDashboard: mockDashboards[0],
        availableDashboards: mockDashboards
      })

      expect(store.currentDashboard).toEqual(mockDashboards[0])
      expect(store.availableDashboards).toEqual(mockDashboards)
      expect(store.navigationHistory).toHaveLength(1)
      expect(store.navigationHistory[0].uriKey).toBe('main')
    })

    it('restores state from localStorage when enabled', () => {
      const storedState = {
        favorites: ['analytics', 'reports'],
        recentlyViewed: [mockDashboards[1]],
        navigationHistory: [mockDashboards[0], mockDashboards[1]],
        preferences: { maxHistoryItems: 15 },
        timestamp: Date.now()
      }

      global.window.localStorage.getItem.mockReturnValue(JSON.stringify(storedState))

      store.initialize({
        currentDashboard: mockDashboards[0],
        availableDashboards: mockDashboards
      })

      expect(store.favorites).toEqual(['analytics', 'reports'])
      expect(store.recentlyViewed).toHaveLength(1)
      expect(store.navigationPreferences.maxHistoryItems).toBe(15)
    })
  })

  describe('Dashboard Navigation', () => {
    beforeEach(() => {
      store.setAvailableDashboards(mockDashboards)
    })

    it('sets current dashboard and adds to history', () => {
      store.setCurrentDashboard(mockDashboards[0])

      expect(store.currentDashboard).toEqual(mockDashboards[0])
      expect(store.navigationHistory).toHaveLength(1)
      expect(store.navigationHistory[0].uriKey).toBe('main')
    })

    it('adds dashboard to recently viewed', () => {
      store.setCurrentDashboard(mockDashboards[1])

      expect(store.recentlyViewed).toHaveLength(1)
      expect(store.recentlyViewed[0].uriKey).toBe('analytics')
    })

    it('dispatches navigation event', () => {
      store.setCurrentDashboard(mockDashboards[0])
      store.setCurrentDashboard(mockDashboards[1])

      expect(global.window.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'dashboard-navigation',
          detail: expect.objectContaining({
            from: mockDashboards[0],
            to: mockDashboards[1]
          })
        })
      )
    })
  })

  describe('Navigation History', () => {
    beforeEach(() => {
      store.setAvailableDashboards(mockDashboards)
    })

    it('maintains navigation history', () => {
      store.setCurrentDashboard(mockDashboards[0])
      store.setCurrentDashboard(mockDashboards[1])
      store.setCurrentDashboard(mockDashboards[2])

      expect(store.navigationHistory).toHaveLength(3)
      expect(store.navigationHistory.map(d => d.uriKey)).toEqual(['main', 'analytics', 'reports'])
    })

    it('removes duplicates from history', () => {
      store.setCurrentDashboard(mockDashboards[0])
      store.setCurrentDashboard(mockDashboards[1])
      store.setCurrentDashboard(mockDashboards[0]) // Revisit

      expect(store.navigationHistory).toHaveLength(2)
      expect(store.navigationHistory.map(d => d.uriKey)).toEqual(['analytics', 'main'])
    })

    it('limits history size', () => {
      store.updatePreferences({ maxHistoryItems: 2 })

      store.setCurrentDashboard(mockDashboards[0])
      store.setCurrentDashboard(mockDashboards[1])
      store.setCurrentDashboard(mockDashboards[2])

      expect(store.navigationHistory).toHaveLength(2)
      expect(store.navigationHistory.map(d => d.uriKey)).toEqual(['analytics', 'reports'])
    })

    it('provides correct navigation state', () => {
      store.setCurrentDashboard(mockDashboards[0])
      store.setCurrentDashboard(mockDashboards[1])

      expect(store.canGoBack).toBe(true)
      expect(store.canGoForward).toBe(false)
      expect(store.previousDashboard.uriKey).toBe('main')
    })

    it('clears history', () => {
      store.setCurrentDashboard(mockDashboards[0])
      store.setCurrentDashboard(mockDashboards[1])
      store.clearHistory()

      expect(store.navigationHistory).toEqual([])
      expect(store.canGoBack).toBe(false)
    })
  })

  describe('Recently Viewed', () => {
    beforeEach(() => {
      store.setAvailableDashboards(mockDashboards)
    })

    it('maintains recently viewed list', () => {
      store.setCurrentDashboard(mockDashboards[0])
      store.setCurrentDashboard(mockDashboards[1])

      expect(store.recentlyViewed).toHaveLength(2)
      expect(store.recentlyViewed[0].uriKey).toBe('analytics') // Most recent first
      expect(store.recentlyViewed[1].uriKey).toBe('main')
    })

    it('limits recently viewed size', () => {
      store.updatePreferences({ maxRecentItems: 2 })

      store.setCurrentDashboard(mockDashboards[0])
      store.setCurrentDashboard(mockDashboards[1])
      store.setCurrentDashboard(mockDashboards[2])

      expect(store.recentlyViewed).toHaveLength(2)
      expect(store.recentlyViewed.map(d => d.uriKey)).toEqual(['reports', 'analytics'])
    })

    it('removes duplicates from recently viewed', () => {
      store.setCurrentDashboard(mockDashboards[0])
      store.setCurrentDashboard(mockDashboards[1])
      store.setCurrentDashboard(mockDashboards[0]) // Revisit

      expect(store.recentlyViewed).toHaveLength(2)
      expect(store.recentlyViewed[0].uriKey).toBe('main') // Moved to front
    })

    it('clears recently viewed', () => {
      store.setCurrentDashboard(mockDashboards[0])
      store.setCurrentDashboard(mockDashboards[1])
      store.clearRecentlyViewed()

      expect(store.recentlyViewed).toEqual([])
    })
  })

  describe('Favorites', () => {
    beforeEach(() => {
      store.setAvailableDashboards(mockDashboards)
    })

    it('toggles favorites', () => {
      store.toggleFavorite('analytics')
      expect(store.favorites).toContain('analytics')

      store.toggleFavorite('analytics')
      expect(store.favorites).not.toContain('analytics')
    })

    it('maintains multiple favorites', () => {
      store.toggleFavorite('analytics')
      store.toggleFavorite('reports')

      expect(store.favorites).toEqual(['analytics', 'reports'])
    })
  })

  describe('Breadcrumbs', () => {
    beforeEach(() => {
      store.setAvailableDashboards(mockDashboards)
    })

    it('generates breadcrumbs for main dashboard', () => {
      store.setCurrentDashboard(mockDashboards[0])

      const breadcrumbs = store.breadcrumbs
      expect(breadcrumbs).toHaveLength(1)
      expect(breadcrumbs[0].label).toBe('Dashboards')
      expect(breadcrumbs[0].isHome).toBe(true)
    })

    it('generates breadcrumbs for non-main dashboard', () => {
      store.setCurrentDashboard(mockDashboards[1])

      const breadcrumbs = store.breadcrumbs
      expect(breadcrumbs).toHaveLength(2)
      expect(breadcrumbs[0].label).toBe('Dashboards')
      expect(breadcrumbs[1].label).toBe('Analytics Dashboard')
      expect(breadcrumbs[1].isCurrent).toBe(true)
    })
  })

  describe('Quick Switch Options', () => {
    beforeEach(() => {
      store.setAvailableDashboards(mockDashboards)
      store.setCurrentDashboard(mockDashboards[0])
    })

    it('excludes current dashboard from options', () => {
      const options = store.quickSwitchOptions
      expect(options.map(d => d.uriKey)).not.toContain('main')
      expect(options).toHaveLength(3)
    })

    it('sorts options by favorites, recent, then alphabetical', () => {
      store.toggleFavorite('reports')
      store.setCurrentDashboard(mockDashboards[1]) // Add to recent
      store.setCurrentDashboard(mockDashboards[0]) // Back to main

      const options = store.quickSwitchOptions
      expect(options[0].uriKey).toBe('reports') // Favorite first
      expect(options[1].uriKey).toBe('analytics') // Recent second
    })
  })

  describe('URL Generation', () => {
    it('generates correct URLs for dashboards', () => {
      expect(store.getDashboardUrl(mockDashboards[0])).toBe('/admin')
      expect(store.getDashboardUrl(mockDashboards[1])).toBe('/admin/dashboards/analytics')
    })

    it('handles null dashboard', () => {
      expect(store.getDashboardUrl(null)).toBe('#')
    })
  })

  describe('State Persistence', () => {
    beforeEach(() => {
      store.updatePreferences({ persistState: true })
    })

    it('persists state to localStorage', () => {
      store.toggleFavorite('analytics')
      store.setCurrentDashboard(mockDashboards[0])

      expect(global.window.localStorage.setItem).toHaveBeenCalledWith(
        'admin-panel-dashboard-navigation',
        expect.stringContaining('analytics')
      )
    })

    it('skips persistence when disabled', () => {
      // Clear any previous calls
      vi.clearAllMocks()

      store.updatePreferences({ persistState: false })
      store.toggleFavorite('analytics')

      expect(global.window.localStorage.setItem).not.toHaveBeenCalled()
    })

    it('handles localStorage errors gracefully', () => {
      global.window.localStorage.setItem.mockImplementation(() => {
        throw new Error('Storage quota exceeded')
      })

      expect(() => {
        store.toggleFavorite('analytics')
      }).not.toThrow()
    })
  })

  describe('Preferences', () => {
    it('updates preferences', () => {
      store.updatePreferences({
        maxHistoryItems: 15,
        enableKeyboardShortcuts: false
      })

      expect(store.navigationPreferences.maxHistoryItems).toBe(15)
      expect(store.navigationPreferences.enableKeyboardShortcuts).toBe(false)
    })

    it('merges preferences with defaults', () => {
      const originalMax = store.navigationPreferences.maxHistoryItems
      
      store.updatePreferences({
        enableKeyboardShortcuts: false
      })

      expect(store.navigationPreferences.maxHistoryItems).toBe(originalMax)
      expect(store.navigationPreferences.enableKeyboardShortcuts).toBe(false)
    })
  })
})
