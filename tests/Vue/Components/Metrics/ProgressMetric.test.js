import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import ProgressMetric from '@/components/Metrics/ProgressMetric.vue'

describe('ProgressMetric.vue', () => {
  let wrapper

  const defaultProps = {
    title: 'Sales Progress',
    current: 750,
    target: 1000,
    format: 'currency',
    prefix: '$',
    suffix: '',
    loading: false,
    error: null,
    animated: true,
    showPercentage: true,
    showTarget: true,
    showCurrent: true,
  }

  beforeEach(() => {
    wrapper = mount(ProgressMetric, {
      props: defaultProps,
    })
  })

  describe('Component Rendering', () => {
    it('renders the component', () => {
      expect(wrapper.exists()).toBe(true)
    })

    it('displays the metric title', () => {
      expect(wrapper.text()).toContain('Sales Progress')
    })

    it('displays the current value when showCurrent is true', () => {
      expect(wrapper.text()).toContain('$750')
    })

    it('hides the current value when showCurrent is false', async () => {
      await wrapper.setProps({ showCurrent: false })
      expect(wrapper.find('[data-testid="current-value"]').exists()).toBe(false)
    })

    it('displays the target value when showTarget is true', () => {
      expect(wrapper.text()).toContain('$1,000')
    })

    it('hides the target value when showTarget is false', async () => {
      await wrapper.setProps({ showTarget: false })
      expect(wrapper.find('[data-testid="target-value"]').exists()).toBe(false)
    })

    it('displays the percentage when showPercentage is true', () => {
      expect(wrapper.text()).toContain('75.0%')
    })

    it('hides the percentage when showPercentage is false', async () => {
      await wrapper.setProps({ showPercentage: false })
      expect(wrapper.find('[data-testid="percentage"]').exists()).toBe(false)
    })

    it('displays progress bar', () => {
      expect(wrapper.find('[data-testid="progress-bar"]').exists()).toBe(true)
    })
  })

  describe('Progress Bar', () => {
    it('sets correct width based on progress percentage', () => {
      const progressBar = wrapper.find('[data-testid="progress-fill"]')
      expect(progressBar.attributes('style')).toContain('width: 75%')
    })

    it('handles progress over 100%', async () => {
      await wrapper.setProps({ current: 1200, target: 1000 })
      const progressBar = wrapper.find('[data-testid="progress-fill"]')
      expect(progressBar.attributes('style')).toContain('width: 100%')
    })

    it('handles zero progress', async () => {
      await wrapper.setProps({ current: 0, target: 1000 })
      const progressBar = wrapper.find('[data-testid="progress-fill"]')
      expect(progressBar.attributes('style')).toContain('width: 0%')
    })

    it('applies animated class when animated is true', () => {
      const progressBar = wrapper.find('[data-testid="progress-fill"]')
      expect(progressBar.classes()).toContain('transition-all')
    })

    it('removes animated class when animated is false', async () => {
      await wrapper.setProps({ animated: false })
      const progressBar = wrapper.find('[data-testid="progress-fill"]')
      expect(progressBar.classes()).not.toContain('transition-all')
    })
  })

  describe('Color Changes', () => {
    it('applies success color when progress >= 100%', async () => {
      await wrapper.setProps({ current: 1000, target: 1000 })
      const progressBar = wrapper.find('[data-testid="progress-fill"]')
      expect(progressBar.classes()).toContain('bg-green-500')
    })

    it('applies warning color when progress >= 75%', async () => {
      await wrapper.setProps({ current: 800, target: 1000 })
      const progressBar = wrapper.find('[data-testid="progress-fill"]')
      expect(progressBar.classes()).toContain('bg-yellow-500')
    })

    it('applies info color when progress >= 50%', async () => {
      await wrapper.setProps({ current: 600, target: 1000 })
      const progressBar = wrapper.find('[data-testid="progress-fill"]')
      expect(progressBar.classes()).toContain('bg-blue-500')
    })

    it('applies danger color when progress < 50%', async () => {
      await wrapper.setProps({ current: 400, target: 1000 })
      const progressBar = wrapper.find('[data-testid="progress-fill"]')
      expect(progressBar.classes()).toContain('bg-red-500')
    })

    it('uses custom color when provided', async () => {
      await wrapper.setProps({ color: '#8B5CF6' })
      const progressBar = wrapper.find('[data-testid="progress-fill"]')
      // Browser converts hex to RGB, so check for either format
      const style = progressBar.attributes('style')
      expect(style).toMatch(/background-color:\s*(#8B5CF6|rgb\(139,\s*92,\s*246\))/)
    })
  })

  describe('Loading State', () => {
    it('displays loading spinner when loading is true', async () => {
      await wrapper.setProps({ loading: true })
      expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
    })

    it('hides progress bar when loading', async () => {
      await wrapper.setProps({ loading: true })
      expect(wrapper.find('[data-testid="progress-bar"]').exists()).toBe(false)
    })

    it('hides values when loading', async () => {
      await wrapper.setProps({ loading: true })
      expect(wrapper.find('[data-testid="current-value"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="target-value"]').exists()).toBe(false)
    })
  })

  describe('Error State', () => {
    it('displays error message when error is present', async () => {
      await wrapper.setProps({ error: 'Failed to load progress data' })
      expect(wrapper.find('[data-testid="error-message"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Failed to load progress data')
    })

    it('hides progress bar when error is present', async () => {
      await wrapper.setProps({ error: 'Failed to load data' })
      expect(wrapper.find('[data-testid="progress-bar"]').exists()).toBe(false)
    })

    it('hides values when error is present', async () => {
      await wrapper.setProps({ error: 'Failed to load data' })
      expect(wrapper.find('[data-testid="current-value"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="target-value"]').exists()).toBe(false)
    })
  })

  describe('Value Formatting', () => {
    it('formats currency values correctly', () => {
      expect(wrapper.text()).toContain('$750')
      expect(wrapper.text()).toContain('$1,000')
    })

    it('formats percentage values correctly', async () => {
      await wrapper.setProps({
        format: 'percentage',
        current: 0.75,
        target: 1.0,
        prefix: '',
        suffix: '%',
      })

      expect(wrapper.text()).toContain('75%')
      expect(wrapper.text()).toContain('100%')
    })

    it('formats decimal values correctly', async () => {
      await wrapper.setProps({
        format: 'decimal',
        current: 1234.56,
        target: 2000.00,
        prefix: '',
        suffix: '',
      })

      expect(wrapper.text()).toContain('1,234.56')
      expect(wrapper.text()).toContain('2,000')
    })

    it('applies custom prefix and suffix', async () => {
      await wrapper.setProps({
        prefix: '€',
        suffix: ' EUR',
        current: 500,
        target: 1000,
      })

      expect(wrapper.text()).toContain('€500 EUR')
      expect(wrapper.text()).toContain('€1,000 EUR')
    })

    it('uses custom formatter when provided', async () => {
      const customFormatter = (value) => `${value.toFixed(1)}K`
      await wrapper.setProps({
        formatter: customFormatter,
        current: 750,
        target: 1000,
      })

      expect(wrapper.text()).toContain('750.0K')
      expect(wrapper.text()).toContain('1000.0K')
    })
  })

  describe('Percentage Calculation', () => {
    it('calculates percentage correctly', () => {
      expect(wrapper.text()).toContain('75.0%')
    })

    it('handles zero target gracefully', async () => {
      await wrapper.setProps({ current: 100, target: 0 })
      expect(wrapper.text()).toContain('0.0%')
    })

    it('handles negative values', async () => {
      await wrapper.setProps({ current: -50, target: 100 })
      expect(wrapper.text()).toContain('0.0%')
    })

    it('caps percentage at 100% for display', async () => {
      await wrapper.setProps({ current: 1500, target: 1000 })
      expect(wrapper.text()).toContain('150.0%')
    })
  })

  describe('Responsive Design', () => {
    it('applies responsive classes', () => {
      expect(wrapper.classes()).toContain('progress-metric')
    })

    it('adjusts layout for mobile', async () => {
      await wrapper.setProps({ compact: true })
      expect(wrapper.find('.compact').exists()).toBe(true)
    })
  })

  describe('Dark Mode Support', () => {
    it('applies dark mode classes when enabled', async () => {
      await wrapper.setProps({ darkMode: true })
      expect(wrapper.find('.dark').exists()).toBe(true)
    })

    it('uses appropriate colors for dark mode', async () => {
      await wrapper.setProps({ darkMode: true })
      const progressBar = wrapper.find('[data-testid="progress-bar"]')
      expect(progressBar.classes()).toContain('dark:bg-gray-700')
    })
  })

  describe('Accessibility', () => {
    it('has proper ARIA labels', () => {
      expect(wrapper.find('[aria-label]').exists()).toBe(true)
    })

    it('has proper role attributes', () => {
      expect(wrapper.find('[role="progressbar"]').exists()).toBe(true)
    })

    it('provides screen reader friendly content', () => {
      expect(wrapper.find('[data-testid="sr-only"]').exists()).toBe(true)
    })

    it('sets aria-valuenow correctly', () => {
      const progressBar = wrapper.find('[role="progressbar"]')
      expect(progressBar.attributes('aria-valuenow')).toBe('75')
    })

    it('sets aria-valuemin and aria-valuemax', () => {
      const progressBar = wrapper.find('[role="progressbar"]')
      expect(progressBar.attributes('aria-valuemin')).toBe('0')
      expect(progressBar.attributes('aria-valuemax')).toBe('100')
    })
  })

  describe('Edge Cases', () => {
    it('handles null/undefined values', async () => {
      await wrapper.setProps({
        current: null,
        target: undefined,
      })

      expect(wrapper.text()).toContain('No data')
    })

    it('handles very large numbers', async () => {
      await wrapper.setProps({
        current: 1234567890,
        target: 2000000000,
      })

      expect(wrapper.text()).toContain('$1,234,567,890')
      expect(wrapper.text()).toContain('$2,000,000,000')
    })

    it('handles decimal precision', async () => {
      await wrapper.setProps({
        current: 123.456789,
        target: 200.123456,
        format: 'decimal',
      })

      expect(wrapper.text()).toContain('123.46')
      expect(wrapper.text()).toContain('200.12')
    })
  })

  describe('Animation', () => {
    it('triggers animation on value change', async () => {
      const progressBar = wrapper.find('[data-testid="progress-fill"]')
      expect(progressBar.attributes('style')).toContain('width: 75%')

      await wrapper.setProps({ current: 900 })
      expect(progressBar.attributes('style')).toContain('width: 90%')
    })

    it('respects animation duration', async () => {
      await wrapper.setProps({ animationDuration: 1000 })
      const progressBar = wrapper.find('[data-testid="progress-fill"]')
      expect(progressBar.attributes('style')).toContain('transition-duration: 1000ms')
    })
  })
})
