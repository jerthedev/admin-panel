import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import TagField from '../../../../resources/js/components/Fields/TagField.vue'

/**
 * TagField Integration Tests (Vue)
 *
 * Integration tests for TagField Vue component testing PHP/Vue interoperability,
 * API interactions, and complete user workflows. Tests the integration between
 * the Vue frontend and PHP backend through simulated API calls.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

// Mock API responses
const mockApiResponses = {
  searchTags: [
    { id: 1, title: 'PHP', subtitle: 'Programming Language', image: null },
    { id: 2, title: 'Laravel', subtitle: 'PHP Framework', image: null },
    { id: 3, title: 'Vue.js', subtitle: 'JavaScript Framework', image: null },
  ],
  createTag: { id: 4, title: 'New Tag', subtitle: 'Newly created tag', image: null }
}

// Mock fetch for API calls
global.fetch = vi.fn()

describe('TagField Integration Tests', () => {
  let wrapper
  let pinia
  let mockField

  beforeEach(() => {
    // Setup Pinia
    pinia = createPinia()
    setActivePinia(pinia)

    // Reset fetch mock
    fetch.mockClear()

    mockField = {
      name: 'Tags',
      attribute: 'tags',
      component: 'TagField',
      resourceClass: 'App\\Resources\\TagResource',
      relationshipName: 'tags',
      withPreview: false,
      displayAsList: false,
      showCreateRelationButton: false,
      modalSize: 'md',
      preload: false,
      searchable: true
    }
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
    vi.clearAllMocks()
  })

  describe('API Integration', () => {
    it('loads available tags when tag selector is opened', async () => {
      // Mock successful API response
      fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: mockApiResponses.searchTags })
      })

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        },
        global: {
          plugins: [pinia]
        }
      })

      // Open tag selector
      await wrapper.find('[data-testid="add-tags-button"]').trigger('click')
      await flushPromises()

      // Check that API was called
      expect(fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/tags/search'),
        expect.objectContaining({
          method: 'GET',
          headers: expect.objectContaining({
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          })
        })
      )

      // Check that tags are displayed
      await wrapper.vm.$nextTick()
      expect(wrapper.find('[data-testid="tag-selector"]').exists()).toBe(true)
    })

    it('searches tags with debounced API calls', async () => {
      // Mock API responses for search
      fetch.mockResolvedValue({
        ok: true,
        json: async () => ({ 
          data: mockApiResponses.searchTags.filter(tag => 
            tag.title.toLowerCase().includes('php')
          )
        })
      })

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        },
        global: {
          plugins: [pinia]
        }
      })

      // Open tag selector
      await wrapper.find('[data-testid="add-tags-button"]').trigger('click')
      
      // Type in search input
      const searchInput = wrapper.find('[data-testid="tag-search"] input')
      await searchInput.setValue('PHP')
      await searchInput.trigger('input')

      // Wait for debounced search
      await new Promise(resolve => setTimeout(resolve, 350))
      await flushPromises()

      // Check that search API was called with query
      expect(fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/tags/search?search=PHP'),
        expect.any(Object)
      )
    })

    it('handles API errors gracefully', async () => {
      // Mock API error
      fetch.mockRejectedValueOnce(new Error('Network error'))

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        },
        global: {
          plugins: [pinia]
        }
      })

      // Try to open tag selector
      await wrapper.find('[data-testid="add-tags-button"]').trigger('click')
      await flushPromises()

      // Check that error is handled (component should still be functional)
      expect(wrapper.find('[data-testid="add-tags-button"]').exists()).toBe(true)
    })
  })

  describe('PHP Backend Integration', () => {
    it('sends correct data format to PHP backend when tags are selected', async () => {
      const selectedTags = [
        { id: 1, title: 'PHP' },
        { id: 2, title: 'Laravel' }
      ]

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        },
        global: {
          plugins: [pinia]
        }
      })

      // Simulate tag selection
      await wrapper.vm.addTag(selectedTags[0])
      await wrapper.vm.addTag(selectedTags[1])

      // Check that the correct data is emitted
      const emittedEvents = wrapper.emitted('update:modelValue')
      expect(emittedEvents).toBeTruthy()
      expect(emittedEvents[emittedEvents.length - 1][0]).toEqual(selectedTags)
    })

    it('handles PHP field meta data correctly', async () => {
      const fieldWithAllOptions = {
        ...mockField,
        withPreview: true,
        displayAsList: true,
        showCreateRelationButton: true,
        modalSize: '7xl',
        preload: true,
        searchable: true
      }

      wrapper = mount(TagField, {
        props: {
          field: fieldWithAllOptions,
          modelValue: []
        },
        global: {
          plugins: [pinia]
        }
      })

      // Check that all PHP meta options are respected
      expect(wrapper.vm.field.withPreview).toBe(true)
      expect(wrapper.vm.field.displayAsList).toBe(true)
      expect(wrapper.vm.field.showCreateRelationButton).toBe(true)
      expect(wrapper.vm.field.modalSize).toBe('7xl')
      expect(wrapper.vm.field.preload).toBe(true)
      expect(wrapper.vm.field.searchable).toBe(true)
    })

    it('processes PHP field value format correctly', async () => {
      const phpFieldValue = {
        tags: [
          { id: 1, title: 'PHP', subtitle: 'Programming Language', image: null },
          { id: 2, title: 'Laravel', subtitle: 'PHP Framework', image: null }
        ],
        count: 2,
        resource_id: 123,
        resource_class: 'App\\Resources\\TagResource'
      }

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: phpFieldValue
        },
        global: {
          plugins: [pinia]
        }
      })

      // Check that PHP field value is processed correctly
      expect(wrapper.vm.selectedTags).toEqual(phpFieldValue.tags)
      expect(wrapper.vm.tagCount).toBe(2)
    })
  })

  describe('User Workflow Integration', () => {
    it('completes full tag selection workflow', async () => {
      // Mock API for tag loading
      fetch.mockResolvedValue({
        ok: true,
        json: async () => ({ data: mockApiResponses.searchTags })
      })

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        },
        global: {
          plugins: [pinia]
        }
      })

      // 1. Open tag selector
      await wrapper.find('[data-testid="add-tags-button"]').trigger('click')
      expect(wrapper.find('[data-testid="tag-selector"]').exists()).toBe(true)

      // 2. Search for tags
      const searchInput = wrapper.find('[data-testid="tag-search"] input')
      await searchInput.setValue('PHP')
      await new Promise(resolve => setTimeout(resolve, 350))
      await flushPromises()

      // 3. Select a tag
      await wrapper.vm.addTag(mockApiResponses.searchTags[0])

      // 4. Check that tag is selected
      expect(wrapper.vm.selectedTags).toContain(mockApiResponses.searchTags[0])
      expect(wrapper.vm.tagCount).toBe(1)

      // 5. Check that update event is emitted
      const emittedEvents = wrapper.emitted('update:modelValue')
      expect(emittedEvents).toBeTruthy()
      expect(emittedEvents[emittedEvents.length - 1][0]).toContain(mockApiResponses.searchTags[0])
    })

    it('handles tag removal workflow', async () => {
      const initialTags = [
        { id: 1, title: 'PHP' },
        { id: 2, title: 'Laravel' }
      ]

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: initialTags
        },
        global: {
          plugins: [pinia]
        }
      })

      // Check initial state
      expect(wrapper.vm.selectedTags).toEqual(initialTags)
      expect(wrapper.vm.tagCount).toBe(2)

      // Remove a tag
      await wrapper.vm.removeTag(initialTags[0])

      // Check that tag was removed
      expect(wrapper.vm.selectedTags).toEqual([initialTags[1]])
      expect(wrapper.vm.tagCount).toBe(1)

      // Check that update event is emitted
      const emittedEvents = wrapper.emitted('update:modelValue')
      expect(emittedEvents).toBeTruthy()
      expect(emittedEvents[emittedEvents.length - 1][0]).toEqual([initialTags[1]])
    })

    it('handles create tag workflow when enabled', async () => {
      const fieldWithCreate = {
        ...mockField,
        showCreateRelationButton: true
      }

      // Mock create tag API
      fetch.mockResolvedValue({
        ok: true,
        json: async () => ({ data: mockApiResponses.createTag })
      })

      wrapper = mount(TagField, {
        props: {
          field: fieldWithCreate,
          modelValue: []
        },
        global: {
          plugins: [pinia]
        }
      })

      // Check that create button is visible
      expect(wrapper.find('[data-testid="create-tag-button"]').exists()).toBe(true)

      // Click create button
      await wrapper.find('[data-testid="create-tag-button"]').trigger('click')

      // Check that create modal functionality is triggered
      expect(wrapper.vm.showCreateModal).toBeDefined()
    })
  })

  describe('Display Mode Integration', () => {
    it('displays tags in inline format by default', async () => {
      const tags = [
        { id: 1, title: 'PHP' },
        { id: 2, title: 'Laravel' }
      ]

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: tags
        },
        global: {
          plugins: [pinia]
        }
      })

      // Check inline display
      expect(wrapper.find('[data-testid="inline-tags"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="list-tags"]').exists()).toBe(false)
    })

    it('displays tags in list format when configured', async () => {
      const fieldWithList = {
        ...mockField,
        displayAsList: true
      }

      const tags = [
        { id: 1, title: 'PHP' },
        { id: 2, title: 'Laravel' }
      ]

      wrapper = mount(TagField, {
        props: {
          field: fieldWithList,
          modelValue: tags
        },
        global: {
          plugins: [pinia]
        }
      })

      // Check list display
      expect(wrapper.find('[data-testid="list-tags"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="inline-tags"]').exists()).toBe(false)
    })
  })

  describe('Preview Integration', () => {
    it('shows preview modal when preview is enabled', async () => {
      const fieldWithPreview = {
        ...mockField,
        withPreview: true
      }

      const tags = [
        { id: 1, title: 'PHP', subtitle: 'Programming Language' }
      ]

      wrapper = mount(TagField, {
        props: {
          field: fieldWithPreview,
          modelValue: tags
        },
        global: {
          plugins: [pinia]
        }
      })

      // Trigger preview
      await wrapper.vm.showPreview(tags[0])

      // Check that preview modal is shown
      expect(wrapper.vm.showingPreview).toBe(true)
      expect(wrapper.vm.previewTag).toEqual(tags[0])
    })
  })

  describe('Error Handling Integration', () => {
    it('displays field errors from PHP backend', async () => {
      const errors = ['Tags are required', 'At least one tag must be selected']

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: [],
          errors
        },
        global: {
          plugins: [pinia]
        }
      })

      // Check that errors are displayed
      expect(wrapper.find('[data-testid="field-errors"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="field-errors"]').text()).toContain('Tags are required')
      expect(wrapper.find('[data-testid="field-errors"]').text()).toContain('At least one tag must be selected')
    })
  })
})
