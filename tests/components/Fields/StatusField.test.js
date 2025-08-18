import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import StatusField from '@/components/Fields/StatusField.vue'
import BaseField from '@/components/Fields/BaseField.vue'

/**
 * Status Field Vue Component Tests
 *
 * Tests for StatusField Vue component with 100% Nova API compatibility.
 * Tests all Nova Status field features including loadingWhen, failedWhen,
 * successWhen, types, icons, withIcons, label, and labels methods.
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

// Helper function to create mock field
const createMockField = (overrides = {}) => ({
  name: 'Status',
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
  loadingWhen: [],
  failedWhen: [],
  successWhen: [],
  customTypes: {},
  customIcons: {},
  withIcons: true,
  labelMap: {},
  ...overrides
})

// Helper function to mount field component
const mountField = (component, props = {}) => {
  return mount(component, {
    props: {
      field: createMockField(),
      modelValue: null,
      errors: [],
      disabled: false,
      readonly: true,
      size: 'default',
      ...props
    },
    global: {
      components: {
        BaseField
      }
    }
  })
}

describe('StatusField', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders status field with BaseField wrapper', () => {
      wrapper = mountField(StatusField, { field: createMockField() })

      expect(wrapper.findComponent(BaseField).exists()).toBe(true)
      expect(wrapper.find('.inline-flex').exists()).toBe(true)
    })

    it('renders with default status type when no value', () => {
      wrapper = mountField(StatusField, {
        field: createMockField({ withIcons: false }),
        modelValue: null
      })

      const status = wrapper.find('.inline-flex')
      expect(status.exists()).toBe(true)
      expect(status.text()).toBe('')
    })

    it('renders with model value as label', () => {
      wrapper = mountField(StatusField, {
        field: createMockField({ withIcons: false }),
        modelValue: 'waiting'
      })

      const status = wrapper.find('.inline-flex')
      expect(status.text()).toBe('Waiting')
    })
  })

  describe('Nova API Compatibility - Status Type Resolution', () => {
    it('resolves loading status correctly', async () => {
      const field = createMockField({
        loadingWhen: ['waiting', 'running', 'processing']
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'waiting'
      })

      expect(wrapper.vm.statusType).toBe('loading')

      await wrapper.setProps({ modelValue: 'running' })
      expect(wrapper.vm.statusType).toBe('loading')

      await wrapper.setProps({ modelValue: 'processing' })
      expect(wrapper.vm.statusType).toBe('loading')
    })

    it('resolves failed status correctly', async () => {
      const field = createMockField({
        failedWhen: ['failed', 'error', 'cancelled']
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'failed'
      })

      expect(wrapper.vm.statusType).toBe('failed')

      await wrapper.setProps({ modelValue: 'error' })
      expect(wrapper.vm.statusType).toBe('failed')

      await wrapper.setProps({ modelValue: 'cancelled' })
      expect(wrapper.vm.statusType).toBe('failed')
    })

    it('resolves success status correctly', async () => {
      const field = createMockField({
        successWhen: ['completed', 'finished', 'done']
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'completed'
      })

      expect(wrapper.vm.statusType).toBe('success')

      await wrapper.setProps({ modelValue: 'finished' })
      expect(wrapper.vm.statusType).toBe('success')

      await wrapper.setProps({ modelValue: 'done' })
      expect(wrapper.vm.statusType).toBe('success')
    })

    it('defaults to default status type for unmapped values', () => {
      const field = createMockField({
        loadingWhen: ['waiting'],
        failedWhen: ['failed'],
        successWhen: ['completed']
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'unknown'
      })

      expect(wrapper.vm.statusType).toBe('default')
    })
  })

  describe('Nova API Compatibility - Built-in Types', () => {
    it('uses built-in status classes by default', () => {
      wrapper = mountField(StatusField, {
        field: createMockField({
          loadingWhen: ['waiting']
        }),
        modelValue: 'waiting'
      })

      const expectedClasses = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
      expect(wrapper.vm.statusClasses).toBe(expectedClasses)
    })

    it('applies correct built-in classes for each status type', () => {
      const field = createMockField({
        loadingWhen: ['loading'],
        failedWhen: ['failed'],
        successWhen: ['success']
      })

      const testCases = [
        ['loading', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
        ['failed', 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
        ['success', 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
        ['unknown', 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'] // default
      ]

      testCases.forEach(([value, expectedClasses]) => {
        wrapper = mountField(StatusField, {
          field,
          modelValue: value
        })
        expect(wrapper.vm.statusClasses).toBe(expectedClasses)
      })
    })
  })

  describe('Nova API Compatibility - Custom Types', () => {
    it('uses custom types when defined', () => {
      const field = createMockField({
        loadingWhen: ['waiting'],
        customTypes: {
          'loading': 'bg-blue-50 text-blue-700 ring-blue-600/20'
        }
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'waiting'
      })

      expect(wrapper.vm.statusClasses).toBe('bg-blue-50 text-blue-700 ring-blue-600/20')
    })

    it('falls back to built-in types for unmapped custom types', () => {
      const field = createMockField({
        loadingWhen: ['waiting'],
        failedWhen: ['failed'],
        customTypes: {
          'loading': 'custom-loading-class'
        }
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'failed' // Maps to 'failed' but no custom type defined
      })

      const expectedClasses = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
      expect(wrapper.vm.statusClasses).toBe(expectedClasses)
    })
  })

  describe('Nova API Compatibility - Labels', () => {
    it('uses label mapping when defined', () => {
      const field = createMockField({
        withIcons: false,
        labelMap: {
          'waiting': 'Waiting in Queue',
          'completed': 'Successfully Completed'
        }
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'waiting'
      })

      expect(wrapper.vm.displayLabel).toBe('Waiting in Queue')
      expect(wrapper.find('.inline-flex').text()).toBe('Waiting in Queue')
    })

    it('defaults to formatted value when no label mapping', () => {
      wrapper = mountField(StatusField, {
        field: createMockField(),
        modelValue: 'in_progress'
      })

      expect(wrapper.vm.displayLabel).toBe('In Progress')
    })

    it('handles empty values gracefully', () => {
      wrapper = mountField(StatusField, {
        field: createMockField(),
        modelValue: null
      })

      expect(wrapper.vm.displayLabel).toBe('')
    })
  })

  describe('Nova API Compatibility - Icons', () => {
    it('shows icon when withIcons is true and icon exists', () => {
      const field = createMockField({
        loadingWhen: ['waiting'],
        withIcons: true,
        customIcons: {
          'loading': 'clock'
        }
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'waiting'
      })

      expect(wrapper.vm.showIcon).toBe(true)
      expect(wrapper.vm.iconName).toBe('clock')
    })

    it('uses built-in icons by default', () => {
      const field = createMockField({
        loadingWhen: ['waiting'],
        withIcons: true
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'waiting'
      })

      expect(wrapper.vm.showIcon).toBe(true)
      expect(wrapper.vm.iconName).toBe('spinner')
    })

    it('does not show icon when withIcons is false', () => {
      const field = createMockField({
        loadingWhen: ['waiting'],
        withIcons: false,
        customIcons: {
          'loading': 'clock'
        }
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'waiting'
      })

      expect(wrapper.vm.showIcon).toBe(false)
    })

    it('adds spinning animation for loading status', () => {
      const field = createMockField({
        loadingWhen: ['waiting'],
        withIcons: true
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'waiting'
      })

      expect(wrapper.vm.iconClasses).toContain('animate-spin')
    })

    it('does not add spinning animation for non-loading status', () => {
      const field = createMockField({
        successWhen: ['completed'],
        withIcons: true
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'completed'
      })

      expect(wrapper.vm.iconClasses).not.toContain('animate-spin')
    })
  })

  describe('Nova API Compatibility - PHP Resolved Values', () => {
    it('handles PHP-resolved status object correctly', () => {
      const resolvedValue = {
        value: 'waiting',
        label: 'Waiting in Queue',
        type: 'loading',
        classes: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        icon: 'clock'
      }

      wrapper = mountField(StatusField, {
        field: createMockField(),
        modelValue: resolvedValue
      })

      expect(wrapper.vm.statusInfo).toEqual(resolvedValue)
      expect(wrapper.vm.statusType).toBe('loading')
      expect(wrapper.vm.statusClasses).toBe('bg-blue-50 text-blue-700 ring-blue-600/20')
      expect(wrapper.vm.displayLabel).toBe('Waiting in Queue')
      expect(wrapper.vm.iconName).toBe('clock')
    })

    it('falls back to frontend resolution when PHP value is simple', () => {
      const field = createMockField({
        loadingWhen: ['waiting'],
        labelMap: {
          'waiting': 'Waiting in Queue'
        }
      })

      wrapper = mountField(StatusField, {
        field,
        modelValue: 'waiting' // Simple string value
      })

      expect(wrapper.vm.statusType).toBe('loading')
      expect(wrapper.vm.displayLabel).toBe('Waiting in Queue')
    })
  })

  describe('Component Interface', () => {
    it('implements focus method as no-op', () => {
      wrapper = mountField(StatusField, { field: createMockField() })

      expect(typeof wrapper.vm.focus).toBe('function')
      expect(() => wrapper.vm.focus()).not.toThrow()
    })

    it('implements blur method as no-op', () => {
      wrapper = mountField(StatusField, { field: createMockField() })

      expect(typeof wrapper.vm.blur).toBe('function')
      expect(() => wrapper.vm.blur()).not.toThrow()
    })

    it('is readonly by default', () => {
      wrapper = mountField(StatusField, { field: createMockField() })

      expect(wrapper.props('readonly')).toBe(true)
    })
  })

  describe('Complex Nova Configuration', () => {
    it('handles complex status field configuration', () => {
      const field = createMockField({
        loadingWhen: ['waiting', 'running', 'processing'],
        failedWhen: ['failed', 'error', 'cancelled'],
        successWhen: ['completed', 'finished', 'done'],
        customTypes: {
          loading: 'bg-blue-50 text-blue-700 ring-blue-600/20',
          failed: 'bg-red-50 text-red-700 ring-red-600/20',
          success: 'bg-green-50 text-green-700 ring-green-600/20',
        },
        customIcons: {
          loading: 'clock',
          failed: 'exclamation-triangle',
          success: 'check-circle',
        },
        withIcons: true,
        labelMap: {
          'waiting': 'Waiting in Queue',
          'running': 'Currently Processing',
          'completed': 'Successfully Completed',
          'failed': 'Processing Failed',
        }
      })

      // Test loading state
      wrapper = mountField(StatusField, {
        field,
        modelValue: 'waiting'
      })

      expect(wrapper.vm.statusType).toBe('loading')
      expect(wrapper.vm.statusClasses).toBe('bg-blue-50 text-blue-700 ring-blue-600/20')
      expect(wrapper.vm.displayLabel).toBe('Waiting in Queue')
      expect(wrapper.vm.iconName).toBe('clock')
      expect(wrapper.vm.showIcon).toBe(true)
      expect(wrapper.vm.iconClasses).toContain('animate-spin')
    })

    it('provides consistent Nova API behavior', () => {
      const field = createMockField()

      wrapper = mountField(StatusField, { field })

      // Test that all Nova-compatible computed properties exist
      expect(wrapper.vm.statusInfo).toBeDefined()
      expect(wrapper.vm.statusType).toBeDefined()
      expect(wrapper.vm.statusClasses).toBeDefined()
      expect(wrapper.vm.displayLabel).toBeDefined()
      expect(wrapper.vm.showIcon).toBeDefined()
      expect(wrapper.vm.iconName).toBeDefined()
      expect(wrapper.vm.iconClasses).toBeDefined()

      // Test that Nova-compatible methods exist
      expect(typeof wrapper.vm.resolveStatusType).toBe('function')
      expect(typeof wrapper.vm.resolveLabel).toBe('function')
      expect(typeof wrapper.vm.focus).toBe('function')
      expect(typeof wrapper.vm.blur).toBe('function')
    })
  })
})
