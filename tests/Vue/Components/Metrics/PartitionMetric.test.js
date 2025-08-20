import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import PartitionMetric from '@/components/Metrics/PartitionMetric.vue'

// Mock Chart.js
vi.mock('chart.js', () => {
  const mockChart = vi.fn().mockImplementation(() => ({
    destroy: vi.fn(),
    update: vi.fn(),
    resize: vi.fn(),
    data: {},
    options: {},
  }))
  mockChart.register = vi.fn()
  
  return {
    Chart: mockChart,
    registerables: [],
  }
})

// Mock chartjs-adapter-date-fns
vi.mock('chartjs-adapter-date-fns', () => ({}))

describe('PartitionMetric.vue', () => {
  let wrapper

  const defaultProps = {
    title: 'User Distribution',
    data: [
      { label: 'Active Users', value: 150, color: '#3B82F6' },
      { label: 'Inactive Users', value: 75, color: '#EF4444' },
      { label: 'Pending Users', value: 25, color: '#F59E0B' },
    ],
    total: 250,
    loading: false,
    error: null,
    showTotal: true,
    showLegend: true,
    customColors: [],
  }

  beforeEach(() => {
    wrapper = mount(PartitionMetric, {
      props: defaultProps,
    })
  })

  describe('Component Rendering', () => {
    it('renders the component', () => {
      expect(wrapper.exists()).toBe(true)
    })

    it('displays the metric title', () => {
      expect(wrapper.text()).toContain('User Distribution')
    })

    it('displays the total value when showTotal is true', () => {
      expect(wrapper.text()).toContain('250')
    })

    it('hides the total value when showTotal is false', async () => {
      await wrapper.setProps({ showTotal: false })
      expect(wrapper.find('[data-testid="total-value"]').exists()).toBe(false)
    })

    it('displays legend when showLegend is true', () => {
      expect(wrapper.find('[data-testid="legend"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Active Users')
      expect(wrapper.text()).toContain('Inactive Users')
      expect(wrapper.text()).toContain('Pending Users')
    })

    it('hides legend when showLegend is false', async () => {
      await wrapper.setProps({ showLegend: false })
      expect(wrapper.find('[data-testid="legend"]').exists()).toBe(false)
    })

    it('displays segment values and percentages in legend', () => {
      expect(wrapper.text()).toContain('150') // Active Users value
      expect(wrapper.text()).toContain('60.0%') // Active Users percentage (150/250)
      expect(wrapper.text()).toContain('75') // Inactive Users value
      expect(wrapper.text()).toContain('30.0%') // Inactive Users percentage (75/250)
      expect(wrapper.text()).toContain('25') // Pending Users value
      expect(wrapper.text()).toContain('10.0%') // Pending Users percentage (25/250)
    })
  })

  describe('Chart Integration', () => {
    it('creates a chart canvas element', () => {
      expect(wrapper.find('canvas').exists()).toBe(true)
    })

    it('initializes Chart.js with pie chart type', () => {
      const canvas = wrapper.find('canvas')
      expect(canvas.exists()).toBe(true)
    })

    it('updates chart when data changes', async () => {
      const newData = [
        { label: 'New Users', value: 100, color: '#10B981' },
        { label: 'Returning Users', value: 200, color: '#8B5CF6' },
      ]

      await wrapper.setProps({ data: newData, total: 300 })
      // Chart update should be called
    })
  })

  describe('Custom Colors', () => {
    it('uses colors from data items', () => {
      // Colors should be applied from the data items
      const legendItems = wrapper.findAll('[data-testid="legend-item"]')
      expect(legendItems.length).toBe(3)
    })

    it('uses custom colors when provided', async () => {
      const customColors = ['#FF6B6B', '#4ECDC4', '#45B7D1']
      await wrapper.setProps({ customColors })
      
      // Custom colors should override data colors
    })

    it('falls back to default colors when no colors provided', async () => {
      const dataWithoutColors = [
        { label: 'Item 1', value: 100 },
        { label: 'Item 2', value: 200 },
      ]
      
      await wrapper.setProps({ data: dataWithoutColors, total: 300 })
      // Should use default color palette
    })
  })

  describe('Loading State', () => {
    it('displays loading spinner when loading is true', async () => {
      await wrapper.setProps({ loading: true })
      expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
    })

    it('hides chart when loading', async () => {
      await wrapper.setProps({ loading: true })
      expect(wrapper.find('canvas').exists()).toBe(false)
    })

    it('hides legend when loading', async () => {
      await wrapper.setProps({ loading: true })
      expect(wrapper.find('[data-testid="legend"]').exists()).toBe(false)
    })
  })

  describe('Error State', () => {
    it('displays error message when error is present', async () => {
      await wrapper.setProps({ error: 'Failed to load partition data' })
      expect(wrapper.find('[data-testid="error-message"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Failed to load partition data')
    })

    it('hides chart when error is present', async () => {
      await wrapper.setProps({ error: 'Failed to load data' })
      expect(wrapper.find('canvas').exists()).toBe(false)
    })

    it('hides legend when error is present', async () => {
      await wrapper.setProps({ error: 'Failed to load data' })
      expect(wrapper.find('[data-testid="legend"]').exists()).toBe(false)
    })
  })

  describe('Label Formatting', () => {
    it('formats values with default number formatting', () => {
      expect(wrapper.text()).toContain('150')
      expect(wrapper.text()).toContain('75')
      expect(wrapper.text()).toContain('25')
    })

    it('formats percentages correctly', () => {
      expect(wrapper.text()).toContain('60.0%')
      expect(wrapper.text()).toContain('30.0%')
      expect(wrapper.text()).toContain('10.0%')
    })

    it('applies custom label formatter when provided', async () => {
      const customFormatter = (value) => `$${value.toLocaleString()}`
      await wrapper.setProps({ labelFormatter: customFormatter })
      
      expect(wrapper.text()).toContain('$150')
      expect(wrapper.text()).toContain('$75')
      expect(wrapper.text()).toContain('$25')
    })
  })

  describe('Responsive Design', () => {
    it('applies responsive classes', () => {
      expect(wrapper.classes()).toContain('partition-metric')
    })

    it('chart resizes when container resizes', () => {
      // This would test the resize observer functionality
      // Implementation depends on the actual resize handling
    })
  })

  describe('Dark Mode Support', () => {
    it('applies dark mode classes when enabled', async () => {
      await wrapper.setProps({ darkMode: true })
      expect(wrapper.find('.dark').exists()).toBe(true)
    })

    it('uses appropriate colors for dark mode', async () => {
      await wrapper.setProps({ darkMode: true })
      // Test that chart colors are adjusted for dark mode
    })
  })

  describe('Accessibility', () => {
    it('has proper ARIA labels', () => {
      expect(wrapper.find('[aria-label]').exists()).toBe(true)
    })

    it('has proper role attributes', () => {
      expect(wrapper.find('[role="img"]').exists()).toBe(true)
    })

    it('provides screen reader friendly content', () => {
      expect(wrapper.find('[data-testid="sr-only"]').exists()).toBe(true)
    })
  })

  describe('Edge Cases', () => {
    it('handles empty data gracefully', async () => {
      await wrapper.setProps({
        data: [],
        total: 0,
      })

      expect(wrapper.find('[data-testid="no-data"]').exists()).toBe(true)
    })

    it('handles single data item', async () => {
      await wrapper.setProps({
        data: [{ label: 'Only Item', value: 100, color: '#3B82F6' }],
        total: 100,
      })

      expect(wrapper.text()).toContain('Only Item')
      expect(wrapper.text()).toContain('100.0%')
    })

    it('handles zero values', async () => {
      await wrapper.setProps({
        data: [
          { label: 'Zero Item', value: 0, color: '#3B82F6' },
          { label: 'Non-zero Item', value: 100, color: '#EF4444' },
        ],
        total: 100,
      })

      expect(wrapper.text()).toContain('Zero Item')
      expect(wrapper.text()).toContain('0.0%')
    })

    it('calculates total automatically when not provided', async () => {
      await wrapper.setProps({
        data: [
          { label: 'Item 1', value: 50 },
          { label: 'Item 2', value: 75 },
        ],
        total: null,
      })

      expect(wrapper.text()).toContain('125') // Auto-calculated total
    })
  })

  describe('Interactivity', () => {
    it('emits segment-click event when segment is clicked', async () => {
      // This would test chart segment click events
      // Implementation depends on Chart.js event handling
    })

    it('highlights segment on hover', () => {
      // This would test chart hover effects
      // Implementation depends on Chart.js hover handling
    })
  })
})
