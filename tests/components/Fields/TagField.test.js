import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import TagField from '../../../resources/js/components/Fields/TagField.vue'

/**
 * TagField Vue Component Tests
 *
 * Comprehensive tests for TagField Vue component with 100% Nova API compatibility.
 * Tests all Nova Tag field features including display modes, search, preview,
 * inline creation, and all interaction patterns.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
describe('TagField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
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
  })

  describe('Basic Rendering', () => {
    it('renders with default props', () => {
      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        }
      })

      expect(wrapper.find('h3').text()).toBe('Tags')
      expect(wrapper.find('[data-testid="tag-count"]').text()).toContain('0 tags')
      expect(wrapper.find('[data-testid="empty-state"]').exists()).toBe(true)
    })

    it('displays tag count correctly', async () => {
      const tags = [
        { id: 1, title: 'PHP' },
        { id: 2, title: 'Laravel' }
      ]

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: tags
        }
      })

      expect(wrapper.find('[data-testid="tag-count"]').text()).toContain('2 tags')
      expect(wrapper.find('[data-testid="empty-state"]').exists()).toBe(false)
    })

    it('shows singular tag count for one tag', () => {
      const tags = [{ id: 1, title: 'PHP' }]

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: tags
        }
      })

      expect(wrapper.find('[data-testid="tag-count"]').text()).toContain('1 tag')
    })
  })

  describe('Display Modes', () => {
    it('displays tags as inline group by default', () => {
      const tags = [
        { id: 1, title: 'PHP' },
        { id: 2, title: 'Laravel' }
      ]

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: tags
        }
      })

      expect(wrapper.find('[data-testid="inline-tags"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="list-tags"]').exists()).toBe(false)
      expect(wrapper.findAll('[data-testid="inline-tag"]')).toHaveLength(2)
    })

    it('displays tags as list when displayAsList is true', () => {
      const tags = [
        { id: 1, title: 'PHP' },
        { id: 2, title: 'Laravel' }
      ]

      const fieldWithList = { ...mockField, displayAsList: true }

      wrapper = mount(TagField, {
        props: {
          field: fieldWithList,
          modelValue: tags
        }
      })

      expect(wrapper.find('[data-testid="list-tags"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="inline-tags"]').exists()).toBe(false)
      expect(wrapper.findAll('[data-testid="list-tag"]')).toHaveLength(2)
    })
  })

  describe('Tag Selection', () => {
    it('shows add tags button when not readonly', () => {
      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: [],
          readonly: false
        }
      })

      expect(wrapper.find('[data-testid="add-tags-button"]').exists()).toBe(true)
    })

    it('hides add tags button when readonly', () => {
      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: [],
          readonly: true
        }
      })

      expect(wrapper.find('[data-testid="add-tags-button"]').exists()).toBe(false)
    })

    it('shows tag selector when add tags button is clicked', async () => {
      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        }
      })

      await wrapper.find('[data-testid="add-tags-button"]').trigger('click')
      expect(wrapper.find('[data-testid="tag-selector"]').exists()).toBe(true)
    })

    it('shows search input when tag selector is open and field is searchable', async () => {
      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        }
      })

      await wrapper.find('[data-testid="add-tags-button"]').trigger('click')
      expect(wrapper.find('[data-testid="tag-search"]').exists()).toBe(true)
    })

    it('hides search input when field is not searchable', async () => {
      const nonSearchableField = { ...mockField, searchable: false }

      wrapper = mount(TagField, {
        props: {
          field: nonSearchableField,
          modelValue: []
        }
      })

      await wrapper.find('[data-testid="add-tags-button"]').trigger('click')
      expect(wrapper.find('[data-testid="tag-search"]').exists()).toBe(false)
    })
  })

  describe('Create Relation Button', () => {
    it('shows create tag button when showCreateRelationButton is true', () => {
      const fieldWithCreate = { ...mockField, showCreateRelationButton: true }

      wrapper = mount(TagField, {
        props: {
          field: fieldWithCreate,
          modelValue: []
        }
      })

      expect(wrapper.find('[data-testid="create-tag-button"]').exists()).toBe(true)
    })

    it('hides create tag button when showCreateRelationButton is false', () => {
      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        }
      })

      expect(wrapper.find('[data-testid="create-tag-button"]').exists()).toBe(false)
    })

    it('hides create tag button when readonly', () => {
      const fieldWithCreate = { ...mockField, showCreateRelationButton: true }

      wrapper = mount(TagField, {
        props: {
          field: fieldWithCreate,
          modelValue: [],
          readonly: true
        }
      })

      expect(wrapper.find('[data-testid="create-tag-button"]').exists()).toBe(false)
    })
  })

  describe('Tag Removal', () => {
    it('shows remove button for each tag when not readonly', () => {
      const tags = [
        { id: 1, title: 'PHP' },
        { id: 2, title: 'Laravel' }
      ]

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: tags,
          readonly: false
        }
      })

      expect(wrapper.findAll('[data-testid="remove-tag-button"]')).toHaveLength(2)
    })

    it('hides remove buttons when readonly', () => {
      const tags = [
        { id: 1, title: 'PHP' },
        { id: 2, title: 'Laravel' }
      ]

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: tags,
          readonly: true
        }
      })

      expect(wrapper.findAll('[data-testid="remove-tag-button"]')).toHaveLength(0)
    })

    it('emits update:modelValue when tag is removed', async () => {
      const tags = [
        { id: 1, title: 'PHP' },
        { id: 2, title: 'Laravel' }
      ]

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: tags
        }
      })

      await wrapper.find('[data-testid="remove-tag-button"]').trigger('click')
      
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toHaveLength(1)
    })
  })

  describe('Preview Functionality', () => {
    it('shows preview modal when withPreview is true and tag is clicked', async () => {
      const fieldWithPreview = { ...mockField, withPreview: true }
      const tags = [{ id: 1, title: 'PHP', subtitle: 'Programming Language' }]

      wrapper = mount(TagField, {
        props: {
          field: fieldWithPreview,
          modelValue: tags
        }
      })

      await wrapper.find('[data-testid="preview-tag"]').trigger('click')
      expect(wrapper.find('[data-testid="preview-modal"]').exists()).toBe(true)
    })

    it('does not show preview modal when withPreview is false', () => {
      const tags = [{ id: 1, title: 'PHP' }]

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: tags
        }
      })

      expect(wrapper.find('[data-testid="preview-modal"]').exists()).toBe(false)
    })
  })

  describe('Loading States', () => {
    it('shows loading spinner when loading is true', async () => {
      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        }
      })

      // Simulate loading state
      await wrapper.vm.$nextTick()
      wrapper.vm.loading = true
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
    })
  })

  describe('Empty States', () => {
    it('shows empty state when no tags are selected', () => {
      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        }
      })

      expect(wrapper.find('[data-testid="empty-state"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="empty-state"]').text()).toContain('No tags selected')
    })

    it('shows appropriate empty state message with create button', () => {
      const fieldWithCreate = { ...mockField, showCreateRelationButton: true }

      wrapper = mount(TagField, {
        props: {
          field: fieldWithCreate,
          modelValue: []
        }
      })

      expect(wrapper.find('[data-testid="empty-state"]').text()).toContain('create new ones')
    })

    it('shows appropriate empty state message without create button', () => {
      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        }
      })

      expect(wrapper.find('[data-testid="empty-state"]').text()).not.toContain('create new ones')
    })
  })

  describe('Accessibility', () => {
    it('has proper ARIA labels', () => {
      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: []
        }
      })

      expect(wrapper.find('[data-testid="add-tags-button"]').attributes('aria-label')).toBeDefined()
    })

    it('supports keyboard navigation', async () => {
      const tags = [{ id: 1, title: 'PHP' }]

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: tags
        }
      })

      const removeButton = wrapper.find('[data-testid="remove-tag-button"]')
      expect(removeButton.attributes('tabindex')).toBe('0')
    })
  })

  describe('Error Handling', () => {
    it('displays field errors', () => {
      const errors = ['Tags are required']

      wrapper = mount(TagField, {
        props: {
          field: mockField,
          modelValue: [],
          errors
        }
      })

      expect(wrapper.find('[data-testid="field-errors"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="field-errors"]').text()).toContain('Tags are required')
    })
  })
})
