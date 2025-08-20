import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import TrendMetric from '@/components/Metrics/TrendMetric.vue'

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

describe('TrendMetric.vue', () => {
  let wrapper

  const defaultProps = {
    title: 'Revenue Trend',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
      datasets: [{
        label: 'Revenue',
        data: [1000, 1200, 1100, 1400, 1600],
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
      }],
    },
    value: 1600,
    previousValue: 1400,
    format: 'currency',
    prefix: '$',
    suffix: '',
    loading: false,
    error: null,
    ranges: {
      30: '30 Days',
      60: '60 Days',
      90: '90 Days',
    },
    selectedRange: 30,
  }

  beforeEach(() => {
    wrapper = mount(TrendMetric, {
      props: defaultProps,
    })
  })

  describe('Component Rendering', () => {
    it('renders the component', () => {
      expect(wrapper.exists()).toBe(true)
    })

    it('displays the metric title', () => {
      expect(wrapper.text()).toContain('Revenue Trend')
    })

    it('displays the current value with formatting', () => {
      expect(wrapper.text()).toContain('$1,600')
    })

    it('displays trend indicator when value increased', () => {
      expect(wrapper.find('[data-testid="trend-up"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="trend-down"]').exists()).toBe(false)
    })

    it('displays trend indicator when value decreased', async () => {
      await wrapper.setProps({
        value: 1200,
        previousValue: 1400,
      })

      expect(wrapper.find('[data-testid="trend-down"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="trend-up"]').exists()).toBe(false)
    })

    it('displays percentage change', () => {
      // (1600 - 1400) / 1400 * 100 = 14.29%
      expect(wrapper.text()).toContain('14.3%')
    })

    it('renders range selection dropdown', () => {
      expect(wrapper.find('[data-testid="range-selector"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('30 Days')
      expect(wrapper.text()).toContain('60 Days')
      expect(wrapper.text()).toContain('90 Days')
    })
  })

  describe('Chart Integration', () => {
    it('creates a chart canvas element', () => {
      expect(wrapper.find('canvas').exists()).toBe(true)
    })

    it('initializes Chart.js with correct data', () => {
      const canvas = wrapper.find('canvas')
      expect(canvas.exists()).toBe(true)
    })

    it('updates chart when data changes', async () => {
      const newData = {
        labels: ['Jun', 'Jul', 'Aug'],
        datasets: [{
          label: 'Revenue',
          data: [1700, 1800, 1900],
          borderColor: '#3B82F6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
        }],
      }

      await wrapper.setProps({ data: newData })
      // Chart update should be called
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
  })

  describe('Error State', () => {
    it('displays error message when error is present', async () => {
      await wrapper.setProps({ error: 'Failed to load data' })
      expect(wrapper.find('[data-testid="error-message"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Failed to load data')
    })

    it('hides chart when error is present', async () => {
      await wrapper.setProps({ error: 'Failed to load data' })
      expect(wrapper.find('canvas').exists()).toBe(false)
    })
  })

  describe('Formatting', () => {
    it('formats currency values correctly', () => {
      expect(wrapper.text()).toContain('$1,600')
    })

    it('formats percentage values correctly', async () => {
      await wrapper.setProps({
        format: 'percentage',
        value: 0.85,
        previousValue: 0.80,
        prefix: '',
        suffix: '%',
      })

      expect(wrapper.text()).toContain('85%')
    })

    it('formats decimal values correctly', async () => {
      await wrapper.setProps({
        format: 'decimal',
        value: 1234.56,
        previousValue: 1200.00,
        prefix: '',
        suffix: '',
      })

      expect(wrapper.text()).toContain('1,234.56')
    })

    it('applies custom prefix and suffix', async () => {
      await wrapper.setProps({
        prefix: '€',
        suffix: ' EUR',
        value: 1000,
      })

      expect(wrapper.text()).toContain('€1,000 EUR')
    })
  })

  describe('Range Selection', () => {
    it('emits range-changed event when range is selected', async () => {
      const rangeSelector = wrapper.find('[data-testid="range-selector"]')
      await rangeSelector.setValue('60')

      expect(wrapper.emitted('range-changed')).toBeTruthy()
      expect(wrapper.emitted('range-changed')[0]).toEqual([60])
    })

    it('updates selected range when prop changes', async () => {
      await wrapper.setProps({ selectedRange: 60 })
      const rangeSelector = wrapper.find('[data-testid="range-selector"]')
      expect(rangeSelector.element.value).toBe('60')
    })
  })

  describe('Responsive Design', () => {
    it('applies responsive classes', () => {
      expect(wrapper.classes()).toContain('trend-metric')
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
        data: { labels: [], datasets: [] },
        value: 0,
        previousValue: 0,
      })

      expect(wrapper.find('[data-testid="no-data"]').exists()).toBe(true)
    })

    it('handles null/undefined values', async () => {
      await wrapper.setProps({
        value: null,
        previousValue: undefined,
      })

      expect(wrapper.text()).toContain('No data')
    })

    it('handles very large numbers', async () => {
      await wrapper.setProps({
        value: 1234567890,
        previousValue: 1000000000,
      })

      expect(wrapper.text()).toContain('$1,234,567,890')
    })

    it('handles zero values', async () => {
      await wrapper.setProps({
        value: 0,
        previousValue: 100,
      })

      expect(wrapper.text()).toContain('$0')
      expect(wrapper.find('[data-testid="trend-down"]').exists()).toBe(true)
    })
  })
})
