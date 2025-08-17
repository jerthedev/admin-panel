import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BooleanField from '@/components/Fields/BooleanField.vue'
import BaseField from '@/components/Fields/BaseField.vue'
import { createMockField } from '../../../helpers.js'

/**
 * Boolean Field Integration Tests
 *
 * Tests the integration between the PHP Boolean field class and Vue component,
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

describe('BooleanField Integration', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP to Vue Integration', () => {
    it('receives and processes PHP field configuration correctly', async () => {
      const phpFieldConfig = createMockField({
        name: 'Is Active',
        attribute: 'is_active',
        component: 'BooleanField',
        trueValue: 'active',
        falseValue: 'inactive',
        rules: ['required']
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'active'
        }
      })

      // Verify PHP configuration is properly received
      expect(wrapper.vm.field.name).toBe('Is Active')
      expect(wrapper.vm.field.trueValue).toBe('active')
      expect(wrapper.vm.field.falseValue).toBe('inactive')
      expect(wrapper.vm.field.rules).toContain('required')
    })

    it('correctly processes Nova API trueValue() method output', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 'On',
        falseValue: false
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'On'
        }
      })

      expect(wrapper.vm.trueValue).toBe('On')
      expect(wrapper.vm.isChecked).toBe(true)

      // Test different value
      await await wrapper.setProps({ modelValue: false })
      expect(wrapper.vm.isChecked).toBe(false) // false != 'On' (trueValue)
    })

    it('correctly processes Nova API falseValue() method output', async () => {
      const phpFieldConfig = createMockField({
        trueValue: true,
        falseValue: 'Off'
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'Off'
        }
      })

      expect(wrapper.vm.falseValue).toBe('Off')
      expect(wrapper.vm.isChecked).toBe(false)

      // Test different value
      await await wrapper.setProps({ modelValue: true })
      expect(wrapper.vm.isChecked).toBe(true)
    })

    it('correctly processes both trueValue and falseValue together', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 'yes',
        falseValue: 'no'
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'yes'
        }
      })

      expect(wrapper.vm.trueValue).toBe('yes')
      expect(wrapper.vm.falseValue).toBe('no')
      expect(wrapper.vm.isChecked).toBe(true)

      // Test switching to false value
      await await wrapper.setProps({ modelValue: 'no' })
      expect(wrapper.vm.isChecked).toBe(false)
    })

    it('handles numeric true/false values from PHP', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 1,
        falseValue: 0
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 1
        }
      })

      expect(wrapper.vm.trueValue).toBe(1)
      expect(wrapper.vm.falseValue).toBe(0)
      expect(wrapper.vm.isChecked).toBe(true)
    })

    it('handles mixed type true/false values from PHP', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 'active',
        falseValue: 0
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'active'
        }
      })

      expect(wrapper.vm.trueValue).toBe('active')
      expect(wrapper.vm.falseValue).toBe(0)
      expect(wrapper.vm.isChecked).toBe(true)

      // Test false value
      await wrapper.setProps({ modelValue: 0 })
      expect(wrapper.vm.isChecked).toBe(false)
    })
  })

  describe('Nova API Compatibility Integration', () => {
    it('integrates all Nova API methods correctly', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 'On',
        falseValue: 'Off',
        rules: ['required'],
        nullable: false
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'On'
        }
      })

      // Test complete integration
      expect(wrapper.vm.trueValue).toBe('On')
      expect(wrapper.vm.falseValue).toBe('Off')
      expect(wrapper.vm.isChecked).toBe(true)
      expect(wrapper.vm.isRequired).toBe(true)

      // Test switching values
      await await wrapper.setProps({ modelValue: 'Off' })
      expect(wrapper.vm.isChecked).toBe(false)
    })

    it('handles fallback behavior correctly', async () => {
      const phpFieldConfig = createMockField({
        // No custom values provided, should use defaults
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: true
        }
      })

      // Should fall back to defaults
      expect(wrapper.vm.trueValue).toBe(true)
      expect(wrapper.vm.falseValue).toBe(false)
      expect(wrapper.vm.isChecked).toBe(true)
    })

    it('handles undefined custom values correctly', async () => {
      const phpFieldConfig = createMockField({
        trueValue: undefined,
        falseValue: undefined
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: true
        }
      })

      // Should fall back to defaults when undefined
      expect(wrapper.vm.trueValue).toBe(true)
      expect(wrapper.vm.falseValue).toBe(false)
      expect(wrapper.vm.isChecked).toBe(true)
    })
  })

  describe('User Interaction Integration', () => {
    it('emits correct values based on PHP configuration', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 'active',
        falseValue: 'inactive'
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'inactive'
        }
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.setChecked(true)
      await checkbox.trigger('change')

      // Should emit the PHP-configured true value
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('active')
      expect(wrapper.emitted('change')[0][0]).toBe('active')
    })

    it('toggles between PHP-configured values', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 1,
        falseValue: 0
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 0
        }
      })

      const checkbox = wrapper.find('input[type="checkbox"]')

      // Toggle to true
      await checkbox.setChecked(true)
      await checkbox.trigger('change')
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(1)

      // Reset and toggle to false
      await await wrapper.setProps({ modelValue: 1 })

      // Create a new wrapper to reset emitted events
      wrapper.unmount()
      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 1
        }
      })

      const newCheckbox = wrapper.find('input[type="checkbox"]')
      await newCheckbox.setChecked(false)
      await newCheckbox.trigger('change')
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(0)
    })
  })

  describe('CRUD Operations Integration', () => {
    it('handles create operation with boolean field', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 'active',
        falseValue: 'inactive'
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: null // New record
        }
      })

      expect(wrapper.vm.isChecked).toBe(false) // null should be false
    })

    it('handles read operation with boolean field', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 'yes',
        falseValue: 'no'
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'yes', // Existing record
          readonly: true
        }
      })

      expect(wrapper.vm.isChecked).toBe(true)
      expect(wrapper.find('.inline-flex').exists()).toBe(true)
      expect(wrapper.find('input[type="checkbox"]').exists()).toBe(false)
    })

    it('handles update operation with boolean field', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 'enabled',
        falseValue: 'disabled'
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'disabled' // Current value
        }
      })

      expect(wrapper.vm.isChecked).toBe(false)

      // Simulate update
      await await wrapper.setProps({ modelValue: 'enabled' })
      expect(wrapper.vm.isChecked).toBe(true)
    })
  })

  describe('Validation Integration', () => {
    it('displays required indicator based on PHP field rules', async () => {
      const phpFieldConfig = createMockField({
        rules: ['required', 'boolean']
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: false
        }
      })

      const requiredIndicator = wrapper.find('.text-red-500')
      expect(requiredIndicator.exists()).toBe(true)
      expect(requiredIndicator.text()).toBe('*')
    })

    it('handles validation errors correctly', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 'valid',
        falseValue: 'invalid'
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'invalid',
          errors: ['Field is required'] // Validation errors
        }
      })

      // Boolean field should still display correctly even with errors
      expect(wrapper.vm.isChecked).toBe(false)
      expect(wrapper.find('input[type="checkbox"]').exists()).toBe(true)
    })
  })

  describe('Advanced Integration Scenarios', () => {
    it('handles dynamic field configuration changes', async () => {
      let phpFieldConfig = createMockField({
        trueValue: 'on',
        falseValue: 'off'
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'on'
        }
      })

      expect(wrapper.vm.isChecked).toBe(true)

      // Simulate field configuration change (e.g., from PHP backend)
      phpFieldConfig = createMockField({
        trueValue: 'active',
        falseValue: 'inactive'
      })

      await await wrapper.setProps({ field: phpFieldConfig, modelValue: 'active' })

      expect(wrapper.vm.trueValue).toBe('active')
      expect(wrapper.vm.falseValue).toBe('inactive')
      expect(wrapper.vm.isChecked).toBe(true)
    })

    it('handles complex Nova configuration from PHP', async () => {
      const phpFieldConfig = createMockField({
        name: 'User Status',
        attribute: 'user_status',
        trueValue: 'active_user',
        falseValue: 'inactive_user',
        rules: ['required', 'boolean'],
        nullable: false,
        readonly: false,
        helpText: 'Toggle user active status'
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'active_user'
        }
      })

      // Test all configurations are processed correctly
      expect(wrapper.vm.trueValue).toBe('active_user')
      expect(wrapper.vm.falseValue).toBe('inactive_user')
      expect(wrapper.vm.isChecked).toBe(true)
      expect(wrapper.vm.isRequired).toBe(true)

      // Test field name is displayed
      const label = wrapper.find('label')
      expect(label.text()).toContain('User Status')
    })

    it('integrates with BaseField wrapper correctly', async () => {
      const phpFieldConfig = createMockField({
        name: 'Active Status',
        helpText: 'Toggle the active status',
        trueValue: 'on',
        falseValue: 'off'
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'on'
        }
      })

      // Test BaseField integration
      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.exists()).toBe(true)
      expect(baseField.props('field')).toEqual(phpFieldConfig)
      expect(baseField.props('modelValue')).toBe('on')
    })
  })

  describe('Type Coercion and Edge Cases', () => {
    it('handles type coercion correctly', async () => {
      const phpFieldConfig = createMockField({
        trueValue: '1', // String '1'
        falseValue: 0   // Integer 0
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: '1'
        }
      })

      expect(wrapper.vm.isChecked).toBe(true)

      // Test with integer 1 (should match string '1' with == comparison)
      await await wrapper.setProps({ modelValue: 1 })
      expect(wrapper.vm.isChecked).toBe(true) // Uses == comparison, so 1 == '1' is true
    })

    it('handles null values from PHP correctly', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 'active',
        falseValue: null
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        }
      })

      expect(wrapper.vm.falseValue).toBe(null)
      expect(wrapper.vm.isChecked).toBe(false)
    })

    it('handles empty string values from PHP correctly', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 'yes',
        falseValue: ''
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: ''
        }
      })

      expect(wrapper.vm.falseValue).toBe('')
      expect(wrapper.vm.isChecked).toBe(false)
    })
  })

  describe('Performance and Reactivity', () => {
    it('updates efficiently when props change', async () => {
      const phpFieldConfig = createMockField({
        trueValue: 'on',
        falseValue: 'off'
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'off'
        }
      })

      expect(wrapper.vm.isChecked).toBe(false)

      // Multiple rapid changes should work correctly
      await await wrapper.setProps({ modelValue: 'on' })
      expect(wrapper.vm.isChecked).toBe(true)

      await await wrapper.setProps({ modelValue: 'off' })
      expect(wrapper.vm.isChecked).toBe(false)

      await await wrapper.setProps({ modelValue: 'on' })
      expect(wrapper.vm.isChecked).toBe(true)
    })

    it('maintains reactivity with complex field changes', async () => {
      let phpFieldConfig = createMockField({
        trueValue: 'yes',
        falseValue: 'no'
      })

      wrapper = mount(BooleanField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'yes'
        }
      })

      expect(wrapper.vm.isChecked).toBe(true)

      // Change both field config and value simultaneously
      phpFieldConfig = createMockField({
        trueValue: 'active',
        falseValue: 'inactive'
      })

      await await wrapper.setProps({
        field: phpFieldConfig,
        modelValue: 'active'
      })

      expect(wrapper.vm.trueValue).toBe('active')
      expect(wrapper.vm.isChecked).toBe(true)
    })
  })
})
