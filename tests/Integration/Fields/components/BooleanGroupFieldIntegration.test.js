import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BooleanGroupField from '@/components/Fields/BooleanGroupField.vue'
import BaseField from '@/components/Fields/BaseField.vue'
import { createMockField } from '../../../helpers.js'

/**
 * Boolean Group Field Integration Tests
 *
 * Tests the integration between the PHP BooleanGroup field class and Vue component,
 * ensuring proper data flow, API compatibility, and Nova-style behavior.
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

describe('BooleanGroupField Integration', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP to Vue Integration', () => {
    it('receives and processes PHP field configuration correctly', () => {
      const phpFieldConfig = createMockField({
        name: 'User Permissions',
        attribute: 'user_permissions',
        component: 'BooleanGroupField',
        options: {
          create: 'Create Posts',
          edit: 'Edit Posts',
          delete: 'Delete Posts',
          publish: 'Publish Posts'
        },
        hideFalseValues: true,
        hideTrueValues: false,
        noValueText: 'No permissions selected',
        rules: ['required']
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { create: true, edit: false, delete: true, publish: false }
        }
      })

      // Verify PHP configuration is properly received
      expect(wrapper.vm.field.name).toBe('User Permissions')
      expect(wrapper.vm.field.options).toEqual({
        create: 'Create Posts',
        edit: 'Edit Posts',
        delete: 'Delete Posts',
        publish: 'Publish Posts'
      })
      expect(wrapper.vm.field.hideFalseValues).toBe(true)
      expect(wrapper.vm.field.noValueText).toBe('No permissions selected')
      expect(wrapper.vm.field.rules).toContain('required')
    })

    it('correctly processes Nova API options() method output', () => {
      const phpFieldConfig = createMockField({
        options: {
          'user_create': 'Create Users',
          'user_edit': 'Edit Users',
          'user_delete': 'Delete Users'
        }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { user_create: true, user_edit: false, user_delete: true }
        }
      })

      expect(wrapper.vm.options).toEqual({
        'user_create': 'Create Users',
        'user_edit': 'Edit Users',
        'user_delete': 'Delete Users'
      })

      // Test checkboxes are rendered correctly
      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      expect(checkboxes).toHaveLength(3)
      expect(checkboxes[0].element.checked).toBe(true)  // user_create
      expect(checkboxes[1].element.checked).toBe(false) // user_edit
      expect(checkboxes[2].element.checked).toBe(true)  // user_delete
    })

    it('correctly processes Nova API hideFalseValues() method output', () => {
      const phpFieldConfig = createMockField({
        options: {
          create: 'Create',
          read: 'Read',
          update: 'Update',
          delete: 'Delete'
        },
        hideFalseValues: true
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { create: true, read: false, update: true, delete: false },
          readonly: true
        }
      })

      expect(wrapper.vm.hideFalseValues).toBe(true)

      // In readonly mode, only true values should be displayed
      const badges = wrapper.findAll('.inline-flex')
      expect(badges).toHaveLength(2) // Only create and update (true values)
      expect(wrapper.text()).toContain('Create')
      expect(wrapper.text()).toContain('Update')
      expect(wrapper.text()).not.toContain('Read')
      expect(wrapper.text()).not.toContain('Delete')
    })

    it('correctly processes Nova API hideTrueValues() method output', () => {
      const phpFieldConfig = createMockField({
        options: {
          create: 'Create',
          read: 'Read',
          update: 'Update',
          delete: 'Delete'
        },
        hideTrueValues: true
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { create: true, read: false, update: true, delete: false },
          readonly: true
        }
      })

      expect(wrapper.vm.hideTrueValues).toBe(true)

      // In readonly mode, only false values should be displayed
      const badges = wrapper.findAll('.inline-flex')
      expect(badges).toHaveLength(2) // Only read and delete (false values)
      expect(wrapper.text()).toContain('Read')
      expect(wrapper.text()).toContain('Delete')
      expect(wrapper.text()).not.toContain('Create')
      expect(wrapper.text()).not.toContain('Update')
    })

    it('correctly processes Nova API noValueText() method output', () => {
      const phpFieldConfig = createMockField({
        options: { create: 'Create', read: 'Read' },
        hideFalseValues: true,
        noValueText: 'No permissions assigned to this user'
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { create: false, read: false }, // All false, will be hidden
          readonly: true
        }
      })

      expect(wrapper.vm.noValueText).toBe('No permissions assigned to this user')
      expect(wrapper.text()).toContain('No permissions assigned to this user')
    })

    it('handles empty options from PHP correctly', () => {
      const phpFieldConfig = createMockField({
        options: {}
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: {}
        }
      })

      expect(wrapper.vm.options).toEqual({})
      expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(0)
    })
  })

  describe('Nova API Compatibility Integration', () => {
    it('integrates all Nova API methods correctly', () => {
      const phpFieldConfig = createMockField({
        options: {
          'admin_access': 'Administrator Access',
          'user_management': 'User Management',
          'content_management': 'Content Management'
        },
        hideFalseValues: false,
        hideTrueValues: false,
        noValueText: 'No special permissions',
        rules: ['required'],
        nullable: false
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { admin_access: true, user_management: false, content_management: true }
        }
      })

      // Test complete integration
      expect(wrapper.vm.options).toEqual({
        'admin_access': 'Administrator Access',
        'user_management': 'User Management',
        'content_management': 'Content Management'
      })
      expect(wrapper.vm.hideFalseValues).toBe(false)
      expect(wrapper.vm.hideTrueValues).toBe(false)
      expect(wrapper.vm.noValueText).toBe('No special permissions')
      expect(wrapper.vm.isRequired).toBe(true)

      // Test checkbox states
      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      expect(checkboxes[0].element.checked).toBe(true)  // admin_access
      expect(checkboxes[1].element.checked).toBe(false) // user_management
      expect(checkboxes[2].element.checked).toBe(true)  // content_management
    })

    it('handles fallback behavior correctly', () => {
      const phpFieldConfig = createMockField({
        // No custom configuration provided, should use defaults
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: {}
        }
      })

      // Should fall back to defaults
      expect(wrapper.vm.hideFalseValues).toBe(false)
      expect(wrapper.vm.hideTrueValues).toBe(false)
      expect(wrapper.vm.noValueText).toBe('No Data')
    })

    it('handles undefined configuration values correctly', () => {
      const phpFieldConfig = createMockField({
        options: undefined,
        hideFalseValues: undefined,
        hideTrueValues: undefined,
        noValueText: undefined
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: {}
        }
      })

      // Should fall back to defaults when undefined
      expect(wrapper.vm.options).toEqual({})
      expect(wrapper.vm.hideFalseValues).toBe(false)
      expect(wrapper.vm.hideTrueValues).toBe(false)
      expect(wrapper.vm.noValueText).toBe('No Data')
    })
  })

  describe('User Interaction Integration', () => {
    it('emits correct values based on PHP configuration', async () => {
      const phpFieldConfig = createMockField({
        options: {
          'permission_a': 'Permission A',
          'permission_b': 'Permission B',
          'permission_c': 'Permission C'
        }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { permission_a: false, permission_b: false, permission_c: false }
        }
      })

      const firstCheckbox = wrapper.findAll('input[type="checkbox"]')[0]
      await firstCheckbox.setChecked(true)
      await firstCheckbox.trigger('change')

      // Should emit the PHP-configured structure
      expect(wrapper.emitted('update:modelValue')[0][0]).toEqual({
        permission_a: true,
        permission_b: false,
        permission_c: false
      })
      expect(wrapper.emitted('change')[0][0]).toEqual({
        permission_a: true,
        permission_b: false,
        permission_c: false
      })
    })

    it('handles multiple checkbox changes with PHP structure', async () => {
      const phpFieldConfig = createMockField({
        options: {
          'read': 'Read Access',
          'write': 'Write Access',
          'admin': 'Admin Access'
        }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { read: false, write: false, admin: false }
        }
      })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')

      // Check read permission
      await checkboxes[0].setChecked(true)
      await checkboxes[0].trigger('change')

      // Update the modelValue to reflect the first change
      await wrapper.setProps({
        modelValue: { read: true, write: false, admin: false }
      })

      // Check admin permission
      await checkboxes[2].setChecked(true)
      await checkboxes[2].trigger('change')

      // Should have multiple emissions with correct structure
      expect(wrapper.emitted('update:modelValue').length).toBeGreaterThanOrEqual(2)

      // Last emission should have admin set to true and preserve read state
      const lastEmission = wrapper.emitted('update:modelValue').slice(-1)[0][0]
      expect(lastEmission.admin).toBe(true)
      expect(lastEmission.read).toBe(true) // Should preserve previous state
    })
  })

  describe('CRUD Operations Integration', () => {
    it('handles create operation with boolean group field', () => {
      const phpFieldConfig = createMockField({
        options: {
          'create_posts': 'Create Posts',
          'edit_posts': 'Edit Posts',
          'delete_posts': 'Delete Posts'
        }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: null // New record
        }
      })

      // All checkboxes should be unchecked for new record
      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      checkboxes.forEach(checkbox => {
        expect(checkbox.element.checked).toBe(false)
      })
    })

    it('handles read operation with boolean group field', () => {
      const phpFieldConfig = createMockField({
        options: {
          'view_users': 'View Users',
          'edit_users': 'Edit Users',
          'delete_users': 'Delete Users'
        }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { view_users: true, edit_users: true, delete_users: false }, // Existing record
          readonly: true
        }
      })

      // Should display readonly badges
      const badges = wrapper.findAll('.inline-flex')
      expect(badges).toHaveLength(3)
      expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(0)
    })

    it('handles update operation with boolean group field', async () => {
      const phpFieldConfig = createMockField({
        options: {
          'feature_a': 'Feature A',
          'feature_b': 'Feature B'
        }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { feature_a: false, feature_b: false } // Current values
        }
      })

      // Simulate update
      await wrapper.setProps({
        modelValue: { feature_a: true, feature_b: false }
      })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      expect(checkboxes[0].element.checked).toBe(true)  // feature_a updated
      expect(checkboxes[1].element.checked).toBe(false) // feature_b unchanged
    })
  })

  describe('Validation Integration', () => {
    it('displays required indicator based on PHP field rules', () => {
      const phpFieldConfig = createMockField({
        options: { create: 'Create', read: 'Read' },
        rules: ['required', 'array']
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: {}
        }
      })

      expect(wrapper.text()).toContain('* Required')
    })

    it('handles validation errors correctly', () => {
      const phpFieldConfig = createMockField({
        options: { permission: 'Permission' }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: {},
          errors: ['At least one permission is required'] // Validation errors
        }
      })

      // Boolean group field should still display correctly even with errors
      expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(1)
    })
  })

  describe('Advanced Integration Scenarios', () => {
    it('handles dynamic field configuration changes', async () => {
      let phpFieldConfig = createMockField({
        options: { basic: 'Basic Permission' }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { basic: true }
        }
      })

      expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(1)

      // Simulate field configuration change (e.g., from PHP backend)
      phpFieldConfig = createMockField({
        options: {
          basic: 'Basic Permission',
          advanced: 'Advanced Permission',
          admin: 'Admin Permission'
        }
      })

      await wrapper.setProps({
        field: phpFieldConfig,
        modelValue: { basic: true, advanced: false, admin: true }
      })

      expect(wrapper.vm.options).toEqual({
        basic: 'Basic Permission',
        advanced: 'Advanced Permission',
        admin: 'Admin Permission'
      })
      expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(3)
    })

    it('handles complex Nova configuration from PHP', () => {
      const phpFieldConfig = createMockField({
        name: 'User Permissions',
        attribute: 'user_permissions',
        options: {
          'posts.create': 'Create Posts',
          'posts.edit': 'Edit Posts',
          'posts.delete': 'Delete Posts',
          'users.manage': 'Manage Users'
        },
        hideFalseValues: true,
        noValueText: 'No permissions assigned',
        rules: ['required', 'array'],
        nullable: false,
        readonly: false,
        helpText: 'Select user permissions'
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: {
            'posts.create': true,
            'posts.edit': false,
            'posts.delete': true,
            'users.manage': false
          }
        }
      })

      // Test all configurations are processed correctly
      expect(wrapper.vm.options).toEqual({
        'posts.create': 'Create Posts',
        'posts.edit': 'Edit Posts',
        'posts.delete': 'Delete Posts',
        'users.manage': 'Manage Users'
      })
      expect(wrapper.vm.hideFalseValues).toBe(true)
      expect(wrapper.vm.noValueText).toBe('No permissions assigned')
      expect(wrapper.vm.isRequired).toBe(true)

      // Test checkboxes are rendered
      expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(4)
    })

    it('integrates with BaseField wrapper correctly', () => {
      const phpFieldConfig = createMockField({
        name: 'Permissions',
        helpText: 'Select the permissions for this role',
        options: { create: 'Create', read: 'Read' }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { create: true, read: false }
        }
      })

      // Test BaseField integration
      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.exists()).toBe(true)
      expect(baseField.props('field')).toEqual(phpFieldConfig)
      expect(baseField.props('modelValue')).toEqual({ create: true, read: false })
    })
  })

  describe('Type Handling and Edge Cases', () => {
    it('handles complex option keys correctly', () => {
      const phpFieldConfig = createMockField({
        options: {
          '0': 'Zero Key',
          '1': 'One Key',
          'string_key': 'String Key',
          'kebab-case': 'Kebab Case',
          'snake_case': 'Snake Case',
          'camelCase': 'Camel Case'
        }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: {
            '0': true,
            '1': false,
            'string_key': true,
            'kebab-case': false,
            'snake_case': true,
            'camelCase': false
          }
        }
      })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      expect(checkboxes).toHaveLength(6)
      expect(checkboxes[0].element.checked).toBe(true)  // '0'
      expect(checkboxes[1].element.checked).toBe(false) // '1'
      expect(checkboxes[2].element.checked).toBe(true)  // 'string_key'
      expect(checkboxes[3].element.checked).toBe(false) // 'kebab-case'
      expect(checkboxes[4].element.checked).toBe(true)  // 'snake_case'
      expect(checkboxes[5].element.checked).toBe(false) // 'camelCase'
    })

    it('handles null and undefined values from PHP correctly', () => {
      const phpFieldConfig = createMockField({
        options: { permission: 'Permission' }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        }
      })

      // Should handle null gracefully
      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(false)
    })

    it('handles missing keys in modelValue from PHP', () => {
      const phpFieldConfig = createMockField({
        options: {
          'permission_a': 'Permission A',
          'permission_b': 'Permission B',
          'permission_c': 'Permission C'
        }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { permission_a: true } // Missing permission_b and permission_c
        }
      })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      expect(checkboxes[0].element.checked).toBe(true)  // permission_a (present)
      expect(checkboxes[1].element.checked).toBe(false) // permission_b (missing, defaults to false)
      expect(checkboxes[2].element.checked).toBe(false) // permission_c (missing, defaults to false)
    })
  })

  describe('Performance and Reactivity', () => {
    it('updates efficiently when props change', async () => {
      const phpFieldConfig = createMockField({
        options: { create: 'Create', read: 'Read' }
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { create: false, read: false }
        }
      })

      expect(wrapper.findAll('input[type="checkbox"]')[0].element.checked).toBe(false)

      // Multiple rapid changes should work correctly
      await wrapper.setProps({ modelValue: { create: true, read: false } })
      expect(wrapper.findAll('input[type="checkbox"]')[0].element.checked).toBe(true)

      await wrapper.setProps({ modelValue: { create: false, read: true } })
      expect(wrapper.findAll('input[type="checkbox"]')[0].element.checked).toBe(false)
      expect(wrapper.findAll('input[type="checkbox"]')[1].element.checked).toBe(true)

      await wrapper.setProps({ modelValue: { create: true, read: true } })
      expect(wrapper.findAll('input[type="checkbox"]')[0].element.checked).toBe(true)
      expect(wrapper.findAll('input[type="checkbox"]')[1].element.checked).toBe(true)
    })

    it('maintains reactivity with complex field changes', async () => {
      let phpFieldConfig = createMockField({
        options: { basic: 'Basic' },
        hideFalseValues: false
      })

      wrapper = mount(BooleanGroupField, {
        props: {
          field: phpFieldConfig,
          modelValue: { basic: true }
        }
      })

      expect(wrapper.vm.hideFalseValues).toBe(false)

      // Change both field config and value simultaneously
      phpFieldConfig = createMockField({
        options: { basic: 'Basic', advanced: 'Advanced' },
        hideFalseValues: true
      })

      await wrapper.setProps({
        field: phpFieldConfig,
        modelValue: { basic: false, advanced: true }
      })

      expect(wrapper.vm.hideFalseValues).toBe(true)
      expect(wrapper.vm.options).toEqual({ basic: 'Basic', advanced: 'Advanced' })
      expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(2)
    })
  })
})
