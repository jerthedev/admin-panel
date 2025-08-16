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

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  ChevronUpIcon: { template: '<div data-testid="chevron-up-icon"></div>' },
  ChevronDownIcon: { template: '<div data-testid="chevron-down-icon"></div>' }
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

  describe('Increment/Decrement Buttons', () => {
    it('shows buttons when showButtons is true', () => {
      const fieldWithButtons = createMockField({
        ...mockField,
        showButtons: true
      })

      wrapper = mountField(NumberField, { field: fieldWithButtons })

      const incrementButton = wrapper.find('[data-testid="chevron-up-icon"]').element.parentElement
      const decrementButton = wrapper.find('[data-testid="chevron-down-icon"]').element.parentElement

      expect(incrementButton).toBeTruthy()
      expect(decrementButton).toBeTruthy()
    })

    it('hides buttons when showButtons is false', () => {
      const fieldWithoutButtons = createMockField({
        ...mockField,
        showButtons: false
      })

      wrapper = mountField(NumberField, { field: fieldWithoutButtons })

      expect(wrapper.find('[data-testid="chevron-up-icon"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="chevron-down-icon"]').exists()).toBe(false)
    })

    it('increments value when increment button is clicked', async () => {
      const fieldWithButtons = createMockField({
        ...mockField,
        showButtons: true
      })

      wrapper = mountField(NumberField, {
        field: fieldWithButtons,
        modelValue: 5
      })

      const incrementButton = wrapper.find('[data-testid="chevron-up-icon"]').element.parentElement
      await incrementButton.click()

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(6)
      expect(wrapper.emitted('change')[0][0]).toBe(6)
    })

    it('decrements value when decrement button is clicked', async () => {
      const fieldWithButtons = createMockField({
        ...mockField,
        showButtons: true
      })

      wrapper = mountField(NumberField, {
        field: fieldWithButtons,
        modelValue: 5
      })

      const decrementButton = wrapper.find('[data-testid="chevron-down-icon"]').element.parentElement
      await decrementButton.click()

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(4)
      expect(wrapper.emitted('change')[0][0]).toBe(4)
    })

    it('respects max value when incrementing', async () => {
      const fieldWithButtons = createMockField({
        ...mockField,
        showButtons: true,
        max: 10
      })

      wrapper = mountField(NumberField, {
        field: fieldWithButtons,
        modelValue: 10
      })

      const incrementButton = wrapper.find('[data-testid="chevron-up-icon"]').element.parentElement
      await incrementButton.click()

      // Should not emit since we're at max
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('respects min value when decrementing', async () => {
      const fieldWithButtons = createMockField({
        ...mockField,
        showButtons: true,
        min: 0
      })

      wrapper = mountField(NumberField, {
        field: fieldWithButtons,
        modelValue: 0
      })

      const decrementButton = wrapper.find('[data-testid="chevron-down-icon"]').element.parentElement
      await decrementButton.click()

      // Should not emit since we're at min
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('disables increment button at max value', () => {
      const fieldWithButtons = createMockField({
        ...mockField,
        showButtons: true,
        max: 10
      })

      wrapper = mountField(NumberField, {
        field: fieldWithButtons,
        modelValue: 10
      })

      const incrementButton = wrapper.find('[data-testid="chevron-up-icon"]').element.parentElement
      expect(incrementButton.disabled).toBe(true)
    })

    it('disables decrement button at min value', () => {
      const fieldWithButtons = createMockField({
        ...mockField,
        showButtons: true,
        min: 0
      })

      wrapper = mountField(NumberField, {
        field: fieldWithButtons,
        modelValue: 0
      })

      const decrementButton = wrapper.find('[data-testid="chevron-down-icon"]').element.parentElement
      expect(decrementButton.disabled).toBe(true)
    })
  })

  describe('Step Functionality', () => {
    it('uses custom step value for increment/decrement', async () => {
      const fieldWithStep = createMockField({
        ...mockField,
        showButtons: true,
        step: 5
      })

      wrapper = mountField(NumberField, {
        field: fieldWithStep,
        modelValue: 10
      })

      const incrementButton = wrapper.find('[data-testid="chevron-up-icon"]').element.parentElement
      await incrementButton.click()

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(15)
    })

    it('handles decimal step values', async () => {
      const fieldWithDecimalStep = createMockField({
        ...mockField,
        showButtons: true,
        step: 0.5
      })

      wrapper = mountField(NumberField, {
        field: fieldWithDecimalStep,
        modelValue: 1.0
      })

      const incrementButton = wrapper.find('[data-testid="chevron-up-icon"]').element.parentElement
      await incrementButton.click()

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(1.5)
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


  })





  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(NumberField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
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
    it('handles zero value correctly', () => {
      wrapper = mountField(NumberField, {
        field: mockField,
        modelValue: 0
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
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

    it('handles increment from null value', async () => {
      const fieldWithButtons = createMockField({
        ...mockField,
        showButtons: true
      })

      wrapper = mountField(NumberField, {
        field: fieldWithButtons,
        modelValue: null
      })

      const incrementButton = wrapper.find('[data-testid="chevron-up-icon"]').element.parentElement
      await incrementButton.click()

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(1)
    })
  })
})
