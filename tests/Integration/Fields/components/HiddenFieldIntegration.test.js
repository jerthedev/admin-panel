import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import HiddenField from '@/components/Fields/HiddenField.vue'
import { createMockField } from '../../../helpers.js'

/**
 * Hidden Field Integration Tests
 *
 * Tests the integration between the PHP Hidden field class and Vue component,
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

// Mock the store
vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('HiddenField Integration', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'User ID',
      attribute: 'user_id',
      type: 'hidden',
      component: 'HiddenField',
      default: null,
      showOnIndex: false,
      showOnDetail: false,
      showOnCreation: true,
      showOnUpdate: true
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP-Vue Integration', () => {
    it('renders field with PHP field configuration', () => {
      wrapper = mount(HiddenField, {
        props: {
          field: mockField,
          modelValue: 'test-value'
        }
      })

      const input = wrapper.find('input[type="hidden"]')
      expect(input.exists()).toBe(true)
      expect(input.attributes('name')).toBe('user_id')
      expect(input.element.value).toBe('test-value')
    })

    it('handles PHP default values', () => {
      const fieldWithDefault = createMockField({
        ...mockField,
        default: 'php-default-value'
      })

      wrapper = mount(HiddenField, {
        props: {
          field: fieldWithDefault,
          modelValue: null
        }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('php-default-value')
    })

    it('prioritizes model value over PHP default', () => {
      const fieldWithDefault = createMockField({
        ...mockField,
        default: 'php-default-value'
      })

      wrapper = mount(HiddenField, {
        props: {
          field: fieldWithDefault,
          modelValue: 'model-value'
        }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('model-value')
    })

    it('handles PHP callable defaults (resolved server-side)', () => {
      // In real integration, callable defaults are resolved server-side
      const fieldWithResolvedCallable = createMockField({
        ...mockField,
        default: 'resolved-callable-value'
      })

      wrapper = mount(HiddenField, {
        props: {
          field: fieldWithResolvedCallable,
          modelValue: null
        }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('resolved-callable-value')
    })
  })

  describe('Form Integration', () => {
    it('integrates with form submission', async () => {
      wrapper = mount(HiddenField, {
        props: {
          field: mockField,
          modelValue: 'form-value'
        }
      })

      const input = wrapper.find('input')
      
      // Simulate form data collection
      const formData = new FormData()
      formData.append(input.attributes('name'), input.element.value)
      
      expect(formData.get('user_id')).toBe('form-value')
    })

    it('updates parent component on value change', async () => {
      wrapper = mount(HiddenField, {
        props: {
          field: mockField,
          modelValue: 'initial-value'
        }
      })

      const input = wrapper.find('input')
      await input.setValue('new-value')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('new-value')
      expect(wrapper.emitted('change')).toBeTruthy()
      expect(wrapper.emitted('change')[0][0]).toBe('new-value')
    })

    it('handles empty values correctly', async () => {
      wrapper = mount(HiddenField, {
        props: {
          field: mockField,
          modelValue: ''
        }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')

      await input.setValue('')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('')
    })
  })

  describe('Nova API Compatibility', () => {
    it('matches Nova Hidden field behavior', () => {
      wrapper = mount(HiddenField, {
        props: {
          field: mockField,
          modelValue: 'nova-compatible-value'
        }
      })

      const input = wrapper.find('input')
      
      // Nova Hidden fields are simple hidden inputs
      expect(input.attributes('type')).toBe('hidden')
      expect(input.attributes('name')).toBe(mockField.attribute)
      expect(input.element.value).toBe('nova-compatible-value')
      
      // Should not have visible UI elements
      expect(wrapper.find('label').exists()).toBe(false)
      expect(wrapper.find('.field-wrapper').exists()).toBe(false)
    })

    it('supports Nova field visibility settings', () => {
      const novaField = createMockField({
        ...mockField,
        showOnIndex: false,
        showOnDetail: false,
        showOnCreation: true,
        showOnUpdate: true
      })

      wrapper = mount(HiddenField, {
        props: {
          field: novaField,
          modelValue: 'test'
        }
      })

      // Hidden fields should always render the input regardless of visibility
      // (visibility is handled by the parent form/resource component)
      const input = wrapper.find('input[type="hidden"]')
      expect(input.exists()).toBe(true)
    })

    it('handles Nova-style field attributes', () => {
      const novaStyleField = createMockField({
        name: 'CSRF Token',
        attribute: 'csrf_token',
        component: 'HiddenField',
        default: 'abc123',
        rules: ['required'],
        showOnIndex: false,
        showOnDetail: false,
        showOnCreation: true,
        showOnUpdate: true
      })

      wrapper = mount(HiddenField, {
        props: {
          field: novaStyleField,
          modelValue: null
        }
      })

      const input = wrapper.find('input')
      expect(input.attributes('name')).toBe('csrf_token')
      expect(input.element.value).toBe('abc123')
    })
  })

  describe('Error Handling Integration', () => {
    it('handles validation errors from PHP backend', () => {
      const errors = {
        user_id: ['The user id field is required.']
      }

      wrapper = mount(HiddenField, {
        props: {
          field: mockField,
          modelValue: '',
          errors: errors
        }
      })

      // Hidden fields don't display errors visually, but should still receive them
      expect(wrapper.props('errors')).toEqual(errors)
    })
  })

  describe('Accessibility Integration', () => {
    it('maintains accessibility standards', () => {
      wrapper = mount(HiddenField, {
        props: {
          field: mockField,
          modelValue: 'accessible-value'
        }
      })

      const input = wrapper.find('input')
      
      // Hidden fields should have proper attributes
      expect(input.attributes('type')).toBe('hidden')
      expect(input.attributes('name')).toBe('user_id')
      
      // Should have a unique ID
      expect(input.attributes('id')).toMatch(/^hidden-field-user_id-/)
    })
  })

  describe('Performance Integration', () => {
    it('renders efficiently without unnecessary re-renders', async () => {
      wrapper = mount(HiddenField, {
        props: {
          field: mockField,
          modelValue: 'performance-test'
        }
      })

      // Initial render should work correctly
      const input = wrapper.find('input')
      expect(input.element.value).toBe('performance-test')

      // Value changes should update the input
      await wrapper.setProps({ modelValue: 'new-value' })
      expect(input.element.value).toBe('new-value')

      // Component should remain reactive
      expect(wrapper.props('modelValue')).toBe('new-value')
    })
  })

  describe('Real-world Usage Scenarios', () => {
    it('handles user ID scenario from Nova docs', () => {
      const userIdField = createMockField({
        name: 'User',
        attribute: 'user_id',
        component: 'HiddenField',
        default: '123' // Resolved from callable default server-side
      })

      wrapper = mount(HiddenField, {
        props: {
          field: userIdField,
          modelValue: null
        }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('123')
      expect(input.attributes('name')).toBe('user_id')
    })

    it('handles CSRF token scenario', () => {
      const csrfField = createMockField({
        name: 'CSRF Token',
        attribute: '_token',
        component: 'HiddenField',
        default: 'csrf-token-value'
      })

      wrapper = mount(HiddenField, {
        props: {
          field: csrfField,
          modelValue: null
        }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('csrf-token-value')
      expect(input.attributes('name')).toBe('_token')
    })
  })
})
