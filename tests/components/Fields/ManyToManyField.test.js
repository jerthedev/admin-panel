import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import ManyToManyField from '@/components/Fields/ManyToManyField.vue'
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
  LinkIcon: {
    name: 'LinkIcon',
    template: '<svg data-testid="link-icon"></svg>'
  },
  TagIcon: {
    name: 'TagIcon',
    template: '<svg data-testid="tag-icon"></svg>'
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

describe('ManyToManyField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Related Items',
      attribute: 'related_items',
      type: 'many_to_many',
      showAttachButton: true,
      showDetachButton: true,
      searchable: true,
      pivotFields: ['role', 'created_at']
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
    it('renders field header with name and count', () => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 5, resource_id: 1 }
      })

      expect(wrapper.text()).toContain('Related Items')
      expect(wrapper.text()).toContain('5 items')
    })

    it('displays singular item count correctly', () => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 1, resource_id: 1 }
      })

      expect(wrapper.text()).toContain('1 item')
    })

    it('shows attach button when enabled and not readonly/disabled', () => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 0, resource_id: 1 }
      })

      const attachButton = wrapper.find('button')
      expect(attachButton.exists()).toBe(true)
      expect(attachButton.text()).toBe('Attach')
      expect(wrapper.find('[data-testid="link-icon"]').exists()).toBe(true)
    })



    it('hides attach button when showAttachButton is false', () => {
      const fieldWithoutAttach = createMockField({
        ...mockField,
        showAttachButton: false
      })

      wrapper = mountField(ManyToManyField, {
        field: fieldWithoutAttach,
        modelValue: { count: 0, resource_id: 1 }
      })

      expect(wrapper.find('button').exists()).toBe(false)
    })
  })

  describe('Loading State', () => {
    it('shows loading spinner when loading', async () => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 0, resource_id: 1 }
      })

      // Set loading state
      wrapper.vm.loading = true
      await nextTick()

      expect(wrapper.find('.animate-spin').exists()).toBe(true)
    })

    it('hides loading spinner when not loading', async () => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 0, resource_id: 1 }
      })

      // Ensure loading is false
      wrapper.vm.loading = false
      await nextTick()

      expect(wrapper.find('.animate-spin').exists()).toBe(false)
    })
  })

  describe('Empty State', () => {
    it('shows empty state when no items attached', async () => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 0, resource_id: 1 }
      })

      // Set empty state
      wrapper.vm.loading = false
      wrapper.vm.attachedItems = []
      await nextTick()

      expect(wrapper.find('[data-testid="tag-icon"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('No related items attached')
      expect(wrapper.text()).toContain('Attach items to get started.')
    })

    it('shows different empty message when attach button is disabled', async () => {
      const fieldWithoutAttach = createMockField({
        ...mockField,
        showAttachButton: false
      })

      wrapper = mountField(ManyToManyField, {
        field: fieldWithoutAttach,
        modelValue: { count: 0, resource_id: 1 }
      })

      // Set empty state
      wrapper.vm.loading = false
      wrapper.vm.attachedItems = []
      await nextTick()

      expect(wrapper.text()).toContain('No items to display.')
    })
  })

  describe('Attached Items Display', () => {
    beforeEach(async () => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 2, resource_id: 1 }
      })

      // Set up attached items
      wrapper.vm.loading = false
      wrapper.vm.attachedItems = [
        {
          id: 1,
          title: 'Test Item 1',
          subtitle: 'Test Description 1',
          pivot: { role: 'admin', created_at: '2024-01-01' }
        },
        {
          id: 2,
          title: 'Test Item 2',
          subtitle: 'Test Description 2',
          pivot: { role: 'user', created_at: '2024-01-02' }
        }
      ]
      await nextTick()
    })

    it('displays attached items with titles and subtitles', () => {
      expect(wrapper.text()).toContain('Test Item 1')
      expect(wrapper.text()).toContain('Test Description 1')
      expect(wrapper.text()).toContain('Test Item 2')
      expect(wrapper.text()).toContain('Test Description 2')
    })

    it('displays pivot data when available', () => {
      expect(wrapper.text()).toContain('role: admin')
      expect(wrapper.text()).toContain('created_at: 2024-01-01')
      expect(wrapper.text()).toContain('role: user')
      expect(wrapper.text()).toContain('created_at: 2024-01-02')
    })

    it('shows view button for each item', () => {
      const viewButtons = wrapper.findAll('button').filter(btn => btn.text() === 'View')
      expect(viewButtons).toHaveLength(2)
    })

    it('shows edit button when pivot fields exist and not readonly/disabled', () => {
      const editButtons = wrapper.findAll('button').filter(btn => btn.text() === 'Edit')
      expect(editButtons).toHaveLength(2)
    })

    it('shows detach button when enabled and not readonly/disabled', () => {
      const detachButtons = wrapper.findAll('button').filter(btn => btn.text() === 'Detach')
      expect(detachButtons).toHaveLength(2)
    })

    it('hides edit buttons when readonly', async () => {
      await wrapper.setProps({ readonly: true })

      const editButtons = wrapper.findAll('button').filter(btn => btn.text() === 'Edit')
      expect(editButtons).toHaveLength(0)
    })

    it('hides detach buttons when readonly', async () => {
      await wrapper.setProps({ readonly: true })

      const detachButtons = wrapper.findAll('button').filter(btn => btn.text() === 'Detach')
      expect(detachButtons).toHaveLength(0)
    })

    it('hides edit buttons when disabled', async () => {
      await wrapper.setProps({ disabled: true })

      const editButtons = wrapper.findAll('button').filter(btn => btn.text() === 'Edit')
      expect(editButtons).toHaveLength(0)
    })

    it('hides detach buttons when disabled', async () => {
      await wrapper.setProps({ disabled: true })

      const detachButtons = wrapper.findAll('button').filter(btn => btn.text() === 'Detach')
      expect(detachButtons).toHaveLength(0)
    })
  })

  describe('Item Actions', () => {
    beforeEach(async () => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 1, resource_id: 1 }
      })

      // Set up attached items
      wrapper.vm.loading = false
      wrapper.vm.attachedItems = [
        {
          id: 1,
          title: 'Test Item 1',
          subtitle: 'Test Description 1',
          pivot: { role: 'admin', created_at: '2024-01-01' }
        }
      ]
      await nextTick()
    })

    it('calls viewItem when view button is clicked', async () => {
      const viewSpy = vi.spyOn(wrapper.vm, 'viewItem')

      const viewButton = wrapper.findAll('button').find(btn => btn.text() === 'View')
      await viewButton.trigger('click')

      expect(viewSpy).toHaveBeenCalledWith(wrapper.vm.attachedItems[0])
    })

    it('calls editPivot when edit button is clicked', async () => {
      const editSpy = vi.spyOn(wrapper.vm, 'editPivot')

      const editButton = wrapper.findAll('button').find(btn => btn.text() === 'Edit')
      await editButton.trigger('click')

      expect(editSpy).toHaveBeenCalledWith(wrapper.vm.attachedItems[0])
    })

    it('calls detachItem when detach button is clicked', async () => {
      const detachSpy = vi.spyOn(wrapper.vm, 'detachItem')

      const detachButton = wrapper.findAll('button').find(btn => btn.text() === 'Detach')
      await detachButton.trigger('click')

      expect(detachSpy).toHaveBeenCalled()
    })

    it('removes item from attachedItems when detached', async () => {
      expect(wrapper.vm.attachedItems).toHaveLength(1)

      const detachButton = wrapper.findAll('button').find(btn => btn.text() === 'Detach')
      await detachButton.trigger('click')

      expect(wrapper.vm.attachedItems).toHaveLength(0)
    })
  })

  describe('Attach Modal', () => {
    beforeEach(() => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 0, resource_id: 1 }
      })
    })

    it('opens attach modal when attach button is clicked', async () => {
      const attachButton = wrapper.find('button')
      await attachButton.trigger('click')

      expect(wrapper.vm.showAttachModalState).toBe(true)
    })

    it('hides search input when field is not searchable', async () => {
      const fieldWithoutSearch = createMockField({
        ...mockField,
        searchable: false
      })

      wrapper = mountField(ManyToManyField, {
        field: fieldWithoutSearch,
        modelValue: { count: 0, resource_id: 1 }
      })

      wrapper.vm.showAttachModalState = true
      await nextTick()

      const searchInput = wrapper.find('input[type="text"]')
      expect(searchInput.exists()).toBe(false)
    })

    it('shows loading state in modal when loading attachable items', async () => {
      wrapper.vm.showAttachModalState = true
      wrapper.vm.loadingAttachable = true
      await nextTick()

      expect(wrapper.find('.animate-spin').exists()).toBe(true)
    })
  })



  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 1, resource_id: 1 }
      })

      expect(wrapper.find('.text-gray-100').exists()).toBe(true)
      expect(wrapper.find('.bg-gray-700').exists()).toBe(true)
    })

    it('applies light theme classes when light theme is active', () => {
      mockAdminStore.isDarkTheme = false

      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 1, resource_id: 1 }
      })

      expect(wrapper.find('.text-gray-900').exists()).toBe(true)
      expect(wrapper.find('.bg-gray-100').exists()).toBe(true)
    })


  })

  describe('Utility Methods', () => {
    beforeEach(() => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 0, resource_id: 1 }
      })
    })

    it('getItemTitle returns correct title', () => {
      const item1 = { title: 'Test Title' }
      const item2 = { name: 'Test Name' }
      const item3 = { id: 123 }

      expect(wrapper.vm.getItemTitle(item1)).toBe('Test Title')
      expect(wrapper.vm.getItemTitle(item2)).toBe('Test Name')
      expect(wrapper.vm.getItemTitle(item3)).toBe('Item 123')
    })

    it('getItemSubtitle returns correct subtitle', () => {
      const item1 = { subtitle: 'Test Subtitle' }
      const item2 = { description: 'Test Description' }
      const item3 = { id: 123 }

      expect(wrapper.vm.getItemSubtitle(item1)).toBe('Test Subtitle')
      expect(wrapper.vm.getItemSubtitle(item2)).toBe('Test Description')
      expect(wrapper.vm.getItemSubtitle(item3)).toBe(null)
    })
  })

  describe('Edge Cases', () => {
    it('handles missing modelValue gracefully', () => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.totalCount).toBe(0)
    })

    it('handles modelValue without count gracefully', () => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { resource_id: 1 }
      })

      expect(wrapper.vm.totalCount).toBe(0)
    })

    it('handles field without pivot fields gracefully', () => {
      const fieldWithoutPivot = createMockField({
        ...mockField,
        pivotFields: []
      })

      wrapper = mountField(ManyToManyField, {
        field: fieldWithoutPivot,
        modelValue: { count: 1, resource_id: 1 }
      })

      // Set up attached items
      wrapper.vm.loading = false
      wrapper.vm.attachedItems = [
        {
          id: 1,
          title: 'Test Item 1',
          pivot: { role: 'admin' }
        }
      ]

      // Should not show edit buttons when no pivot fields
      const editButtons = wrapper.findAll('button').filter(btn => btn.text() === 'Edit')
      expect(editButtons).toHaveLength(0)
    })

    it('handles items without pivot data gracefully', async () => {
      wrapper = mountField(ManyToManyField, {
        field: mockField,
        modelValue: { count: 1, resource_id: 1 }
      })

      // Set up attached items without pivot data
      wrapper.vm.loading = false
      wrapper.vm.attachedItems = [
        {
          id: 1,
          title: 'Test Item 1'
        }
      ]
      await nextTick()

      expect(wrapper.text()).toContain('Test Item 1')
      // Should not show pivot data section
      expect(wrapper.text()).not.toContain('role:')
    })
  })
})
