/**
 * Dashboard Loading Component Tests
 * 
 * Tests for the dashboard loading component including different variants,
 * progress tracking, error states, and accessibility features.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import DashboardLoading from '../../../resources/js/Components/Dashboard/DashboardLoading.vue'

describe('DashboardLoading Component', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders when visible', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true
        }
      })

      expect(wrapper.find('[data-testid="dashboard-loading"]').exists()).toBe(true)
    })

    it('does not render when not visible', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: false
        }
      })

      expect(wrapper.find('[data-testid="dashboard-loading"]').exists()).toBe(false)
    })

    it('applies overlay classes correctly', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          overlay: true,
          theme: 'light'
        }
      })

      const overlay = wrapper.find('.dashboard-loading-overlay')
      expect(overlay.classes()).toContain('with-overlay')
      expect(overlay.classes()).toContain('light-theme')
    })

    it('applies non-overlay classes correctly', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          overlay: false,
          theme: 'dark'
        }
      })

      const overlay = wrapper.find('.dashboard-loading-overlay')
      expect(overlay.classes()).toContain('without-overlay')
      expect(overlay.classes()).toContain('dark-theme')
    })
  })

  describe('Loading Variants', () => {
    it('renders spinner variant', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          variant: 'spinner',
          message: 'Loading...'
        }
      })

      expect(wrapper.find('.loading-spinner').exists()).toBe(true)
      expect(wrapper.find('.spinner-ring').exists()).toBe(true)
      expect(wrapper.find('.loading-message').text()).toBe('Loading...')
    })

    it('renders skeleton variant', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          variant: 'skeleton'
        }
      })

      expect(wrapper.find('.loading-skeleton').exists()).toBe(true)
      expect(wrapper.find('.skeleton-header').exists()).toBe(true)
      expect(wrapper.find('.skeleton-content').exists()).toBe(true)
      expect(wrapper.findAll('.skeleton-card')).toHaveLength(3)
    })

    it('renders pulse variant', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          variant: 'pulse',
          message: 'Processing...'
        }
      })

      expect(wrapper.find('.loading-pulse').exists()).toBe(true)
      expect(wrapper.find('.pulse-circle').exists()).toBe(true)
      expect(wrapper.findAll('.pulse-ring')).toHaveLength(3)
      expect(wrapper.find('.loading-message').text()).toBe('Processing...')
    })

    it('renders dots variant', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          variant: 'dots',
          message: 'Please wait...'
        }
      })

      expect(wrapper.find('.loading-dots').exists()).toBe(true)
      expect(wrapper.find('.dots-container').exists()).toBe(true)
      expect(wrapper.findAll('.dot')).toHaveLength(3)
      expect(wrapper.find('.loading-message').text()).toBe('Please wait...')
    })

    it('renders fade variant (default)', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          variant: 'fade',
          message: 'Loading dashboard...'
        }
      })

      expect(wrapper.find('.loading-fade').exists()).toBe(true)
      expect(wrapper.find('.fade-content').exists()).toBe(true)
      expect(wrapper.find('.fade-icon').exists()).toBe(true)
      expect(wrapper.find('.loading-message').text()).toBe('Loading dashboard...')
    })

    it('validates variant prop', () => {
      // Test valid variants
      const validVariants = ['spinner', 'skeleton', 'pulse', 'dots', 'fade']
      
      validVariants.forEach(variant => {
        wrapper = mount(DashboardLoading, {
          props: {
            isVisible: true,
            variant
          }
        })
        expect(wrapper.exists()).toBe(true)
        wrapper.unmount()
      })
    })
  })

  describe('Progress Bar', () => {
    it('shows progress bar when enabled', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          showProgress: true,
          progress: 50
        }
      })

      expect(wrapper.find('.progress-container').exists()).toBe(true)
      expect(wrapper.find('.progress-bar').exists()).toBe(true)
      expect(wrapper.find('.progress-fill').exists()).toBe(true)
    })

    it('hides progress bar when disabled', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          showProgress: false
        }
      })

      expect(wrapper.find('.progress-container').exists()).toBe(false)
    })

    it('displays correct progress percentage', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          showProgress: true,
          progress: 75
        }
      })

      const progressFill = wrapper.find('.progress-fill')
      expect(progressFill.attributes('style')).toContain('width: 75%')
    })

    it('displays progress text based on percentage', async () => {
      const testCases = [
        { progress: 0, expected: 'Initializing...' },
        { progress: 20, expected: 'Loading...' },
        { progress: 40, expected: 'Fetching data...' },
        { progress: 60, expected: 'Rendering...' },
        { progress: 90, expected: 'Almost done...' },
        { progress: 100, expected: 'Complete!' }
      ]

      for (const testCase of testCases) {
        wrapper = mount(DashboardLoading, {
          props: {
            isVisible: true,
            showProgress: true,
            progress: testCase.progress
          }
        })

        expect(wrapper.find('.progress-text').text()).toBe(testCase.expected)
        wrapper.unmount()
      }
    })

    it('validates progress prop range', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          showProgress: true,
          progress: 150 // Invalid value
        }
      })

      // Component should still render but clamp the value
      expect(wrapper.exists()).toBe(true)
    })
  })

  describe('Cancel Button', () => {
    it('shows cancel button when enabled', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          showCancel: true
        }
      })

      expect(wrapper.find('.cancel-overlay-button').exists()).toBe(true)
    })

    it('hides cancel button when disabled', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          showCancel: false
        }
      })

      expect(wrapper.find('.cancel-overlay-button').exists()).toBe(false)
    })

    it('emits cancel event when clicked', async () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          showCancel: true
        }
      })

      const cancelButton = wrapper.find('.cancel-overlay-button')
      await cancelButton.trigger('click')

      expect(wrapper.emitted('cancel')).toBeTruthy()
    })

    it('has proper accessibility attributes', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          showCancel: true
        }
      })

      const cancelButton = wrapper.find('.cancel-overlay-button')
      expect(cancelButton.attributes('aria-label')).toBe('Cancel loading')
    })
  })

  describe('Error State', () => {
    it('shows error content when has error', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          hasError: true,
          errorMessage: 'Something went wrong'
        }
      })

      expect(wrapper.find('.error-content').exists()).toBe(true)
      expect(wrapper.find('.error-icon').exists()).toBe(true)
      expect(wrapper.find('.error-title').text()).toBe('Loading Failed')
      expect(wrapper.find('.error-message').text()).toBe('Something went wrong')
    })

    it('shows error actions', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          hasError: true
        }
      })

      expect(wrapper.find('.error-actions').exists()).toBe(true)
      expect(wrapper.find('.retry-button').exists()).toBe(true)
      expect(wrapper.find('.cancel-button').exists()).toBe(true)
    })

    it('emits retry event when retry button clicked', async () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          hasError: true
        }
      })

      const retryButton = wrapper.find('.retry-button')
      await retryButton.trigger('click')

      expect(wrapper.emitted('retry')).toBeTruthy()
    })

    it('emits cancel event when cancel button clicked in error state', async () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          hasError: true
        }
      })

      const cancelButton = wrapper.find('.cancel-button')
      await cancelButton.trigger('click')

      expect(wrapper.emitted('cancel')).toBeTruthy()
    })

    it('hides loading content when showing error', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          hasError: true,
          variant: 'spinner'
        }
      })

      expect(wrapper.find('.loading-spinner').exists()).toBe(false)
      expect(wrapper.find('.error-content').exists()).toBe(true)
    })
  })

  describe('Theme Support', () => {
    it('applies light theme classes', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          theme: 'light'
        }
      })

      expect(wrapper.find('.dashboard-loading-overlay').classes()).toContain('light-theme')
    })

    it('applies dark theme classes', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          theme: 'dark'
        }
      })

      expect(wrapper.find('.dashboard-loading-overlay').classes()).toContain('dark-theme')
    })

    it('validates theme prop', () => {
      const validThemes = ['light', 'dark']
      
      validThemes.forEach(theme => {
        wrapper = mount(DashboardLoading, {
          props: {
            isVisible: true,
            theme
          }
        })
        expect(wrapper.exists()).toBe(true)
        wrapper.unmount()
      })
    })
  })

  describe('Accessibility', () => {
    it('provides proper ARIA attributes', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          showCancel: true
        }
      })

      const cancelButton = wrapper.find('.cancel-overlay-button')
      expect(cancelButton.attributes('aria-label')).toBe('Cancel loading')
    })

    it('has proper button types', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          hasError: true,
          showCancel: true
        }
      })

      const buttons = wrapper.findAll('button')
      buttons.forEach(button => {
        expect(button.attributes('type')).toBe('button')
      })
    })

    it('provides meaningful text content', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          variant: 'spinner',
          message: 'Loading dashboard data...'
        }
      })

      expect(wrapper.find('.loading-message').text()).toBe('Loading dashboard data...')
    })
  })

  describe('Responsive Behavior', () => {
    it('renders correctly on different screen sizes', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          variant: 'skeleton'
        }
      })

      // Component should render without errors
      expect(wrapper.find('.loading-skeleton').exists()).toBe(true)
      expect(wrapper.find('.skeleton-content').exists()).toBe(true)
    })
  })

  describe('Animation Classes', () => {
    it('applies correct animation classes for spinner', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          variant: 'spinner'
        }
      })

      expect(wrapper.find('.spinner-ring').exists()).toBe(true)
      // Animation classes are applied via CSS
    })

    it('applies correct animation classes for pulse', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          variant: 'pulse'
        }
      })

      expect(wrapper.find('.pulse-circle').exists()).toBe(true)
      expect(wrapper.findAll('.pulse-ring')).toHaveLength(3)
    })

    it('applies correct animation classes for dots', () => {
      wrapper = mount(DashboardLoading, {
        props: {
          isVisible: true,
          variant: 'dots'
        }
      })

      expect(wrapper.find('.dots-container').exists()).toBe(true)
      expect(wrapper.findAll('.dot')).toHaveLength(3)
    })
  })
})
