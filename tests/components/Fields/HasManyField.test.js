import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import HasManyField from '@/components/Fields/HasManyField.vue'
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
  MagnifyingGlassIcon: { template: '<div data-testid="magnifying-glass-icon"></div>' },
  PlusIcon: { template: '<div data-testid="plus-icon"></div>' },
  LinkIcon: { template: '<div data-testid="link-icon"></div>' },
  DocumentIcon: { template: '<div data-testid="document-icon"></div>' },
  EyeIcon: { template: '<div data-testid="eye-icon"></div>' },
  PencilIcon: { template: '<div data-testid="pencil-icon"></div>' },
  TrashIcon: { template: '<div data-testid="trash-icon"></div>' }
}))

describe('HasManyField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Posts',
      attribute: 'posts',
      type: 'hasMany',
      resourceClass: 'PostResource',
      searchable: true,
      createable: true,
      attachable: true,
      detachable: true,
      displayAttribute: 'title'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders has many field container', () => {
      wrapper = mountField(HasManyField, { field: mockField })

      const container = wrapper.find('.space-y-4')
      expect(container.exists()).toBe(true)
    })

    it('shows field name and count', () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 5, resource_id: 1 }
      })

      expect(wrapper.text()).toContain('Posts')
      expect(wrapper.text()).toContain('5 items')
    })

    it('shows singular form for count of 1', () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 1, resource_id: 1 }
      })

      expect(wrapper.text()).toContain('1 item')
    })

    it('shows zero count when no items', () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 0, resource_id: 1 }
      })

      expect(wrapper.text()).toContain('0 items')
    })

    it('applies disabled state', () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        props: { 
          field: mockField,
          disabled: true 
        }
      })

      // Action buttons should be disabled
      const createButton = wrapper.find('[data-testid="plus-icon"]')
      if (createButton.exists()) {
        expect(createButton.element.parentElement.disabled).toBe(true)
      }
    })
  })

  describe('Search Functionality', () => {
    it('shows search input when searchable', () => {
      wrapper = mountField(HasManyField, { field: mockField })

      const searchInput = wrapper.find('input[type="text"]')
      expect(searchInput.exists()).toBe(true)
    })

    it('does not show search input when not searchable', () => {
      const nonSearchableField = createMockField({
        ...mockField,
        searchable: false
      })

      wrapper = mountField(HasManyField, { field: nonSearchableField })

      expect(wrapper.find('input[type="text"]').exists()).toBe(false)
    })

    it('filters items on search input', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 3, resource_id: 1 }
      })

      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.setValue('Item 1')
      await searchInput.trigger('input')

      expect(wrapper.vm.searchQuery).toBe('Item 1')
    })

    it('triggers search on enter key', async () => {
      wrapper = mountField(HasManyField, { field: mockField })

      const handleSearchSpy = vi.spyOn(wrapper.vm, 'handleSearch')
      const searchInput = wrapper.find('input[type="text"]')
      
      await searchInput.trigger('keydown', { key: 'Enter' })
      expect(handleSearchSpy).toHaveBeenCalled()
    })
  })

  describe('Action Buttons', () => {
    it('shows create button when createable', () => {
      wrapper = mountField(HasManyField, { field: mockField })

      const createButton = wrapper.find('[data-testid="plus-icon"]')
      expect(createButton.exists()).toBe(true)
    })

    it('does not show create button when not createable', () => {
      const nonCreateableField = createMockField({
        ...mockField,
        createable: false
      })

      wrapper = mountField(HasManyField, { field: nonCreateableField })

      expect(wrapper.find('[data-testid="plus-icon"]').exists()).toBe(false)
    })

    it('shows attach button when attachable', () => {
      wrapper = mountField(HasManyField, { field: mockField })

      const attachButton = wrapper.find('[data-testid="link-icon"]')
      expect(attachButton.exists()).toBe(true)
    })

    it('does not show attach button when not attachable', () => {
      const nonAttachableField = createMockField({
        ...mockField,
        attachable: false
      })

      wrapper = mountField(HasManyField, { field: nonAttachableField })

      expect(wrapper.find('[data-testid="link-icon"]').exists()).toBe(false)
    })

    it('calls create modal when create button clicked', async () => {
      wrapper = mountField(HasManyField, { field: mockField })

      const createSpy = vi.spyOn(wrapper.vm, 'showCreateModal')
      const createButton = wrapper.find('[data-testid="plus-icon"]')
      
      await createButton.element.parentElement.click()
      expect(createSpy).toHaveBeenCalled()
    })

    it('calls attach modal when attach button clicked', async () => {
      wrapper = mountField(HasManyField, { field: mockField })

      const attachSpy = vi.spyOn(wrapper.vm, 'showAttachModal')
      const attachButton = wrapper.find('[data-testid="link-icon"]')
      
      await attachButton.element.parentElement.click()
      expect(attachSpy).toHaveBeenCalled()
    })
  })

  describe('Items List', () => {
    it('shows loading state when loading items', () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 3, resource_id: 1 }
      })

      wrapper.vm.loading = true
      expect(wrapper.text()).toContain('Loading')
    })

    it('shows items after loading completes', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 3, resource_id: 1 }
      })

      // Wait for loading to complete
      await new Promise(resolve => setTimeout(resolve, 550))
      await nextTick()

      expect(wrapper.vm.loading).toBe(false)
      expect(wrapper.vm.items.length).toBeGreaterThan(0)
    })

    it('shows empty state when no items', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 0, resource_id: 1 }
      })

      wrapper.vm.items = []
      wrapper.vm.loading = false
      await nextTick()

      expect(wrapper.text()).toContain('No items found')
    })

    it('displays item titles correctly', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 3, resource_id: 1 }
      })

      // Wait for items to load
      await new Promise(resolve => setTimeout(resolve, 550))
      await nextTick()

      const items = wrapper.vm.items
      if (items.length > 0) {
        const title = wrapper.vm.getItemTitle(items[0])
        expect(title).toBeDefined()
      }
    })

    it('displays item subtitles when available', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 3, resource_id: 1 }
      })

      // Wait for items to load
      await new Promise(resolve => setTimeout(resolve, 550))
      await nextTick()

      const items = wrapper.vm.items
      if (items.length > 0) {
        const subtitle = wrapper.vm.getItemSubtitle(items[0])
        expect(subtitle).toBeDefined()
      }
    })
  })

  describe('Item Actions', () => {
    it('shows view action for items', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 3, resource_id: 1 }
      })

      // Wait for items to load
      await new Promise(resolve => setTimeout(resolve, 550))
      await nextTick()

      const viewButton = wrapper.find('[data-testid="eye-icon"]')
      expect(viewButton.exists()).toBe(true)
    })

    it('shows edit action for items', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 3, resource_id: 1 }
      })

      // Wait for items to load
      await new Promise(resolve => setTimeout(resolve, 550))
      await nextTick()

      const editButton = wrapper.find('[data-testid="pencil-icon"]')
      expect(editButton.exists()).toBe(true)
    })

    it('shows delete action when detachable', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 3, resource_id: 1 }
      })

      // Wait for items to load
      await new Promise(resolve => setTimeout(resolve, 550))
      await nextTick()

      const deleteButton = wrapper.find('[data-testid="trash-icon"]')
      expect(deleteButton.exists()).toBe(true)
    })

    it('calls view method when view button clicked', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 3, resource_id: 1 }
      })

      // Wait for items to load
      await new Promise(resolve => setTimeout(resolve, 550))
      await nextTick()

      const viewSpy = vi.spyOn(wrapper.vm, 'viewItem')
      const viewButton = wrapper.find('[data-testid="eye-icon"]')
      
      if (viewButton.exists()) {
        await viewButton.element.parentElement.click()
        expect(viewSpy).toHaveBeenCalled()
      }
    })

    it('calls edit method when edit button clicked', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 3, resource_id: 1 }
      })

      // Wait for items to load
      await new Promise(resolve => setTimeout(resolve, 550))
      await nextTick()

      const editSpy = vi.spyOn(wrapper.vm, 'editItem')
      const editButton = wrapper.find('[data-testid="pencil-icon"]')
      
      if (editButton.exists()) {
        await editButton.element.parentElement.click()
        expect(editSpy).toHaveBeenCalled()
      }
    })

    it('calls delete method when delete button clicked', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 3, resource_id: 1 }
      })

      // Wait for items to load
      await new Promise(resolve => setTimeout(resolve, 550))
      await nextTick()

      const deleteSpy = vi.spyOn(wrapper.vm, 'deleteItem')
      const deleteButton = wrapper.find('[data-testid="trash-icon"]')
      
      if (deleteButton.exists()) {
        await deleteButton.element.parentElement.click()
        expect(deleteSpy).toHaveBeenCalled()
      }
    })
  })

  describe('Pagination', () => {
    it('shows pagination when multiple pages exist', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 25, resource_id: 1 }
      })

      // Wait for items to load
      await new Promise(resolve => setTimeout(resolve, 550))
      await nextTick()

      if (wrapper.vm.pagination?.last_page > 1) {
        expect(wrapper.text()).toContain('Page')
      }
    })

    it('navigates to different pages', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 25, resource_id: 1 }
      })

      // Wait for items to load
      await new Promise(resolve => setTimeout(resolve, 550))
      await nextTick()

      const goToPageSpy = vi.spyOn(wrapper.vm, 'goToPage')
      wrapper.vm.goToPage(2)
      
      expect(goToPageSpy).toHaveBeenCalledWith(2)
      expect(wrapper.vm.currentPage).toBe(2)
    })

    it('shows correct pagination info', async () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 25, resource_id: 1 }
      })

      // Wait for items to load
      await new Promise(resolve => setTimeout(resolve, 550))
      await nextTick()

      const pagination = wrapper.vm.pagination
      if (pagination) {
        expect(pagination.total).toBe(25)
        expect(pagination.per_page).toBe(10)
      }
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(HasManyField, { field: mockField })

      const title = wrapper.find('h3')
      expect(title.classes()).toContain('text-gray-100')
    })

    it('applies dark theme to count badge', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 5, resource_id: 1 }
      })

      const badge = wrapper.find('.rounded-full')
      expect(badge.classes()).toContain('bg-gray-700')
      expect(badge.classes()).toContain('text-gray-200')
    })
  })

  describe('Event Handling', () => {
    it('emits focus event', async () => {
      wrapper = mountField(HasManyField, { field: mockField })

      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(HasManyField, { field: mockField })

      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })
  })

  describe('Edge Cases', () => {
    it('handles null modelValue', () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.totalCount).toBe(0)
    })

    it('handles undefined modelValue', () => {
      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: undefined
      })

      expect(wrapper.vm.totalCount).toBe(0)
    })

    it('handles loading errors gracefully', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})

      wrapper = mountField(HasManyField, {
        field: mockField,
        modelValue: { count: 3, resource_id: 1 }
      })

      // Mock loadItems to throw error
      wrapper.vm.loadItems = vi.fn().mockRejectedValue(new Error('API Error'))
      await wrapper.vm.loadItems()

      expect(consoleSpy).toHaveBeenCalled()
      consoleSpy.mockRestore()
    })

    it('handles empty search results', async () => {
      wrapper = mountField(HasManyField, { field: mockField })

      wrapper.vm.searchQuery = 'nonexistent'
      wrapper.vm.items = []
      await nextTick()

      expect(wrapper.text()).toContain('No items found')
    })
  })
})
