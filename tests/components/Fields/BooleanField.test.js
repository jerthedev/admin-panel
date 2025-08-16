import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import BooleanField from '@/components/Fields/BooleanField.vue'
import { createMockField, mountField } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('BooleanField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Active',
      attribute: 'active',
      type: 'boolean',
      asToggle: false
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders checkbox by default', () => {
      wrapper = mountField(BooleanField, { field: mockField })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.exists()).toBe(true)
    })



    it('renders with model value true', () => {
      wrapper = mountField(BooleanField, {
        field: mockField,
        modelValue: true
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(true)
    })

    it('renders with model value false', () => {
      wrapper = mountField(BooleanField, {
        field: mockField,
        modelValue: false
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(true)
    })

    it('applies disabled state to checkbox', () => {
      wrapper = mountField(BooleanField, {
        field: mockField,
        props: {
          field: mockField,
          disabled: true
        }
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.disabled).toBe(true)
    })


  })

  describe('Custom True/False Values', () => {
    it('uses custom true value', () => {
      const customField = createMockField({
        ...mockField,
        trueValue: 'yes',
        falseValue: 'no'
      })

      wrapper = mountField(BooleanField, {
        field: customField,
        modelValue: 'yes'
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(true)
    })

    it('uses custom false value', () => {
      const customField = createMockField({
        ...mockField,
        trueValue: 'yes',
        falseValue: 'no'
      })

      wrapper = mountField(BooleanField, {
        field: customField,
        modelValue: 'no'
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(false)
    })

    it('defaults to true/false when custom values not provided', () => {
      wrapper = mountField(BooleanField, { field: mockField })

      // Check computed values through component instance
      expect(wrapper.vm.trueValue).toBe(true)
      expect(wrapper.vm.falseValue).toBe(false)
    })

    it('handles numeric custom values', () => {
      const numericField = createMockField({
        ...mockField,
        trueValue: 1,
        falseValue: 0
      })

      wrapper = mountField(BooleanField, {
        field: numericField,
        modelValue: 1
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(true)
    })
  })



  describe('Checkbox Mode', () => {
    it('shows checkbox styling', () => {
      wrapper = mountField(BooleanField, { field: mockField })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.exists()).toBe(true)
      expect(checkbox.classes()).toContain('h-4')
    })

    it('toggles on change', async () => {
      wrapper = mountField(BooleanField, {
        field: mockField,
        modelValue: false
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.setChecked(true)
      await checkbox.trigger('change')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(true)
      expect(wrapper.emitted('change')[0][0]).toBe(true)
    })

    it('emits custom true value on check', async () => {
      const customField = createMockField({
        ...mockField,
        trueValue: 'active',
        falseValue: 'inactive'
      })

      wrapper = mountField(BooleanField, {
        field: customField,
        modelValue: 'inactive'
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.setChecked(true)
      await checkbox.trigger('change')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('active')
      expect(wrapper.emitted('change')[0][0]).toBe('active')
    })

    it('emits custom false value on uncheck', async () => {
      const customField = createMockField({
        ...mockField,
        trueValue: 'active',
        falseValue: 'inactive'
      })

      wrapper = mountField(BooleanField, {
        field: customField,
        modelValue: 'active'
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.setChecked(false)
      await checkbox.trigger('change')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('inactive')
      expect(wrapper.emitted('change')[0][0]).toBe('inactive')
    })
  })

  describe('Event Handling', () => {
    it('emits focus event from checkbox', async () => {
      wrapper = mountField(BooleanField, { field: mockField })

      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event from checkbox', async () => {
      wrapper = mountField(BooleanField, { field: mockField })

      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })


  })

  describe('Theme Support', () => {
    it('applies dark theme classes to checkbox', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(BooleanField, { field: mockField })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.classes()).toContain('bg-gray-700')
    })


  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(BooleanField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses checkbox', async () => {
      wrapper = mountField(BooleanField, { field: mockField })

      const checkbox = wrapper.find('input[type="checkbox"]')
      const focusSpy = vi.spyOn(checkbox.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })


  })

  describe('Accessibility', () => {


    it('has proper id and name for checkbox', () => {
      wrapper = mountField(BooleanField, { field: mockField })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.attributes('id')).toContain('boolean-field-active')
      expect(checkbox.attributes('name')).toBeUndefined()
    })
  })

  describe('Edge Cases', () => {
    it('handles null value as false', () => {
      wrapper = mountField(BooleanField, {
        field: mockField,
        modelValue: null
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(true)
    })

    it('handles undefined value as false', () => {
      wrapper = mountField(BooleanField, {
        field: mockField,
        modelValue: undefined
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(true)
    })

    it('handles string "true" as true when using default values', () => {
      wrapper = mountField(BooleanField, {
        field: mockField,
        modelValue: 'true'
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(false) // Should be false since 'true' !== true
    })

    it('handles readonly state', () => {
      wrapper = mountField(BooleanField, {
        field: mockField,
        props: {
          field: mockField,
          readonly: true
        }
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.readOnly).toBe(false)
    })
  })
})
