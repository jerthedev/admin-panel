import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import CurrencyField from '@/components/Fields/CurrencyField.vue'
import { createMockField, mountField } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('CurrencyField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Price',
      attribute: 'price',
      type: 'currency',
      symbol: '$',
      precision: 2,
      currency: 'USD',
      locale: 'en_US'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders currency input field', () => {
      wrapper = mountField(CurrencyField, { field: mockField })

      const input = wrapper.find('input[type="number"]')
      expect(input.exists()).toBe(true)
    })

    it('displays currency symbol in placeholder', () => {
      wrapper = mountField(CurrencyField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('$0.00')
    })

    it('renders with model value', () => {
      wrapper = mountField(CurrencyField, {
        field: mockField,
        modelValue: 123.45
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('123.45')
    })

    it('applies disabled state', () => {
      wrapper = mountField(CurrencyField, {
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
      wrapper = mountField(CurrencyField, {
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

  describe('Currency Configuration', () => {
    it('uses custom currency symbol', () => {
      const euroField = createMockField({
        ...mockField,
        symbol: '€',
        currency: 'EUR'
      })

      wrapper = mountField(CurrencyField, { field: euroField })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('€0.00')
    })

    it('handles different precision values', () => {
      const precisionField = createMockField({
        ...mockField,
        precision: 3
      })

      wrapper = mountField(CurrencyField, { field: precisionField })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('$0.000')
    })

    it('handles missing symbol gracefully', () => {
      const noSymbolField = createMockField({
        ...mockField,
        symbol: undefined
      })

      wrapper = mountField(CurrencyField, { field: noSymbolField })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('0.00')
    })

    it('applies step attribute based on precision', () => {
      wrapper = mountField(CurrencyField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('step')).toBe('0.01')
    })

    it('applies step for different precision', () => {
      const precisionField = createMockField({
        ...mockField,
        precision: 3
      })

      wrapper = mountField(CurrencyField, { field: precisionField })

      const input = wrapper.find('input')
      expect(input.attributes('step')).toBe('0.001')
    })
  })

  describe('Value Validation and Range', () => {
    it('applies min value constraint', () => {
      const minField = createMockField({
        ...mockField,
        minValue: 10.00
      })

      wrapper = mountField(CurrencyField, { field: minField })

      const input = wrapper.find('input')
      expect(input.attributes('min')).toBe('10')
    })

    it('applies max value constraint', () => {
      const maxField = createMockField({
        ...mockField,
        maxValue: 1000.00
      })

      wrapper = mountField(CurrencyField, { field: maxField })

      const input = wrapper.find('input')
      expect(input.attributes('max')).toBe('1000')
    })

    it('displays range information when min and max are set', () => {
      const rangeField = createMockField({
        ...mockField,
        minValue: 10.00,
        maxValue: 1000.00
      })

      wrapper = mountField(CurrencyField, { field: rangeField })

      expect(wrapper.text()).toContain('$10 - $1000')
    })

    it('displays min only information', () => {
      const minField = createMockField({
        ...mockField,
        minValue: 10.00
      })

      wrapper = mountField(CurrencyField, { field: minField })

      expect(wrapper.text()).toContain('Min: $10')
    })

    it('displays max only information', () => {
      const maxField = createMockField({
        ...mockField,
        maxValue: 1000.00
      })

      wrapper = mountField(CurrencyField, { field: maxField })

      expect(wrapper.text()).toContain('Max: $1000')
    })
  })

  describe('Event Handling', () => {
    it('emits update:modelValue on input with numeric value', async () => {
      wrapper = mountField(CurrencyField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('123.45')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(123.45)
      expect(wrapper.emitted('change')[0][0]).toBe(123.45)
    })

    it('emits null for empty input', async () => {
      wrapper = mountField(CurrencyField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
      expect(wrapper.emitted('change')[0][0]).toBe(null)
    })

    it('handles invalid numeric input', async () => {
      wrapper = mountField(CurrencyField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('abc')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
      expect(wrapper.emitted('change')[0][0]).toBe(null)
    })

    it('emits focus event', async () => {
      wrapper = mountField(CurrencyField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(CurrencyField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })
  })

  describe('Display Value', () => {
    it('displays null value as empty string', () => {
      wrapper = mountField(CurrencyField, {
        field: mockField,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('displays undefined value as empty string', () => {
      wrapper = mountField(CurrencyField, {
        field: mockField,
        modelValue: undefined
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('converts numeric value to string for display', () => {
      wrapper = mountField(CurrencyField, {
        field: mockField,
        modelValue: 42
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('42')
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(CurrencyField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('does not apply dark theme classes when light theme is active', () => {
      mockAdminStore.isDarkTheme = false

      wrapper = mountField(CurrencyField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).not.toContain('admin-input-dark')
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(CurrencyField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the input', async () => {
      wrapper = mountField(CurrencyField, { field: mockField })

      const input = wrapper.find('input')
      const focusSpy = vi.spyOn(input.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles zero value correctly', () => {
      wrapper = mountField(CurrencyField, {
        field: mockField,
        modelValue: 0
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('0')
    })

    it('handles negative values when allowed', () => {
      wrapper = mountField(CurrencyField, {
        field: mockField,
        modelValue: -50.25
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('-50.25')
    })

    it('handles very large numbers', () => {
      wrapper = mountField(CurrencyField, {
        field: mockField,
        modelValue: 999999.99
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('999999.99')
    })

    it('handles precision with no symbol', () => {
      const noSymbolField = createMockField({
        ...mockField,
        symbol: null,
        precision: 4
      })

      wrapper = mountField(CurrencyField, { field: noSymbolField })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('0.0000')
    })
  })
})
