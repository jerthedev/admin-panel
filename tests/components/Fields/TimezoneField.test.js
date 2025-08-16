import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import TimezoneField from '@/components/Fields/TimezoneField.vue'
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
  ChevronDownIcon: {
    name: 'ChevronDownIcon',
    template: '<svg data-testid="chevron-down-icon"></svg>'
  },
  CheckIcon: {
    name: 'CheckIcon',
    template: '<svg data-testid="check-icon"></svg>'
  }
}))

// Mock BaseField component
vi.mock('@/components/Fields/BaseField.vue', () => ({
  default: {
    name: 'BaseField',
    template: '<div class="base-field"><slot /></div>',
    props: ['field', 'modelValue', 'errors', 'disabled', 'readonly', 'size']
  }
}))

// Mock Intl.DateTimeFormat
global.Intl = {
  DateTimeFormat: vi.fn().mockImplementation((locale, options) => ({
    format: vi.fn().mockReturnValue('12:34:56 PM')
  }))
}

describe('TimezoneField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Timezone',
      attribute: 'timezone',
      type: 'timezone',
      placeholder: 'Select timezone...',
      searchable: true,
      groupByRegion: false,
      timezones: {
        'America/New_York': 'Eastern Time (New York)',
        'America/Chicago': 'Central Time (Chicago)',
        'America/Denver': 'Mountain Time (Denver)',
        'America/Los_Angeles': 'Pacific Time (Los Angeles)',
        'Europe/London': 'Greenwich Mean Time (London)',
        'Europe/Paris': 'Central European Time (Paris)',
        'Asia/Tokyo': 'Japan Standard Time (Tokyo)',
        'Australia/Sydney': 'Australian Eastern Time (Sydney)'
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
    it('renders timezone dropdown with placeholder', () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: ''
      })

      expect(wrapper.text()).toContain('Select timezone...')
      expect(wrapper.find('[data-testid="chevron-down-icon"]').exists()).toBe(true)
    })

    it('displays selected timezone when modelValue is provided', () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: 'America/New_York'
      })

      expect(wrapper.text()).toContain('Eastern Time (New York)')
    })

    it('shows custom placeholder when specified', () => {
      const fieldWithPlaceholder = createMockField({
        ...mockField,
        placeholder: 'Choose your timezone'
      })

      wrapper = mountField(TimezoneField, {
        field: fieldWithPlaceholder,
        modelValue: ''
      })

      expect(wrapper.text()).toContain('Choose your timezone')
    })

    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: ''
      })

      const input = wrapper.find('.admin-input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('applies error styling when errors are present', () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: '',
        errors: { timezone: ['Timezone is required'] }
      })

      const input = wrapper.find('.admin-input')
      expect(input.classes()).toContain('border-red-300')
    })


  })

  describe('Dropdown Functionality', () => {
    beforeEach(() => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: ''
      })
    })

    it('opens dropdown when clicked', async () => {
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.vm.isOpen).toBe(true)
      expect(wrapper.find('.absolute.z-10').exists()).toBe(true)
    })

    it('closes dropdown when clicked outside', async () => {
      // Open dropdown first
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
      expect(wrapper.vm.isOpen).toBe(true)

      // Simulate click outside
      const outsideEvent = new Event('click')
      Object.defineProperty(outsideEvent, 'target', { value: document.body })
      document.dispatchEvent(outsideEvent)
      await nextTick()

      expect(wrapper.vm.isOpen).toBe(false)
    })

    it('does not open dropdown when disabled', async () => {
      await wrapper.setProps({ disabled: true })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.vm.isOpen).toBe(false)
    })

    it('does not open dropdown when readonly', async () => {
      await wrapper.setProps({ readonly: true })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.vm.isOpen).toBe(false)
    })

    it('rotates chevron icon when dropdown is open', async () => {
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      const chevron = wrapper.find('[data-testid="chevron-down-icon"]')
      expect(chevron.classes()).toContain('rotate-180')
    })

    it('displays timezone options in dropdown', async () => {
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.text()).toContain('Eastern Time (New York)')
      expect(wrapper.text()).toContain('Pacific Time (Los Angeles)')
      expect(wrapper.text()).toContain('Greenwich Mean Time (London)')
    })

    it('shows check icon for selected timezone', async () => {
      await wrapper.setProps({ modelValue: 'America/New_York' })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.find('[data-testid="check-icon"]').exists()).toBe(true)
    })
  })

  describe('Timezone Selection', () => {
    beforeEach(() => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: ''
      })
    })

    it('selects timezone when option is clicked', async () => {
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      // Find and click a timezone option
      const timezoneOption = wrapper.findAll('.cursor-pointer').find(el =>
        el.text().includes('Eastern Time (New York)')
      )
      await timezoneOption.trigger('click')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('America/New_York')
      expect(wrapper.emitted('change')[0][0]).toBe('America/New_York')
      expect(wrapper.vm.isOpen).toBe(false)
    })

    it('does not select timezone when disabled', async () => {
      await wrapper.setProps({ disabled: true })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      // Should not open dropdown, so no timezone options to click
      expect(wrapper.vm.isOpen).toBe(false)
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('does not select timezone when readonly', async () => {
      await wrapper.setProps({ readonly: true })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      // Should not open dropdown, so no timezone options to click
      expect(wrapper.vm.isOpen).toBe(false)
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })


  })

  describe('Search Functionality', () => {
    beforeEach(() => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: ''
      })
    })

    it('shows search input when dropdown is open and field is searchable', async () => {
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
      await nextTick()

      const searchInput = wrapper.find('input[type="text"]')
      expect(searchInput.exists()).toBe(true)
      expect(searchInput.attributes('placeholder')).toBe('Search timezones...')
    })

    it('hides search input when field is not searchable', async () => {
      const nonSearchableField = createMockField({
        ...mockField,
        searchable: false
      })

      wrapper = mountField(TimezoneField, {
        field: nonSearchableField,
        modelValue: ''
      })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
      await nextTick()

      const searchInput = wrapper.find('input[type="text"]')
      expect(searchInput.exists()).toBe(false)
    })

    it('filters timezones based on search query', async () => {
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
      await nextTick()

      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.setValue('New York')
      await nextTick()

      expect(wrapper.text()).toContain('Eastern Time (New York)')
      expect(wrapper.text()).not.toContain('Pacific Time (Los Angeles)')
    })



    it('closes dropdown when escape key is pressed', async () => {
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
      await nextTick()

      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.trigger('keydown', { key: 'Escape' })

      expect(wrapper.vm.isOpen).toBe(false)
      expect(wrapper.vm.searchQuery).toBe('')
    })

    it('prevents event propagation when search input is clicked', async () => {
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
      await nextTick()

      const searchInput = wrapper.find('input[type="text"]')
      const clickSpy = vi.fn()
      searchInput.element.addEventListener('click', clickSpy)

      await searchInput.trigger('click')

      // The dropdown should remain open (not toggle)
      expect(wrapper.vm.isOpen).toBe(true)
    })
  })

  describe('Regional Grouping', () => {
    beforeEach(() => {
      const groupedField = createMockField({
        ...mockField,
        groupByRegion: true,
        searchable: false,
        timezones: {
          'North America': {
            'America/New_York': 'Eastern Time (New York)',
            'America/Chicago': 'Central Time (Chicago)',
            'America/Los_Angeles': 'Pacific Time (Los Angeles)'
          },
          'Europe': {
            'Europe/London': 'Greenwich Mean Time (London)',
            'Europe/Paris': 'Central European Time (Paris)'
          },
          'Asia': {
            'Asia/Tokyo': 'Japan Standard Time (Tokyo)'
          }
        }
      })

      wrapper = mountField(TimezoneField, {
        field: groupedField,
        modelValue: ''
      })
    })

    it('displays timezones grouped by region', async () => {
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.text()).toContain('North America')
      expect(wrapper.text()).toContain('Europe')
      expect(wrapper.text()).toContain('Asia')
    })

    it('shows region headers with proper styling', async () => {
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      const regionHeaders = wrapper.findAll('.text-xs.font-semibold')
      expect(regionHeaders).toHaveLength(3)
      expect(regionHeaders[0].text()).toBe('North America')
      expect(regionHeaders[1].text()).toBe('Europe')
      expect(regionHeaders[2].text()).toBe('Asia')
    })

    it('finds selected timezone in grouped structure', async () => {
      await wrapper.setProps({ modelValue: 'America/New_York' })

      expect(wrapper.vm.selectedTimezone).toBe('Eastern Time (New York)')
    })

    it('applies dark theme to region headers', async () => {
      mockAdminStore.isDarkTheme = true

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      const regionHeader = wrapper.find('.text-xs.font-semibold')
      expect(regionHeader.classes()).toContain('text-gray-300')
      expect(regionHeader.classes()).toContain('bg-gray-700')
    })


  })

  describe('Current Time Display', () => {
    beforeEach(() => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: 'America/New_York'
      })
    })



    it('hides current time when readonly', async () => {
      await wrapper.setProps({ readonly: true })

      expect(wrapper.text()).not.toContain('Current time:')
    })

    it('hides current time when disabled', async () => {
      await wrapper.setProps({ disabled: true })

      expect(wrapper.text()).not.toContain('Current time:')
    })

    it('hides current time when no timezone is selected', async () => {
      await wrapper.setProps({ modelValue: '' })

      expect(wrapper.text()).not.toContain('Current time:')
    })

    it('handles invalid timezone gracefully', async () => {
      // Mock DateTimeFormat to throw an error
      global.Intl.DateTimeFormat = vi.fn().mockImplementation(() => {
        throw new Error('Invalid timezone')
      })

      await wrapper.setProps({ modelValue: 'Invalid/Timezone' })
      wrapper.vm.updateCurrentTime()
      await nextTick()

      expect(wrapper.text()).toContain('Invalid timezone')
    })
  })

  describe('Utility Methods', () => {
    beforeEach(() => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: ''
      })
    })



    it('clears time interval when timezone is cleared', async () => {
      const clearIntervalSpy = vi.spyOn(global, 'clearInterval')

      // Set a timezone first
      await wrapper.setProps({ modelValue: 'America/New_York' })

      // Then clear it
      await wrapper.setProps({ modelValue: '' })

      expect(clearIntervalSpy).toHaveBeenCalled()
    })


  })

  describe('Edge Cases', () => {
    it('handles missing timezones gracefully', () => {
      const fieldWithoutTimezones = createMockField({
        ...mockField,
        timezones: undefined
      })

      wrapper = mountField(TimezoneField, {
        field: fieldWithoutTimezones,
        modelValue: ''
      })

      expect(wrapper.vm.timezones).toEqual({})
    })

    it('handles empty timezones object', () => {
      const fieldWithEmptyTimezones = createMockField({
        ...mockField,
        timezones: {}
      })

      wrapper = mountField(TimezoneField, {
        field: fieldWithEmptyTimezones,
        modelValue: ''
      })

      expect(wrapper.vm.filteredTimezones).toEqual({})
    })

    it('returns modelValue as selectedTimezone when not found in timezones', () => {
      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: 'Unknown/Timezone'
      })

      expect(wrapper.vm.selectedTimezone).toBe('Unknown/Timezone')
    })



    it('applies dark theme to dropdown menu', async () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(TimezoneField, {
        field: mockField,
        modelValue: ''
      })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      const dropdownMenu = wrapper.find('.absolute.z-10')
      expect(dropdownMenu.classes()).toContain('bg-gray-800')
      expect(dropdownMenu.classes()).toContain('border-gray-600')
    })


  })
})
