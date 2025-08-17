import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BooleanGroupField from '@/components/Fields/BooleanGroupField.vue'
import BaseField from '@/components/Fields/BaseField.vue'

/**
 * Boolean Group Field Vue Component Tests
 *
 * Tests for BooleanGroupField Vue component with 100% Nova API compatibility.
 * Tests all Nova BooleanGroup field features including options, hide methods, and noValueText.
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

// Helper function to create mock field
const createMockField = (overrides = {}) => ({
  name: 'Permissions',
  attribute: 'permissions',
  component: 'BooleanGroupField',
  options: {
    create: 'Create',
    read: 'Read',
    update: 'Update',
    delete: 'Delete'
  },
  hideFalseValues: false,
  hideTrueValues: false,
  noValueText: 'No Data',
  rules: [],
  ...overrides
})

// Helper function to mount field component
const mountField = (component, props = {}) => {
  return mount(component, {
    props: {
      field: createMockField(),
      modelValue: {},
      errors: [],
      disabled: false,
      readonly: false,
      size: 'default',
      ...props
    },
    global: {
      components: {
        BaseField
      }
    }
  })
}

describe('BooleanGroupField', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders boolean group field with BaseField wrapper', () => {
      wrapper = mountField(BooleanGroupField, { field: createMockField() })

      expect(wrapper.findComponent(BaseField).exists()).toBe(true)
      expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(4) // 4 options
    })

    it('renders all checkbox options correctly', () => {
      const field = createMockField({
        options: {
          create: 'Create Posts',
          edit: 'Edit Posts',
          delete: 'Delete Posts'
        }
      })

      wrapper = mountField(BooleanGroupField, { field })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      // Find labels that are siblings of checkboxes (not the BaseField label)
      const optionLabels = wrapper.findAll('input[type="checkbox"] + label')

      expect(checkboxes).toHaveLength(3)
      expect(optionLabels[0].text()).toContain('Create Posts')
      expect(optionLabels[1].text()).toContain('Edit Posts')
      expect(optionLabels[2].text()).toContain('Delete Posts')
    })

    it('renders with default unchecked state', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        modelValue: {}
      })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      checkboxes.forEach(checkbox => {
        expect(checkbox.element.checked).toBe(false)
      })
    })

    it('renders with some values checked', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        modelValue: {
          create: true,
          read: false,
          update: true,
          delete: false
        }
      })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      expect(checkboxes[0].element.checked).toBe(true)  // create
      expect(checkboxes[1].element.checked).toBe(false) // read
      expect(checkboxes[2].element.checked).toBe(true)  // update
      expect(checkboxes[3].element.checked).toBe(false) // delete
    })
  })

  describe('Nova API Compatibility - Options', () => {
    it('handles empty options correctly', () => {
      const field = createMockField({ options: {} })

      wrapper = mountField(BooleanGroupField, { field })

      expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(0)
    })

    it('handles custom options correctly', () => {
      const field = createMockField({
        options: {
          'custom_key': 'Custom Label',
          'another_key': 'Another Label'
        }
      })

      wrapper = mountField(BooleanGroupField, { field })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      const optionLabels = wrapper.findAll('input[type="checkbox"] + label')

      expect(checkboxes).toHaveLength(2)
      expect(optionLabels[0].text()).toContain('Custom Label')
      expect(optionLabels[1].text()).toContain('Another Label')
    })

    it('handles numeric and string keys correctly', () => {
      const field = createMockField({
        options: {
          '0': 'Zero',
          '1': 'One',
          'string_key': 'String Key'
        }
      })

      wrapper = mountField(BooleanGroupField, { field })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      expect(checkboxes).toHaveLength(3)
    })
  })

  describe('User Interactions', () => {
    it('toggles checkbox values when clicked', async () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        modelValue: { create: false, read: false, update: false, delete: false }
      })

      const firstCheckbox = wrapper.findAll('input[type="checkbox"]')[0]
      await firstCheckbox.setChecked(true)
      await firstCheckbox.trigger('change')

      expect(wrapper.emitted('update:modelValue')[0][0]).toEqual({
        create: true,
        read: false,
        update: false,
        delete: false
      })
      expect(wrapper.emitted('change')[0][0]).toEqual({
        create: true,
        read: false,
        update: false,
        delete: false
      })
    })

    it('handles multiple checkbox changes', async () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        modelValue: {}
      })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')

      // Check first checkbox
      await checkboxes[0].setChecked(true)
      await checkboxes[0].trigger('change')

      // Check third checkbox (need to reset the wrapper to clear previous emissions)
      const emissionCount = wrapper.emitted('update:modelValue')?.length || 0

      await checkboxes[2].setChecked(true)
      await checkboxes[2].trigger('change')

      // Should have at least two emissions (may have more due to setChecked)
      expect(wrapper.emitted('update:modelValue').length).toBeGreaterThanOrEqual(2)
      expect(wrapper.emitted('change').length).toBeGreaterThanOrEqual(2)
    })

    it('emits focus and blur events', async () => {
      wrapper = mountField(BooleanGroupField, { field: createMockField() })

      const firstCheckbox = wrapper.findAll('input[type="checkbox"]')[0]

      await firstCheckbox.trigger('focus')
      expect(wrapper.emitted('focus')).toBeTruthy()

      await firstCheckbox.trigger('blur')
      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('handles label association with checkboxes correctly', async () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        modelValue: {}
      })

      const firstCheckbox = wrapper.findAll('input[type="checkbox"]')[0]
      const firstOptionLabel = wrapper.findAll('input[type="checkbox"] + label')[0]

      // Test that label is properly associated with checkbox
      const checkboxId = firstCheckbox.attributes('id')
      const labelFor = firstOptionLabel.attributes('for')
      expect(checkboxId).toBe(labelFor)

      // Test checkbox functionality (labels work through HTML association)
      await firstCheckbox.setChecked(true)
      await firstCheckbox.trigger('change')
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    })
  })

  describe('Disabled State', () => {
    it('disables all checkboxes when disabled prop is true', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        disabled: true
      })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      checkboxes.forEach(checkbox => {
        expect(checkbox.element.disabled).toBe(true)
      })
    })

    it('does not emit events when disabled', async () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        disabled: true,
        modelValue: {}
      })

      const firstCheckbox = wrapper.findAll('input[type="checkbox"]')[0]
      await firstCheckbox.trigger('change')

      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
      expect(wrapper.emitted('change')).toBeFalsy()
    })

    it('applies disabled styling', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        disabled: true
      })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      const optionLabels = wrapper.findAll('input[type="checkbox"] + label')

      checkboxes.forEach(checkbox => {
        expect(checkbox.classes()).toContain('opacity-50')
        expect(checkbox.classes()).toContain('cursor-not-allowed')
      })

      optionLabels.forEach(label => {
        expect(label.classes()).toContain('cursor-not-allowed')
        expect(label.classes()).toContain('opacity-50')
      })
    })
  })

  describe('Readonly State', () => {
    it('shows readonly display instead of checkboxes', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        readonly: true,
        modelValue: { create: true, read: false, update: true, delete: false }
      })

      expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(0)
      expect(wrapper.findAll('.inline-flex')).toHaveLength(4) // All options shown as badges
    })

    it('displays correct readonly values with badges', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        readonly: true,
        modelValue: { create: true, read: false, update: true, delete: false }
      })

      const badges = wrapper.findAll('.inline-flex')

      // Check true values have green styling
      expect(badges[0].classes()).toContain('bg-green-100')
      expect(badges[0].classes()).toContain('text-green-800')

      // Check false values have gray styling
      expect(badges[1].classes()).toContain('bg-gray-100')
      expect(badges[1].classes()).toContain('text-gray-800')
    })

    it('shows no data message when no values and false values are hidden', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField({ hideFalseValues: true }),
        readonly: true,
        modelValue: {} // All false values, should be hidden
      })

      expect(wrapper.text()).toContain('No Data')
    })
  })

  describe('Nova API Compatibility - Hide Methods', () => {
    it('hides false values when hideFalseValues is true', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField({ hideFalseValues: true }),
        readonly: true,
        modelValue: { create: true, read: false, update: true, delete: false }
      })

      const badges = wrapper.findAll('.inline-flex')
      expect(badges).toHaveLength(2) // Only true values shown

      // Should only show create and update (true values)
      expect(wrapper.text()).toContain('Create')
      expect(wrapper.text()).toContain('Update')
      expect(wrapper.text()).not.toContain('Read')
      expect(wrapper.text()).not.toContain('Delete')
    })

    it('hides true values when hideTrueValues is true', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField({ hideTrueValues: true }),
        readonly: true,
        modelValue: { create: true, read: false, update: true, delete: false }
      })

      const badges = wrapper.findAll('.inline-flex')
      expect(badges).toHaveLength(2) // Only false values shown

      // Should only show read and delete (false values)
      expect(wrapper.text()).toContain('Read')
      expect(wrapper.text()).toContain('Delete')
      expect(wrapper.text()).not.toContain('Create')
      expect(wrapper.text()).not.toContain('Update')
    })

    it('shows custom no value text', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField({
          noValueText: 'No permissions selected',
          hideFalseValues: true
        }),
        readonly: true,
        modelValue: { create: false, read: false, update: false, delete: false }
      })

      expect(wrapper.text()).toContain('No permissions selected')
    })
  })

  describe('Required Field Indicator', () => {
    it('shows required indicator when field has required rule', () => {
      const field = createMockField({
        rules: ['required']
      })

      wrapper = mountField(BooleanGroupField, { field })

      expect(wrapper.text()).toContain('* Required')
    })

    it('does not show required indicator when field is not required', () => {
      wrapper = mountField(BooleanGroupField, { field: createMockField() })

      expect(wrapper.text()).not.toContain('* Required')
    })
  })

  describe('Dark Theme Support', () => {
    beforeEach(() => {
      mockAdminStore.isDarkTheme = true
    })

    afterEach(() => {
      mockAdminStore.isDarkTheme = false
    })

    it('applies dark theme classes to checkboxes', () => {
      wrapper = mountField(BooleanGroupField, { field: createMockField() })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      checkboxes.forEach(checkbox => {
        expect(checkbox.classes()).toContain('border-gray-600')
        expect(checkbox.classes()).toContain('bg-gray-700')
      })
    })

    it('applies dark theme classes to labels', () => {
      wrapper = mountField(BooleanGroupField, { field: createMockField() })

      const optionLabels = wrapper.findAll('input[type="checkbox"] + label')
      optionLabels.forEach(label => {
        expect(label.classes()).toContain('text-white')
      })
    })

    it('applies dark theme classes to readonly badges', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        readonly: true,
        modelValue: { create: true, read: false }
      })

      const badges = wrapper.findAll('.inline-flex')
      expect(badges[0].classes()).toContain('bg-green-900')
      expect(badges[0].classes()).toContain('text-green-200')
      expect(badges[1].classes()).toContain('bg-gray-700')
      expect(badges[1].classes()).toContain('text-gray-300')
    })
  })

  describe('Component Interface', () => {
    it('implements focus method', () => {
      wrapper = mountField(BooleanGroupField, { field: createMockField() })

      expect(typeof wrapper.vm.focus).toBe('function')
      expect(() => wrapper.vm.focus()).not.toThrow()
    })

    it('implements blur method', () => {
      wrapper = mountField(BooleanGroupField, { field: createMockField() })

      expect(typeof wrapper.vm.blur).toBe('function')
      expect(() => wrapper.vm.blur()).not.toThrow()
    })

    it('generates unique field ID', () => {
      const wrapper1 = mountField(BooleanGroupField, { field: createMockField() })
      const wrapper2 = mountField(BooleanGroupField, { field: createMockField() })

      expect(wrapper1.vm.fieldId).not.toBe(wrapper2.vm.fieldId)

      wrapper1.unmount()
      wrapper2.unmount()
    })
  })

  describe('Edge Cases', () => {
    it('handles null modelValue gracefully', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        modelValue: null
      })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      checkboxes.forEach(checkbox => {
        expect(checkbox.element.checked).toBe(false)
      })
    })

    it('handles non-object modelValue gracefully', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        modelValue: 'invalid'
      })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      checkboxes.forEach(checkbox => {
        expect(checkbox.element.checked).toBe(false)
      })
    })

    it('handles missing keys in modelValue', () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        modelValue: { create: true } // Missing other keys
      })

      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      expect(checkboxes[0].element.checked).toBe(true)  // create
      expect(checkboxes[1].element.checked).toBe(false) // read (missing)
      expect(checkboxes[2].element.checked).toBe(false) // update (missing)
      expect(checkboxes[3].element.checked).toBe(false) // delete (missing)
    })
  })

  describe('Reactivity', () => {
    it('updates when modelValue changes', async () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField(),
        modelValue: { create: false }
      })

      expect(wrapper.findAll('input[type="checkbox"]')[0].element.checked).toBe(false)

      await wrapper.setProps({ modelValue: { create: true } })

      expect(wrapper.findAll('input[type="checkbox"]')[0].element.checked).toBe(true)
    })

    it('updates when field options change', async () => {
      wrapper = mountField(BooleanGroupField, {
        field: createMockField({ options: { create: 'Create' } }),
        modelValue: {}
      })

      expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(1)

      const newField = createMockField({
        options: { create: 'Create', read: 'Read', update: 'Update' }
      })

      await wrapper.setProps({ field: newField })

      expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(3)
    })
  })
})
