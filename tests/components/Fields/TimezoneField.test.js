import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import TimezoneField from '@/components/Fields/TimezoneField.vue'
import { createMockField, mountField } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock BaseField component
vi.mock('@/components/Fields/BaseField.vue', () => ({
  default: {
    name: 'BaseField',
    template: '<div class="base-field"><slot /></div>',
    props: ['field', 'modelValue', 'errors', 'disabled', 'readonly', 'size']
  }
}))

describe('TimezoneField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Timezone',
      attribute: 'timezone',
      type: 'timezone',
      placeholder: 'Select timezone...',
      options: {
        'America/New_York': 'America/New_York',
        'America/Chicago': 'America/Chicago',
        'America/Denver': 'America/Denver',
        'America/Los_Angeles': 'America/Los_Angeles',
        'Europe/London': 'Europe/London',
        'Europe/Paris': 'Europe/Paris',
        'Asia/Tokyo': 'Asia/Tokyo',
        'Australia/Sydney': 'Australia/Sydney',
        'UTC': 'UTC'
      }
    })

    // Mock console methods to avoid noise in tests
    vi.spyOn(console, 'log').mockImplementation(() => {})
    vi.spyOn(console, 'error').mockImplementation(() => {})
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
    vi.restoreAllMocks()
  })

  describe('Basic Rendering', () => {
    it('renders timezone select field with placeholder', () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: null
      })

      const select = wrapper.find('select')
      expect(select.exists()).toBe(true)

      const placeholder = wrapper.find('option[value=""]')
      expect(placeholder.exists()).toBe(true)
      expect(placeholder.text()).toBe('Select timezone...')
    })

    it('displays selected timezone when modelValue is provided', () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: 'America/New_York'
      })

      const select = wrapper.find('select')
      expect(select.element.value).toBe('America/New_York')
    })

    it('shows custom placeholder when specified', () => {
      const fieldWithPlaceholder = createMockField({
        ...mockField,
        placeholder: 'Choose your timezone'
      })

      wrapper = mountField(TimezoneField, {
        field: fieldWithPlaceholder,
        modelValue: null
      })

      const placeholder = wrapper.find('option[value=""]')
      expect(placeholder.text()).toBe('Choose your timezone')
    })

    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: null
      })

      const select = wrapper.find('select')
      expect(select.classes()).toContain('admin-input-dark')
    })

    it('applies error styling when errors are present', () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: null,
        errors: ['Timezone is required']
      })

      const select = wrapper.find('select')
      expect(select.classes()).toContain('border-red-300')
    })

    it('renders all timezone options', () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: null
      })

      const options = wrapper.findAll('option')
      // Should have placeholder + all timezone options
      expect(options).toHaveLength(Object.keys(mockField.options).length + 1)

      // Check specific timezones are present
      expect(wrapper.text()).toContain('America/New_York')
      expect(wrapper.text()).toContain('Europe/London')
      expect(wrapper.text()).toContain('Asia/Tokyo')
      expect(wrapper.text()).toContain('UTC')
    })
  })

  describe('Select Functionality', () => {
    beforeEach(() => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: null
      })
    })

    it('emits update:modelValue when option is selected', async () => {
      const select = wrapper.find('select')
      await select.setValue('America/New_York')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('America/New_York')
    })

    it('emits null when empty option is selected', async () => {
      const select = wrapper.find('select')
      await select.setValue('')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
    })

    it('is disabled when disabled prop is true', async () => {
      await wrapper.setProps({ disabled: true })

      const select = wrapper.find('select')
      expect(select.attributes('disabled')).toBeDefined()
      expect(select.classes()).toContain('opacity-50')
      expect(select.classes()).toContain('cursor-not-allowed')
    })

    it('is disabled when readonly prop is true', async () => {
      await wrapper.setProps({ readonly: true })

      const select = wrapper.find('select')
      expect(select.attributes('disabled')).toBeDefined()
      expect(select.classes()).toContain('opacity-50')
      expect(select.classes()).toContain('cursor-not-allowed')
    })

    it('emits focus event when focused', async () => {
      const select = wrapper.find('select')
      await select.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event when blurred', async () => {
      const select = wrapper.find('select')
      await select.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })
  })

  describe('Options Handling', () => {
    it('handles missing options gracefully', () => {
      const fieldWithoutOptions = createMockField({
        ...mockField,
        options: undefined
      })

      wrapper = mountField(TimezoneField, {
        field: fieldWithoutOptions,
        modelValue: null
      })

      expect(wrapper.vm.options).toEqual({})

      // Should only have placeholder option
      const options = wrapper.findAll('option')
      expect(options).toHaveLength(1)
      expect(options[0].attributes('value')).toBe('')
    })

    it('handles empty options object', () => {
      const fieldWithEmptyOptions = createMockField({
        ...mockField,
        options: {}
      })

      wrapper = mountField(TimezoneField, {
        field: fieldWithEmptyOptions,
        modelValue: null
      })

      expect(wrapper.vm.options).toEqual({})

      // Should only have placeholder option
      const options = wrapper.findAll('option')
      expect(options).toHaveLength(1)
    })

    it('displays timezone identifiers as both value and label', () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: null
      })

      const options = wrapper.findAll('option[value="America/New_York"]')
      expect(options).toHaveLength(1)
      expect(options[0].text()).toBe('America/New_York')
      expect(options[0].attributes('value')).toBe('America/New_York')
    })
  })

  describe('Nova API Compatibility', () => {
    it('matches Nova field structure with options', () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: null
      })

      // Should use 'options' from field meta, not 'timezones'
      expect(wrapper.vm.options).toEqual(mockField.options)
      expect(wrapper.vm.options).not.toBeUndefined()
    })

    it('emits only standard field events', async () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: null
      })

      const select = wrapper.find('select')
      await select.trigger('focus')
      await select.trigger('blur')

      // Should only emit standard Nova field events
      expect(wrapper.emitted('focus')).toBeTruthy()
      expect(wrapper.emitted('blur')).toBeTruthy()

      // The component should handle change events internally and emit update:modelValue
      // but not expose custom events beyond Nova's standard API
      expect(wrapper.emitted('update:modelValue')).toBeFalsy() // No change yet
    })

    it('uses simple select element without complex features', () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: null
      })

      // Should be a simple select element
      expect(wrapper.find('select').exists()).toBe(true)

      // Should NOT have complex dropdown features
      expect(wrapper.find('input[type="text"]').exists()).toBe(false) // No search
      expect(wrapper.find('.dropdown').exists()).toBe(false) // No custom dropdown
      expect(wrapper.text()).not.toContain('Current time:') // No time display
    })

    it('handles all timezone identifiers as simple strings', () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: null
      })

      // All options should have timezone identifier as both key and value
      Object.entries(wrapper.vm.options).forEach(([key, value]) => {
        expect(key).toBe(value)
        expect(typeof key).toBe('string')
        expect(typeof value).toBe('string')
      })
    })
  })
})
