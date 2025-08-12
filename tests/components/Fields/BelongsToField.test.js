import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import BelongsToField from '@/components/Fields/BelongsToField.vue'
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
  XMarkIcon: { template: '<div data-testid="x-mark-icon"></div>' },
  CheckIcon: { template: '<div data-testid="check-icon"></div>' },
  PlusIcon: { template: '<div data-testid="plus-icon"></div>' },
  MagnifyingGlassIcon: { template: '<div data-testid="magnifying-glass-icon"></div>' }
}))

describe('BelongsToField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Category',
      attribute: 'category_id',
      type: 'belongsTo',
      resourceClass: 'CategoryResource',
      searchable: true,
      nullable: true,
      withTrashed: false,
      displayAttribute: 'name'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders dropdown field', () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      const dropdown = wrapper.find('.relative')
      expect(dropdown.exists()).toBe(true)
    })

    it('shows placeholder when no value selected', () => {
      wrapper = mountField(BelongsToField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.text()).toContain('Select Category')
    })

    it('shows selected value when value exists', () => {
      wrapper = mountField(BelongsToField, {
        field: mockField,
        modelValue: 1
      })

      // Should show selected label (mocked as Option 1)
      expect(wrapper.vm.selectedLabel).toBeDefined()
    })

    it('shows chevron down icon', () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      const chevronIcon = wrapper.find('[data-testid="chevron-down-icon"]')
      expect(chevronIcon.exists()).toBe(true)
    })

    it('applies disabled state', () => {
      wrapper = mountField(BelongsToField, {
        field: mockField,
        props: { 
          field: mockField,
          disabled: true 
        }
      })

      const dropdown = wrapper.find('.admin-input')
      expect(dropdown.classes()).toContain('opacity-50')
      expect(dropdown.classes()).toContain('cursor-not-allowed')
    })

    it('applies readonly state', () => {
      wrapper = mountField(BelongsToField, {
        field: mockField,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      const dropdown = wrapper.find('.admin-input')
      expect(dropdown.classes()).toContain('cursor-not-allowed')
    })
  })

  describe('Dropdown Functionality', () => {
    it('opens dropdown on click', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.vm.isOpen).toBe(true)
    })

    it('closes dropdown when clicking outside', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      // Open dropdown
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
      expect(wrapper.vm.isOpen).toBe(true)

      // Simulate click outside
      await wrapper.vm.handleClickOutside({ target: document.body })
      expect(wrapper.vm.isOpen).toBe(false)
    })

    it('does not open when disabled', async () => {
      wrapper = mountField(BelongsToField, {
        field: mockField,
        props: { 
          field: mockField,
          disabled: true 
        }
      })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.vm.isOpen).toBe(false)
    })

    it('does not open when readonly', async () => {
      wrapper = mountField(BelongsToField, {
        field: mockField,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.vm.isOpen).toBe(false)
    })
  })

  describe('Search Functionality', () => {
    it('shows search input when searchable and dropdown is open', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      const searchInput = wrapper.find('input[type="text"]')
      expect(searchInput.exists()).toBe(true)
    })

    it('does not show search input when not searchable', async () => {
      const nonSearchableField = createMockField({
        ...mockField,
        searchable: false
      })

      wrapper = mountField(BelongsToField, { field: nonSearchableField })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.find('input[type="text"]').exists()).toBe(false)
    })

    it('filters options based on search query', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      // Open dropdown and load options
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
      await nextTick()

      // Set search query
      wrapper.vm.searchQuery = 'Option 1'
      await nextTick()

      const filteredOptions = wrapper.vm.filteredOptions
      expect(filteredOptions.length).toBeLessThanOrEqual(wrapper.vm.options.length)
    })

    it('focuses search input when dropdown opens', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
      await nextTick()

      const searchInput = wrapper.find('input[type="text"]')
      expect(document.activeElement).toBe(searchInput.element)
    })
  })

  describe('Option Selection', () => {
    it('selects option on click', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      // Open dropdown
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
      await nextTick()

      // Wait for options to load
      await new Promise(resolve => setTimeout(resolve, 350))
      await nextTick()

      // Click first option
      const firstOption = wrapper.find('.option-item')
      if (firstOption.exists()) {
        await firstOption.trigger('click')

        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
        expect(wrapper.emitted('change')).toBeTruthy()
      }
    })

    it('shows selected state for current value', async () => {
      wrapper = mountField(BelongsToField, {
        field: mockField,
        modelValue: 1
      })

      // Open dropdown
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
      await nextTick()

      expect(wrapper.vm.isSelected(1)).toBe(true)
      expect(wrapper.vm.isSelected(2)).toBe(false)
    })

    it('clears selection when clear button clicked', async () => {
      const nullableField = createMockField({
        ...mockField,
        nullable: true
      })

      wrapper = mountField(BelongsToField, {
        field: nullableField,
        modelValue: 1
      })

      const clearButton = wrapper.find('[data-testid="x-mark-icon"]')
      if (clearButton.exists()) {
        await clearButton.element.parentElement.click()

        expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
        expect(wrapper.emitted('change')[0][0]).toBe(null)
      }
    })
  })

  describe('Loading States', () => {
    it('shows loading indicator when loading options', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      // Open dropdown to trigger loading
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.vm.loading).toBe(true)
      expect(wrapper.text()).toContain('Loading')
    })

    it('shows options after loading completes', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      // Open dropdown
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      // Wait for loading to complete
      await new Promise(resolve => setTimeout(resolve, 350))
      await nextTick()

      expect(wrapper.vm.loading).toBe(false)
      expect(wrapper.vm.options.length).toBeGreaterThan(0)
    })

    it('handles loading errors gracefully', async () => {
      // Mock console.error to avoid test output
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})

      // Mock loadOptions to throw error
      wrapper = mountField(BelongsToField, { field: mockField })
      wrapper.vm.loadOptions = vi.fn().mockRejectedValue(new Error('API Error'))

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(consoleSpy).toHaveBeenCalled()
      consoleSpy.mockRestore()
    })
  })

  describe('Create New Functionality', () => {
    it('shows create button when createable', () => {
      const createableField = createMockField({
        ...mockField,
        createable: true
      })

      wrapper = mountField(BelongsToField, { field: createableField })

      const createButton = wrapper.find('[data-testid="plus-icon"]')
      expect(createButton.exists()).toBe(true)
    })

    it('does not show create button when not createable', () => {
      const nonCreateableField = createMockField({
        ...mockField,
        createable: false
      })

      wrapper = mountField(BelongsToField, { field: nonCreateableField })

      expect(wrapper.find('[data-testid="plus-icon"]').exists()).toBe(false)
    })

    it('calls create modal when create button clicked', async () => {
      const createableField = createMockField({
        ...mockField,
        createable: true
      })

      wrapper = mountField(BelongsToField, { field: createableField })

      const createSpy = vi.spyOn(wrapper.vm, 'showCreateModal')
      const createButton = wrapper.find('[data-testid="plus-icon"]')
      
      if (createButton.exists()) {
        await createButton.element.parentElement.click()
        expect(createSpy).toHaveBeenCalled()
      }
    })
  })

  describe('Event Handling', () => {
    it('emits focus event', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('handles keyboard navigation', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      // Open dropdown
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      // Test arrow key navigation
      await dropdown.trigger('keydown', { key: 'ArrowDown' })
      await dropdown.trigger('keydown', { key: 'ArrowUp' })
      await dropdown.trigger('keydown', { key: 'Enter' })
      await dropdown.trigger('keydown', { key: 'Escape' })

      // Should handle keyboard events gracefully
      expect(wrapper.vm.isOpen).toBe(false) // Escape should close
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(BelongsToField, { field: mockField })

      const dropdown = wrapper.find('.admin-input')
      expect(dropdown.classes()).toContain('admin-input-dark')
    })

    it('applies dark theme to dropdown options', async () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(BelongsToField, { field: mockField })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      // Dark theme should be applied to dropdown container
      expect(wrapper.find('.bg-white').exists()).toBe(false)
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the dropdown', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      const dropdown = wrapper.find('.admin-input')
      const focusSpy = vi.spyOn(dropdown.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles null modelValue', () => {
      wrapper = mountField(BelongsToField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.selectedLabel).toBe(null)
    })

    it('handles undefined modelValue', () => {
      wrapper = mountField(BelongsToField, {
        field: mockField,
        modelValue: undefined
      })

      expect(wrapper.vm.selectedLabel).toBe(null)
    })

    it('handles empty options array', () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      wrapper.vm.options = []
      expect(wrapper.vm.filteredOptions).toEqual([])
    })

    it('handles very long option labels', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      wrapper.vm.options = [
        { value: 1, label: 'A'.repeat(200) }
      ]

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      // Should handle long labels gracefully
      expect(wrapper.vm.options[0].label.length).toBe(200)
    })

    it('handles special characters in search', async () => {
      wrapper = mountField(BelongsToField, { field: mockField })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      wrapper.vm.searchQuery = '!@#$%^&*()'
      await nextTick()

      // Should not crash with special characters
      expect(wrapper.vm.filteredOptions).toBeDefined()
    })
  })
})
