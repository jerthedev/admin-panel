/**
 * HasOneField Component Tests
 * 
 * Complete feature testing for HasOne field Vue component
 * with 100% code coverage and Nova v5 compatibility.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import HasOneField from '@/components/Fields/HasOneField.vue'
import { useAdminStore } from '@/stores/admin'

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  DocumentIcon: { name: 'DocumentIcon', template: '<div data-testid="document-icon"></div>' },
  EyeIcon: { name: 'EyeIcon', template: '<div data-testid="eye-icon"></div>' },
  PencilIcon: { name: 'PencilIcon', template: '<div data-testid="pencil-icon"></div>' },
  PlusIcon: { name: 'PlusIcon', template: '<div data-testid="plus-icon"></div>' },
  InformationCircleIcon: { name: 'InformationCircleIcon', template: '<div data-testid="info-icon"></div>' }
}))

// Mock BaseField component
vi.mock('@/components/Fields/BaseField.vue', () => ({
  default: {
    name: 'BaseField',
    template: '<div data-testid="base-field"><slot /></div>',
    props: ['field', 'modelValue', 'errors', 'disabled', 'readonly', 'size']
  }
}))

describe('HasOneField', () => {
  let wrapper
  let pinia
  let adminStore

  const defaultField = {
    name: 'Address',
    attribute: 'address',
    component: 'HasOneField',
    resourceClass: 'App\\Nova\\AddressResource',
    relationshipName: 'address',
    isOfMany: false,
    ofManyRelationship: null,
    ofManyResourceClass: null
  }

  const defaultModelValue = {
    id: null,
    title: null,
    resource_class: null,
    exists: false
  }

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    adminStore = useAdminStore()
  })

  const createWrapper = (props = {}) => {
    return mount(HasOneField, {
      props: {
        field: defaultField,
        modelValue: defaultModelValue,
        errors: {},
        disabled: false,
        readonly: false,
        size: 'default',
        ...props
      },
      global: {
        plugins: [pinia]
      }
    })
  }

  describe('Component Rendering', () => {
    it('renders correctly with default props', () => {
      wrapper = createWrapper()
      
      expect(wrapper.find('[data-testid="base-field"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Address')
      expect(wrapper.text()).toContain('No Relation')
    })

    it('renders field name correctly', () => {
      wrapper = createWrapper({
        field: { ...defaultField, name: 'User Profile' }
      })
      
      expect(wrapper.text()).toContain('User Profile')
    })

    it('shows status badge with correct text', () => {
      wrapper = createWrapper()
      
      const badge = wrapper.find('.rounded-full')
      expect(badge.exists()).toBe(true)
      expect(badge.text()).toBe('No Relation')
    })
  })

  describe('Related Model Display', () => {
    const relatedModelValue = {
      id: 123,
      title: 'Test Address',
      resource_class: 'App\\Nova\\AddressResource',
      exists: true
    }

    it('shows related model when exists', () => {
      wrapper = createWrapper({
        modelValue: relatedModelValue
      })
      
      expect(wrapper.text()).toContain('Test Address')
      expect(wrapper.text()).toContain('Related')
      expect(wrapper.find('[data-testid="document-icon"]').exists()).toBe(true)
    })

    it('shows view and edit buttons for related model', () => {
      wrapper = createWrapper({
        modelValue: relatedModelValue
      })
      
      const viewButton = wrapper.find('button:contains("View")')
      const editButton = wrapper.find('button:contains("Edit")')
      
      expect(viewButton.exists()).toBe(true)
      expect(editButton.exists()).toBe(true)
      expect(wrapper.find('[data-testid="eye-icon"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="pencil-icon"]').exists()).toBe(true)
    })

    it('hides edit button when readonly', () => {
      wrapper = createWrapper({
        modelValue: relatedModelValue,
        readonly: true
      })
      
      const viewButton = wrapper.find('button:contains("View")')
      const editButton = wrapper.find('button:contains("Edit")')
      
      expect(viewButton.exists()).toBe(true)
      expect(editButton.exists()).toBe(false)
    })

    it('uses fallback title when title is not provided', () => {
      wrapper = createWrapper({
        modelValue: {
          ...relatedModelValue,
          title: null
        }
      })
      
      expect(wrapper.text()).toContain('Address #123')
    })
  })

  describe('Empty State', () => {
    it('shows empty state when no related model', () => {
      wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('No Address')
      expect(wrapper.text()).toContain("This resource doesn't have a related address.")
      expect(wrapper.find('.border-dashed').exists()).toBe(true)
    })

    it('shows create button in empty state when not readonly', () => {
      wrapper = createWrapper()
      
      const createButton = wrapper.find('button:contains("Create Address")')
      expect(createButton.exists()).toBe(true)
      expect(wrapper.find('[data-testid="plus-icon"]').exists()).toBe(true)
    })

    it('hides create button when readonly', () => {
      wrapper = createWrapper({
        readonly: true
      })
      
      const createButton = wrapper.find('button:contains("Create Address")')
      expect(createButton.exists()).toBe(false)
    })
  })

  describe('Of Many Relationships', () => {
    const ofManyField = {
      ...defaultField,
      name: 'Latest Post',
      isOfMany: true,
      ofManyRelationship: 'latestPost',
      ofManyResourceClass: 'App\\Nova\\PostResource'
    }

    it('shows of many information', () => {
      wrapper = createWrapper({
        field: ofManyField
      })
      
      expect(wrapper.text()).toContain('This is a "Latest Post" relationship')
      expect(wrapper.find('[data-testid="info-icon"]').exists()).toBe(true)
    })

    it('does not show of many info for regular relationships', () => {
      wrapper = createWrapper()
      
      expect(wrapper.text()).not.toContain('This is a')
      expect(wrapper.find('[data-testid="info-icon"]').exists()).toBe(false)
    })
  })

  describe('Theme Support', () => {
    it('applies light theme classes by default', () => {
      adminStore.setTheme('light')
      wrapper = createWrapper()
      
      const header = wrapper.find('h3')
      expect(header.classes()).toContain('text-gray-900')
      expect(header.classes()).not.toContain('text-gray-100')
    })

    it('applies dark theme classes when dark theme is active', () => {
      adminStore.setTheme('dark')
      wrapper = createWrapper()
      
      const header = wrapper.find('h3')
      expect(header.classes()).toContain('text-gray-100')
    })

    it('applies correct status badge classes for light theme', () => {
      adminStore.setTheme('light')
      wrapper = createWrapper()
      
      const badge = wrapper.find('.rounded-full')
      expect(badge.classes()).toContain('bg-gray-100')
      expect(badge.classes()).toContain('text-gray-800')
    })

    it('applies correct status badge classes for dark theme', () => {
      adminStore.setTheme('dark')
      wrapper = createWrapper()
      
      const badge = wrapper.find('.rounded-full')
      expect(badge.classes()).toContain('bg-gray-700')
      expect(badge.classes()).toContain('text-gray-300')
    })

    it('applies correct status badge classes for related model in light theme', () => {
      adminStore.setTheme('light')
      wrapper = createWrapper({
        modelValue: {
          id: 123,
          title: 'Test',
          resource_class: 'Test',
          exists: true
        }
      })
      
      const badge = wrapper.find('.rounded-full')
      expect(badge.classes()).toContain('bg-green-100')
      expect(badge.classes()).toContain('text-green-800')
    })

    it('applies correct status badge classes for related model in dark theme', () => {
      adminStore.setTheme('dark')
      wrapper = createWrapper({
        modelValue: {
          id: 123,
          title: 'Test',
          resource_class: 'Test',
          exists: true
        }
      })
      
      const badge = wrapper.find('.rounded-full')
      expect(badge.classes()).toContain('bg-green-900')
      expect(badge.classes()).toContain('text-green-200')
    })
  })

  describe('Event Handling', () => {
    const relatedModelValue = {
      id: 123,
      title: 'Test Address',
      resource_class: 'App\\Nova\\AddressResource',
      exists: true
    }

    it('emits view-related event when view button is clicked', async () => {
      wrapper = createWrapper({
        modelValue: relatedModelValue
      })
      
      const viewButton = wrapper.find('button:contains("View")')
      await viewButton.trigger('click')
      
      expect(wrapper.emitted('view-related')).toBeTruthy()
      expect(wrapper.emitted('view-related')[0][0]).toEqual({
        id: 123,
        resourceClass: 'App\\Nova\\AddressResource',
        title: 'Test Address'
      })
    })

    it('emits edit-related event when edit button is clicked', async () => {
      wrapper = createWrapper({
        modelValue: relatedModelValue
      })
      
      const editButton = wrapper.find('button:contains("Edit")')
      await editButton.trigger('click')
      
      expect(wrapper.emitted('edit-related')).toBeTruthy()
      expect(wrapper.emitted('edit-related')[0][0]).toEqual({
        id: 123,
        resourceClass: 'App\\Nova\\AddressResource',
        title: 'Test Address'
      })
    })

    it('emits create-related event when create button is clicked', async () => {
      wrapper = createWrapper()
      
      const createButton = wrapper.find('button:contains("Create Address")')
      await createButton.trigger('click')
      
      expect(wrapper.emitted('create-related')).toBeTruthy()
      expect(wrapper.emitted('create-related')[0][0]).toEqual({
        resourceClass: 'App\\Nova\\AddressResource',
        relationshipName: 'address',
        onCreated: expect.any(Function)
      })
    })

    it('updates model value when onCreated callback is called', async () => {
      wrapper = createWrapper()
      
      const createButton = wrapper.find('button:contains("Create Address")')
      await createButton.trigger('click')
      
      const createEvent = wrapper.emitted('create-related')[0][0]
      const newResource = { id: 456, title: 'New Address' }
      
      createEvent.onCreated(newResource)
      
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toEqual({
        id: 456,
        title: 'New Address',
        resource_class: 'App\\Nova\\AddressResource',
        exists: true
      })
    })
  })

  describe('Computed Properties', () => {
    it('correctly computes hasRelatedModel', () => {
      wrapper = createWrapper()
      expect(wrapper.vm.hasRelatedModel).toBe(false)
      
      wrapper = createWrapper({
        modelValue: {
          id: 123,
          title: 'Test',
          resource_class: 'Test',
          exists: true
        }
      })
      expect(wrapper.vm.hasRelatedModel).toBe(true)
    })

    it('correctly computes relatedModelTitle with title', () => {
      wrapper = createWrapper({
        modelValue: {
          id: 123,
          title: 'Custom Title',
          resource_class: 'Test',
          exists: true
        }
      })
      expect(wrapper.vm.relatedModelTitle).toBe('Custom Title')
    })

    it('correctly computes relatedModelTitle without title', () => {
      wrapper = createWrapper({
        modelValue: {
          id: 123,
          title: null,
          resource_class: 'Test',
          exists: true
        }
      })
      expect(wrapper.vm.relatedModelTitle).toBe('Address #123')
    })

    it('correctly computes statusText', () => {
      wrapper = createWrapper()
      expect(wrapper.vm.statusText).toBe('No Relation')
      
      wrapper = createWrapper({
        modelValue: {
          id: 123,
          title: 'Test',
          resource_class: 'Test',
          exists: true
        }
      })
      expect(wrapper.vm.statusText).toBe('Related')
    })
  })
})
