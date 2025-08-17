import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BadgeField from '@/components/Fields/BadgeField.vue'
import BaseField from '@/components/Fields/BaseField.vue'

/**
 * Badge Field Vue Component Tests
 *
 * Tests for BadgeField Vue component with 100% Nova API compatibility.
 * Tests all Nova Badge field features including map, types, addTypes,
 * withIcons, icons, label, and labels methods.
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
  component: 'BadgeField',
  builtInTypes: {
    info: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    danger: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
  },
  valueMap: {},
  customTypes: {},
  withIcons: false,
  iconMap: {},
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

describe('BadgeField', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders badge field with BaseField wrapper', () => {
      wrapper = mountField(BadgeField, { field: createMockField() })

      expect(wrapper.findComponent(BaseField).exists()).toBe(true)
      expect(wrapper.find('.inline-flex').exists()).toBe(true)
    })

    it('renders with default info badge type when no value', () => {
      wrapper = mountField(BadgeField, {
        field: createMockField(),
        modelValue: null
      })

      const badge = wrapper.find('.inline-flex')
      expect(badge.exists()).toBe(true)
      expect(badge.text()).toBe('')
    })

    it('renders with model value as label', () => {
      wrapper = mountField(BadgeField, {
        field: createMockField(),
        modelValue: 'draft'
      })

      const badge = wrapper.find('.inline-flex')
      expect(badge.text()).toBe('draft')
    })
  })

  describe('Nova API Compatibility - Value Mapping', () => {
    it('maps values to badge types correctly', async () => {
      const field = createMockField({
        valueMap: {
          'draft': 'danger',
          'published': 'success'
        }
      })

      wrapper = mountField(BadgeField, {
        field,
        modelValue: 'draft'
      })

      expect(wrapper.vm.badgeType).toBe('danger')

      await wrapper.setProps({ modelValue: 'published' })
      expect(wrapper.vm.badgeType).toBe('success')
    })

    it('defaults to info badge type for unmapped values', () => {
      const field = createMockField({
        valueMap: {
          'draft': 'danger'
        }
      })

      wrapper = mountField(BadgeField, {
        field,
        modelValue: 'unknown'
      })

      expect(wrapper.vm.badgeType).toBe('info')
    })
  })

  describe('Nova API Compatibility - Built-in Types', () => {
    it('uses built-in badge classes by default', () => {
      wrapper = mountField(BadgeField, {
        field: createMockField(),
        modelValue: 'test'
      })

      const expectedClasses = 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
      expect(wrapper.vm.badgeClasses).toBe(expectedClasses)
    })

    it('applies correct built-in classes for each badge type', () => {
      const field = createMockField({
        valueMap: {
          'info': 'info',
          'success': 'success',
          'danger': 'danger',
          'warning': 'warning'
        }
      })

      const testCases = [
        ['info', 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
        ['success', 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
        ['danger', 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
        ['warning', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200']
      ]

      testCases.forEach(([value, expectedClasses]) => {
        wrapper = mountField(BadgeField, {
          field,
          modelValue: value
        })
        expect(wrapper.vm.badgeClasses).toBe(expectedClasses)
      })
    })
  })

  describe('Nova API Compatibility - Custom Types', () => {
    it('uses custom types when defined', () => {
      const field = createMockField({
        valueMap: {
          'draft': 'danger'
        },
        customTypes: {
          'danger': 'bg-red-50 text-red-700 ring-red-600/20'
        }
      })

      wrapper = mountField(BadgeField, {
        field,
        modelValue: 'draft'
      })

      expect(wrapper.vm.badgeClasses).toBe('bg-red-50 text-red-700 ring-red-600/20')
    })

    it('falls back to built-in types for unmapped custom types', () => {
      const field = createMockField({
        valueMap: {
          'draft': 'danger',
          'published': 'success'
        },
        customTypes: {
          'danger': 'custom-danger-class'
        }
      })

      wrapper = mountField(BadgeField, {
        field,
        modelValue: 'published' // Maps to 'success' but no custom type defined
      })

      const expectedClasses = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
      expect(wrapper.vm.badgeClasses).toBe(expectedClasses)
    })
  })

  describe('Nova API Compatibility - Labels', () => {
    it('uses label mapping when defined', () => {
      const field = createMockField({
        labelMap: {
          'draft': 'Draft Post',
          'published': 'Published Post'
        }
      })

      wrapper = mountField(BadgeField, {
        field,
        modelValue: 'draft'
      })

      expect(wrapper.vm.displayLabel).toBe('Draft Post')
      expect(wrapper.find('.inline-flex').text()).toBe('Draft Post')
    })

    it('defaults to value when no label mapping', () => {
      wrapper = mountField(BadgeField, {
        field: createMockField(),
        modelValue: 'draft'
      })

      expect(wrapper.vm.displayLabel).toBe('draft')
    })

    it('handles empty values gracefully', () => {
      wrapper = mountField(BadgeField, {
        field: createMockField(),
        modelValue: null
      })

      expect(wrapper.vm.displayLabel).toBe('')
    })
  })

  describe('Nova API Compatibility - Icons', () => {
    it('shows icon when withIcons is true and icon exists', () => {
      const field = createMockField({
        valueMap: {
          'draft': 'danger'
        },
        withIcons: true,
        iconMap: {
          'danger': 'exclamation-triangle'
        }
      })

      wrapper = mountField(BadgeField, {
        field,
        modelValue: 'draft'
      })

      expect(wrapper.vm.showIcon).toBe(true)
      expect(wrapper.vm.iconName).toBe('exclamation-triangle')
    })

    it('does not show icon when withIcons is false', () => {
      const field = createMockField({
        valueMap: {
          'draft': 'danger'
        },
        withIcons: false,
        iconMap: {
          'danger': 'exclamation-triangle'
        }
      })

      wrapper = mountField(BadgeField, {
        field,
        modelValue: 'draft'
      })

      expect(wrapper.vm.showIcon).toBe(false)
    })

    it('does not show icon when no icon mapping exists', () => {
      const field = createMockField({
        valueMap: {
          'draft': 'danger'
        },
        withIcons: true,
        iconMap: {}
      })

      wrapper = mountField(BadgeField, {
        field,
        modelValue: 'draft'
      })

      expect(wrapper.vm.showIcon).toBe(false)
    })
  })

  describe('Component Interface', () => {
    it('implements focus method as no-op', () => {
      wrapper = mountField(BadgeField, { field: createMockField() })

      expect(typeof wrapper.vm.focus).toBe('function')
      expect(() => wrapper.vm.focus()).not.toThrow()
    })

    it('implements blur method as no-op', () => {
      wrapper = mountField(BadgeField, { field: createMockField() })

      expect(typeof wrapper.vm.blur).toBe('function')
      expect(() => wrapper.vm.blur()).not.toThrow()
    })

    it('is readonly by default', () => {
      wrapper = mountField(BadgeField, { field: createMockField() })

      expect(wrapper.props('readonly')).toBe(true)
    })
  })
})
