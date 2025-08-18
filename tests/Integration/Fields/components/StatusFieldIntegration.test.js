import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import StatusField from '@/components/Fields/StatusField.vue'
import { createMockField } from '../../../helpers.js'

/**
 * Status Field Integration Tests
 *
 * Tests the integration between the PHP Status field class and Vue component,
 * ensuring proper data flow, API compatibility, and Nova-style behavior.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false,
  fullscreenMode: false,
  sidebarCollapsed: false,
  toggleDarkTheme: vi.fn(),
  toggleFullscreen: vi.fn(),
  toggleSidebar: vi.fn()
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('StatusField Integration', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP to Vue Integration', () => {
    it('receives and processes PHP field configuration correctly', async () => {
      const phpFieldConfig = createMockField({
        name: 'Job Status',
        attribute: 'status',
        component: 'StatusField',
        builtInTypes: {
          loading: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
          failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
          success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
          default: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        },
        builtInIcons: {
          loading: 'spinner',
          failed: 'exclamation-circle',
          success: 'check-circle',
          default: 'information-circle',
        },
        loadingWhen: ['waiting', 'running'],
        failedWhen: ['failed', 'error'],
        successWhen: ['completed', 'done'],
        customTypes: {
          'loading': 'bg-blue-50 text-blue-700 ring-blue-600/20',
          'failed': 'bg-red-50 text-red-700 ring-red-600/20'
        },
        customIcons: {
          'loading': 'clock',
          'failed': 'exclamation-triangle',
          'success': 'check-circle'
        },
        withIcons: true,
        labelMap: {
          'waiting': 'Waiting in Queue',
          'running': 'Currently Processing',
          'completed': 'Successfully Completed'
        }
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'waiting'
        }
      })

      // Verify PHP configuration is properly received
      expect(wrapper.vm.field.name).toBe('Job Status')
      expect(wrapper.vm.field.loadingWhen).toEqual(['waiting', 'running'])
      expect(wrapper.vm.field.failedWhen).toEqual(['failed', 'error'])
      expect(wrapper.vm.field.successWhen).toEqual(['completed', 'done'])
      expect(wrapper.vm.field.customTypes).toEqual({
        'loading': 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'failed': 'bg-red-50 text-red-700 ring-red-600/20'
      })
      expect(wrapper.vm.field.customIcons).toEqual({
        'loading': 'clock',
        'failed': 'exclamation-triangle',
        'success': 'check-circle'
      })
      expect(wrapper.vm.field.withIcons).toBe(true)
      expect(wrapper.vm.field.labelMap).toEqual({
        'waiting': 'Waiting in Queue',
        'running': 'Currently Processing',
        'completed': 'Successfully Completed'
      })
    })

    it('correctly processes Nova API loadingWhen() method output', async () => {
      const phpFieldConfig = createMockField({
        loadingWhen: ['waiting', 'running', 'processing']
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'waiting'
        }
      })

      expect(wrapper.vm.statusType).toBe('loading')

      // Test different loading values
      await wrapper.setProps({ modelValue: 'running' })
      expect(wrapper.vm.statusType).toBe('loading')

      await wrapper.setProps({ modelValue: 'processing' })
      expect(wrapper.vm.statusType).toBe('loading')
    })

    it('correctly processes Nova API failedWhen() method output', async () => {
      const phpFieldConfig = createMockField({
        failedWhen: ['failed', 'error', 'cancelled']
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'failed'
        }
      })

      expect(wrapper.vm.statusType).toBe('failed')

      // Test different failed values
      await wrapper.setProps({ modelValue: 'error' })
      expect(wrapper.vm.statusType).toBe('failed')

      await wrapper.setProps({ modelValue: 'cancelled' })
      expect(wrapper.vm.statusType).toBe('failed')
    })

    it('correctly processes Nova API successWhen() method output', async () => {
      const phpFieldConfig = createMockField({
        successWhen: ['completed', 'finished', 'done']
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'completed'
        }
      })

      expect(wrapper.vm.statusType).toBe('success')

      // Test different success values
      await wrapper.setProps({ modelValue: 'finished' })
      expect(wrapper.vm.statusType).toBe('success')

      await wrapper.setProps({ modelValue: 'done' })
      expect(wrapper.vm.statusType).toBe('success')
    })

    it('correctly processes Nova API types() method output', async () => {
      const phpFieldConfig = createMockField({
        loadingWhen: ['waiting'],
        customTypes: {
          'loading': 'bg-blue-50 text-blue-700 ring-blue-600/20 font-medium'
        }
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'waiting'
        }
      })

      expect(wrapper.vm.statusClasses).toBe('bg-blue-50 text-blue-700 ring-blue-600/20 font-medium')
    })

    it('correctly processes Nova API withIcons() method output', async () => {
      const phpFieldConfig = createMockField({
        withIcons: true,
        loadingWhen: ['waiting'],
        customIcons: {
          'loading': 'clock'
        }
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'waiting'
        }
      })

      expect(wrapper.vm.showIcon).toBe(true)
      expect(wrapper.vm.iconName).toBe('clock')
    })

    it('correctly processes Nova API icons() method output', async () => {
      const phpFieldConfig = createMockField({
        withIcons: true,
        loadingWhen: ['waiting'],
        failedWhen: ['failed'],
        successWhen: ['completed'],
        customIcons: {
          'loading': 'clock',
          'failed': 'exclamation-triangle',
          'success': 'check-circle'
        }
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'waiting'
        }
      })

      expect(wrapper.vm.iconName).toBe('clock')

      await wrapper.setProps({ modelValue: 'failed' })
      expect(wrapper.vm.iconName).toBe('exclamation-triangle')

      await wrapper.setProps({ modelValue: 'completed' })
      expect(wrapper.vm.iconName).toBe('check-circle')
    })

    it('correctly processes Nova API labels() method output', async () => {
      const phpFieldConfig = createMockField({
        withIcons: false,
        labelMap: {
          'waiting': 'Waiting in Queue',
          'running': 'Currently Processing',
          'completed': 'Successfully Completed'
        }
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'waiting'
        }
      })

      expect(wrapper.vm.displayLabel).toBe('Waiting in Queue')
      expect(wrapper.find('.inline-flex').text()).toBe('Waiting in Queue')

      await wrapper.setProps({ modelValue: 'running' })
      expect(wrapper.vm.displayLabel).toBe('Currently Processing')

      await wrapper.setProps({ modelValue: 'completed' })
      expect(wrapper.vm.displayLabel).toBe('Successfully Completed')
    })

    it('correctly processes PHP-resolved status objects', async () => {
      const phpResolvedValue = {
        value: 'waiting',
        label: 'Waiting in Queue',
        type: 'loading',
        classes: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        icon: 'clock'
      }

      const phpFieldConfig = createMockField({})

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: phpResolvedValue
        }
      })

      expect(wrapper.vm.statusInfo).toEqual(phpResolvedValue)
      expect(wrapper.vm.statusType).toBe('loading')
      expect(wrapper.vm.statusClasses).toBe('bg-blue-50 text-blue-700 ring-blue-600/20')
      expect(wrapper.vm.displayLabel).toBe('Waiting in Queue')
      expect(wrapper.vm.iconName).toBe('clock')
    })
  })

  describe('Nova API Compatibility Integration', () => {
    it('integrates all Nova API methods correctly', async () => {
      const phpFieldConfig = createMockField({
        loadingWhen: ['waiting'],
        failedWhen: ['failed'],
        successWhen: ['completed'],
        customTypes: {
          'loading': 'bg-blue-50 text-blue-700 ring-blue-600/20',
          'failed': 'bg-red-50 text-red-700 ring-red-600/20',
          'success': 'bg-green-50 text-green-700 ring-green-600/20'
        },
        withIcons: true,
        customIcons: {
          'loading': 'clock',
          'failed': 'exclamation-triangle',
          'success': 'check-circle'
        },
        labelMap: {
          'waiting': 'Waiting in Queue',
          'failed': 'Processing Failed',
          'completed': 'Successfully Completed'
        }
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'waiting'
        }
      })

      // Test complete integration
      expect(wrapper.vm.statusType).toBe('loading')
      expect(wrapper.vm.statusClasses).toBe('bg-blue-50 text-blue-700 ring-blue-600/20')
      expect(wrapper.vm.displayLabel).toBe('Waiting in Queue')
      expect(wrapper.vm.showIcon).toBe(true)
      expect(wrapper.vm.iconName).toBe('clock')

      // Test switching values
      await wrapper.setProps({ modelValue: 'completed' })

      expect(wrapper.vm.statusType).toBe('success')
      expect(wrapper.vm.statusClasses).toBe('bg-green-50 text-green-700 ring-green-600/20')
      expect(wrapper.vm.displayLabel).toBe('Successfully Completed')
      expect(wrapper.vm.showIcon).toBe(true)
      expect(wrapper.vm.iconName).toBe('check-circle')
    })

    it('handles fallback behavior correctly', async () => {
      const phpFieldConfig = createMockField({
        loadingWhen: ['waiting'],
        customTypes: {
          'loading': 'custom-loading-class'
        },
        withIcons: true,
        customIcons: {
          'loading': 'clock'
        },
        labelMap: {
          'waiting': 'Waiting'
        }
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'unknown' // Unmapped value
        }
      })

      // Should fall back to defaults
      expect(wrapper.vm.statusType).toBe('default') // Default status type
      expect(wrapper.vm.statusClasses).toBe('bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200') // Built-in default class
      expect(wrapper.vm.displayLabel).toBe('Unknown') // Formatted value
      expect(wrapper.vm.showIcon).toBe(true) // Icons enabled
      expect(wrapper.vm.iconName).toBe('information-circle') // Default icon
    })
  })

  describe('CRUD Operations Integration', () => {
    it('handles create operation with status field', async () => {
      const phpFieldConfig = createMockField({
        loadingWhen: ['waiting'],
        labelMap: {
          'waiting': 'Waiting'
        },
        withIcons: false
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: null // New record
        }
      })

      expect(wrapper.vm.displayLabel).toBe('')
      expect(wrapper.vm.statusType).toBe('default')

      // Simulate setting initial value
      await wrapper.setProps({ modelValue: 'waiting' })

      expect(wrapper.vm.displayLabel).toBe('Waiting')
      expect(wrapper.vm.statusType).toBe('loading')
    })

    it('handles read operation with status field', async () => {
      const phpFieldConfig = createMockField({
        successWhen: ['completed'],
        labelMap: {
          'completed': 'Completed'
        },
        withIcons: false
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'completed' // Existing record
        }
      })

      expect(wrapper.vm.displayLabel).toBe('Completed')
      expect(wrapper.vm.statusType).toBe('success')
      expect(wrapper.find('.inline-flex').text()).toBe('Completed')
    })

    it('handles update operation with status field', async () => {
      const phpFieldConfig = createMockField({
        loadingWhen: ['waiting'],
        successWhen: ['completed'],
        labelMap: {
          'waiting': 'Waiting',
          'completed': 'Completed'
        },
        withIcons: false
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'waiting' // Current value
        }
      })

      expect(wrapper.vm.displayLabel).toBe('Waiting')
      expect(wrapper.vm.statusType).toBe('loading')

      // Simulate update
      await wrapper.setProps({ modelValue: 'completed' })

      expect(wrapper.vm.displayLabel).toBe('Completed')
      expect(wrapper.vm.statusType).toBe('success')
    })
  })

  describe('Validation Integration', () => {
    it('displays status field correctly regardless of validation state', async () => {
      const phpFieldConfig = createMockField({
        failedWhen: ['invalid'],
        labelMap: {
          'invalid': 'Invalid Status'
        },
        withIcons: false
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'invalid',
          errors: ['Status is invalid'] // Validation errors
        }
      })

      // Status field should still display correctly even with errors
      expect(wrapper.vm.displayLabel).toBe('Invalid Status')
      expect(wrapper.vm.statusType).toBe('failed')
      expect(wrapper.find('.inline-flex').text()).toBe('Invalid Status')
    })
  })

  describe('Advanced Integration Scenarios', () => {
    it('handles dynamic field configuration changes', async () => {
      let phpFieldConfig = createMockField({
        loadingWhen: ['waiting'],
        labelMap: {
          'waiting': 'Waiting'
        },
        withIcons: false
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'waiting'
        }
      })

      expect(wrapper.vm.displayLabel).toBe('Waiting')
      expect(wrapper.vm.statusType).toBe('loading')

      // Simulate field configuration change (e.g., from PHP backend)
      phpFieldConfig = createMockField({
        failedWhen: ['waiting'], // Changed from loading to failed
        labelMap: {
          'waiting': 'Waiting (Failed)'
        },
        withIcons: false
      })

      await wrapper.setProps({ field: phpFieldConfig })

      expect(wrapper.vm.displayLabel).toBe('Waiting (Failed)')
      expect(wrapper.vm.statusType).toBe('failed')
    })

    it('handles complex Nova configuration from PHP', async () => {
      const phpFieldConfig = createMockField({
        loadingWhen: ['waiting', 'running'],
        failedWhen: ['failed', 'error'],
        successWhen: ['completed', 'done'],
        customTypes: {
          'loading': 'bg-blue-50 text-blue-700 ring-blue-600/20 font-medium',
          'failed': 'bg-red-50 text-red-700 ring-red-600/20 font-medium',
          'success': 'bg-green-50 text-green-700 ring-green-600/20 font-medium'
        },
        withIcons: true,
        customIcons: {
          'loading': 'clock',
          'failed': 'exclamation-triangle',
          'success': 'check-circle',
          'default': 'information-circle'
        },
        labelMap: {
          'waiting': 'Waiting in Queue',
          'running': 'Currently Processing',
          'failed': 'Processing Failed',
          'completed': 'Successfully Completed'
        }
      })

      const testCases = [
        ['waiting', 'loading', 'bg-blue-50 text-blue-700 ring-blue-600/20 font-medium', 'Waiting in Queue', 'clock'],
        ['running', 'loading', 'bg-blue-50 text-blue-700 ring-blue-600/20 font-medium', 'Currently Processing', 'clock'],
        ['failed', 'failed', 'bg-red-50 text-red-700 ring-red-600/20 font-medium', 'Processing Failed', 'exclamation-triangle'],
        ['completed', 'success', 'bg-green-50 text-green-700 ring-green-600/20 font-medium', 'Successfully Completed', 'check-circle']
      ]

      testCases.forEach(([value, expectedType, expectedClasses, expectedLabel, expectedIcon]) => {
        wrapper = mount(StatusField, {
          props: {
            field: phpFieldConfig,
            modelValue: value
          }
        })

        expect(wrapper.vm.statusType).toBe(expectedType)
        expect(wrapper.vm.statusClasses).toBe(expectedClasses)
        expect(wrapper.vm.displayLabel).toBe(expectedLabel)
        expect(wrapper.vm.iconName).toBe(expectedIcon)
        expect(wrapper.vm.showIcon).toBe(true)
      })
    })

    it('handles loading status with spinning animation', async () => {
      const phpFieldConfig = createMockField({
        loadingWhen: ['processing'],
        withIcons: true
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'processing'
        }
      })

      expect(wrapper.vm.statusType).toBe('loading')
      expect(wrapper.vm.iconClasses).toContain('animate-spin')
      expect(wrapper.vm.showIcon).toBe(true)
    })

    it('handles status field without icons', async () => {
      const phpFieldConfig = createMockField({
        loadingWhen: ['waiting'],
        withIcons: false,
        labelMap: {
          'waiting': 'Waiting'
        }
      })

      wrapper = mount(StatusField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'waiting'
        }
      })

      expect(wrapper.vm.showIcon).toBe(false)
      expect(wrapper.find('.inline-flex').text()).toBe('Waiting')
      expect(wrapper.find('span[aria-hidden="true"]').exists()).toBe(false)
    })
  })
})
