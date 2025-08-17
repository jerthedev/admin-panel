import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import NumberField from '@/components/Fields/NumberField.vue'
import { createMockField, mountField } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('NumberField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Quantity',
      attribute: 'quantity',
      type: 'number',
      min: 0,
      max: 100,
      step: 1
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders number input field', () => {
      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input[type="number"]')
      expect(input.exists()).toBe(true)
    })

    it('renders with model value', () => {
      wrapper = mountField(NumberField, {
        field: mockField,
        modelValue: 42
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('42')
    })

    it('applies min, max, and step attributes', () => {
      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('min')).toBe('0')
      expect(input.attributes('max')).toBe('100')
      expect(input.attributes('step')).toBe('1')
    })

    it('uses default step when not provided', () => {
      const fieldWithoutStep = createMockField({
        ...mockField,
        step: undefined
      })

      wrapper = mountField(NumberField, { field: fieldWithoutStep })

      const input = wrapper.find('input')
      expect(input.attributes('step')).toBe('1')
    })

    it('applies disabled state', () => {
      wrapper = mountField(NumberField, {
        field: mockField,
        props: {
          field: mockField,
          disabled: true
        }
      })

      const input = wrapper.find('input')
      expect(input.element.disabled).toBe(true)
    })

    it('applies readonly state', () => {
      wrapper = mountField(NumberField, {
        field: mockField,
        props: {
          field: mockField,
          readonly: true
        }
      })

      const input = wrapper.find('input')
      expect(input.element.readOnly).toBe(true)
    })
  })



  describe('Event Handling', () => {
    it('emits update:modelValue on input', async () => {
      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('42')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(42)
      expect(wrapper.emitted('change')[0][0]).toBe(42)
    })

    it('emits null for empty input', async () => {
      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
      expect(wrapper.emitted('change')[0][0]).toBe(null)
    })

    it('emits focus event', async () => {
      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('handles decimal input correctly', async () => {
      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('19.99')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(19.99)
      expect(wrapper.emitted('change')[0][0]).toBe(19.99)
    })

    it('handles negative input correctly', async () => {
      const fieldWithNegative = createMockField({
        ...mockField,
        min: -100
      })

      wrapper = mountField(NumberField, { field: fieldWithNegative })

      const input = wrapper.find('input')
      await input.setValue('-15')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(-15)
      expect(wrapper.emitted('change')[0][0]).toBe(-15)
    })

    it('handles zero input correctly', async () => {
      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('0')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(0)
      expect(wrapper.emitted('change')[0][0]).toBe(0)
    })
  })





  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('does not apply dark theme classes when dark theme is inactive', () => {
      mockAdminStore.isDarkTheme = false

      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).not.toContain('admin-input-dark')
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(NumberField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the input', async () => {
      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input')
      const focusSpy = vi.spyOn(input.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles zero value correctly via input event', async () => {
      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('0')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(0)
      expect(wrapper.emitted('change')[0][0]).toBe(0)
    })

    it('handles negative values', () => {
      wrapper = mountField(NumberField, {
        field: mockField,
        modelValue: -5
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('-5')
    })

    it('handles null min/max values', () => {
      const fieldWithoutLimits = createMockField({
        ...mockField,
        min: null,
        max: null
      })

      wrapper = mountField(NumberField, { field: fieldWithoutLimits })

      const input = wrapper.find('input')
      expect(input.attributes('min')).toBeUndefined()
      expect(input.attributes('max')).toBeUndefined()
    })

    it('handles null value correctly', () => {
      wrapper = mountField(NumberField, {
        field: mockField,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles large numbers correctly', () => {
      wrapper = mountField(NumberField, {
        field: mockField,
        modelValue: 1000000
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('1000000')
    })

    it('handles decimal values correctly', () => {
      wrapper = mountField(NumberField, {
        field: mockField,
        modelValue: 123.456
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('123.456')
    })
  })

  describe('Nova API Compatibility', () => {
    it('supports all Nova Number field attributes', () => {
      const novaCompatibleField = createMockField({
        name: 'Price',
        attribute: 'price',
        type: 'number',
        min: 0,
        max: 9999.99,
        step: 0.01
      })

      wrapper = mountField(NumberField, { field: novaCompatibleField })

      const input = wrapper.find('input')
      expect(input.attributes('type')).toBe('number')
      expect(input.attributes('min')).toBe('0')
      expect(input.attributes('max')).toBe('9999.99')
      expect(input.attributes('step')).toBe('0.01')
    })

    it('works without min/max/step (Nova defaults)', () => {
      const basicField = createMockField({
        name: 'Count',
        attribute: 'count',
        type: 'number'
      })

      wrapper = mountField(NumberField, { field: basicField })

      const input = wrapper.find('input')
      expect(input.attributes('type')).toBe('number')
      expect(input.attributes('step')).toBe('1') // Default step
    })

    it('handles Nova-style field configuration', async () => {
      const novaField = createMockField({
        name: 'Quantity',
        attribute: 'quantity',
        type: 'number',
        min: 1,
        max: 100,
        step: 1
      })

      wrapper = mountField(NumberField, { field: novaField })

      const input = wrapper.find('input')
      await input.setValue('50')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(50)
    })
  })
})
