import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import Card from '@/components/Cards/Card.vue'
import { createMockCard } from '../../../helpers.js'

// Mock the admin store
const mockAdminStore = { isDarkTheme: false }
vi.mock('@/stores/admin', () => ({ useAdminStore: () => mockAdminStore }))

describe('Integration: Card (PHP <-> Vue)', () => {
  let wrapper
  let card

  beforeEach(() => {
    card = {
      name: 'Dashboard Stats',
      component: 'DashboardStatsCard',
      uriKey: 'dashboard-stats',
      meta: {
        title: 'Key Metrics',
        description: 'Overview of important statistics',
        icon: 'ChartBarIcon',
        refreshInterval: 60,
        chartType: 'bar',
        data: {
          users: 1250,
          orders: 89,
          revenue: 15420.50
        },
        permissions: ['view_dashboard'],
        refreshable: true
      }
    }
  })

  afterEach(() => { if (wrapper) wrapper.unmount() })

  it('renders card with PHP serialized data structure', () => {
    wrapper = mount(Card, { 
      props: { card }
    })

    // Verify card structure matches PHP Card::jsonSerialize()
    expect(wrapper.vm.card.name).toBe('Dashboard Stats')
    expect(wrapper.vm.card.component).toBe('DashboardStatsCard')
    expect(wrapper.vm.card.uriKey).toBe('dashboard-stats')
    expect(wrapper.vm.card.meta).toEqual(expect.objectContaining({
      title: 'Key Metrics',
      description: 'Overview of important statistics',
      refreshable: true
    }))
  })

  it('displays title from meta.title with fallback to card.name', () => {
    wrapper = mount(Card, { props: { card } })

    const title = wrapper.find('h3')
    expect(title.text()).toBe('Key Metrics') // From meta.title

    // Test fallback to card.name
    const cardWithoutMetaTitle = { ...card, meta: { ...card.meta, title: undefined } }
    wrapper = mount(Card, { props: { card: cardWithoutMetaTitle } })
    
    const fallbackTitle = wrapper.find('h3')
    expect(fallbackTitle.text()).toBe('Dashboard Stats') // From card.name
  })

  it('handles complex meta data from PHP withMeta() method', () => {
    wrapper = mount(Card, { props: { card } })

    // Verify complex nested data structure
    expect(wrapper.vm.card.meta.data.users).toBe(1250)
    expect(wrapper.vm.card.meta.data.orders).toBe(89)
    expect(wrapper.vm.card.meta.data.revenue).toBe(15420.50)
    expect(wrapper.vm.card.meta.permissions).toEqual(['view_dashboard'])
    expect(wrapper.vm.card.meta.chartType).toBe('bar')
  })

  it('integrates with dashboard context for authorization', () => {
    const mockDashboardContext = {
      user: { permissions: ['view_dashboard'] },
      canViewCard: vi.fn(() => true)
    }

    wrapper = mount(Card, {
      props: { card },
      global: {
        provide: {
          dashboardContext: mockDashboardContext
        }
      }
    })

    // Card should be visible with proper permissions
    expect(wrapper.find('.relative').exists()).toBe(true)
  })

  it('emits events compatible with PHP authorization callbacks', async () => {
    wrapper = mount(Card, {
      props: { 
        card,
        clickable: true
      }
    })

    await wrapper.trigger('click')

    // Verify event structure matches what PHP canSee() callback expects
    const clickEvents = wrapper.emitted('click')
    expect(clickEvents).toBeTruthy()
    expect(clickEvents[0][1]).toEqual(card) // Card object passed to event
  })

  it('handles refresh functionality for refreshable cards', async () => {
    wrapper = mount(Card, {
      props: { 
        card,
        refreshable: true
      }
    })

    // Test refresh method exposure
    expect(wrapper.vm.refresh).toBeDefined()
    
    // Simulate refresh call
    wrapper.vm.refresh()
    
    const refreshEvents = wrapper.emitted('refresh')
    expect(refreshEvents).toBeTruthy()
    expect(refreshEvents[0][0]).toEqual(card)
  })

  it('supports Nova-compatible styling variants', () => {
    const variants = ['default', 'bordered', 'elevated', 'flat']
    
    variants.forEach(variant => {
      wrapper = mount(Card, {
        props: { card, variant }
      })
      
      const cardElement = wrapper.find('.relative')
      expect(cardElement.exists()).toBe(true)
      
      // Each variant should apply different styling
      if (variant === 'bordered') {
        expect(cardElement.classes()).toContain('border-2')
      } else if (variant === 'elevated') {
        expect(cardElement.classes()).toContain('shadow-lg')
      }
      
      wrapper.unmount()
    })
  })

  it('integrates with Laravel request context through props', () => {
    // Simulate data that would come from Laravel controller
    const laravelCardData = {
      name: 'User Statistics',
      component: 'UserStatsCard',
      uriKey: 'user-statistics',
      meta: {
        title: 'User Metrics',
        data: {
          totalUsers: 5420,
          activeUsers: 3210,
          newUsersToday: 45
        },
        lastUpdated: '2024-01-15T10:30:00Z',
        refreshInterval: 300
      }
    }

    wrapper = mount(Card, {
      props: { card: laravelCardData }
    })

    // Verify Laravel data structure is properly handled
    expect(wrapper.vm.card.meta.data.totalUsers).toBe(5420)
    expect(wrapper.vm.card.meta.lastUpdated).toBe('2024-01-15T10:30:00Z')
  })

  it('handles loading states for async card operations', async () => {
    wrapper = mount(Card, {
      props: { 
        card,
        loading: true
      }
    })

    // Loading overlay should be visible
    const loadingOverlay = wrapper.find('.absolute.inset-0')
    expect(loadingOverlay.exists()).toBe(true)
    
    // Card should have reduced opacity
    const cardElement = wrapper.find('.relative')
    expect(cardElement.classes()).toContain('opacity-75')

    // Click events should be disabled during loading
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeFalsy()
  })

  it('supports dark theme integration with admin store', async () => {
    mockAdminStore.isDarkTheme = true

    wrapper = mount(Card, {
      props: { card }
    })

    // Dark theme classes should be applied
    const cardElement = wrapper.find('.relative')
    expect(cardElement.classes()).toContain('dark:bg-gray-800')
    
    const title = wrapper.find('h3')
    expect(title.classes()).toContain('dark:text-gray-100')
  })

  it('validates card data structure from PHP serialization', () => {
    // Test with invalid card structure (missing required fields)
    const invalidCard = { name: 'Invalid' } // Missing component and uriKey

    const cardProp = Card.props.card
    expect(cardProp.validator(invalidCard)).toBe(false)

    // Test with valid card structure
    expect(cardProp.validator(card)).toBe(true)
  })

  it('handles slot content for custom card implementations', () => {
    wrapper = mount(Card, {
      props: { card },
      slots: {
        default: `
          <div class="custom-content">
            <p>Users: 1250</p>
            <p>Revenue: $15420.50</p>
          </div>
        `,
        actions: '<button class="refresh-btn">Refresh</button>',
        footer: '<div class="last-updated">Last updated: 5 minutes ago</div>'
      }
    })

    expect(wrapper.find('.custom-content').exists()).toBe(true)
    expect(wrapper.find('.refresh-btn').exists()).toBe(true)
    expect(wrapper.find('.last-updated').exists()).toBe(true)
  })

  it('maintains performance with large meta data sets', () => {
    // Create card with large meta data (similar to PHP performance test)
    const largeMeta = {}
    for (let i = 0; i < 1000; i++) {
      largeMeta[`item_${i}`] = {
        id: i,
        name: `Item ${i}`,
        data: Array(100).fill(`data_${i}`)
      }
    }

    const cardWithLargeMeta = {
      ...card,
      meta: { ...card.meta, ...largeMeta }
    }

    const startTime = performance.now()
    wrapper = mount(Card, {
      props: { card: cardWithLargeMeta }
    })
    const endTime = performance.now()

    // Should render efficiently even with large data
    expect(endTime - startTime).toBeLessThan(100) // Less than 100ms
    expect(wrapper.vm.card.meta).toEqual(expect.objectContaining(largeMeta))
  })
})
