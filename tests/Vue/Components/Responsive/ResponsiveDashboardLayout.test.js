/**
 * Responsive Dashboard Layout Tests
 * 
 * Tests for responsive behavior, mobile gestures, and adaptive layouts
 * across different screen sizes and device types.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import ResponsiveDashboardLayout from '@/Components/Responsive/ResponsiveDashboardLayout.vue'

// Mock composables
vi.mock('@/composables/useMobileNavigation', () => ({
  useMobileNavigation: () => ({
    isMobile: { value: false },
    isTablet: { value: false },
    isDesktop: { value: true },
    screenWidth: { value: 1024 },
    screenHeight: { value: 768 },
    orientation: { value: 'landscape' },
    isPortrait: { value: false },
    isLandscape: { value: true },
    setup: vi.fn(),
    cleanup: vi.fn()
  })
}))

vi.mock('@/composables/useMobileGestures', () => ({
  useMobileGestures: () => ({
    on: vi.fn(),
    off: vi.fn(),
    setup: vi.fn(),
    cleanup: vi.fn()
  })
}))

// Mock ResizeObserver
global.ResizeObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn()
}))

describe('ResponsiveDashboardLayout', () => {
  let wrapper
  let mockDashboards
  let mockCurrentDashboard
  let mockCards

  beforeEach(() => {
    setActivePinia(createPinia())
    
    mockDashboards = [
      {
        uriKey: 'analytics',
        name: 'Analytics Dashboard',
        category: 'Analytics'
      },
      {
        uriKey: 'sales',
        name: 'Sales Dashboard',
        category: 'Business'
      }
    ]

    mockCurrentDashboard = mockDashboards[0]

    mockCards = [
      {
        id: 'card-1',
        component: 'MetricCard',
        props: { title: 'Users' },
        data: { value: 1234 },
        loading: false,
        error: null
      },
      {
        id: 'card-2',
        component: 'ChartCard',
        props: { title: 'Revenue' },
        data: { values: [100, 200, 300] },
        loading: false,
        error: null
      }
    ]
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  it('renders desktop layout by default', () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards
      }
    })

    expect(wrapper.find('.desktop-header').exists()).toBe(true)
    expect(wrapper.find('.mobile-header').exists()).toBe(false)
    expect(wrapper.classes()).toContain('is-desktop')
  })

  it('renders mobile layout when on mobile device', async () => {
    // Mock mobile navigation
    vi.doMock('@/composables/useMobileNavigation', () => ({
      useMobileNavigation: () => ({
        isMobile: { value: true },
        isTablet: { value: false },
        isDesktop: { value: false },
        screenWidth: { value: 375 },
        screenHeight: { value: 667 },
        orientation: { value: 'portrait' },
        isPortrait: { value: true },
        isLandscape: { value: false },
        setup: vi.fn(),
        cleanup: vi.fn()
      })
    }))

    const { ResponsiveDashboardLayout: MobileLayout } = await import('@/Components/Responsive/ResponsiveDashboardLayout.vue')
    
    wrapper = mount(MobileLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards
      }
    })

    expect(wrapper.find('.mobile-header').exists()).toBe(true)
    expect(wrapper.find('.desktop-header').exists()).toBe(false)
    expect(wrapper.classes()).toContain('is-mobile')
  })

  it('displays dashboard cards in grid layout', () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards
      }
    })

    const grid = wrapper.find('.dashboard-grid')
    expect(grid.exists()).toBe(true)
    
    const cardContainers = wrapper.findAll('.dashboard-card-container')
    expect(cardContainers).toHaveLength(2)
  })

  it('shows loading state correctly', () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: [],
        isLoading: true
      }
    })

    expect(wrapper.find('.loading-container').exists()).toBe(true)
    expect(wrapper.find('.dashboard-content').exists()).toBe(false)
    expect(wrapper.classes()).toContain('is-loading')
  })

  it('shows error state correctly', () => {
    const error = 'Failed to load dashboard'
    
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: [],
        error
      }
    })

    expect(wrapper.find('.error-container').exists()).toBe(true)
    expect(wrapper.find('.dashboard-content').exists()).toBe(false)
    expect(wrapper.classes()).toContain('has-error')
  })

  it('shows empty state when no cards are available', () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: [],
        isLoading: false,
        error: null
      }
    })

    expect(wrapper.find('.empty-state').exists()).toBe(true)
  })

  it('displays dashboard header with title and description', () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: {
          ...mockCurrentDashboard,
          description: 'Analytics dashboard description'
        },
        dashboardCards: mockCards,
        showDashboardHeader: true
      }
    })

    const header = wrapper.find('.dashboard-header')
    expect(header.exists()).toBe(true)
    
    const title = wrapper.find('.dashboard-title')
    expect(title.text()).toBe('Analytics Dashboard')
    
    const description = wrapper.find('.dashboard-description')
    expect(description.text()).toBe('Analytics dashboard description')
  })

  it('displays dashboard actions when enabled', () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards,
        showDashboardActions: true
      }
    })

    const actions = wrapper.find('.dashboard-actions')
    expect(actions.exists()).toBe(true)
    
    const actionButtons = wrapper.findAll('.dashboard-action')
    expect(actionButtons.length).toBeGreaterThan(0)
  })

  it('handles dashboard action clicks', async () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards,
        showDashboardActions: true
      }
    })

    const refreshButton = wrapper.find('.dashboard-action')
    await refreshButton.trigger('click')

    expect(wrapper.emitted('refresh')).toBeTruthy()
  })

  it('emits dashboard-select event when dashboard is selected', async () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards
      }
    })

    // Simulate dashboard selection from navigation component
    const navigation = wrapper.findComponent({ name: 'DashboardNavigation' })
    await navigation.vm.$emit('dashboard-changed', mockDashboards[1])

    expect(wrapper.emitted('dashboard-select')).toBeTruthy()
    expect(wrapper.emitted('dashboard-select')[0]).toEqual([mockDashboards[1]])
  })

  it('applies correct grid columns based on screen size', () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards,
        gridColumns: {
          mobile: 1,
          tablet: 2,
          desktop: 3,
          wide: 4
        }
      }
    })

    const grid = wrapper.find('.dashboard-grid')
    expect(grid.classes()).toContain('columns-3') // Desktop default
  })

  it('handles card updates correctly', async () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards
      }
    })

    // Simulate card update
    const cardData = { value: 5678 }
    await wrapper.vm.handleCardUpdate('card-1', cardData)

    expect(wrapper.emitted('card-update')).toBeTruthy()
    expect(wrapper.emitted('card-update')[0]).toEqual([{
      cardId: 'card-1',
      data: cardData
    }])
  })

  it('handles card errors correctly', async () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards
      }
    })

    // Simulate card error
    const error = 'Failed to load card data'
    await wrapper.vm.handleCardError('card-1', error)

    expect(wrapper.emitted('card-error')).toBeTruthy()
    expect(wrapper.emitted('card-error')[0]).toEqual([{
      cardId: 'card-1',
      error
    }])
  })

  it('applies card span classes correctly', () => {
    const cardsWithSpan = [
      {
        ...mockCards[0],
        span: 2
      },
      {
        ...mockCards[1],
        fullWidth: true
      }
    ]

    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: cardsWithSpan
      }
    })

    const cardContainers = wrapper.findAll('.dashboard-card-container')
    expect(cardContainers[0].classes()).toContain('card-span-2')
    expect(cardContainers[1].classes()).toContain('card-full-width')
  })

  it('shows filters when enabled and filters are provided', () => {
    const filters = [
      {
        key: 'date_range',
        type: 'date_range',
        label: 'Date Range'
      },
      {
        key: 'category',
        type: 'select',
        label: 'Category',
        options: ['all', 'active', 'inactive']
      }
    ]

    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards,
        filters,
        showFilters: true
      }
    })

    expect(wrapper.find('.dashboard-filters').exists()).toBe(true)
  })

  it('handles filter updates correctly', async () => {
    const filters = [
      {
        key: 'category',
        type: 'select',
        label: 'Category'
      }
    ]

    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards,
        filters,
        showFilters: true
      }
    })

    const newFilters = { category: 'active' }
    await wrapper.vm.handleFiltersUpdate(newFilters)

    expect(wrapper.emitted('filters-update')).toBeTruthy()
    expect(wrapper.emitted('filters-update')[0]).toEqual([newFilters])
  })

  it('shows floating action button on mobile when enabled', async () => {
    // Mock mobile environment
    vi.doMock('@/composables/useMobileNavigation', () => ({
      useMobileNavigation: () => ({
        isMobile: { value: true },
        isTablet: { value: false },
        isDesktop: { value: false },
        setup: vi.fn(),
        cleanup: vi.fn()
      })
    }))

    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards,
        showFAB: true
      }
    })

    expect(wrapper.find('.floating-action-button').exists()).toBe(true)
  })

  it('handles FAB click correctly', async () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards,
        showFAB: true
      }
    })

    const fab = wrapper.find('.floating-action-button')
    if (fab.exists()) {
      await fab.trigger('click')
      expect(wrapper.emitted('fab-click')).toBeTruthy()
    }
  })

  it('applies correct layout classes based on device type', () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards
      }
    })

    const layoutClasses = wrapper.classes()
    expect(layoutClasses).toContain('responsive-dashboard-layout-base')
    expect(layoutClasses).toContain('is-desktop')
  })

  it('handles refresh action correctly', async () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards
      }
    })

    await wrapper.vm.handleRefresh()

    expect(wrapper.emitted('refresh')).toBeTruthy()
  })

  it('manages pull-to-refresh state correctly', async () => {
    wrapper = mount(ResponsiveDashboardLayout, {
      props: {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard,
        dashboardCards: mockCards,
        enablePullToRefresh: true
      }
    })

    // Simulate pull to refresh
    wrapper.vm.isPulling = true
    await wrapper.vm.$nextTick()

    expect(wrapper.classes()).toContain('is-pulling')

    // Simulate refresh completion
    await wrapper.vm.handleRefresh()
    expect(wrapper.vm.isPulling).toBe(false)
  })
})
