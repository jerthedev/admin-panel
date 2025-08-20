/**
 * Dashboard Breadcrumbs Component Tests
 * 
 * Tests for the dashboard breadcrumbs component including navigation,
 * quick actions, keyboard shortcuts, and accessibility features.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import DashboardBreadcrumbs from '../../../resources/js/Components/Dashboard/DashboardBreadcrumbs.vue'
import { useDashboardNavigationStore } from '../../../resources/js/stores/dashboardNavigation'

// Mock global functions
global.route = vi.fn((name, params) => {
  if (name === 'admin-panel.dashboard') return '/admin'
  if (name === 'admin-panel.dashboards.show') return `/admin/dashboards/${params.uriKey}`
  return '/admin'
})

// Mock window.location
Object.defineProperty(window, 'location', {
  value: {
    href: '',
    assign: vi.fn(),
    replace: vi.fn(),
    reload: vi.fn()
  },
  writable: true
})

// Mock Inertia Link component
const MockLink = {
  name: 'Link',
  template: '<a :href="href" @click="$emit(\'click\', $event)"><slot /></a>',
  props: ['href'],
  emits: ['click']
}

describe('DashboardBreadcrumbs Component', () => {
  let wrapper
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
    }
  ]

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useDashboardNavigationStore()

    // Mock navigateToDashboard to prevent actual navigation
    vi.spyOn(store, 'navigateToDashboard').mockImplementation(() => {})

    // Initialize store with mock data
    store.initialize({
      currentDashboard: mockDashboards[0],
      availableDashboards: mockDashboards
    })

    vi.clearAllMocks()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
    vi.restoreAllMocks()
  })

  describe('Basic Rendering', () => {
    it('renders breadcrumbs when enabled', () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showBreadcrumbs: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      expect(wrapper.find('[data-testid="dashboard-breadcrumbs"]').exists()).toBe(true)
    })

    it('hides breadcrumbs when disabled', () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showBreadcrumbs: false
        },
        global: {
          components: { Link: MockLink }
        }
      })

      expect(wrapper.find('[data-testid="dashboard-breadcrumbs"]').exists()).toBe(false)
    })

    it('renders navigation controls when enabled', () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showNavigation: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const navigationControls = wrapper.find('.navigation-controls')
      expect(navigationControls.exists()).toBe(true)
      expect(navigationControls.findAll('button')).toHaveLength(2) // Back and forward buttons
    })

    it('hides navigation controls when disabled', () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showNavigation: false
        },
        global: {
          components: { Link: MockLink }
        }
      })

      expect(wrapper.find('.navigation-controls').exists()).toBe(false)
    })

    it('renders quick actions when enabled', () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showQuickActions: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      expect(wrapper.find('.quick-actions').exists()).toBe(true)
    })
  })

  describe('Navigation Controls', () => {
    beforeEach(() => {
      // Set up navigation history
      store.setCurrentDashboard(mockDashboards[0])
      store.setCurrentDashboard(mockDashboards[1])
    })

    it('enables back button when navigation is possible', () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showNavigation: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const backButton = wrapper.findAll('.navigation-button')[0]
      expect(backButton.classes()).toContain('enabled')
      expect(backButton.attributes('disabled')).toBeUndefined()
    })

    it('disables back button when navigation is not possible', () => {
      // Reset to single dashboard
      store.clearHistory()
      store.setCurrentDashboard(mockDashboards[0])

      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showNavigation: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const backButton = wrapper.findAll('.navigation-button')[0]
      expect(backButton.classes()).toContain('disabled')
      expect(backButton.attributes('disabled')).toBeDefined()
    })

    it('calls navigateBack when back button is clicked', async () => {
      const navigateBackSpy = vi.spyOn(store, 'navigateBack')

      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showNavigation: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const backButton = wrapper.findAll('.navigation-button')[0]
      await backButton.trigger('click')

      expect(navigateBackSpy).toHaveBeenCalled()
    })

    it('calls navigateForward when forward button is clicked', async () => {
      // Mock canGoForward to return true
      vi.spyOn(store, 'canGoForward', 'get').mockReturnValue(true)

      // Mock navigateForward to return true (successful navigation)
      const navigateForwardSpy = vi.spyOn(store, 'navigateForward').mockReturnValue(true)

      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showNavigation: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const forwardButton = wrapper.findAll('.navigation-button')[1]
      await forwardButton.trigger('click')

      expect(navigateForwardSpy).toHaveBeenCalled()
    })
  })

  describe('Breadcrumb Trail', () => {
    it('shows home breadcrumb for main dashboard', () => {
      store.setCurrentDashboard(mockDashboards[0])

      wrapper = mount(DashboardBreadcrumbs, {
        global: {
          components: { Link: MockLink }
        }
      })

      const breadcrumbItems = wrapper.findAll('.breadcrumb-item')
      expect(breadcrumbItems).toHaveLength(1)
      expect(breadcrumbItems[0].text()).toContain('Dashboards')
    })

    it('shows full breadcrumb trail for non-main dashboard', () => {
      store.setCurrentDashboard(mockDashboards[1])

      wrapper = mount(DashboardBreadcrumbs, {
        global: {
          components: { Link: MockLink }
        }
      })

      const breadcrumbItems = wrapper.findAll('.breadcrumb-item')
      expect(breadcrumbItems).toHaveLength(2)
      expect(breadcrumbItems[0].text()).toContain('Dashboards')
      expect(breadcrumbItems[1].text()).toContain('Analytics Dashboard')
    })

    it('emits dashboard-switched event when home breadcrumb is clicked', async () => {
      store.setCurrentDashboard(mockDashboards[1])

      wrapper = mount(DashboardBreadcrumbs, {
        global: {
          components: { Link: MockLink }
        }
      })

      const homeLink = wrapper.find('.breadcrumb-link')
      await homeLink.trigger('click')

      expect(wrapper.emitted('dashboard-switched')).toBeTruthy()
      expect(wrapper.emitted('dashboard-switched')[0]).toEqual([
        { uriKey: 'main', name: 'Dashboard' }
      ])
    })
  })

  describe('Quick Actions', () => {
    beforeEach(() => {
      store.setCurrentDashboard(mockDashboards[1]) // Non-main dashboard
    })

    it('shows dashboard selector when multiple dashboards available', () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showQuickActions: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const dashboardSelectorButton = wrapper.find('button[title="Switch dashboard"]')
      expect(dashboardSelectorButton.exists()).toBe(true)
    })

    it('shows favorite toggle for non-main dashboard', () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showQuickActions: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const favoriteButton = wrapper.find('.favorite-button')
      expect(favoriteButton.exists()).toBe(true)
    })

    it('toggles favorite when favorite button is clicked', async () => {
      const toggleFavoriteSpy = vi.spyOn(store, 'toggleFavorite')

      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showQuickActions: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const favoriteButton = wrapper.find('.favorite-button')
      await favoriteButton.trigger('click')

      expect(toggleFavoriteSpy).toHaveBeenCalledWith('analytics')
    })

    it('shows refresh button when enabled', () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showQuickActions: true,
          showRefreshButton: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const refreshButton = wrapper.find('.refresh-button')
      expect(refreshButton.exists()).toBe(true)
    })

    it('emits refresh-dashboard event when refresh button is clicked', async () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showQuickActions: true,
          showRefreshButton: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const refreshButton = wrapper.find('.refresh-button')
      await refreshButton.trigger('click')

      expect(wrapper.emitted('refresh-dashboard')).toBeTruthy()
    })
  })

  describe('Quick Switcher Dropdown', () => {
    beforeEach(() => {
      store.setCurrentDashboard(mockDashboards[0])
      store.toggleFavorite('analytics') // Add a favorite
      store.setCurrentDashboard(mockDashboards[1]) // Add to recent
      store.setCurrentDashboard(mockDashboards[0]) // Back to main
    })

    it('opens quick switcher dropdown when button is clicked', async () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showQuickActions: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const switcherButton = wrapper.find('button[title="Switch dashboard"]')
      await switcherButton.trigger('click')

      expect(wrapper.find('.quick-switcher-dropdown').exists()).toBe(true)
    })

    it('shows recent dashboards section', async () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showQuickActions: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const switcherButton = wrapper.find('button[title="Switch dashboard"]')
      await switcherButton.trigger('click')

      const recentSection = wrapper.find('.dashboard-group')
      expect(recentSection.exists()).toBe(true)
      expect(recentSection.text()).toContain('Recent')
    })

    it('closes dropdown when close button is clicked', async () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showQuickActions: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const switcherButton = wrapper.find('button[title="Switch dashboard"]')
      await switcherButton.trigger('click')

      const closeButton = wrapper.find('.close-button')
      await closeButton.trigger('click')

      expect(wrapper.find('.quick-switcher-dropdown').exists()).toBe(false)
    })

    it('switches dashboard when option is clicked', async () => {
      const navigateToDashboardSpy = vi.spyOn(store, 'navigateToDashboard')

      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showQuickActions: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const switcherButton = wrapper.find('button[title="Switch dashboard"]')
      await switcherButton.trigger('click')

      const dashboardOption = wrapper.find('.dashboard-option')
      await dashboardOption.trigger('click')

      expect(navigateToDashboardSpy).toHaveBeenCalled()
      expect(wrapper.emitted('dashboard-switched')).toBeTruthy()
    })
  })

  describe('Keyboard Shortcuts', () => {
    it('handles Ctrl+K shortcut to open quick switcher', async () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          enableKeyboardShortcuts: true,
          showQuickActions: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      // Simulate document-level keydown event
      const event = new KeyboardEvent('keydown', { key: 'k', ctrlKey: true })
      document.dispatchEvent(event)
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.quick-switcher-dropdown').exists()).toBe(true)
    })

    it('handles Alt+Left shortcut for back navigation', async () => {
      store.setCurrentDashboard(mockDashboards[1]) // Create history
      const navigateBackSpy = vi.spyOn(store, 'navigateBack').mockReturnValue(true)

      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          enableKeyboardShortcuts: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      // Simulate document-level keydown event
      const event = new KeyboardEvent('keydown', { key: 'ArrowLeft', altKey: true })
      document.dispatchEvent(event)
      await wrapper.vm.$nextTick()

      expect(navigateBackSpy).toHaveBeenCalled()
    })

    it('handles Alt+Right shortcut for forward navigation', async () => {
      // Mock canGoForward to return true
      vi.spyOn(store, 'canGoForward', 'get').mockReturnValue(true)

      const navigateForwardSpy = vi.spyOn(store, 'navigateForward').mockReturnValue(true)

      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          enableKeyboardShortcuts: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      // Simulate document-level keydown event
      const event = new KeyboardEvent('keydown', { key: 'ArrowRight', altKey: true })
      document.dispatchEvent(event)
      await wrapper.vm.$nextTick()

      expect(navigateForwardSpy).toHaveBeenCalled()
    })

    it('handles Escape key to close quick switcher', async () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          enableKeyboardShortcuts: true,
          showQuickActions: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      // Open quick switcher first
      const switcherButton = wrapper.find('button[title="Switch dashboard"]')
      await switcherButton.trigger('click')

      // Then close with Escape using document-level event
      const event = new KeyboardEvent('keydown', { key: 'Escape' })
      document.dispatchEvent(event)
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.quick-switcher-dropdown').exists()).toBe(false)
    })

    it('ignores shortcuts when disabled', async () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          enableKeyboardShortcuts: false,
          showQuickActions: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      await wrapper.trigger('keydown', { key: 'k', ctrlKey: true })

      expect(wrapper.find('.quick-switcher-dropdown').exists()).toBe(false)
    })
  })

  describe('Accessibility', () => {
    it('provides proper ARIA attributes', () => {
      wrapper = mount(DashboardBreadcrumbs, {
        global: {
          components: { Link: MockLink }
        }
      })

      const nav = wrapper.find('nav')
      expect(nav.attributes('aria-label')).toBe('Dashboard breadcrumb')
    })

    it('provides proper button labels', () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showNavigation: true,
          showQuickActions: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const backButton = wrapper.findAll('.navigation-button')[0]
      expect(backButton.attributes('aria-label')).toBe('Go back to previous dashboard')

      const forwardButton = wrapper.findAll('.navigation-button')[1]
      expect(forwardButton.attributes('aria-label')).toBe('Go forward to next dashboard')
    })

    it('shows keyboard hints when enabled', async () => {
      wrapper = mount(DashboardBreadcrumbs, {
        props: {
          showQuickActions: true,
          showKeyboardHints: true
        },
        global: {
          components: { Link: MockLink }
        }
      })

      const switcherButton = wrapper.find('button[title="Switch dashboard"]')
      await switcherButton.trigger('click')

      const keyboardHints = wrapper.find('.keyboard-hints')
      expect(keyboardHints.exists()).toBe(true)
      expect(keyboardHints.text()).toContain('Ctrl')
      expect(keyboardHints.text()).toContain('Alt')
    })
  })
})
