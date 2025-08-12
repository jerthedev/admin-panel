import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseField from '@/components/Fields/BaseField.vue'
import { createMockField, createMockTheme } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('BaseField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Test Field',
      attribute: 'test_field',
      helpText: 'This is help text',
      rules: ['required']
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders the field wrapper', () => {
      wrapper = mount(BaseField, {
        props: {
          field: mockField
        }
      })

      expect(wrapper.find('.field-wrapper').exists()).toBe(true)
    })

    it('renders the label when showLabel is true', () => {
      wrapper = mount(BaseField, {
        props: {
          field: mockField,
          showLabel: true
        }
      })

      const label = wrapper.find('label')
      expect(label.exists()).toBe(true)
      expect(label.text()).toContain('Test Field')
    })

    it('does not render the label when showLabel is false', () => {
      wrapper = mount(BaseField, {
        props: {
          field: mockField,
          showLabel: false
        }
      })

      expect(wrapper.find('label').exists()).toBe(false)
    })

    it('renders help text when provided', () => {
      wrapper = mount(BaseField, {
        props: {
          field: mockField
        }
      })

      const helpText = wrapper.find('.text-gray-500')
      expect(helpText.exists()).toBe(true)
      expect(helpText.text()).toBe('This is help text')
    })

    it('renders required asterisk for required fields', () => {
      wrapper = mount(BaseField, {
        props: {
          field: mockField
        }
      })

      const asterisk = wrapper.find('.text-red-500')
      expect(asterisk.exists()).toBe(true)
      expect(asterisk.text()).toBe('*')
    })
  })

  describe('Error Handling', () => {
    it('displays error message when errors are provided', () => {
      const errors = {
        test_field: ['This field is required']
      }

      wrapper = mount(BaseField, {
        props: {
          field: mockField,
          errors
        }
      })

      const errorMessage = wrapper.find('.text-red-600')
      expect(errorMessage.exists()).toBe(true)
      expect(errorMessage.text()).toBe('This field is required')
    })

    it('does not display error message when no errors', () => {
      wrapper = mount(BaseField, {
        props: {
          field: mockField,
          errors: {}
        }
      })

      expect(wrapper.find('.text-red-600').exists()).toBe(false)
    })

    it('applies error class when field has errors', () => {
      const errors = {
        test_field: ['This field is required']
      }

      wrapper = mount(BaseField, {
        props: {
          field: mockField,
          errors
        }
      })

      expect(wrapper.find('.field-wrapper').classes()).toContain('field-error')
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', async () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mount(BaseField, {
        props: {
          field: mockField
        }
      })

      const label = wrapper.find('label')
      expect(label.classes()).toContain('admin-label-dark')
    })

    it('does not apply dark theme classes when light theme is active', () => {
      mockAdminStore.isDarkTheme = false

      wrapper = mount(BaseField, {
        props: {
          field: mockField
        }
      })

      const label = wrapper.find('label')
      expect(label.classes()).not.toContain('admin-label-dark')
    })
  })

  describe('Props Validation', () => {
    it('accepts valid size prop values', () => {
      const validSizes = ['small', 'default', 'large']
      
      validSizes.forEach(size => {
        wrapper = mount(BaseField, {
          props: {
            field: mockField,
            size
          }
        })
        expect(wrapper.props('size')).toBe(size)
        wrapper.unmount()
      })
    })

    it('applies disabled class when disabled prop is true', () => {
      wrapper = mount(BaseField, {
        props: {
          field: mockField,
          disabled: true
        }
      })

      expect(wrapper.find('.field-wrapper').classes()).toContain('field-disabled')
    })
  })

  describe('Slot Content', () => {
    it('renders slot content in field-content div', () => {
      wrapper = mount(BaseField, {
        props: {
          field: mockField
        },
        slots: {
          default: '<input type="text" class="test-input" />'
        }
      })

      const fieldContent = wrapper.find('.field-content')
      expect(fieldContent.exists()).toBe(true)
      expect(fieldContent.find('.test-input').exists()).toBe(true)
    })
  })
})
