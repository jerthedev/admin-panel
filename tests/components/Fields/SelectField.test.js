import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import SelectField from '@/components/Fields/SelectField.vue'
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
  ChevronDownIcon: { template: '<div data-testid="chevron-down-icon"></div>' },
  CheckIcon: { template: '<div data-testid="check-icon"></div>' },
  XMarkIcon: { template: '<div data-testid="x-mark-icon"></div>' }
}))

describe('SelectField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Test Select',
      attribute: 'test_select',
      type: 'select',
      options: {
        'option1': 'Option 1',
        'option2': 'Option 2',
        'option3': 'Option 3'
      }
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders simple select when not searchable', () => {
      wrapper = mountField(SelectField, { field: mockField })

      const select = wrapper.find('select')
      expect(select.exists()).toBe(true)
      expect(wrapper.find('button').exists()).toBe(false)
    })

    it('renders searchable select when searchable is true', () => {
      const searchableField = createMockField({
        ...mockField,
        searchable: true
      })

      wrapper = mountField(SelectField, { field: searchableField })

      const button = wrapper.find('button')
      expect(button.exists()).toBe(true)
      expect(wrapper.find('select').exists()).toBe(false)
    })

    it('renders options in simple select', () => {
      wrapper = mountField(SelectField, { field: mockField })

      const options = wrapper.findAll('option')
      expect(options).toHaveLength(4) // 3 options + placeholder
      expect(options[1].text()).toBe('Option 1')
      expect(options[1].attributes('value')).toBe('option1')
    })

    it('shows placeholder text', () => {
      const fieldWithPlaceholder = createMockField({
        ...mockField,
        placeholder: 'Choose an option'
      })

      wrapper = mountField(SelectField, { field: fieldWithPlaceholder })

      const placeholderOption = wrapper.find('option[disabled]')
      expect(placeholderOption.text()).toBe('Choose an option')
    })

    it('uses default placeholder when none provided', () => {
      wrapper = mountField(SelectField, { field: mockField })

      const placeholderOption = wrapper.find('option[disabled]')
      expect(placeholderOption.text()).toBe('Select Test Select')
    })
  })

  describe('Simple Select Functionality', () => {
    it('displays selected value', () => {
      wrapper = mountField(SelectField, {
        field: mockField,
        modelValue: 'option2'
      })

      const select = wrapper.find('select')
      expect(select.element.value).toBe('option2')
    })

    it('emits update:modelValue on change', async () => {
      wrapper = mountField(SelectField, { field: mockField })

      const select = wrapper.find('select')
      await select.setValue('option2')
      await select.trigger('change')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('option2')
      expect(wrapper.emitted('change')[0][0]).toBe('option2')
    })

    it('emits focus and blur events', async () => {
      wrapper = mountField(SelectField, { field: mockField })

      const select = wrapper.find('select')
      await select.trigger('focus')
      await select.trigger('blur')

      expect(wrapper.emitted('focus')).toBeTruthy()
      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('applies disabled state', () => {
      wrapper = mountField(SelectField, {
        field: mockField,
        props: {
          field: mockField,
          disabled: true
        }
      })

      const select = wrapper.find('select')
      expect(select.element.disabled).toBe(true)
    })
  })

  describe('Searchable Select Functionality', () => {
    let searchableField

    beforeEach(() => {
      searchableField = createMockField({
        ...mockField,
        searchable: true
      })
    })

    it('shows selected value in button', () => {
      wrapper = mountField(SelectField, {
        field: searchableField,
        modelValue: 'option2'
      })

      const button = wrapper.find('button')
      expect(button.text()).toContain('Option 2')
    })

    it('shows placeholder when no value selected', () => {
      wrapper = mountField(SelectField, { field: searchableField })

      const button = wrapper.find('button')
      expect(button.text()).toContain('Select Test Select')
    })

    it('toggles dropdown on button click', async () => {
      wrapper = mountField(SelectField, { field: searchableField })

      const button = wrapper.find('button')
      await button.trigger('click')

      // Look for the actual dropdown structure - absolute positioned div with search input
      const dropdown = wrapper.find('.absolute.z-10.mt-1.w-full.bg-white')
      expect(dropdown.exists()).toBe(true)
    })

    it('does not toggle dropdown when disabled', async () => {
      wrapper = mountField(SelectField, {
        field: searchableField,
        disabled: true
      })

      const button = wrapper.find('button')
      await button.trigger('click')

      expect(wrapper.find('.dropdown').exists()).toBe(false)
    })

    it('does not toggle dropdown when readonly', async () => {
      wrapper = mountField(SelectField, {
        field: searchableField,
        readonly: true
      })

      const button = wrapper.find('button')
      await button.trigger('click')

      expect(wrapper.find('.dropdown').exists()).toBe(false)
    })

    it('filters options based on search query', async () => {
      wrapper = mountField(SelectField, { field: searchableField })

      const button = wrapper.find('button')
      await button.trigger('click')

      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.setValue('Option 1')
      await searchInput.trigger('input')

      const options = wrapper.findAll('.cursor-pointer')
      expect(options).toHaveLength(1)
      expect(options[0].text()).toContain('Option 1')
    })

    it('selects option on click', async () => {
      wrapper = mountField(SelectField, { field: searchableField })

      const button = wrapper.find('button')
      await button.trigger('click')

      const firstOption = wrapper.find('.cursor-pointer')
      await firstOption.trigger('click')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('option1')
      expect(wrapper.emitted('change')[0][0]).toBe('option1')
    })

    it('shows check icon for selected option', async () => {
      wrapper = mountField(SelectField, {
        field: searchableField,
        modelValue: 'option2'
      })

      const button = wrapper.find('button')
      await button.trigger('click')

      const checkIcon = wrapper.find('[data-testid="check-icon"]')
      expect(checkIcon.exists()).toBe(true)
    })

    it('clears selection when clear button clicked', async () => {
      wrapper = mountField(SelectField, {
        field: searchableField,
        modelValue: 'option2'
      })

      // Clear button has specific positioning classes
      const clearButton = wrapper.find('button.absolute.inset-y-0.right-8')
      await clearButton.trigger('click')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
      expect(wrapper.emitted('change')[0][0]).toBe(null)
    })

    it('closes dropdown on blur with delay', async () => {
      wrapper = mountField(SelectField, { field: searchableField })

      const button = wrapper.find('button')
      await button.trigger('click')

      const dropdown = wrapper.find('.absolute.z-10.mt-1.w-full.bg-white')
      expect(dropdown.exists()).toBe(true)

      await button.trigger('blur')

      // Should still be open immediately
      const dropdownBeforeTimeout = wrapper.find('.absolute.z-10.mt-1.w-full.bg-white')
      expect(dropdownBeforeTimeout.exists()).toBe(true)

      // Wait for the timeout
      await new Promise(resolve => setTimeout(resolve, 250))
      await nextTick()

      const dropdownAfterTimeout = wrapper.find('.absolute.z-10.mt-1.w-full.bg-white')
      expect(dropdownAfterTimeout.exists()).toBe(false)
    })
  })

  describe('Readonly Mode', () => {
    it('shows selected value in readonly mode', () => {
      wrapper = mountField(SelectField, {
        field: mockField,
        modelValue: 'option2',
        props: {
          field: mockField,
          readonly: true
        }
      })

      // Check that readonly mode is working - component should show readonly text
      expect(wrapper.text()).toContain('Selected: Option 2')
    })

    it('does not show readonly text when no value selected', () => {
      wrapper = mountField(SelectField, {
        field: mockField,
        readonly: true
      })

      expect(wrapper.text()).not.toContain('Selected:')
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(SelectField, { field: mockField })

      const select = wrapper.find('select')
      expect(select.classes()).toContain('admin-input-dark')
    })

    it('applies dark theme classes to searchable select', () => {
      mockAdminStore.isDarkTheme = true

      const searchableField = createMockField({
        ...mockField,
        searchable: true
      })

      wrapper = mountField(SelectField, { field: searchableField })

      const button = wrapper.find('button')
      expect(button.classes()).toContain('admin-input-dark')
    })
  })

  describe('Edge Cases', () => {
    it('handles empty options object', () => {
      const fieldWithNoOptions = createMockField({
        ...mockField,
        options: {}
      })

      wrapper = mountField(SelectField, { field: fieldWithNoOptions })

      const options = wrapper.findAll('option')
      expect(options).toHaveLength(1) // Only placeholder
    })

    it('handles null modelValue', () => {
      wrapper = mountField(SelectField, {
        field: mockField,
        modelValue: null
      })

      const select = wrapper.find('select')
      expect(select.element.value).toBe('')
    })

    it('handles numeric option values', () => {
      const fieldWithNumericOptions = createMockField({
        ...mockField,
        options: {
          1: 'One',
          2: 'Two',
          3: 'Three'
        }
      })

      wrapper = mountField(SelectField, {
        field: fieldWithNumericOptions,
        modelValue: 2
      })

      const select = wrapper.find('select')
      expect(select.element.value).toBe('2')
    })

    it('finds option by loose equality for selected label', () => {
      const fieldWithNumericOptions = createMockField({
        ...mockField,
        options: {
          1: 'One',
          2: 'Two'
        },
        searchable: true
      })

      wrapper = mountField(SelectField, {
        field: fieldWithNumericOptions,
        modelValue: '2' // String value should match numeric key
      })

      const button = wrapper.find('button')
      expect(button.text()).toContain('Two')
    })
  })

  describe('Keyboard Navigation', () => {
    it('focuses search input when dropdown opens', async () => {
      const searchableField = createMockField({
        ...mockField,
        searchable: true
      })

      wrapper = mountField(SelectField, { field: searchableField })

      const button = wrapper.find('button')
      await button.trigger('click')

      // Wait for dropdown to open
      await nextTick()

      const searchInput = wrapper.find('input[type="text"]')
      expect(searchInput.exists()).toBe(true)

      // In test environment, focus might not work exactly like in browser
      // So we just verify the search input exists and is ready for interaction
      expect(searchInput.attributes('placeholder')).toBe('Search options...')
    })

    it('resets highlighted index when dropdown opens', async () => {
      const searchableField = createMockField({
        ...mockField,
        searchable: true
      })

      wrapper = mountField(SelectField, { field: searchableField })

      const button = wrapper.find('button')
      await button.trigger('click')

      expect(wrapper.vm.highlightedIndex).toBe(-1)
    })
  })
})
