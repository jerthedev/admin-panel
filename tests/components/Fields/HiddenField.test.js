import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import HiddenField from '@/components/Fields/HiddenField.vue'
import { createMockField, mountField } from '../../helpers.js'

describe('HiddenField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Hidden Value',
      attribute: 'hidden_value',
      type: 'hidden',
      default: 'default-value'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders hidden input field', () => {
      wrapper = mountField(HiddenField, { field: mockField })

      const input = wrapper.find('input[type="hidden"]')
      expect(input.exists()).toBe(true)
    })

    it('does not render BaseField wrapper', () => {
      wrapper = mountField(HiddenField, { field: mockField })

      // Should not have any visible field wrapper elements
      expect(wrapper.find('.field-wrapper').exists()).toBe(false)
      expect(wrapper.find('label').exists()).toBe(false)
    })

    it('sets correct name attribute', () => {
      wrapper = mountField(HiddenField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('name')).toBe('hidden_value')
    })

    it('generates unique field ID', () => {
      wrapper = mountField(HiddenField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('id')).toContain('hidden-field-hidden_value')
    })
  })

  describe('Value Handling', () => {
    it('renders with model value', () => {
      wrapper = mountField(HiddenField, {
        field: mockField,
        modelValue: 'test-value'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('test-value')
    })

    it('uses default value when model value is null', () => {
      wrapper = mountField(HiddenField, {
        field: mockField,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('default-value')
    })

    it('uses default value when model value is undefined', () => {
      wrapper = mountField(HiddenField, {
        field: mockField,
        modelValue: undefined
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('default-value')
    })

    it('uses empty string when no model value or default', () => {
      const fieldWithoutDefault = createMockField({
        ...mockField,
        default: undefined
      })

      wrapper = mountField(HiddenField, {
        field: fieldWithoutDefault,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles string values', () => {
      wrapper = mountField(HiddenField, {
        field: mockField,
        modelValue: 'string-value'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('string-value')
    })

    it('handles numeric values', () => {
      wrapper = mountField(HiddenField, {
        field: mockField,
        modelValue: 123
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('123')
    })

    it('handles boolean values', () => {
      wrapper = mountField(HiddenField, {
        field: mockField,
        modelValue: true
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('true')
    })

    it('handles zero value correctly', () => {
      wrapper = mountField(HiddenField, {
        field: mockField,
        modelValue: 0
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('0')
    })

    it('handles empty string value', () => {
      wrapper = mountField(HiddenField, {
        field: mockField,
        modelValue: ''
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })
  })

  describe('Event Handling', () => {
    it('emits update:modelValue on input', async () => {
      wrapper = mountField(HiddenField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('new-value')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('new-value')
      expect(wrapper.emitted('change')[0][0]).toBe('new-value')
    })

    it('emits events with empty string', async () => {
      wrapper = mountField(HiddenField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('')
      expect(wrapper.emitted('change')[0][0]).toBe('')
    })

    it('emits events with numeric string', async () => {
      wrapper = mountField(HiddenField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('123')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('123')
      expect(wrapper.emitted('change')[0][0]).toBe('123')
    })
  })

  describe('Props Handling', () => {
    it('accepts disabled prop without affecting functionality', () => {
      wrapper = mountField(HiddenField, {
        field: mockField,
        props: { 
          field: mockField,
          disabled: true 
        }
      })

      const input = wrapper.find('input')
      expect(input.exists()).toBe(true)
      // Hidden fields typically ignore disabled state since they're not interactive
    })

    it('accepts readonly prop without affecting functionality', () => {
      wrapper = mountField(HiddenField, {
        field: mockField,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      const input = wrapper.find('input')
      expect(input.exists()).toBe(true)
      // Hidden fields typically ignore readonly state since they're not interactive
    })

    it('accepts size prop without affecting functionality', () => {
      wrapper = mountField(HiddenField, {
        field: mockField,
        props: { 
          field: mockField,
          size: 'large' 
        }
      })

      const input = wrapper.find('input')
      expect(input.exists()).toBe(true)
      // Hidden fields ignore size since they're not visible
    })

    it('accepts errors prop without affecting functionality', () => {
      wrapper = mountField(HiddenField, {
        field: mockField,
        props: { 
          field: mockField,
          errors: { hidden_value: ['Some error'] }
        }
      })

      const input = wrapper.find('input')
      expect(input.exists()).toBe(true)
      // Hidden fields don't display errors since they're not visible
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(HiddenField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the input', async () => {
      wrapper = mountField(HiddenField, { field: mockField })

      const input = wrapper.find('input')
      const focusSpy = vi.spyOn(input.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Field ID Generation', () => {
    it('generates unique IDs for different instances', () => {
      const wrapper1 = mountField(HiddenField, { field: mockField })
      const wrapper2 = mountField(HiddenField, { field: mockField })

      const input1 = wrapper1.find('input')
      const input2 = wrapper2.find('input')

      expect(input1.attributes('id')).not.toBe(input2.attributes('id'))

      wrapper1.unmount()
      wrapper2.unmount()
    })

    it('includes field attribute in ID', () => {
      wrapper = mountField(HiddenField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('id')).toContain('hidden_value')
    })
  })

  describe('Edge Cases', () => {
    it('handles field without default value', () => {
      const fieldWithoutDefault = createMockField({
        name: 'No Default',
        attribute: 'no_default',
        type: 'hidden'
        // No default property
      })

      wrapper = mountField(HiddenField, {
        field: fieldWithoutDefault,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles field with null default value', () => {
      const fieldWithNullDefault = createMockField({
        ...mockField,
        default: null
      })

      wrapper = mountField(HiddenField, {
        field: fieldWithNullDefault,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles field with empty string default', () => {
      const fieldWithEmptyDefault = createMockField({
        ...mockField,
        default: ''
      })

      wrapper = mountField(HiddenField, {
        field: fieldWithEmptyDefault,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles field with numeric default', () => {
      const fieldWithNumericDefault = createMockField({
        ...mockField,
        default: 42
      })

      wrapper = mountField(HiddenField, {
        field: fieldWithNumericDefault,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('42')
    })

    it('handles field with boolean default', () => {
      const fieldWithBooleanDefault = createMockField({
        ...mockField,
        default: false
      })

      wrapper = mountField(HiddenField, {
        field: fieldWithBooleanDefault,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('false')
    })
  })

  describe('Component Structure', () => {
    it('renders only the input element', () => {
      wrapper = mountField(HiddenField, { field: mockField })

      // Should only have one child element (the input)
      expect(wrapper.element.tagName).toBe('INPUT')
      expect(wrapper.element.type).toBe('hidden')
    })

    it('does not render any visible content', () => {
      wrapper = mountField(HiddenField, { field: mockField })

      // Hidden field should not be visible
      expect(wrapper.element.offsetHeight).toBe(0)
      expect(wrapper.element.offsetWidth).toBe(0)
    })
  })
})
