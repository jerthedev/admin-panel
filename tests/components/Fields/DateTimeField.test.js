import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import DateTimeField from '@/components/Fields/DateTimeField.vue'
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
  ClockIcon: { template: '<div data-testid="clock-icon"></div>' }
}))

describe('DateTimeField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Event DateTime',
      attribute: 'event_datetime',
      type: 'datetime',
      minDateTime: '2020-01-01T00:00',
      maxDateTime: '2030-12-31T23:59',
      step: 60,
      timezone: 'UTC'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders datetime-local input field', () => {
      wrapper = mountField(DateTimeField, { field: mockField })

      const input = wrapper.find('input[type="datetime-local"]')
      expect(input.exists()).toBe(true)
    })

    it('renders with model value', () => {
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: '2023-06-15T14:30:00'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-06-15T14:30')
    })

    it('applies min and max datetime constraints', () => {
      wrapper = mountField(DateTimeField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('min')).toBe('2020-01-01T00:00')
      expect(input.attributes('max')).toBe('2030-12-31T23:59')
    })

    it('applies step attribute', () => {
      wrapper = mountField(DateTimeField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('step')).toBe('60')
    })

    it('shows clock icon', () => {
      wrapper = mountField(DateTimeField, { field: mockField })

      const clockIcon = wrapper.find('[data-testid="clock-icon"]')
      expect(clockIcon.exists()).toBe(true)
    })

    it('applies disabled state', () => {
      wrapper = mountField(DateTimeField, {
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
      wrapper = mountField(DateTimeField, {
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

  describe('DateTime Formatting', () => {
    it('formats ISO datetime string for input', () => {
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: '2023-06-15T14:30:00.000Z'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-06-15T14:30')
    })

    it('handles Date object input', () => {
      const date = new Date('2023-06-15T14:30:00Z')
      
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: date.toISOString()
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-06-15T14:30')
    })

    it('handles datetime without seconds', () => {
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: '2023-06-15T14:30'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-06-15T14:30')
    })

    it('handles invalid datetime gracefully', () => {
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: 'invalid-datetime'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('invalid-datetime')
    })

    it('handles null value', () => {
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })
  })

  describe('Step Configuration', () => {
    it('uses default step when not provided', () => {
      const fieldWithoutStep = createMockField({
        ...mockField,
        step: undefined
      })

      wrapper = mountField(DateTimeField, { field: fieldWithoutStep })

      const input = wrapper.find('input')
      expect(input.attributes('step')).toBe('60') // Default 1 minute
    })

    it('converts step minutes to seconds', () => {
      const fieldWithMinuteStep = createMockField({
        ...mockField,
        step: 15 // 15 minutes
      })

      wrapper = mountField(DateTimeField, { field: fieldWithMinuteStep })

      const input = wrapper.find('input')
      expect(input.attributes('step')).toBe('900') // 15 * 60 = 900 seconds
    })

    it('handles second-based steps', () => {
      const fieldWithSecondStep = createMockField({
        ...mockField,
        stepInSeconds: 30
      })

      wrapper = mountField(DateTimeField, { field: fieldWithSecondStep })

      const input = wrapper.find('input')
      expect(input.attributes('step')).toBe('30')
    })
  })

  describe('Event Handling', () => {
    it('emits update:modelValue on input', async () => {
      wrapper = mountField(DateTimeField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('2023-07-20T16:45')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('2023-07-20T16:45:00')
    })

    it('emits change event', async () => {
      wrapper = mountField(DateTimeField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('2023-07-20T16:45')
      await input.trigger('change')

      expect(wrapper.emitted('change')[0][0]).toBe('2023-07-20T16:45:00')
    })

    it('emits null for empty input', async () => {
      wrapper = mountField(DateTimeField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
    })

    it('emits focus event', async () => {
      wrapper = mountField(DateTimeField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(DateTimeField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })
  })

  describe('Timezone Support', () => {
    it('shows timezone information when set', () => {
      const timezoneField = createMockField({
        ...mockField,
        timezone: 'America/New_York'
      })

      wrapper = mountField(DateTimeField, { field: timezoneField })

      expect(wrapper.text()).toContain('Timezone: America/New_York')
    })

    it('does not show timezone for UTC', () => {
      wrapper = mountField(DateTimeField, { field: mockField })

      expect(wrapper.text()).not.toContain('Timezone: UTC')
    })

    it('handles timezone conversion', () => {
      const timezoneField = createMockField({
        ...mockField,
        timezone: 'America/Los_Angeles'
      })

      wrapper = mountField(DateTimeField, {
        field: timezoneField,
        modelValue: '2023-06-15T14:30:00-07:00'
      })

      // Should handle timezone-aware datetime
      expect(wrapper.vm.formattedValue).toBeDefined()
    })
  })

  describe('Display Features', () => {
    it('shows formatted display in readonly mode', () => {
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: '2023-06-15T14:30:00',
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      expect(wrapper.text()).toContain('Formatted:')
    })

    it('shows relative time when enabled', () => {
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: '2023-06-15T14:30:00',
        showRelativeTime: true
      })

      // Should show relative time display
      expect(wrapper.vm.relativeTime).toBeDefined()
    })

    it('does not show formatted display when no value', () => {
      wrapper = mountField(DateTimeField, {
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

      wrapper = mountField(DateTimeField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('applies dark theme to formatted display', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: '2023-06-15T14:30:00',
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
      wrapper = mountField(DateTimeField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the input', async () => {
      wrapper = mountField(DateTimeField, { field: mockField })

      const input = wrapper.find('input')
      const focusSpy = vi.spyOn(input.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles field without min/max datetime', () => {
      const fieldWithoutLimits = createMockField({
        ...mockField,
        minDateTime: undefined,
        maxDateTime: undefined
      })

      wrapper = mountField(DateTimeField, { field: fieldWithoutLimits })

      const input = wrapper.find('input')
      expect(input.attributes('min')).toBeUndefined()
      expect(input.attributes('max')).toBeUndefined()
    })

    it('handles leap year datetime', () => {
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: '2024-02-29T12:00:00' // Leap year
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2024-02-29T12:00')
    })

    it('handles daylight saving time transitions', () => {
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: '2023-03-12T02:30:00' // DST transition in US
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-03-12T02:30')
    })

    it('handles midnight datetime', () => {
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: '2023-06-15T00:00:00'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-06-15T00:00')
    })

    it('handles end of day datetime', () => {
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: '2023-06-15T23:59:59'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-06-15T23:59')
    })

    it('handles microseconds in datetime', () => {
      wrapper = mountField(DateTimeField, {
        field: mockField,
        modelValue: '2023-06-15T14:30:00.123456Z'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-06-15T14:30')
    })
  })
})
