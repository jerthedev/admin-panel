/**
 * Dashboard Quick Switcher Component Tests
 * 
 * Tests for the dashboard quick switcher modal including search,
 * keyboard navigation, dashboard grouping, and accessibility.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import DashboardQuickSwitcher from '../../../resources/js/Components/Dashboard/DashboardQuickSwitcher.vue'
import { useDashboardNavigationStore } from '../../../resources/js/stores/dashboardNavigation'

// Mock global functions
global.route = vi.fn((name, params) => {
  if (name === 'admin-panel.dashboard') return '/admin'
  if (name === 'admin-panel.dashboards.show') return `/admin/dashboards/${params.uriKey}`
  return '/admin'
})

describe('DashboardQuickSwitcher Component', () => {
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
    },
    {
      name: 'Settings Dashboard',
      uriKey: 'settings',
      description: 'Application settings',
      icon: 'CogIcon',
      category: 'Configuration'
    },
    {
      name: 'User Management',
      uriKey: 'users',
      description: 'Manage users and permissions',
      icon: 'UsersIcon',
      category: 'Administration'
    }
  ]

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useDashboardNavigationStore()
    
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
    it('renders modal when open', async () => {
      wrapper = mount(DashboardQuickSwitcher)
      
      await wrapper.vm.open()

      expect(wrapper.find('[data-testid="quick-switcher-overlay"]').exists()).toBe(true)
      expect(wrapper.find('.quick-switcher-modal').exists()).toBe(true)
    })

    it('does not render modal when closed', () => {
      wrapper = mount(DashboardQuickSwitcher)

      expect(wrapper.find('[data-testid="quick-switcher-overlay"]').exists()).toBe(false)
    })

    it('renders modal header with title and close button', async () => {
      wrapper = mount(DashboardQuickSwitcher)
      
      await wrapper.vm.open()

      expect(wrapper.find('.modal-title').text()).toBe('Quick Switch Dashboard')
      expect(wrapper.find('.close-button').exists()).toBe(true)
    })

    it('renders search input', async () => {
      wrapper = mount(DashboardQuickSwitcher)
      
      await wrapper.vm.open()

      const searchInput = wrapper.find('.search-input')
      expect(searchInput.exists()).toBe(true)
      expect(searchInput.attributes('placeholder')).toBe('Search dashboards...')
    })

    it('renders keyboard shortcuts in footer', async () => {
      wrapper = mount(DashboardQuickSwitcher)
      
      await wrapper.vm.open()

      const footer = wrapper.find('.modal-footer')
      expect(footer.exists()).toBe(true)
      expect(footer.text()).toContain('Navigate')
      expect(footer.text()).toContain('Select')
      expect(footer.text()).toContain('Close')
    })
  })

  describe('Dashboard Groups', () => {
    beforeEach(async () => {
      // Set up test data with recent and favorites
      store.setCurrentDashboard(mockDashboards[1]) // Analytics
      store.setCurrentDashboard(mockDashboards[2]) // Reports (recent)
      store.toggleFavorite('settings') // Add to favorites
      store.setCurrentDashboard(mockDashboards[0]) // Back to main

      wrapper = mount(DashboardQuickSwitcher)
      await wrapper.vm.open()
    })

    it('shows recent dashboards section', () => {
      const recentSection = wrapper.find('.dashboard-group')
      expect(recentSection.exists()).toBe(true)
      expect(recentSection.text()).toContain('Recent')
    })

    it('shows favorite dashboards section', () => {
      const sections = wrapper.findAll('.dashboard-group')
      const favoriteSection = sections.find(section => section.text().includes('Favorites'))
      expect(favoriteSection).toBeTruthy()
    })

    it('shows all dashboards section', () => {
      const sections = wrapper.findAll('.dashboard-group')
      const allSection = sections.find(section => section.text().includes('All Dashboards'))
      expect(allSection).toBeTruthy()
    })

    it('excludes current dashboard from all groups', () => {
      const dashboardItems = wrapper.findAll('.dashboard-item')
      const mainDashboardItem = dashboardItems.find(item => 
        item.text().includes('Main Dashboard')
      )
      expect(mainDashboardItem).toBeFalsy()
    })

    it('shows correct indicators for recent and favorite dashboards', () => {
      const dashboardItems = wrapper.findAll('.dashboard-item')
      
      // Check for recent indicator
      const recentItem = dashboardItems.find(item => 
        item.text().includes('Reports Dashboard')
      )
      expect(recentItem.find('.recent-time').exists()).toBe(true)

      // Check for favorite indicator
      const favoriteItem = dashboardItems.find(item => 
        item.text().includes('Settings Dashboard')
      )
      expect(favoriteItem.find('.favorite-indicator.filled').exists()).toBe(true)
    })
  })

  describe('Search Functionality', () => {
    beforeEach(async () => {
      wrapper = mount(DashboardQuickSwitcher)
      await wrapper.vm.open()
    })

    it('filters dashboards based on search query', async () => {
      const searchInput = wrapper.find('.search-input')
      await searchInput.setValue('Analytics')

      const dashboardItems = wrapper.findAll('.dashboard-item')
      expect(dashboardItems).toHaveLength(1)
      expect(dashboardItems[0].text()).toContain('Analytics Dashboard')
    })

    it('searches in dashboard descriptions', async () => {
      const searchInput = wrapper.find('.search-input')
      await searchInput.setValue('metrics')

      const dashboardItems = wrapper.findAll('.dashboard-item')
      expect(dashboardItems).toHaveLength(1)
      expect(dashboardItems[0].text()).toContain('Analytics Dashboard')
    })

    it('searches in dashboard categories', async () => {
      const searchInput = wrapper.find('.search-input')
      await searchInput.setValue('Configuration')

      const dashboardItems = wrapper.findAll('.dashboard-item')
      expect(dashboardItems).toHaveLength(1)
      expect(dashboardItems[0].text()).toContain('Settings Dashboard')
    })

    it('shows no results message when no matches found', async () => {
      const searchInput = wrapper.find('.search-input')
      await searchInput.setValue('nonexistent')

      expect(wrapper.find('.no-results').exists()).toBe(true)
      expect(wrapper.find('.no-results-text').text()).toContain('No dashboards match your search')
    })

    it('shows clear search button when search has value', async () => {
      const searchInput = wrapper.find('.search-input')
      await searchInput.setValue('Analytics')

      expect(wrapper.find('.clear-search-button').exists()).toBe(true)
    })

    it('clears search when clear button is clicked', async () => {
      const searchInput = wrapper.find('.search-input')
      await searchInput.setValue('Analytics')

      const clearButton = wrapper.find('.clear-search-button')
      await clearButton.trigger('click')

      expect(searchInput.element.value).toBe('')
      expect(wrapper.findAll('.dashboard-item').length).toBeGreaterThan(1)
    })

    it('clears search when clear link in no results is clicked', async () => {
      const searchInput = wrapper.find('.search-input')
      await searchInput.setValue('nonexistent')

      const clearLink = wrapper.find('.clear-search-link')
      await clearLink.trigger('click')

      expect(searchInput.element.value).toBe('')
    })
  })

  describe('Keyboard Navigation', () => {
    beforeEach(async () => {
      wrapper = mount(DashboardQuickSwitcher)
      await wrapper.vm.open()
    })

    it('focuses first item by default', () => {
      expect(wrapper.vm.focusedIndex).toBe(0)
    })

    it('navigates down with arrow down key', async () => {
      const modal = wrapper.find('.quick-switcher-modal')
      await modal.trigger('keydown.arrow-down')

      expect(wrapper.vm.focusedIndex).toBe(1)
    })

    it('navigates up with arrow up key', async () => {
      wrapper.vm.focusedIndex = 1

      const modal = wrapper.find('.quick-switcher-modal')
      await modal.trigger('keydown.arrow-up')

      expect(wrapper.vm.focusedIndex).toBe(0)
    })

    it('does not navigate beyond bounds', async () => {
      const modal = wrapper.find('.quick-switcher-modal')
      
      // Try to go up from first item
      await modal.trigger('keydown.arrow-up')
      expect(wrapper.vm.focusedIndex).toBe(0)

      // Go to last item and try to go down
      wrapper.vm.focusedIndex = wrapper.vm.allVisibleDashboards.length - 1
      await modal.trigger('keydown.arrow-down')
      expect(wrapper.vm.focusedIndex).toBe(wrapper.vm.allVisibleDashboards.length - 1)
    })

    it('selects focused item with enter key', async () => {
      const navigateToDashboardSpy = vi.spyOn(store, 'navigateToDashboard')

      const modal = wrapper.find('.quick-switcher-modal')
      await modal.trigger('keydown.enter')

      expect(navigateToDashboardSpy).toHaveBeenCalled()
      expect(wrapper.emitted('dashboard-selected')).toBeTruthy()
    })

    it('closes modal with escape key', async () => {
      const modal = wrapper.find('.quick-switcher-modal')
      await modal.trigger('keydown.escape')

      expect(wrapper.vm.isOpen).toBe(false)
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('resets focus when search changes', async () => {
      wrapper.vm.focusedIndex = 2

      const searchInput = wrapper.find('.search-input')
      await searchInput.setValue('Analytics')

      expect(wrapper.vm.focusedIndex).toBe(0)
    })
  })

  describe('Dashboard Selection', () => {
    beforeEach(async () => {
      wrapper = mount(DashboardQuickSwitcher)
      await wrapper.vm.open()
    })

    it('selects dashboard when clicked', async () => {
      const navigateToDashboardSpy = vi.spyOn(store, 'navigateToDashboard')

      const dashboardItem = wrapper.find('.dashboard-item')
      await dashboardItem.trigger('click')

      expect(navigateToDashboardSpy).toHaveBeenCalled()
      expect(wrapper.emitted('dashboard-selected')).toBeTruthy()
      expect(wrapper.vm.isOpen).toBe(false)
    })

    it('emits correct dashboard data when selected', async () => {
      const dashboardItem = wrapper.find('.dashboard-item')
      await dashboardItem.trigger('click')

      const emittedEvents = wrapper.emitted('dashboard-selected')
      expect(emittedEvents).toBeTruthy()
      expect(emittedEvents[0][0]).toHaveProperty('uriKey')
      expect(emittedEvents[0][0]).toHaveProperty('name')
    })
  })

  describe('Modal Controls', () => {
    beforeEach(async () => {
      wrapper = mount(DashboardQuickSwitcher)
      await wrapper.vm.open()
    })

    it('closes modal when close button is clicked', async () => {
      const closeButton = wrapper.find('.close-button')
      await closeButton.trigger('click')

      expect(wrapper.vm.isOpen).toBe(false)
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('closes modal when overlay is clicked', async () => {
      const overlay = wrapper.find('[data-testid="quick-switcher-overlay"]')
      await overlay.trigger('click')

      expect(wrapper.vm.isOpen).toBe(false)
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('does not close modal when modal content is clicked', async () => {
      const modal = wrapper.find('.quick-switcher-modal')
      await modal.trigger('click')

      expect(wrapper.vm.isOpen).toBe(true)
    })

    it('focuses search input when opened', async () => {
      const searchInput = wrapper.find('.search-input')
      const focusSpy = vi.spyOn(searchInput.element, 'focus')

      await wrapper.vm.open()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Global Keyboard Shortcuts', () => {
    it('opens modal with Ctrl+K shortcut', async () => {
      wrapper = mount(DashboardQuickSwitcher, {
        props: {
          enableKeyboardShortcuts: true
        }
      })

      // Simulate global keydown event
      const event = new KeyboardEvent('keydown', { key: 'k', ctrlKey: true })
      document.dispatchEvent(event)

      expect(wrapper.vm.isOpen).toBe(true)
    })

    it('opens modal with Cmd+K shortcut on Mac', async () => {
      wrapper = mount(DashboardQuickSwitcher, {
        props: {
          enableKeyboardShortcuts: true
        }
      })

      // Simulate global keydown event
      const event = new KeyboardEvent('keydown', { key: 'k', metaKey: true })
      document.dispatchEvent(event)

      expect(wrapper.vm.isOpen).toBe(true)
    })

    it('ignores shortcuts when disabled', async () => {
      wrapper = mount(DashboardQuickSwitcher, {
        props: {
          enableKeyboardShortcuts: false
        }
      })

      // Simulate global keydown event
      const event = new KeyboardEvent('keydown', { key: 'k', ctrlKey: true })
      document.dispatchEvent(event)

      expect(wrapper.vm.isOpen).toBe(false)
    })
  })

  describe('Time Formatting', () => {
    it('formats recent times correctly', () => {
      const now = Date.now()
      
      expect(wrapper.vm.formatRecentTime(now)).toBe('Just now')
      expect(wrapper.vm.formatRecentTime(now - 30 * 1000)).toBe('Just now') // 30 seconds
      expect(wrapper.vm.formatRecentTime(now - 5 * 60 * 1000)).toBe('5m ago') // 5 minutes
      expect(wrapper.vm.formatRecentTime(now - 2 * 60 * 60 * 1000)).toBe('2h ago') // 2 hours
      expect(wrapper.vm.formatRecentTime(now - 3 * 24 * 60 * 60 * 1000)).toBe('3d ago') // 3 days
    })

    it('handles null timestamp', () => {
      expect(wrapper.vm.formatRecentTime(null)).toBe('')
    })
  })

  describe('Accessibility', () => {
    beforeEach(async () => {
      wrapper = mount(DashboardQuickSwitcher)
      await wrapper.vm.open()
    })

    it('provides proper ARIA labels', () => {
      const closeButton = wrapper.find('.close-button')
      expect(closeButton.attributes('aria-label')).toBe('Close quick switcher')

      const clearButton = wrapper.find('.clear-search-button')
      if (clearButton.exists()) {
        expect(clearButton.attributes('aria-label')).toBe('Clear search')
      }
    })

    it('manages focus properly', async () => {
      const searchInput = wrapper.find('.search-input')
      expect(document.activeElement).toBe(searchInput.element)
    })

    it('prevents body scroll when open', async () => {
      expect(document.body.style.overflow).toBe('hidden')
    })

    it('restores body scroll when closed', async () => {
      await wrapper.vm.close()
      expect(document.body.style.overflow).toBe('')
    })
  })

  describe('Component Lifecycle', () => {
    it('cleans up event listeners on unmount', () => {
      const removeEventListenerSpy = vi.spyOn(document, 'removeEventListener')
      
      wrapper = mount(DashboardQuickSwitcher)
      wrapper.unmount()

      expect(removeEventListenerSpy).toHaveBeenCalledWith('keydown', expect.any(Function))
    })

    it('restores body overflow on unmount', () => {
      wrapper = mount(DashboardQuickSwitcher)
      wrapper.vm.open()
      wrapper.unmount()

      expect(document.body.style.overflow).toBe('')
    })
  })
})
