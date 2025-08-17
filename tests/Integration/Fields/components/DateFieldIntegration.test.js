import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import DateField from '@/components/Fields/DateField.vue'
import { createMockField, mountField } from '../../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  CalendarDaysIcon: { template: '<div data-testid="calendar-days-icon"></div>' },
  XMarkIcon: { template: '<div data-testid="x-mark-icon"></div>' },
  ChevronLeftIcon: { template: '<div data-testid="chevron-left-icon"></div>' },
  ChevronRightIcon: { template: '<div data-testid="chevron-right-icon"></div>' }
}))

describe('DateField Integration Tests', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Event Date',
      attribute: 'event_date',
      type: 'date',
      displayFormat: 'Y-m-d',
      storageFormat: 'Y-m-d',
      minDate: '2020-01-01',
      maxDate: '2030-12-31',
      showPicker: true,
      pickerFormat: null,
      pickerDisplayFormat: null,
      firstDayOfWeek: 0
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP/Vue Interoperability', () => {
    it('correctly receives and processes PHP field configuration', () => {
      const phpField = createMockField({
        name: 'Birth Date',
        attribute: 'birth_date',
        displayFormat: 'd/m/Y',
        storageFormat: 'Y-m-d',
        minDate: '1900-01-01',
        maxDate: '2023-12-31',
        pickerFormat: 'd-m-Y',
        pickerDisplayFormat: 'DD-MM-YYYY',
        firstDayOfWeek: 1,
        showPicker: true
      })

      wrapper = mountField(DateField, {
        field: phpField,
        modelValue: '1990-05-15'
      })

      // Verify PHP configuration is properly received
      expect(wrapper.vm.field.displayFormat).toBe('d/m/Y')
      expect(wrapper.vm.field.storageFormat).toBe('Y-m-d')
      expect(wrapper.vm.field.minDate).toBe('1900-01-01')
      expect(wrapper.vm.field.maxDate).toBe('2023-12-31')
      expect(wrapper.vm.field.pickerFormat).toBe('d-m-Y')
      expect(wrapper.vm.field.pickerDisplayFormat).toBe('DD-MM-YYYY')
      expect(wrapper.vm.field.firstDayOfWeek).toBe(1)
      expect(wrapper.vm.field.showPicker).toBe(true)
    })

    it('formats dates according to PHP pickerFormat configuration', () => {
      const fieldWithPickerFormat = createMockField({
        ...mockField,
        pickerFormat: 'd-m-Y'
      })

      wrapper = mountField(DateField, {
        field: fieldWithPickerFormat,
        modelValue: '2023-06-15'
      })

      // Should format according to pickerFormat
      expect(wrapper.vm.formattedValue).toBe('15-06-2023')
    })

    it('displays dates according to PHP pickerDisplayFormat configuration', () => {
      const fieldWithPickerDisplayFormat = createMockField({
        ...mockField,
        pickerDisplayFormat: 'DD-MM-YYYY'
      })

      wrapper = mountField(DateField, {
        field: fieldWithPickerDisplayFormat,
        modelValue: '2023-06-15'
      })

      // Should display according to pickerDisplayFormat
      expect(wrapper.vm.displayValue).toBe('15-06-2023')
    })
  })

  describe('CRUD Operations Integration', () => {
    it('handles create operation with proper data flow', async () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: null
      })

      const input = wrapper.find('input')
      await input.setValue('2023-07-20')
      await input.trigger('input')

      // Should emit proper value for backend processing
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('2023-07-20')
    })

    it('handles read operation with formatted display', () => {
      const readField = createMockField({
        ...mockField,
        displayFormat: 'd/m/Y'
      })

      wrapper = mountField(DateField, {
        field: readField,
        modelValue: '2023-06-15'
      })

      // Should display formatted value for reading
      expect(wrapper.vm.displayValue).toBe('15/06/2023')
    })

    it('handles update operation with validation', async () => {
      const updateField = createMockField({
        ...mockField,
        minDate: '2023-01-01',
        maxDate: '2023-12-31'
      })

      wrapper = mountField(DateField, {
        field: updateField,
        modelValue: '2023-06-15'
      })

      const input = wrapper.find('input')
      expect(input.attributes('min')).toBe('2023-01-01')
      expect(input.attributes('max')).toBe('2023-12-31')

      await input.setValue('2023-08-25')
      await input.trigger('change')

      expect(wrapper.emitted('change')[0][0]).toBe('2023-08-25')
    })

    it('handles delete operation (clearing value)', async () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: '2023-06-15'
      })

      const clearButton = wrapper.find('[data-testid="x-mark-icon"]').element.parentElement
      await clearButton.click()

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
      expect(wrapper.emitted('change')[0][0]).toBe(null)
    })
  })

  describe('Nova API Compatibility Integration', () => {
    it('integrates all Nova API features together', () => {
      const novaCompatibleField = createMockField({
        name: 'Nova Date',
        attribute: 'nova_date',
        displayFormat: 'd/m/Y',
        storageFormat: 'Y-m-d',
        minDate: '2020-01-01',
        maxDate: '2030-12-31',
        pickerFormat: 'd-m-Y',
        pickerDisplayFormat: 'DD-MM-YYYY',
        firstDayOfWeek: 1,
        showPicker: true
      })

      wrapper = mountField(DateField, {
        field: novaCompatibleField,
        modelValue: '2023-06-15'
      })

      // Verify all Nova features work together
      expect(wrapper.vm.formattedValue).toBe('15-06-2023') // pickerFormat
      expect(wrapper.vm.field.firstDayOfWeek).toBe(1) // firstDayOfWeek
      expect(wrapper.vm.field.showPicker).toBe(true) // showPicker

      const input = wrapper.find('input')
      expect(input.attributes('min')).toBe('2020-01-01')
      expect(input.attributes('max')).toBe('2030-12-31')
    })

    it('maintains backward compatibility with existing API', () => {
      const legacyField = createMockField({
        name: 'Legacy Date',
        attribute: 'legacy_date',
        displayFormat: 'Y-m-d',
        storageFormat: 'Y-m-d',
        minDate: '2020-01-01',
        maxDate: '2030-12-31',
        showPicker: true
        // No Nova API properties
      })

      wrapper = mountField(DateField, {
        field: legacyField,
        modelValue: '2023-06-15'
      })

      // Should work without Nova API properties
      expect(wrapper.vm.formattedValue).toBe('2023-06-15')
      expect(wrapper.vm.displayValue).toBe('2023-06-15')
      expect(wrapper.find('input').exists()).toBe(true)
    })
  })

  describe('Error Handling Integration', () => {
    it('handles invalid date values gracefully', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: 'invalid-date'
      })

      // Should not crash and handle gracefully
      expect(wrapper.vm.formattedValue).toBe('invalid-date')
      expect(wrapper.vm.displayValue).toBe('invalid-date')
    })

    it('handles missing field properties gracefully', () => {
      const incompleteField = createMockField({
        name: 'Incomplete Date',
        attribute: 'incomplete_date'
        // Missing many properties
      })

      wrapper = mountField(DateField, {
        field: incompleteField,
        modelValue: '2023-06-15'
      })

      // Should work with defaults
      expect(wrapper.find('input').exists()).toBe(true)
      expect(wrapper.vm.formattedValue).toBe('2023-06-15')
    })
  })

  describe('Performance Integration', () => {
    it('efficiently handles rapid value changes', async () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: '2023-06-15'
      })

      const input = wrapper.find('input')
      
      // Simulate rapid changes
      for (let i = 1; i <= 5; i++) {
        await input.setValue(`2023-06-${String(15 + i).padStart(2, '0')}`)
        await input.trigger('input')
      }

      // Should handle all changes (setValue + trigger('input') each emit an event)
      expect(wrapper.emitted('update:modelValue')).toHaveLength(10)
      expect(wrapper.emitted('update:modelValue')[9][0]).toBe('2023-06-20')
    })

    it('efficiently updates computed properties', async () => {
      const fieldWithFormats = createMockField({
        ...mockField,
        pickerFormat: 'd-m-Y',
        pickerDisplayFormat: 'DD-MM-YYYY'
      })

      wrapper = mountField(DateField, {
        field: fieldWithFormats,
        modelValue: '2023-06-15'
      })

      const initialFormatted = wrapper.vm.formattedValue
      const initialDisplay = wrapper.vm.displayValue

      // Change value
      await wrapper.setProps({ modelValue: '2023-07-20' })

      // Should update computed properties
      expect(wrapper.vm.formattedValue).not.toBe(initialFormatted)
      expect(wrapper.vm.displayValue).not.toBe(initialDisplay)
      expect(wrapper.vm.formattedValue).toBe('20-07-2023')
      expect(wrapper.vm.displayValue).toBe('20-07-2023')
    })
  })
})
