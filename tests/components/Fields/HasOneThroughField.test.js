/**
 * HasOneThroughField Component Tests
 * 
 * Comprehensive test suite for the HasOneThroughField Vue component.
 * Tests component rendering, user interactions, and Nova v5 compatibility.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useAdminStore } from '@/stores/admin'
import HasOneThroughField from '@/components/Fields/HasOneThroughField.vue'
import {
  DocumentIcon,
  EyeIcon,
  PencilIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'

// Mock the admin store
vi.mock('@/stores/admin', () => ({
  useAdminStore: vi.fn()
}))

describe('HasOneThroughField', () => {
  let wrapper
  let pinia
  let adminStore

  const defaultField = {
    name: 'Owner',
    attribute: 'owner',
    component: 'HasOneThroughField',
    resourceClass: 'App\\Nova\\OwnerResource',
    relationshipName: 'owner',
    through: 'App\\Models\\Car'
  }

  const defaultModelValue = {
    id: null,
    title: null,
    resource_class: null,
    exists: false,
    through: null
  }

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    adminStore = useAdminStore()
  })

  const createWrapper = (props = {}) => {
    return mount(HasOneThroughField, {
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
    it('renders the field name', () => {
      wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('Owner')
    })

    it('renders the correct component structure', () => {
      wrapper = createWrapper()
      
      expect(wrapper.find('h3').exists()).toBe(true)
      expect(wrapper.find('span').exists()).toBe(true) // Status badge
    })

    it('shows through relationship information when through is provided', () => {
      wrapper = createWrapper({
        field: {
          ...defaultField,
          through: 'App\\Models\\Car'
        }
      })
      
      expect(wrapper.text()).toContain('This relationship is accessed through App\\Models\\Car')
      expect(wrapper.find('[data-testid="info-icon"]').exists()).toBe(true)
    })

    it('does not show through info when through is not provided', () => {
      wrapper = createWrapper({
        field: {
          ...defaultField,
          through: null
        }
      })
      
      expect(wrapper.text()).not.toContain('This relationship is accessed through')
      expect(wrapper.find('[data-testid="info-icon"]').exists()).toBe(false)
    })
  })

  describe('Related Model Display', () => {
    const relatedModelValue = {
      id: 1,
      title: 'John Doe',
      resource_class: 'App\\Nova\\OwnerResource',
      exists: true,
      through: 'App\\Models\\Car'
    }

    it('shows related model when exists', () => {
      wrapper = createWrapper({
        modelValue: relatedModelValue
      })
      
      expect(wrapper.text()).toContain('John Doe')
      expect(wrapper.text()).toContain('App\\Nova\\OwnerResource')
      expect(wrapper.text()).toContain('Related')
    })

    it('shows action buttons for related model', () => {
      wrapper = createWrapper({
        modelValue: relatedModelValue
      })
      
      const viewButton = wrapper.find('button:first-of-type')
      const editButton = wrapper.find('button:last-of-type')
      
      expect(viewButton.text()).toContain('View')
      expect(editButton.text()).toContain('Edit')
    })

    it('emits view-related event when view button is clicked', async () => {
      wrapper = createWrapper({
        modelValue: relatedModelValue
      })
      
      const viewButton = wrapper.find('button:first-of-type')
      await viewButton.trigger('click')
      
      expect(wrapper.emitted('view-related')).toBeTruthy()
      expect(wrapper.emitted('view-related')[0][0]).toEqual({
        id: 1,
        resourceClass: 'App\\Nova\\OwnerResource',
        title: 'John Doe',
        through: 'App\\Models\\Car'
      })
    })

    it('emits edit-related event when edit button is clicked', async () => {
      wrapper = createWrapper({
        modelValue: relatedModelValue
      })
      
      const editButton = wrapper.find('button:last-of-type')
      await editButton.trigger('click')
      
      expect(wrapper.emitted('edit-related')).toBeTruthy()
      expect(wrapper.emitted('edit-related')[0][0]).toEqual({
        id: 1,
        resourceClass: 'App\\Nova\\OwnerResource',
        title: 'John Doe',
        through: 'App\\Models\\Car'
      })
    })

    it('uses fallback title when title is not provided', () => {
      wrapper = createWrapper({
        modelValue: {
          ...relatedModelValue,
          title: null
        }
      })
      
      expect(wrapper.text()).toContain('Owner #1')
    })
  })

  describe('Empty State', () => {
    it('shows empty state when no related model exists', () => {
      wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('No owner found')
      expect(wrapper.text()).toContain('No Relation')
    })

    it('shows through information in empty state', () => {
      wrapper = createWrapper({
        field: {
          ...defaultField,
          through: 'App\\Models\\Car'
        }
      })
      
      expect(wrapper.text()).toContain('This relationship is accessed through App\\Models\\Car')
    })

    it('shows generic through message when through is not specified', () => {
      wrapper = createWrapper({
        field: {
          ...defaultField,
          through: null
        }
      })
      
      expect(wrapper.text()).toContain('This relationship is accessed through an intermediate model')
    })
  })

  describe('Status Badge', () => {
    it('shows "Related" status when model exists', () => {
      wrapper = createWrapper({
        modelValue: {
          id: 1,
          title: 'John Doe',
          resource_class: 'App\\Nova\\OwnerResource',
          exists: true,
          through: 'App\\Models\\Car'
        }
      })
      
      expect(wrapper.text()).toContain('Related')
    })

    it('shows "No Relation" status when model does not exist', () => {
      wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('No Relation')
    })
  })

  describe('Disabled and Readonly States', () => {
    const relatedModelValue = {
      id: 1,
      title: 'John Doe',
      resource_class: 'App\\Nova\\OwnerResource',
      exists: true,
      through: 'App\\Models\\Car'
    }

    it('hides action buttons when disabled', () => {
      wrapper = createWrapper({
        modelValue: relatedModelValue,
        disabled: true
      })
      
      expect(wrapper.find('button').exists()).toBe(false)
    })

    it('hides action buttons when readonly', () => {
      wrapper = createWrapper({
        modelValue: relatedModelValue,
        readonly: true
      })
      
      expect(wrapper.find('button').exists()).toBe(false)
    })

    it('shows action buttons when not disabled or readonly', () => {
      wrapper = createWrapper({
        modelValue: relatedModelValue,
        disabled: false,
        readonly: false
      })
      
      expect(wrapper.findAll('button')).toHaveLength(2)
    })
  })

  describe('Dark Theme Support', () => {
    beforeEach(() => {
      adminStore.isDarkTheme = true
    })

    it('applies dark theme classes when dark theme is enabled', () => {
      wrapper = createWrapper()
      
      // Check for dark theme classes (this is a basic check)
      expect(wrapper.html()).toContain('text-gray-100')
    })
  })

  describe('Field Configuration', () => {
    it('handles different field configurations', () => {
      const customField = {
        name: 'Vehicle Owner',
        attribute: 'vehicle_owner',
        component: 'HasOneThroughField',
        resourceClass: 'App\\Nova\\VehicleOwnerResource',
        relationshipName: 'vehicleOwner',
        through: 'App\\Models\\Vehicle'
      }

      wrapper = createWrapper({
        field: customField
      })
      
      expect(wrapper.text()).toContain('Vehicle Owner')
      expect(wrapper.text()).toContain('This relationship is accessed through App\\Models\\Vehicle')
    })

    it('handles field without through configuration', () => {
      const fieldWithoutThrough = {
        ...defaultField,
        through: undefined
      }

      wrapper = createWrapper({
        field: fieldWithoutThrough
      })
      
      expect(wrapper.text()).not.toContain('This relationship is accessed through')
    })
  })

  describe('Event Handling', () => {
    it('does not emit events when no related model exists', async () => {
      wrapper = createWrapper()
      
      // Try to find buttons (should not exist in empty state)
      const buttons = wrapper.findAll('button')
      expect(buttons).toHaveLength(0)
      
      expect(wrapper.emitted('view-related')).toBeFalsy()
      expect(wrapper.emitted('edit-related')).toBeFalsy()
    })

    it('includes through information in emitted events', async () => {
      const relatedModelValue = {
        id: 1,
        title: 'John Doe',
        resource_class: 'App\\Nova\\OwnerResource',
        exists: true,
        through: 'App\\Models\\Car'
      }

      wrapper = createWrapper({
        modelValue: relatedModelValue
      })
      
      const viewButton = wrapper.find('button:first-of-type')
      await viewButton.trigger('click')
      
      const emittedEvent = wrapper.emitted('view-related')[0][0]
      expect(emittedEvent.through).toBe('App\\Models\\Car')
    })
  })

  describe('Accessibility', () => {
    it('includes proper ARIA attributes and semantic HTML', () => {
      wrapper = createWrapper()
      
      // Check for semantic HTML elements
      expect(wrapper.find('h3').exists()).toBe(true)
      
      // Check for proper button structure when related model exists
      const relatedModelValue = {
        id: 1,
        title: 'John Doe',
        resource_class: 'App\\Nova\\OwnerResource',
        exists: true,
        through: 'App\\Models\\Car'
      }

      wrapper = createWrapper({
        modelValue: relatedModelValue
      })
      
      const buttons = wrapper.findAll('button')
      buttons.forEach(button => {
        expect(button.attributes('type')).toBe('button')
      })
    })
  })
}
