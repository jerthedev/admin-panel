import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import AnalyticsCard from '@/admin-cards/AnalyticsCard.vue'
import { createMockCard } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('AnalyticsCard', () => {
  let wrapper
  let mockCard

  const mockAnalyticsData = {
    totalUsers: 15420,
    activeUsers: 12350,
    pageViews: 89750,
    conversionRate: 3.2,
    revenue: 45230.50,
    topPages: [
      { path: '/dashboard', views: 12500, percentage: 35.2 },
      { path: '/products', views: 8900, percentage: 25.1 },
      { path: '/about', views: 6200, percentage: 17.5 }
    ],
    deviceBreakdown: [
      { device: 'Desktop', users: 8500, percentage: 55.1 },
      { device: 'Mobile', users: 5200, percentage: 33.7 },
      { device: 'Tablet', users: 1720, percentage: 11.2 }
    ],
    lastUpdated: '2024-01-15T10:30:00Z'
  }

  beforeEach(() => {
    mockCard = createMockCard({
      name: 'Analytics Card',
      component: 'AnalyticsCard',
      uriKey: 'analytics-card',
      meta: {
        title: 'Analytics Overview',
        description: 'Key performance metrics and analytics data',
        icon: 'chart-bar',
        group: 'Analytics',
        refreshable: true,
        data: mockAnalyticsData
      }
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders the analytics card wrapper', () => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })

      expect(wrapper.find('.analytics-card').exists()).toBe(true)
    })

    it('renders the card title and description', () => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })

      expect(wrapper.find('h3').text()).toBe('Analytics Overview')
      expect(wrapper.find('p').text()).toBe('Key performance metrics and analytics data')
    })

    it('renders the group badge when provided', () => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })

      const groupBadge = wrapper.find('span')
      expect(groupBadge.text()).toBe('Analytics')
    })

    it('renders refresh button when refreshable is true', () => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })

      const refreshButton = wrapper.find('button[title="Refresh data"]')
      expect(refreshButton.exists()).toBe(true)
    })

    it('does not render refresh button when refreshable is false', () => {
      const nonRefreshableCard = {
        ...mockCard,
        meta: { ...mockCard.meta, refreshable: false }
      }

      wrapper = mount(AnalyticsCard, {
        props: { card: nonRefreshableCard }
      })

      const refreshButton = wrapper.find('button[title="Refresh data"]')
      expect(refreshButton.exists()).toBe(false)
    })
  })

  describe('Metrics Display', () => {
    beforeEach(() => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })
    })

    it('displays total users metric', () => {
      const metrics = wrapper.findAll('.metric-item')
      expect(metrics[0].text()).toContain('15.4K')
      expect(metrics[0].text()).toContain('Total Users')
    })

    it('displays active users metric', () => {
      const metrics = wrapper.findAll('.metric-item')
      expect(metrics[1].text()).toContain('12.4K')
      expect(metrics[1].text()).toContain('Active Users')
    })

    it('displays page views metric', () => {
      const metrics = wrapper.findAll('.metric-item')
      expect(metrics[2].text()).toContain('89.8K')
      expect(metrics[2].text()).toContain('Page Views')
    })

    it('displays revenue metric', () => {
      const metrics = wrapper.findAll('.metric-item')
      expect(metrics[3].text()).toContain('$45,230.50')
      expect(metrics[3].text()).toContain('Revenue')
    })

    it('displays conversion rate', () => {
      expect(wrapper.text()).toContain('3.2%')
      expect(wrapper.text()).toContain('Conversion Rate')
    })
  })

  describe('Top Pages Section', () => {
    beforeEach(() => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })
    })

    it('renders top pages section', () => {
      expect(wrapper.text()).toContain('Top Pages')
    })

    it('displays all top pages', () => {
      const topPages = mockAnalyticsData.topPages
      topPages.forEach(page => {
        expect(wrapper.text()).toContain(page.path)
        expect(wrapper.text()).toContain(`${page.percentage}%`)
      })
    })

    it('formats page view numbers correctly', () => {
      expect(wrapper.text()).toContain('12.5K views')
      expect(wrapper.text()).toContain('8.9K views')
      expect(wrapper.text()).toContain('6.2K views')
    })
  })

  describe('Device Breakdown Section', () => {
    beforeEach(() => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })
    })

    it('renders device breakdown section', () => {
      expect(wrapper.text()).toContain('Device Breakdown')
    })

    it('displays all device types', () => {
      const devices = mockAnalyticsData.deviceBreakdown
      devices.forEach(device => {
        expect(wrapper.text()).toContain(device.device)
        expect(wrapper.text()).toContain(`${device.percentage}%`)
      })
    })

    it('formats device user numbers correctly', () => {
      expect(wrapper.text()).toContain('8.5K') // Desktop users
      expect(wrapper.text()).toContain('5.2K') // Mobile users
      expect(wrapper.text()).toContain('1.7K') // Tablet users
    })
  })

  describe('Error Handling', () => {
    it('displays error message when error is set', async () => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })

      // Set error state
      await wrapper.setData({ error: 'Failed to load data' })

      expect(wrapper.find('.bg-red-50').exists()).toBe(true)
      expect(wrapper.text()).toContain('Error loading analytics data')
      expect(wrapper.text()).toContain('Failed to load data')
    })

    it('applies error styling when error is present', async () => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })

      await wrapper.setData({ error: 'Test error' })

      expect(wrapper.find('.card-error').exists()).toBe(true)
    })
  })

  describe('Loading State', () => {
    it('applies loading styling when loading', async () => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })

      await wrapper.setData({ isLoading: true })

      expect(wrapper.find('.card-loading').exists()).toBe(true)
    })

    it('shows spinning refresh icon when loading', async () => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })

      await wrapper.setData({ isLoading: true })

      const refreshIcon = wrapper.find('svg.animate-spin')
      expect(refreshIcon.exists()).toBe(true)
    })

    it('disables refresh button when loading', async () => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })

      await wrapper.setData({ isLoading: true })

      const refreshButton = wrapper.find('button[title="Refresh data"]')
      expect(refreshButton.attributes('disabled')).toBeDefined()
    })
  })

  describe('User Interactions', () => {
    beforeEach(() => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })
    })

    it('emits refresh event when refresh button is clicked', async () => {
      const refreshButton = wrapper.find('button[title="Refresh data"]')
      await refreshButton.trigger('click')

      expect(wrapper.emitted('refresh')).toBeTruthy()
      expect(wrapper.emitted('refresh')[0]).toEqual([mockCard])
    })

    it('emits configure event when configure button is clicked', async () => {
      const configureButton = wrapper.find('button:contains("Configure")')
      await configureButton.trigger('click')

      expect(wrapper.emitted('configure')).toBeTruthy()
      expect(wrapper.emitted('configure')[0]).toEqual([mockCard])
    })

    it('emits export event when export button is clicked', async () => {
      const exportButton = wrapper.find('button:contains("Export")')
      await exportButton.trigger('click')

      expect(wrapper.emitted('export')).toBeTruthy()
      expect(wrapper.emitted('export')[0]).toEqual([mockCard])
    })
  })

  describe('Data Formatting', () => {
    beforeEach(() => {
      wrapper = mount(AnalyticsCard, {
        props: { card: mockCard }
      })
    })

    it('formats large numbers with K suffix', () => {
      expect(wrapper.vm.formatNumber(15420)).toBe('15.4K')
      expect(wrapper.vm.formatNumber(1500)).toBe('1.5K')
    })

    it('formats millions with M suffix', () => {
      expect(wrapper.vm.formatNumber(1500000)).toBe('1.5M')
    })

    it('formats small numbers without suffix', () => {
      expect(wrapper.vm.formatNumber(500)).toBe('500')
    })

    it('formats currency correctly', () => {
      expect(wrapper.vm.formatCurrency(45230.50)).toBe('$45,230.50')
      expect(wrapper.vm.formatCurrency(0)).toBe('$0.00')
    })

    it('formats timestamps correctly', () => {
      const timestamp = '2024-01-15T10:30:00Z'
      const formatted = wrapper.vm.formatTimestamp(timestamp)
      expect(formatted).toMatch(/\d{1,2}\/\d{1,2}\/\d{4}/)
    })

    it('handles null timestamp', () => {
      expect(wrapper.vm.formatTimestamp(null)).toBe('Never')
    })
  })

  describe('Default Data Handling', () => {
    it('handles missing card data gracefully', () => {
      const cardWithoutData = {
        ...mockCard,
        meta: { ...mockCard.meta, data: undefined }
      }

      wrapper = mount(AnalyticsCard, {
        props: { card: cardWithoutData }
      })

      expect(wrapper.find('.analytics-card').exists()).toBe(true)
      expect(wrapper.text()).toContain('0') // Default values
    })

    it('uses default values when data properties are missing', () => {
      const cardWithPartialData = {
        ...mockCard,
        meta: {
          ...mockCard.meta,
          data: {
            totalUsers: 100
            // Missing other properties
          }
        }
      }

      wrapper = mount(AnalyticsCard, {
        props: { card: cardWithPartialData }
      })

      expect(wrapper.text()).toContain('100') // Provided value
      expect(wrapper.text()).toContain('$0.00') // Default revenue
    })
  })
})
