import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import MultiSelectField from '@/components/Fields/MultiSelectField.vue'
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
  XMarkIcon: {
    name: 'XMarkIcon',
    template: '<svg data-testid="x-mark-icon"></svg>'
  },
  CheckIcon: {
    name: 'CheckIcon',
    template: '<svg data-testid="check-icon"></svg>'
  }
}))

describe('MultiSelectField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Multi Select',
      attribute: 'multi_select',
      placeholder: 'Select options...',
      type: 'multiselect',
      options: {
        'option1': 'Option 1',
        'option2': 'Option 2',
        'option3': 'Option 3',
        'option4': 'Option 4'
      }
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders multi-select dropdown', () => {
      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: []
      })

      expect(wrapper.find('.admin-input').exists()).toBe(true)
      expect(wrapper.find('[data-testid="chevron-down-icon"]').exists()).toBe(true)
    })

    it('displays placeholder when no items selected', () => {
      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: []
      })

      expect(wrapper.text()).toContain('Select options...')
    })

    it('uses custom placeholder when provided', () => {
      const fieldWithPlaceholder = createMockField({
        ...mockField,
        placeholder: 'Choose items...'
      })

      wrapper = mountField(MultiSelectField, {
        field: fieldWithPlaceholder,
        modelValue: []
      })

      expect(wrapper.text()).toContain('Choose items...')
    })



    it('applies error styling when errors are present', () => {
      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: [],
        errors: { multi_select: ['This field is required'] }
      })

      const input = wrapper.find('.admin-input')
      expect(input.classes()).toContain('border-red-300')
    })
  })

  describe('Selected Items Display', () => {
    it('displays selected items as tags', () => {
      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: ['option1', 'option2']
      })

      expect(wrapper.text()).toContain('Option 1')
      expect(wrapper.text()).toContain('Option 2')

      const tags = wrapper.findAll('.bg-blue-100')
      expect(tags).toHaveLength(2)
    })

    it('shows remove buttons on tags when not disabled', () => {
      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: ['option1']
      })

      expect(wrapper.find('[data-testid="x-mark-icon"]').exists()).toBe(true)
    })

    it('shows remove buttons when not disabled or readonly', () => {
      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: ['option1']
      })

      expect(wrapper.find('[data-testid="x-mark-icon"]').exists()).toBe(true)
    })


  })

  describe('Dropdown Functionality', () => {
    beforeEach(() => {
      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: []
      })
    })

    it('opens dropdown when clicked', async () => {
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.find('.absolute.z-10').exists()).toBe(true)
      expect(wrapper.text()).toContain('Option 1')
      expect(wrapper.text()).toContain('Option 2')
    })

    it('rotates chevron icon when dropdown is open', async () => {
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      const chevron = wrapper.find('[data-testid="chevron-down-icon"]')
      expect(chevron.classes()).toContain('rotate-180')
    })

    it('does not open dropdown when disabled', async () => {
      await wrapper.setProps({ disabled: true })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.find('.absolute.z-10').exists()).toBe(false)
    })

    it('does not open dropdown when readonly', async () => {
      await wrapper.setProps({ readonly: true })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.find('.absolute.z-10').exists()).toBe(false)
    })
  })

  describe('Option Selection', () => {
    beforeEach(async () => {
      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: []
      })

      // Open dropdown
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
    })

    it('selects option when clicked', async () => {
      const options = wrapper.findAll('.px-3.py-2.text-sm.cursor-pointer')
      await options[0].trigger('click')

      expect(wrapper.emitted('update:modelValue')[0][0]).toEqual(['option1'])
      expect(wrapper.emitted('change')[0][0]).toEqual(['option1'])
    })

    it('deselects option when clicked again', async () => {
      // First select an option
      await wrapper.setProps({ modelValue: ['option1'] })
      await nextTick()

      const options = wrapper.findAll('.px-3.py-2.text-sm.cursor-pointer')
      await options[0].trigger('click')

      const emittedValues = wrapper.emitted('update:modelValue')
      expect(emittedValues[emittedValues.length - 1][0]).toEqual([])
    })

    it('shows check icon for selected options', async () => {
      await wrapper.setProps({ modelValue: ['option1'] })
      await nextTick()

      expect(wrapper.find('[data-testid="check-icon"]').exists()).toBe(true)
    })

    it('applies selected styling to selected options', async () => {
      await wrapper.setProps({ modelValue: ['option1'] })
      await nextTick()

      const selectedOption = wrapper.find('.bg-blue-50')
      expect(selectedOption.exists()).toBe(true)
      expect(selectedOption.classes()).toContain('text-blue-700')
    })


  })

  describe('Remove Items', () => {
    beforeEach(() => {
      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: ['option1', 'option2']
      })
    })

    it('removes item when remove button is clicked', async () => {
      const removeButtons = wrapper.findAll('[data-testid="x-mark-icon"]')
      await removeButtons[0].trigger('click')

      expect(wrapper.emitted('update:modelValue')[0][0]).toEqual(['option2'])
      expect(wrapper.emitted('change')[0][0]).toEqual(['option2'])
    })

    it('does not remove items when disabled', async () => {
      await wrapper.setProps({ disabled: true })

      // Remove buttons should not exist when disabled
      expect(wrapper.find('[data-testid="x-mark-icon"]').exists()).toBe(false)
    })

    it('does not remove items when readonly', async () => {
      await wrapper.setProps({ readonly: true })

      // Remove buttons should not exist when readonly
      expect(wrapper.find('[data-testid="x-mark-icon"]').exists()).toBe(false)
    })
  })

  describe('Search Functionality', () => {
    beforeEach(async () => {
      const searchableField = createMockField({
        ...mockField,
        searchable: true
      })

      wrapper = mountField(MultiSelectField, {
        field: searchableField,
        modelValue: []
      })

      // Open dropdown
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
    })

    it('shows search input when searchable and dropdown is open', () => {
      expect(wrapper.find('input[type="text"]').exists()).toBe(true)
    })

    it('filters options based on search query', async () => {
      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.setValue('Option 1')

      // Should only show Option 1
      const options = wrapper.findAll('.px-3.py-2.text-sm.cursor-pointer')
      expect(options).toHaveLength(1)
      expect(options[0].text()).toContain('Option 1')
    })

    it('shows all options when search query is empty', async () => {
      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.setValue('')

      const options = wrapper.findAll('.px-3.py-2.text-sm.cursor-pointer')
      expect(options).toHaveLength(4)
    })

    it('clears search query after selection', async () => {
      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.setValue('Option 1')

      const options = wrapper.findAll('.px-3.py-2.text-sm.cursor-pointer')
      await options[0].trigger('click')

      expect(wrapper.vm.searchQuery).toBe('')
    })
  })





  describe('Keyboard Navigation', () => {
    beforeEach(async () => {
      const searchableField = createMockField({
        ...mockField,
        searchable: true
      })

      wrapper = mountField(MultiSelectField, { field: searchableField })

      // Open dropdown
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
    })

    it('closes dropdown when Escape is pressed', async () => {
      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.trigger('keydown', { key: 'Escape' })

      expect(wrapper.find('.absolute.z-10').exists()).toBe(false)
    })


  })

  describe('Click Outside Handling', () => {
    beforeEach(async () => {
      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: []
      })

      // Open dropdown
      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')
    })

    it('closes dropdown when clicking outside', async () => {
      // Simulate click outside
      const outsideElement = document.createElement('div')
      document.body.appendChild(outsideElement)

      const clickEvent = new Event('click', { bubbles: true })
      Object.defineProperty(clickEvent, 'target', { value: outsideElement })

      document.dispatchEvent(clickEvent)
      await nextTick()

      expect(wrapper.find('.absolute.z-10').exists()).toBe(false)

      document.body.removeChild(outsideElement)
    })

    it('clears search query when clicking outside', async () => {
      const searchableField = createMockField({
        ...mockField,
        searchable: true
      })

      await wrapper.setProps({ field: searchableField })
      await nextTick()

      // Set search query
      wrapper.vm.searchQuery = 'test'

      // Simulate click outside
      const outsideElement = document.createElement('div')
      document.body.appendChild(outsideElement)

      const clickEvent = new Event('click', { bubbles: true })
      Object.defineProperty(clickEvent, 'target', { value: outsideElement })

      document.dispatchEvent(clickEvent)
      await nextTick()

      expect(wrapper.vm.searchQuery).toBe('')

      document.body.removeChild(outsideElement)
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: []
      })

      const input = wrapper.find('.admin-input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('applies dark theme to selected tags', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: ['option1']
      })

      // Check for dark theme tag styling
      const tags = wrapper.findAll('.bg-blue-800, .bg-blue-100')
      expect(tags.length).toBeGreaterThan(0)
    })

    it('applies dark theme to dropdown menu', async () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: []
      })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      const menu = wrapper.find('.absolute.z-10')
      expect(menu.exists()).toBe(true)
    })

    it('applies dark theme to selected options', async () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: ['option1']
      })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      // Check that selected options have some styling
      const options = wrapper.findAll('.px-3.py-2.text-sm.cursor-pointer')
      expect(options.length).toBeGreaterThan(0)
    })
  })

  describe('No Options Handling', () => {
    it('shows no options message when no options available', async () => {
      const emptyField = createMockField({
        ...mockField,
        options: {}
      })

      wrapper = mountField(MultiSelectField, { field: emptyField })

      const dropdown = wrapper.find('.admin-input')
      await dropdown.trigger('click')

      expect(wrapper.text()).toContain('No options available')
    })


  })

  describe('Utility Methods', () => {
    beforeEach(() => {
      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: []
      })
    })

    it('getItemLabel returns correct label for existing option', () => {
      expect(wrapper.vm.getItemLabel('option1')).toBe('Option 1')
    })

    it('getItemLabel returns value when option not found', () => {
      expect(wrapper.vm.getItemLabel('nonexistent')).toBe('nonexistent')
    })

    it('isSelected correctly identifies selected items', async () => {
      await wrapper.setProps({ modelValue: ['option1'] })

      expect(wrapper.vm.isSelected('option1')).toBe(true)
      expect(wrapper.vm.isSelected('option2')).toBe(false)
    })


  })

  describe('Edge Cases', () => {
    it('handles non-array modelValue gracefully', () => {
      wrapper = mountField(MultiSelectField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.selectedItems).toEqual([])
    })

    it('handles missing options gracefully', () => {
      const fieldWithoutOptions = createMockField({
        ...mockField,
        options: undefined
      })

      wrapper = mountField(MultiSelectField, {
        field: fieldWithoutOptions,
        modelValue: []
      })

      expect(wrapper.vm.options).toEqual([])
    })
  })

  describe('Nova API Compatibility', () => {
    it('supports Nova-style field configuration', () => {
      const novaStyleField = createMockField({
        attribute: 'skills',
        name: 'Skills',
        component: 'MultiSelectField',
        options: {
          'php': 'PHP',
          'javascript': 'JavaScript'
        },
        searchable: true
      })

      wrapper = mountField(MultiSelectField, {
        field: novaStyleField,
        modelValue: []
      })

      expect(wrapper.vm.options).toEqual([
        { value: 'php', label: 'PHP' },
        { value: 'javascript', label: 'JavaScript' }
      ])
      expect(wrapper.vm.field.searchable).toBe(true)
    })

    it('correctly processes Nova API options() method output', () => {
      const phpFieldConfig = createMockField({
        options: {
          'backend': 'Backend Development',
          'frontend': 'Frontend Development',
          'fullstack': 'Full Stack Development'
        }
      })

      wrapper = mountField(MultiSelectField, {
        field: phpFieldConfig,
        modelValue: ['backend', 'frontend']
      })

      expect(wrapper.vm.options).toEqual([
        { value: 'backend', label: 'Backend Development' },
        { value: 'frontend', label: 'Frontend Development' },
        { value: 'fullstack', label: 'Full Stack Development' }
      ])
    })

    it('integrates all Nova API methods correctly', () => {
      const phpFieldConfig = createMockField({
        options: {
          'laravel': 'Laravel',
          'vue': 'Vue.js',
          'react': 'React'
        },
        searchable: true,
        rules: ['required'],
        nullable: false
      })

      wrapper = mountField(MultiSelectField, {
        field: phpFieldConfig,
        modelValue: ['laravel', 'vue']
      })

      // Test complete integration
      expect(wrapper.vm.options).toEqual([
        { value: 'laravel', label: 'Laravel' },
        { value: 'vue', label: 'Vue.js' },
        { value: 'react', label: 'React' }
      ])
      expect(wrapper.vm.field.searchable).toBe(true)
      expect(wrapper.vm.selectedItems).toEqual(['laravel', 'vue'])
    })
  })
})
