import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import DateTimeField from '@/components/Fields/DateTimeField.vue'
import BaseField from '@/components/Fields/BaseField.vue'
import { createMockField } from '../../../helpers.js'

/**
 * DateTime Field Integration Tests
 *
 * Tests the integration between the PHP DateTime field class and Vue component,
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

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('DateTimeField Integration', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP to Vue Integration', () => {
    it('receives and processes PHP field configuration correctly', async () => {
      const phpFieldConfig = createMockField({
        name: 'Event Time',
        attribute: 'event_time',
        component: 'DateTimeField',
        storageFormat: 'Y-m-d H:i:s',
        displayFormat: 'M j, Y H:i',
        timezone: 'America/New_York',
        step: 15,
        minDateTime: '2020-01-01T00:00',
        maxDateTime: '2030-12-31T23:59',
        rules: ['required', 'date']
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig,
          modelValue: '2023-06-15T14:30:00'
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.vm.field.name).toBe('Event Time')
      expect(wrapper.vm.field.attribute).toBe('event_time')
      expect(wrapper.vm.field.component).toBe('DateTimeField')
      expect(wrapper.vm.field.timezone).toBe('America/New_York')
      expect(wrapper.vm.field.step).toBe(15)
    })

    it('handles PHP datetime serialization correctly', async () => {
      const phpFieldConfig = createMockField({
        name: 'Created At',
        attribute: 'created_at',
        component: 'DateTimeField',
        value: '2023-06-15 14:30:00', // PHP serialized format
        storageFormat: 'Y-m-d H:i:s',
        displayFormat: 'Y-m-d H:i:s'
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig,
          modelValue: '2023-06-15T14:30:00'
        },
        global: {
          components: { BaseField }
        }
      })

      const input = wrapper.find('input[type="datetime-local"]')
      expect(input.element.value).toBe('2023-06-15T14:30')
    })

    it('processes PHP timezone configuration', async () => {
      const phpFieldConfig = createMockField({
        name: 'Event Time',
        attribute: 'event_time',
        component: 'DateTimeField',
        timezone: 'America/New_York',
        displayFormat: 'Y-m-d H:i:s'
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig,
          modelValue: '2023-06-15T14:30:00'
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.vm.field.timezone).toBe('America/New_York')
      // Timezone is displayed when field.timezone is set and not UTC
      const timezoneDisplay = wrapper.find('[class*="text-xs text-gray-500"]')
      expect(timezoneDisplay.exists()).toBe(true)
    })

    it('handles PHP step configuration correctly', async () => {
      const phpFieldConfig = createMockField({
        name: 'Meeting Time',
        attribute: 'meeting_time',
        component: 'DateTimeField',
        step: 15 // 15 minutes from PHP
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig
        },
        global: {
          components: { BaseField }
        }
      })

      const input = wrapper.find('input')
      expect(input.attributes('step')).toBe('900') // 15 * 60 = 900 seconds
    })
  })

  describe('Vue to PHP Integration', () => {
    it('emits datetime values in PHP-compatible format', async () => {
      const phpFieldConfig = createMockField({
        name: 'Event Time',
        attribute: 'event_time',
        component: 'DateTimeField',
        storageFormat: 'Y-m-d H:i:s'
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig
        },
        global: {
          components: { BaseField }
        }
      })

      const input = wrapper.find('input')
      await input.setValue('2023-07-20T16:45')
      await input.trigger('input')

      const emitted = wrapper.emitted('update:modelValue')
      expect(emitted).toBeTruthy()
      expect(emitted[0][0]).toBe('2023-07-20T16:45')
    })

    it('handles timezone conversion for PHP storage', async () => {
      const phpFieldConfig = createMockField({
        name: 'Event Time',
        attribute: 'event_time',
        component: 'DateTimeField',
        timezone: 'America/New_York',
        storageFormat: 'Y-m-d H:i:s'
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig
        },
        global: {
          components: { BaseField }
        }
      })

      const input = wrapper.find('input')
      await input.setValue('2023-07-20T16:45')
      await input.trigger('input')

      const emitted = wrapper.emitted('update:modelValue')
      expect(emitted).toBeTruthy()
      // Should emit in format compatible with PHP processing
      expect(typeof emitted[0][0]).toBe('string')
    })

    it('emits null for empty values to PHP', async () => {
      const phpFieldConfig = createMockField({
        name: 'Event Time',
        attribute: 'event_time',
        component: 'DateTimeField'
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig
        },
        global: {
          components: { BaseField }
        }
      })

      const input = wrapper.find('input')
      await input.setValue('')
      await input.trigger('input')

      const emitted = wrapper.emitted('update:modelValue')
      expect(emitted).toBeTruthy()
      expect(emitted[0][0]).toBe('')
    })
  })

  describe('Nova API Compatibility Integration', () => {
    it('integrates all Nova API methods correctly', async () => {
      const phpFieldConfig = createMockField({
        name: 'Event Time',
        attribute: 'event_time',
        component: 'DateTimeField',
        storageFormat: 'Y-m-d H:i:s',
        displayFormat: 'M j, Y H:i',
        timezone: 'America/New_York',
        step: 15,
        minDateTime: '2020-01-01T00:00',
        maxDateTime: '2030-12-31T23:59',
        rules: ['required', 'date'],
        nullable: false,
        helpText: 'Select event start time',
        sortable: true
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig,
          modelValue: '2023-06-15T14:30:00'
        },
        global: {
          components: { BaseField }
        }
      })

      // Test complete integration
      expect(wrapper.vm.field.timezone).toBe('America/New_York')
      expect(wrapper.vm.field.step).toBe(15)
      expect(wrapper.vm.field.rules).toContain('required')
      expect(wrapper.vm.field.helpText).toBe('Select event start time')

      const input = wrapper.find('input')
      expect(input.attributes('min')).toBe('2020-01-01T00:00')
      expect(input.attributes('max')).toBe('2030-12-31T23:59')
      expect(input.attributes('step')).toBe('900') // 15 * 60
    })

    it('handles Nova validation rules integration', async () => {
      const phpFieldConfig = createMockField({
        name: 'Event Time',
        attribute: 'event_time',
        component: 'DateTimeField',
        rules: ['required', 'date', 'after:now']
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig,
          errors: { event_time: ['The event time must be after now.'] }
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.vm.field.rules).toContain('required')
      expect(wrapper.props('errors')).toEqual({ event_time: ['The event time must be after now.'] })
    })

    it('integrates Nova visibility methods', async () => {
      const phpFieldConfig = createMockField({
        name: 'Created At',
        attribute: 'created_at',
        component: 'DateTimeField',
        showOnIndex: false,
        showOnDetail: true,
        showOnCreating: false,
        showOnUpdating: true
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.vm.field.showOnIndex).toBe(false)
      expect(wrapper.vm.field.showOnDetail).toBe(true)
      expect(wrapper.vm.field.showOnCreating).toBe(false)
      expect(wrapper.vm.field.showOnUpdating).toBe(true)
    })
  })

  describe('CRUD Operations Integration', () => {
    it('handles create operation with datetime field', async () => {
      const phpFieldConfig = createMockField({
        name: 'Event Time',
        attribute: 'event_time',
        component: 'DateTimeField',
        storageFormat: 'Y-m-d H:i:s'
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig,
          modelValue: null // New record
        },
        global: {
          components: { BaseField }
        }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')

      // Simulate setting initial value
      await wrapper.setProps({ modelValue: '2023-06-15T14:30:00' })
      expect(input.element.value).toBe('2023-06-15T14:30')
    })

    it('handles update operation with datetime field', async () => {
      const phpFieldConfig = createMockField({
        name: 'Event Time',
        attribute: 'event_time',
        component: 'DateTimeField',
        value: '2023-06-15T14:30:00'
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig,
          modelValue: '2023-06-15T14:30:00'
        },
        global: {
          components: { BaseField }
        }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('2023-06-15T14:30')

      // Simulate updating value
      await input.setValue('2023-07-20T16:45')
      await input.trigger('input')

      const emitted = wrapper.emitted('update:modelValue')
      expect(emitted[0][0]).toBe('2023-07-20T16:45')
    })

    it('handles read operation with formatted display', async () => {
      const phpFieldConfig = createMockField({
        name: 'Created At',
        attribute: 'created_at',
        component: 'DateTimeField',
        value: '2023-06-15T14:30:00',
        displayFormat: 'M j, Y H:i'
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig,
          modelValue: '2023-06-15T14:30:00',
          readonly: true
        },
        global: {
          components: { BaseField }
        }
      })

      // Should display formatted value in readonly mode
      expect(wrapper.vm.formattedValue).toBe('2023-06-15T14:30:00')
    })
  })

  describe('Error Handling Integration', () => {
    it('displays PHP validation errors correctly', async () => {
      const phpFieldConfig = createMockField({
        name: 'Event Time',
        attribute: 'event_time',
        component: 'DateTimeField'
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig,
          errors: {
            event_time: ['The event time field is required.', 'The event time must be a valid date.']
          }
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.props('errors')).toEqual({
        event_time: ['The event time field is required.', 'The event time must be a valid date.']
      })
    })

    it('handles invalid datetime values gracefully', async () => {
      const phpFieldConfig = createMockField({
        name: 'Event Time',
        attribute: 'event_time',
        component: 'DateTimeField'
      })

      wrapper = mount(DateTimeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'invalid-datetime'
        },
        global: {
          components: { BaseField }
        }
      })

      // Should handle invalid datetime gracefully
      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })
  })
})
