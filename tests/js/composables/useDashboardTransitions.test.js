/**
 * Dashboard Transitions Composable Tests
 * 
 * Tests for the dashboard transitions composable including navigation,
 * loading states, error handling, and data persistence.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useDashboardTransitions } from '../../../resources/js/composables/useDashboardTransitions'
import { useDashboardNavigationStore } from '../../../resources/js/stores/dashboardNavigation'

// Mock Inertia router
vi.mock('@inertiajs/vue3', () => ({
  router: {
    visit: vi.fn()
  }
}))

// Mock global functions
global.route = vi.fn((name, params) => {
  if (name === 'admin-panel.dashboard') return '/admin'
  if (name === 'admin-panel.dashboards.show') return `/admin/dashboards/${params.uriKey}`
  return '/admin'
})

global.window = {
  location: { href: '', reload: vi.fn() },
  dispatchEvent: vi.fn(),
  scrollX: 0,
  scrollY: 0,
  scrollTo: vi.fn(),
  localStorage: {
    getItem: vi.fn(),
    setItem: vi.fn(),
    removeItem: vi.fn()
  }
}

global.document = {
  querySelectorAll: vi.fn(() => []),
  addEventListener: vi.fn(),
  removeEventListener: vi.fn()
}

describe('useDashboardTransitions Composable', () => {
  let transitions
  let navigationStore
  let mockRouter

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
    }
  ]

  beforeEach(async () => {
    setActivePinia(createPinia())
    navigationStore = useDashboardNavigationStore()

    // Get the mocked router
    const { router } = await import('@inertiajs/vue3')
    mockRouter = router

    transitions = useDashboardTransitions()

    // Initialize navigation store
    navigationStore.initialize({
      currentDashboard: mockDashboards[0],
      availableDashboards: mockDashboards
    })

    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('Initial State', () => {
    it('initializes with correct default state', () => {
      expect(transitions.isTransitioning.value).toBe(false)
      expect(transitions.transitionError.value).toBeNull()
      expect(transitions.transitionProgress.value).toBe(0)
      expect(transitions.currentTransition.value).toBeNull()
      expect(transitions.canTransition.value).toBe(true)
      expect(transitions.hasError.value).toBe(false)
      expect(transitions.isQueued.value).toBe(false)
    })

    it('provides transition types constants', () => {
      expect(transitions.TRANSITION_TYPES).toEqual({
        NAVIGATE: 'navigate',
        SWITCH: 'switch',
        REFRESH: 'refresh',
        BACK: 'back',
        FORWARD: 'forward'
      })
    })
  })

  describe('Dashboard Navigation', () => {
    it('navigates to a different dashboard', async () => {
      const targetDashboard = mockDashboards[1]
      
      // Mock successful navigation
      mockRouter.visit.mockImplementation((url, options) => {
        setTimeout(() => options.onSuccess({ props: {} }), 100)
      })

      const result = await transitions.navigateToDashboard(targetDashboard)

      expect(result.success).toBe(true)
      expect(mockRouter.visit).toHaveBeenCalledWith(
        '/admin/dashboards/analytics',
        expect.objectContaining({
          preserveScroll: true,
          preserveState: true
        })
      )
    })

    it('skips navigation to same dashboard', async () => {
      const currentDashboard = mockDashboards[0]
      
      const result = await transitions.navigateToDashboard(currentDashboard)

      expect(result.success).toBe(true)
      expect(result.skipped).toBe(true)
      expect(mockRouter.visit).not.toHaveBeenCalled()
    })

    it('throws error for invalid dashboard', async () => {
      await expect(transitions.navigateToDashboard(null)).rejects.toThrow('Dashboard is required for navigation')
    })

    it('queues transitions when one is in progress', async () => {
      const dashboard1 = mockDashboards[1]
      const dashboard2 = mockDashboards[2]

      // Mock slow navigation
      mockRouter.visit.mockImplementation((url, options) => {
        setTimeout(() => options.onSuccess({ props: {} }), 500)
      })

      // Start first transition
      const promise1 = transitions.navigateToDashboard(dashboard1)
      
      // Queue second transition
      const promise2 = transitions.navigateToDashboard(dashboard2)

      expect(transitions.isTransitioning.value).toBe(true)
      expect(transitions.isQueued.value).toBe(true)

      await Promise.all([promise1, promise2])
    })
  })

  describe('Dashboard Switching', () => {
    it('switches to dashboard with slide animation', async () => {
      const targetDashboard = mockDashboards[1]
      
      mockRouter.visit.mockImplementation((url, options) => {
        setTimeout(() => options.onSuccess({ props: {} }), 100)
      })

      const result = await transitions.switchToDashboard(targetDashboard)

      expect(result.success).toBe(true)
      expect(mockRouter.visit).toHaveBeenCalledWith(
        '/admin/dashboards/analytics',
        expect.objectContaining({
          preserveScroll: false
        })
      )
    })
  })

  describe('Dashboard Refresh', () => {
    it('refreshes current dashboard', async () => {
      mockRouter.visit.mockImplementation((url, options) => {
        setTimeout(() => options.onSuccess({ props: {} }), 100)
      })

      const result = await transitions.refreshDashboard()

      expect(result.success).toBe(true)
      expect(mockRouter.visit).toHaveBeenCalledWith(
        '/admin',
        expect.objectContaining({
          preserveState: false,
          replace: true
        })
      )
    })

    it('throws error when no current dashboard', async () => {
      navigationStore.currentDashboard = null

      await expect(transitions.refreshDashboard()).rejects.toThrow('No current dashboard to refresh')
    })
  })

  describe('History Navigation', () => {
    beforeEach(() => {
      // Set up navigation history
      navigationStore.setCurrentDashboard(mockDashboards[0])
      navigationStore.setCurrentDashboard(mockDashboards[1])
      navigationStore.setCurrentDashboard(mockDashboards[2])
    })

    it('navigates back when possible', async () => {
      mockRouter.visit.mockImplementation((url, options) => {
        setTimeout(() => options.onSuccess({ props: {} }), 100)
      })

      const result = await transitions.navigateBack()

      expect(result.success).toBe(true)
    })

    it('returns error when cannot navigate back', async () => {
      // Clear history
      navigationStore.clearHistory()
      navigationStore.setCurrentDashboard(mockDashboards[0])

      const result = await transitions.navigateBack()

      expect(result.success).toBe(false)
      expect(result.error).toBe('Cannot navigate back')
    })

    it('navigates forward when possible', async () => {
      // Go back to create forward history
      navigationStore.navigateBack()
      
      mockRouter.visit.mockImplementation((url, options) => {
        setTimeout(() => options.onSuccess({ props: {} }), 100)
      })

      const result = await transitions.navigateForward()

      expect(result.success).toBe(true)
    })

    it('returns error when cannot navigate forward', async () => {
      const result = await transitions.navigateForward()

      expect(result.success).toBe(false)
      expect(result.error).toBe('Cannot navigate forward')
    })
  })

  describe('Error Handling', () => {
    it('handles navigation errors', async () => {
      const targetDashboard = mockDashboards[1]
      const error = new Error('Network error')

      mockRouter.visit.mockImplementation((url, options) => {
        setTimeout(() => options.onError(error), 100)
      })

      const result = await transitions.navigateToDashboard(targetDashboard)

      expect(result.success).toBe(false)
      expect(result.error).toBe('Network error')
      expect(transitions.hasError.value).toBe(true)
    })

    it('retries failed transitions', async () => {
      const targetDashboard = mockDashboards[1]
      let attemptCount = 0

      mockRouter.visit.mockImplementation((url, options) => {
        attemptCount++
        if (attemptCount < 3) {
          setTimeout(() => options.onError(new Error('Temporary error')), 100)
        } else {
          setTimeout(() => options.onSuccess({ props: {} }), 100)
        }
      })

      const result = await transitions.navigateToDashboard(targetDashboard, {
        retryAttempts: 2
      })

      expect(result.success).toBe(true)
      expect(attemptCount).toBe(3)
    })

    it('handles timeout errors', async () => {
      const targetDashboard = mockDashboards[1]

      mockRouter.visit.mockImplementation(() => {
        // Never call success or error callbacks to simulate timeout
      })

      const result = await transitions.navigateToDashboard(targetDashboard, {
        timeout: 100
      })

      expect(result.success).toBe(false)
      expect(result.error).toBe('Navigation timeout')
    })

    it('clears errors', () => {
      transitions.transitionError.value = new Error('Test error')
      
      transitions.clearError()
      
      expect(transitions.transitionError.value).toBeNull()
      expect(transitions.hasError.value).toBe(false)
    })
  })

  describe('Data Preservation', () => {
    beforeEach(() => {
      // Mock form elements
      const mockForm = {
        querySelector: vi.fn(() => ({ value: 'test' }))
      }
      global.document.querySelectorAll.mockReturnValue([mockForm])
      
      // Mock FormData
      global.FormData = vi.fn(() => ({
        entries: () => [['field1', 'value1']]
      }))
    })

    it('preserves scroll position', async () => {
      global.window.scrollX = 100
      global.window.scrollY = 200

      const targetDashboard = mockDashboards[1]
      
      mockRouter.visit.mockImplementation((url, options) => {
        setTimeout(() => options.onSuccess({ props: {} }), 100)
      })

      await transitions.navigateToDashboard(targetDashboard, {
        preserveData: true
      })

      expect(transitions.preservedData.value).toEqual(
        expect.objectContaining({
          scrollPosition: { x: 100, y: 200 }
        })
      )
    })

    it('preserves form data', async () => {
      const targetDashboard = mockDashboards[1]
      
      mockRouter.visit.mockImplementation((url, options) => {
        setTimeout(() => options.onSuccess({ props: {} }), 100)
      })

      await transitions.navigateToDashboard(targetDashboard, {
        preserveData: true
      })

      expect(transitions.preservedData.value).toEqual(
        expect.objectContaining({
          formData: expect.any(Object)
        })
      )
    })
  })

  describe('Progress Tracking', () => {
    it('tracks transition progress', async () => {
      const targetDashboard = mockDashboards[1]
      const progressValues = []

      mockRouter.visit.mockImplementation((url, options) => {
        // Simulate progress updates
        setTimeout(() => options.onStart(), 10)
        setTimeout(() => options.onProgress({ percentage: 50 }), 50)
        setTimeout(() => options.onSuccess({ props: {} }), 100)
      })

      // Monitor progress changes
      const stopWatching = vi.fn()
      
      await transitions.navigateToDashboard(targetDashboard, {
        onProgress: (progress) => {
          progressValues.push(transitions.transitionProgress.value)
        }
      })

      expect(progressValues.length).toBeGreaterThan(0)
    })
  })

  describe('Transition Cancellation', () => {
    it('cancels current transition', () => {
      transitions.currentTransition.value = {
        id: 'test-transition',
        dashboard: mockDashboards[1],
        type: 'navigate',
        options: {}
      }
      transitions.isTransitioning.value = true

      transitions.cancelTransition()

      expect(transitions.isTransitioning.value).toBe(false)
      expect(transitions.currentTransition.value).toBeNull()
    })

    it('clears transition queue', () => {
      transitions.transitionQueue.value = [
        { dashboard: mockDashboards[1], options: {}, resolve: vi.fn() }
      ]

      transitions.cancelTransition()

      expect(transitions.transitionQueue.value).toEqual([])
    })
  })

  describe('Event Emission', () => {
    it('emits transition start event', async () => {
      const targetDashboard = mockDashboards[1]
      
      mockRouter.visit.mockImplementation((url, options) => {
        setTimeout(() => options.onSuccess({ props: {} }), 100)
      })

      await transitions.navigateToDashboard(targetDashboard)

      expect(global.window.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'dashboard-transition-start'
        })
      )
    })

    it('emits transition complete event', async () => {
      const targetDashboard = mockDashboards[1]
      
      mockRouter.visit.mockImplementation((url, options) => {
        setTimeout(() => options.onSuccess({ props: {} }), 100)
      })

      await transitions.navigateToDashboard(targetDashboard)

      expect(global.window.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'dashboard-transition-complete'
        })
      )
    })

    it('emits transition error event', async () => {
      const targetDashboard = mockDashboards[1]
      
      mockRouter.visit.mockImplementation((url, options) => {
        setTimeout(() => options.onError(new Error('Test error')), 100)
      })

      await transitions.navigateToDashboard(targetDashboard, {
        retryAttempts: 0
      })

      expect(global.window.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'dashboard-transition-error'
        })
      )
    })
  })
})
