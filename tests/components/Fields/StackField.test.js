import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import StackField from '@/components/Fields/StackField.vue'
import BaseField from '@/components/Fields/BaseField.vue'
import LineField from '@/components/Fields/LineField.vue'
import TextField from '@/components/Fields/TextField.vue'
import { createMockField, mountField } from '../../helpers.js'

/**
 * Stack Field Vue Component Tests
 *
 * Tests for StackField Vue component with 100% Nova API compatibility.
 * Tests all Nova Stack field features including fields rendering,
 * component mapping, and proper display functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false,
  fullscreenMode: false,
  sidebarCollapsed: false,
  toggleDarkTheme: vi.fn(),
  toggleFullscreen: vi.fn(),
  toggleSidebar: vi.fn()
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('StackField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'User Info',
      attribute: 'user_info',
      component: 'StackField',
      fields: [],
      isStack: true,
      readonly: true,
      nullable: true
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders stack field with BaseField wrapper', () => {
      wrapper = mountField(StackField, { field: mockField })

      expect(wrapper.findComponent(BaseField).exists()).toBe(true)
      expect(wrapper.find('.stack-field').exists()).toBe(true)
    })

    it('shows empty state when no fields', () => {
      wrapper = mountField(StackField, { field: mockField })

      const emptyState = wrapper.find('.stack-empty')
      expect(emptyState.exists()).toBe(true)
      expect(emptyState.text()).toBe('No fields to display')
    })

    it('shows label when field has meaningful name', () => {
      wrapper = mountField(StackField, { field: mockField })

      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('showLabel')).toBe(true)
    })

    it('hides label when field name is generic', () => {
      const genericField = createMockField({
        name: 'Stack',
        component: 'StackField',
        fields: []
      })

      wrapper = mountField(StackField, { field: genericField })

      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('showLabel')).toBe(false)
    })
  })

  describe('Field Rendering', () => {
    it('renders line fields correctly', () => {
      const fieldWithLines = createMockField({
        name: 'User Info',
        component: 'StackField',
        fields: [
          {
            name: 'Status',
            component: 'LineField',
            value: 'Active',
            asSmall: false,
            asHeading: false,
            asSubText: false
          },
          {
            name: 'Last Login',
            component: 'LineField',
            value: 'Today',
            asSmall: true,
            asHeading: false,
            asSubText: false
          }
        ]
      })

      wrapper = mountField(StackField, { field: fieldWithLines })

      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(2)

      const lineFields = wrapper.findAllComponents(LineField)
      expect(lineFields).toHaveLength(2)
    })

    it('renders text fields correctly', () => {
      const fieldWithText = createMockField({
        name: 'User Details',
        component: 'StackField',
        fields: [
          {
            name: 'Name',
            component: 'TextField',
            value: 'John Doe'
          },
          {
            name: 'Email',
            component: 'TextField',
            value: 'john@example.com'
          }
        ]
      })

      wrapper = mountField(StackField, { field: fieldWithText })

      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(2)

      const textFields = wrapper.findAllComponents(TextField)
      expect(textFields).toHaveLength(2)
    })

    it('renders mixed field types correctly', () => {
      const mixedField = createMockField({
        name: 'Product Info',
        component: 'StackField',
        fields: [
          {
            name: 'Name',
            component: 'TextField',
            value: 'Product Name'
          },
          {
            name: 'Status',
            component: 'LineField',
            value: 'Available',
            asHeading: true
          },
          {
            name: 'Price',
            component: 'LineField',
            value: '$99.99',
            asSmall: true
          }
        ]
      })

      wrapper = mountField(StackField, { field: mixedField })

      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(3)

      expect(wrapper.findAllComponents(TextField)).toHaveLength(1)
      expect(wrapper.findAllComponents(LineField)).toHaveLength(2)
    })
  })

  describe('Component Mapping', () => {
    it('maps LineField component correctly', () => {
      const field = createMockField({
        name: 'Stack',
        component: 'StackField',
        fields: [{ component: 'LineField' }]
      })

      wrapper = mountField(StackField, { field })

      expect(wrapper.vm.getFieldComponent({ component: 'LineField' })).toBe('LineField')
    })

    it('maps TextField component correctly', () => {
      wrapper = mountField(StackField, { field: mockField })

      expect(wrapper.vm.getFieldComponent({ component: 'TextField' })).toBe('TextField')
    })

    it('maps BelongsToField component correctly', () => {
      wrapper = mountField(StackField, { field: mockField })

      expect(wrapper.vm.getFieldComponent({ component: 'BelongsToField' })).toBe('BelongsToField')
    })

    it('falls back to TextField for unknown components', () => {
      wrapper = mountField(StackField, { field: mockField })

      expect(wrapper.vm.getFieldComponent({ component: 'UnknownField' })).toBe('TextField')
    })

    it('maps common field types to TextField', () => {
      wrapper = mountField(StackField, { field: mockField })

      expect(wrapper.vm.getFieldComponent({ component: 'EmailField' })).toBe('TextField')
      expect(wrapper.vm.getFieldComponent({ component: 'NumberField' })).toBe('TextField')
      expect(wrapper.vm.getFieldComponent({ component: 'PasswordField' })).toBe('TextField')
      expect(wrapper.vm.getFieldComponent({ component: 'URLField' })).toBe('TextField')
    })
  })

  describe('Styling and Layout', () => {
    it('applies proper spacing between stack items', () => {
      const fieldWithMultipleItems = createMockField({
        name: 'Multi Stack',
        component: 'StackField',
        fields: [
          { name: 'Field 1', component: 'LineField', value: 'Value 1' },
          { name: 'Field 2', component: 'LineField', value: 'Value 2' }
        ]
      })

      wrapper = mountField(StackField, { field: fieldWithMultipleItems })

      const stackField = wrapper.find('.stack-field')
      expect(stackField.classes()).toContain('space-y-2')
    })

    it('applies border styling for multiple items', () => {
      const fieldWithMultipleItems = createMockField({
        name: 'Multi Stack',
        component: 'StackField',
        fields: [
          { name: 'Field 1', component: 'LineField', value: 'Value 1' },
          { name: 'Field 2', component: 'LineField', value: 'Value 2' }
        ]
      })

      wrapper = mountField(StackField, { field: fieldWithMultipleItems })

      const stackItems = wrapper.findAll('.stack-item')
      stackItems.forEach(item => {
        expect(item.classes()).toContain('border-l-2')
        expect(item.classes()).toContain('border-gray-200')
        expect(item.classes()).toContain('pl-3')
      })
    })

    it('applies dark theme border styling', () => {
      mockAdminStore.isDarkTheme = true

      const fieldWithMultipleItems = createMockField({
        name: 'Multi Stack',
        component: 'StackField',
        fields: [
          { name: 'Field 1', component: 'LineField', value: 'Value 1' },
          { name: 'Field 2', component: 'LineField', value: 'Value 2' }
        ]
      })

      wrapper = mountField(StackField, { field: fieldWithMultipleItems })

      const stackItems = wrapper.findAll('.stack-item')
      stackItems.forEach(item => {
        expect(item.classes()).toContain('border-gray-600')
      })

      mockAdminStore.isDarkTheme = false
    })

    it('handles disabled state', () => {
      wrapper = mountField(StackField, { 
        field: mockField,
        disabled: true
      })

      const stackField = wrapper.find('.stack-field')
      expect(stackField.classes()).toContain('opacity-75')
    })
  })

  describe('Props and State', () => {
    it('is readonly by default', () => {
      wrapper = mountField(StackField, { field: mockField })

      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('readonly')).toBe(true)
    })

    it('passes readonly to child fields', () => {
      const fieldWithChildren = createMockField({
        name: 'Stack',
        component: 'StackField',
        fields: [
          { name: 'Child', component: 'LineField', value: 'Value' }
        ]
      })

      wrapper = mountField(StackField, { field: fieldWithChildren })

      const lineField = wrapper.findComponent(LineField)
      expect(lineField.props('readonly')).toBe(true)
    })

    it('handles different sizes', () => {
      wrapper = mountField(StackField, { 
        field: mockField,
        size: 'large'
      })

      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('size')).toBe('large')
    })

    it('passes size to child fields', () => {
      const fieldWithChildren = createMockField({
        name: 'Stack',
        component: 'StackField',
        fields: [
          { name: 'Child', component: 'LineField', value: 'Value' }
        ]
      })

      wrapper = mountField(StackField, { 
        field: fieldWithChildren,
        size: 'small'
      })

      const lineField = wrapper.findComponent(LineField)
      expect(lineField.props('size')).toBe('small')
    })
  })

  describe('Events and Methods', () => {
    it('does not emit data change events', () => {
      wrapper = mountField(StackField, { field: mockField })

      // Stack fields should not emit any data changes
      expect(wrapper.emitted()).toEqual({})
    })

    it('exposes focus method', () => {
      wrapper = mountField(StackField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method does not throw error', () => {
      wrapper = mountField(StackField, { field: mockField })

      expect(() => wrapper.vm.focus()).not.toThrow()
    })

    it('prevents child field value updates', () => {
      const fieldWithChildren = createMockField({
        name: 'Stack',
        component: 'StackField',
        fields: [
          { name: 'Child', component: 'TextField', value: 'Value' }
        ]
      })

      wrapper = mountField(StackField, { field: fieldWithChildren })

      const textField = wrapper.findComponent(TextField)
      
      // Child fields should have empty update handler
      textField.vm.$emit('update:modelValue', 'new value')
      
      // No events should be emitted from stack field
      expect(wrapper.emitted()).toEqual({})
    })
  })

  describe('Edge Cases', () => {
    it('handles empty fields array', () => {
      wrapper = mountField(StackField, { field: mockField })

      expect(wrapper.find('.stack-empty').exists()).toBe(true)
      expect(wrapper.findAll('.stack-item')).toHaveLength(0)
    })

    it('handles undefined fields', () => {
      const fieldWithUndefinedFields = createMockField({
        name: 'Stack',
        component: 'StackField'
        // fields property is undefined
      })

      wrapper = mountField(StackField, { field: fieldWithUndefinedFields })

      expect(wrapper.find('.stack-empty').exists()).toBe(true)
      expect(wrapper.findAll('.stack-item')).toHaveLength(0)
    })

    it('handles single field without border styling', () => {
      const fieldWithSingleItem = createMockField({
        name: 'Single Stack',
        component: 'StackField',
        fields: [
          { name: 'Only Field', component: 'LineField', value: 'Only Value' }
        ]
      })

      wrapper = mountField(StackField, { field: fieldWithSingleItem })

      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(1)
      
      // Single items should not have border styling
      expect(stackItems[0].classes()).not.toContain('border-l-2')
    })
  })
})
