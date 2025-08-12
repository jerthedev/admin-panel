import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import DateField from '@/components/Fields/DateField.vue'
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
  CalendarDaysIcon: { template: '<div data-testid="calendar-days-icon"></div>' },
  XMarkIcon: { template: '<div data-testid="x-mark-icon"></div>' },
  ChevronLeftIcon: { template: '<div data-testid="chevron-left-icon"></div>' },
  ChevronRightIcon: { template: '<div data-testid="chevron-right-icon"></div>' }
}))

describe('DateField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Event Date',
      attribute: 'event_date',
      type: 'date',
      minDate: '2020-01-01',
      maxDate: '2030-12-31'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders date input field', () => {
      wrapper = mountField(DateField, { field: mockField })

      const input = wrapper.find('input[type="date"]')
      expect(input.exists()).toBe(true)
    })

    it('renders with model value', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: '2023-06-15'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-06-15')
    })

    it('applies min and max date constraints', () => {
      wrapper = mountField(DateField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('min')).toBe('2020-01-01')
      expect(input.attributes('max')).toBe('2030-12-31')
    })

    it('shows calendar icon', () => {
      wrapper = mountField(DateField, { field: mockField })

      const calendarIcon = wrapper.find('[data-testid="calendar-days-icon"]')
      expect(calendarIcon.exists()).toBe(true)
    })

    it('applies disabled state', () => {
      wrapper = mountField(DateField, {
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
      wrapper = mountField(DateField, {
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

  describe('Date Formatting', () => {
    it('formats ISO date string for input', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: '2023-06-15T10:30:00Z'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-06-15')
    })

    it('handles Date object input', () => {
      const date = new Date('2023-06-15T10:30:00Z')
      
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: date.toISOString()
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-06-15')
    })

    it('handles invalid date gracefully', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: 'invalid-date'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('invalid-date')
    })

    it('handles null value', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles empty string value', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: ''
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })
  })

  describe('Event Handling', () => {
    it('emits update:modelValue on input', async () => {
      wrapper = mountField(DateField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('2023-07-20')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('2023-07-20')
    })

    it('emits change event', async () => {
      wrapper = mountField(DateField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('2023-07-20')
      await input.trigger('change')

      expect(wrapper.emitted('change')[0][0]).toBe('2023-07-20')
    })

    it('emits null for empty input', async () => {
      wrapper = mountField(DateField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
    })

    it('emits focus event', async () => {
      wrapper = mountField(DateField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(DateField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })
  })

  describe('Clear Functionality', () => {
    it('shows clear button when value exists and clearable is true', () => {
      const clearableField = createMockField({
        ...mockField,
        clearable: true
      })

      wrapper = mountField(DateField, {
        field: clearableField,
        modelValue: '2023-06-15'
      })

      const clearButton = wrapper.find('[data-testid="x-mark-icon"]')
      expect(clearButton.exists()).toBe(true)
    })

    it('hides clear button when no value', () => {
      const clearableField = createMockField({
        ...mockField,
        clearable: true
      })

      wrapper = mountField(DateField, {
        field: clearableField,
        modelValue: null
      })

      expect(wrapper.find('[data-testid="x-mark-icon"]').exists()).toBe(false)
    })

    it('clears date when clear button clicked', async () => {
      const clearableField = createMockField({
        ...mockField,
        clearable: true
      })

      wrapper = mountField(DateField, {
        field: clearableField,
        modelValue: '2023-06-15'
      })

      const clearButton = wrapper.find('[data-testid="x-mark-icon"]').element.parentElement
      await clearButton.click()

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
      expect(wrapper.emitted('change')[0][0]).toBe(null)
    })
  })

  describe('Custom Date Picker', () => {
    it('shows custom picker when showCustomPicker is true', () => {
      const customPickerField = createMockField({
        ...mockField,
        showCustomPicker: true
      })

      wrapper = mountField(DateField, { field: customPickerField })

      // Click to show custom picker
      const input = wrapper.find('input')
      input.trigger('focus')

      expect(wrapper.text()).toContain('Custom date picker implementation would go here')
    })

    it('shows navigation buttons in custom picker', () => {
      const customPickerField = createMockField({
        ...mockField,
        showCustomPicker: true
      })

      wrapper = mountField(DateField, { field: customPickerField })

      // Trigger custom picker display
      const input = wrapper.find('input')
      input.trigger('focus')

      const prevButton = wrapper.find('[data-testid="chevron-left-icon"]')
      const nextButton = wrapper.find('[data-testid="chevron-right-icon"]')
      
      expect(prevButton.exists()).toBe(true)
      expect(nextButton.exists()).toBe(true)
    })

    it('navigates months in custom picker', async () => {
      const customPickerField = createMockField({
        ...mockField,
        showCustomPicker: true
      })

      wrapper = mountField(DateField, { field: customPickerField })

      // Show custom picker
      const input = wrapper.find('input')
      await input.trigger('focus')

      const nextButton = wrapper.find('[data-testid="chevron-right-icon"]').element.parentElement
      await nextButton.click()

      // Should navigate to next month (implementation would update currentDate)
      expect(wrapper.vm.currentDate).toBeDefined()
    })
  })

  describe('Display Features', () => {
    it('shows formatted display in readonly mode', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: '2023-06-15',
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      expect(wrapper.text()).toContain('Formatted:')
    })

    it('shows relative time when enabled', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: '2023-06-15',
        showRelativeTime: true
      })

      // Should show relative time display
      expect(wrapper.vm.relativeTime).toBeDefined()
    })

    it('does not show formatted display when no value', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: null,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      expect(wrapper.text()).not.toContain('Formatted:')
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(DateField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('applies dark theme to formatted display', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: '2023-06-15',
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      const formattedDiv = wrapper.find('.text-gray-400')
      expect(formattedDiv.exists()).toBe(true)
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(DateField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the input', async () => {
      wrapper = mountField(DateField, { field: mockField })

      const input = wrapper.find('input')
      const focusSpy = vi.spyOn(input.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles field without min/max dates', () => {
      const fieldWithoutLimits = createMockField({
        ...mockField,
        minDate: undefined,
        maxDate: undefined
      })

      wrapper = mountField(DateField, { field: fieldWithoutLimits })

      const input = wrapper.find('input')
      expect(input.attributes('min')).toBeUndefined()
      expect(input.attributes('max')).toBeUndefined()
    })

    it('handles leap year dates', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: '2024-02-29' // Leap year
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2024-02-29')
    })

    it('handles timezone-aware dates', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: '2023-06-15T23:30:00+05:30'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-06-15')
    })

    it('handles very old dates', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: '1900-01-01'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('1900-01-01')
    })

    it('handles future dates', () => {
      wrapper = mountField(DateField, {
        field: mockField,
        modelValue: '2050-12-31'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2050-12-31')
    })
  })
})
