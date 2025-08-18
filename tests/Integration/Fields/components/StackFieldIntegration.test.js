import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import StackField from '@/components/Fields/StackField.vue'
import LineField from '@/components/Fields/LineField.vue'
import TextField from '@/components/Fields/TextField.vue'
import BaseField from '@/components/Fields/BaseField.vue'
import { createMockField, mountField } from '../../../helpers.js'

/**
 * Stack Field Frontend Integration Tests
 *
 * Tests the integration between the StackField Vue component and the broader frontend system,
 * ensuring proper field composition, data flow, event handling, and Nova-style behavior.
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

describe('StackField Frontend Integration', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP-Vue Data Integration', () => {
    it('renders stack field data from PHP backend correctly', () => {
      // Simulate data coming from PHP backend
      const phpStackData = {
        name: 'User Profile',
        attribute: 'user_profile',
        component: 'StackField',
        fields: [
          {
            name: 'Full Name',
            component: 'TextField',
            value: 'John Doe',
            readonly: true
          },
          {
            name: 'Status',
            component: 'LineField',
            value: 'Active User',
            asHeading: true,
            isLine: true
          },
          {
            name: 'Last Login',
            component: 'LineField',
            value: 'Today at 2:30 PM',
            asSmall: true,
            isLine: true
          }
        ],
        isStack: true,
        readonly: true,
        nullable: true
      }

      wrapper = mountField(StackField, { field: phpStackData })

      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(3)

      // Check that different field types are rendered
      expect(wrapper.findAllComponents(TextField)).toHaveLength(1)
      expect(wrapper.findAllComponents(LineField)).toHaveLength(2)
    })

    it('handles PHP field with mixed field types and formatting', () => {
      const phpStackData = {
        name: 'Product Details',
        component: 'StackField',
        fields: [
          {
            name: 'Product Name',
            component: 'TextField',
            value: 'Premium Widget',
            readonly: true
          },
          {
            name: 'Price',
            component: 'LineField',
            value: '$99.99',
            asHeading: true,
            isLine: true
          },
          {
            name: 'Availability',
            component: 'LineField',
            value: 'In Stock',
            asSmall: true,
            isLine: true
          },
          {
            name: 'Description',
            component: 'LineField',
            value: 'High-quality premium widget with advanced features',
            asSubText: true,
            isLine: true
          }
        ],
        isStack: true
      }

      wrapper = mountField(StackField, { field: phpStackData })

      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(4)

      const lineFields = wrapper.findAllComponents(LineField)
      expect(lineFields).toHaveLength(3)

      // Check that formatting is preserved
      expect(lineFields[0].props('field').asHeading).toBe(true)
      expect(lineFields[1].props('field').asSmall).toBe(true)
      expect(lineFields[2].props('field').asSubText).toBe(true)
    })

    it('handles PHP field with nested attribute resolution', () => {
      const phpStackData = {
        name: 'User Details',
        component: 'StackField',
        fields: [
          {
            name: 'Name',
            attribute: 'name',
            component: 'TextField',
            value: 'John Doe'
          },
          {
            name: 'Bio',
            attribute: 'profile.bio',
            component: 'LineField',
            value: 'Software Developer',
            isLine: true
          },
          {
            name: 'Location',
            attribute: 'profile.location',
            component: 'LineField',
            value: 'New York, NY',
            asSmall: true,
            isLine: true
          }
        ],
        isStack: true
      }

      wrapper = mountField(StackField, { field: phpStackData })

      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(3)

      // Verify that nested attributes are resolved correctly
      const textField = wrapper.findComponent(TextField)
      expect(textField.props('field').value).toBe('John Doe')

      const lineFields = wrapper.findAllComponents(LineField)
      expect(lineFields[0].props('field').value).toBe('Software Developer')
      expect(lineFields[1].props('field').value).toBe('New York, NY')
    })
  })

  describe('Theme Integration', () => {
    it('integrates with global theme system', async () => {
      const stackField = createMockField({
        name: 'Themed Stack',
        component: 'StackField',
        fields: [
          {
            name: 'Field 1',
            component: 'LineField',
            value: 'Content 1',
            isLine: true
          },
          {
            name: 'Field 2',
            component: 'LineField',
            value: 'Content 2',
            isLine: true
          }
        ]
      })

      wrapper = mountField(StackField, { field: stackField })

      // Light theme - check border styling
      let stackItems = wrapper.findAll('.stack-item')
      stackItems.forEach(item => {
        expect(item.classes()).toContain('border-gray-200')
      })

      // Switch to dark theme
      mockAdminStore.isDarkTheme = true
      await wrapper.vm.$nextTick()

      // Re-mount to trigger reactivity
      wrapper.unmount()
      wrapper = mountField(StackField, { field: stackField })

      stackItems = wrapper.findAll('.stack-item')
      stackItems.forEach(item => {
        expect(item.classes()).toContain('border-gray-600')
      })

      // Reset
      mockAdminStore.isDarkTheme = false
    })

    it('passes theme context to child fields', async () => {
      const stackField = createMockField({
        name: 'Theme Stack',
        component: 'StackField',
        fields: [
          {
            name: 'Small Text',
            component: 'LineField',
            value: 'Small content',
            asSmall: true,
            isLine: true
          }
        ]
      })

      // Dark theme
      mockAdminStore.isDarkTheme = true
      wrapper = mountField(StackField, { field: stackField })

      const lineField = wrapper.findComponent(LineField)
      expect(lineField.exists()).toBe(true)

      // Child field should receive theme context
      expect(lineField.props('field').asSmall).toBe(true)

      mockAdminStore.isDarkTheme = false
    })
  })

  describe('Form Integration', () => {
    it('integrates with form context without interfering', () => {
      const stackField = createMockField({
        name: 'Form Stack',
        component: 'StackField',
        fields: [
          {
            name: 'Display Field',
            component: 'LineField',
            value: 'Read-only content',
            isLine: true
          }
        ],
        readonly: true
      })

      wrapper = mountField(StackField, { field: stackField })

      // Stack fields should not emit form events
      expect(wrapper.emitted()).toEqual({})
      
      // Should be properly integrated with BaseField
      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('readonly')).toBe(true)

      // Child fields should also be readonly
      const lineField = wrapper.findComponent(LineField)
      expect(lineField.props('readonly')).toBe(true)
    })

    it('handles disabled state in form context', () => {
      const stackField = createMockField({
        name: 'Disabled Stack',
        component: 'StackField',
        fields: [
          {
            name: 'Child Field',
            component: 'LineField',
            value: 'Content',
            isLine: true
          }
        ]
      })

      wrapper = mountField(StackField, { 
        field: stackField,
        disabled: true
      })

      const stackFieldElement = wrapper.find('.stack-field')
      expect(stackFieldElement.classes()).toContain('opacity-75')
      
      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('disabled')).toBe(true)

      // Child fields should also be disabled
      const lineField = wrapper.findComponent(LineField)
      expect(lineField.props('disabled')).toBe(true)
    })
  })

  describe('Component Composition Integration', () => {
    it('properly composes different field types', () => {
      const complexStack = createMockField({
        name: 'Complex Stack',
        component: 'StackField',
        fields: [
          {
            name: 'Title',
            component: 'TextField',
            value: 'Main Title'
          },
          {
            name: 'Subtitle',
            component: 'LineField',
            value: 'Subtitle text',
            asHeading: true,
            isLine: true
          },
          {
            name: 'Description',
            component: 'LineField',
            value: 'Detailed description',
            asSubText: true,
            isLine: true
          },
          {
            name: 'Status',
            component: 'LineField',
            value: 'Active',
            asSmall: true,
            isLine: true
          }
        ]
      })

      wrapper = mountField(StackField, { field: complexStack })

      // Check component composition
      expect(wrapper.findAllComponents(TextField)).toHaveLength(1)
      expect(wrapper.findAllComponents(LineField)).toHaveLength(3)

      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(4)

      // Check that each field maintains its properties
      const lineFields = wrapper.findAllComponents(LineField)
      expect(lineFields[0].props('field').asHeading).toBe(true)
      expect(lineFields[1].props('field').asSubText).toBe(true)
      expect(lineFields[2].props('field').asSmall).toBe(true)
    })

    it('handles dynamic component mapping correctly', () => {
      const stackField = createMockField({
        name: 'Dynamic Stack',
        component: 'StackField',
        fields: [
          { component: 'LineField', name: 'Line', value: 'Line content', isLine: true },
          { component: 'TextField', name: 'Text', value: 'Text content' },
          { component: 'EmailField', name: 'Email', value: 'email@example.com' }, // Should map to TextField
          { component: 'UnknownField', name: 'Unknown', value: 'Unknown content' } // Should fallback to TextField
        ]
      })

      wrapper = mountField(StackField, { field: stackField })

      // Check component mapping
      expect(wrapper.vm.getFieldComponent({ component: 'LineField' })).toBe('LineField')
      expect(wrapper.vm.getFieldComponent({ component: 'TextField' })).toBe('TextField')
      expect(wrapper.vm.getFieldComponent({ component: 'EmailField' })).toBe('TextField')
      expect(wrapper.vm.getFieldComponent({ component: 'UnknownField' })).toBe('TextField')

      // Check actual rendering
      expect(wrapper.findAllComponents(LineField)).toHaveLength(1)
      expect(wrapper.findAllComponents(TextField)).toHaveLength(3) // TextField + EmailField + UnknownField
    })
  })

  describe('Responsive Integration', () => {
    it('adapts to different field sizes', () => {
      const stackField = createMockField({
        name: 'Responsive Stack',
        component: 'StackField',
        fields: [
          {
            name: 'Child Field',
            component: 'LineField',
            value: 'Content',
            isLine: true
          }
        ]
      })

      // Test different sizes
      const sizes = ['small', 'default', 'large']
      
      sizes.forEach(size => {
        wrapper = mountField(StackField, { field: stackField, size })
        
        const baseField = wrapper.findComponent(BaseField)
        expect(baseField.props('size')).toBe(size)

        // Child fields should inherit size
        const lineField = wrapper.findComponent(LineField)
        expect(lineField.props('size')).toBe(size)
        
        wrapper.unmount()
      })
    })

    it('maintains layout across different screen contexts', () => {
      const stackField = createMockField({
        name: 'Layout Stack',
        component: 'StackField',
        fields: [
          { name: 'Field 1', component: 'LineField', value: 'Content 1', isLine: true },
          { name: 'Field 2', component: 'LineField', value: 'Content 2', isLine: true },
          { name: 'Field 3', component: 'LineField', value: 'Content 3', isLine: true }
        ]
      })

      wrapper = mountField(StackField, { field: stackField })

      const stackFieldElement = wrapper.find('.stack-field')
      expect(stackFieldElement.classes()).toContain('space-y-2')

      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(3)

      // Multiple items should have border styling
      stackItems.forEach(item => {
        expect(item.classes()).toContain('border-l-2')
        expect(item.classes()).toContain('pl-3')
      })
    })
  })

  describe('Error Handling Integration', () => {
    it('handles missing field data gracefully', () => {
      const incompleteStack = {
        name: 'Incomplete Stack',
        component: 'StackField'
        // Missing fields array
      }

      expect(() => {
        wrapper = mountField(StackField, { field: incompleteStack })
      }).not.toThrow()

      expect(wrapper.find('.stack-empty').exists()).toBe(true)
      expect(wrapper.find('.stack-empty').text()).toBe('No fields to display')
    })

    it('handles invalid child field data gracefully', () => {
      const stackWithInvalidChild = createMockField({
        name: 'Stack with Invalid Child',
        component: 'StackField',
        fields: [
          {
            name: 'Valid Field',
            component: 'LineField',
            value: 'Valid content',
            isLine: true
          },
          {
            // Missing required properties
            component: 'LineField'
          }
        ]
      })

      expect(() => {
        wrapper = mountField(StackField, { field: stackWithInvalidChild })
      }).not.toThrow()

      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(2)

      const lineFields = wrapper.findAllComponents(LineField)
      expect(lineFields).toHaveLength(2)
    })
  })

  describe('Performance Integration', () => {
    it('handles large number of fields efficiently', () => {
      const manyFields = Array.from({ length: 50 }, (_, i) => ({
        name: `Field ${i + 1}`,
        component: 'LineField',
        value: `Content ${i + 1}`,
        isLine: true
      }))

      const largeStack = createMockField({
        name: 'Large Stack',
        component: 'StackField',
        fields: manyFields
      })

      const startTime = performance.now()
      wrapper = mountField(StackField, { field: largeStack })
      const endTime = performance.now()

      // Should render quickly even with many fields
      expect(endTime - startTime).toBeLessThan(200) // 200ms threshold
      
      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(50)
    })

    it('handles frequent updates efficiently', async () => {
      const dynamicStack = createMockField({
        name: 'Dynamic Stack',
        component: 'StackField',
        fields: [
          {
            name: 'Dynamic Field',
            component: 'LineField',
            value: 'Initial value',
            isLine: true
          }
        ]
      })

      wrapper = mountField(StackField, { field: dynamicStack })

      // Simulate multiple updates
      const updates = 10
      const startTime = performance.now()

      for (let i = 0; i < updates; i++) {
        dynamicStack.fields[0].value = `Updated value ${i}`
        await wrapper.setProps({ field: { ...dynamicStack } })
        await nextTick()
      }

      const endTime = performance.now()

      // Should handle updates efficiently
      expect(endTime - startTime).toBeLessThan(300) // 300ms for 10 updates
      
      const lineField = wrapper.findComponent(LineField)
      expect(lineField.props('field').value).toBe('Updated value 9')
    })
  })

  describe('Real-world Usage Scenarios', () => {
    it('works in typical resource display scenario', () => {
      // Simulate a typical user resource display
      const userProfileStack = createMockField({
        name: 'User Profile',
        component: 'StackField',
        fields: [
          {
            name: 'Full Name',
            component: 'TextField',
            value: 'John Doe',
            readonly: true
          },
          {
            name: 'Status',
            component: 'LineField',
            value: 'Premium Member',
            asHeading: true,
            isLine: true
          },
          {
            name: 'Member Since',
            component: 'LineField',
            value: 'January 2023',
            asSmall: true,
            isLine: true
          },
          {
            name: 'Bio',
            component: 'LineField',
            value: 'Software developer with expertise in web technologies',
            asSubText: true,
            isLine: true
          }
        ],
        showOnIndex: true,
        showOnDetail: true
      })

      wrapper = mountField(StackField, { field: userProfileStack })

      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(4)

      // Check field composition
      expect(wrapper.findAllComponents(TextField)).toHaveLength(1)
      expect(wrapper.findAllComponents(LineField)).toHaveLength(3)

      // Check that formatting is preserved
      const lineFields = wrapper.findAllComponents(LineField)
      expect(lineFields[0].props('field').asHeading).toBe(true)
      expect(lineFields[1].props('field').asSmall).toBe(true)
      expect(lineFields[2].props('field').asSubText).toBe(true)
    })

    it('works in dashboard widget scenario', () => {
      // Simulate usage in a dashboard widget
      const dashboardStack = createMockField({
        name: 'System Status',
        component: 'StackField',
        fields: [
          {
            name: 'Server Status',
            component: 'LineField',
            value: 'Online',
            asHeading: true,
            isLine: true
          },
          {
            name: 'Active Users',
            component: 'LineField',
            value: '1,234 users online',
            asSmall: true,
            isLine: true
          },
          {
            name: 'Last Updated',
            component: 'LineField',
            value: 'Just now',
            asSmall: true,
            isLine: true
          }
        ]
      })

      wrapper = mountField(StackField, { 
        field: dashboardStack,
        size: 'small'
      })

      const stackItems = wrapper.findAll('.stack-item')
      expect(stackItems).toHaveLength(3)

      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('size')).toBe('small')

      // All child fields should inherit the small size
      const lineFields = wrapper.findAllComponents(LineField)
      lineFields.forEach(field => {
        expect(field.props('size')).toBe('small')
      })
    })
  })
})
