import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BooleanField from '@/components/Fields/BooleanField.vue'
import BaseField from '@/components/Fields/BaseField.vue'

/**
 * Boolean Field Vue Component Tests
 *
 * Tests for BooleanField Vue component with 100% Nova API compatibility.
 * Tests all Nova Boolean field features including trueValue and falseValue methods.
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
  name: 'Active',
  attribute: 'active',
  component: 'BooleanField',
  trueValue: true,
  falseValue: false,
  rules: [],
  ...overrides
})

// Helper function to mount field component
const mountField = (component, props = {}) => {
  return mount(component, {
    props: {
      field: createMockField(),
      modelValue: null,
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

describe('BooleanField', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders boolean field with BaseField wrapper', () => {
      wrapper = mountField(BooleanField, { field: createMockField() })

      expect(wrapper.findComponent(BaseField).exists()).toBe(true)
      expect(wrapper.find('input[type="checkbox"]').exists()).toBe(true)
    })

    it('renders with default false value', () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        modelValue: false
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(false)
    })

    it('renders with true value', () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        modelValue: true
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(true)
    })

    it('renders field label correctly', () => {
      wrapper = mountField(BooleanField, {
        field: createMockField({ name: 'Is Active' })
      })

      const label = wrapper.find('label')
      expect(label.text()).toContain('Is Active')
    })
  })

  describe('Nova API Compatibility - Custom Values', () => {
    it('handles custom true/false values correctly', () => {
      const field = createMockField({
        trueValue: 'On',
        falseValue: 'Off'
      })

      wrapper = mountField(BooleanField, {
        field,
        modelValue: 'On'
      })

      expect(wrapper.vm.trueValue).toBe('On')
      expect(wrapper.vm.falseValue).toBe('Off')
      expect(wrapper.vm.isChecked).toBe(true)
    })

    it('handles numeric custom values', () => {
      const field = createMockField({
        trueValue: 1,
        falseValue: 0
      })

      wrapper = mountField(BooleanField, {
        field,
        modelValue: 1
      })

      expect(wrapper.vm.trueValue).toBe(1)
      expect(wrapper.vm.falseValue).toBe(0)
      expect(wrapper.vm.isChecked).toBe(true)
    })

    it('handles string custom values', () => {
      const field = createMockField({
        trueValue: 'yes',
        falseValue: 'no'
      })

      wrapper = mountField(BooleanField, {
        field,
        modelValue: 'yes'
      })

      expect(wrapper.vm.trueValue).toBe('yes')
      expect(wrapper.vm.falseValue).toBe('no')
      expect(wrapper.vm.isChecked).toBe(true)
    })

    it('defaults to true/false when custom values not provided', () => {
      wrapper = mountField(BooleanField, { field: createMockField() })

      expect(wrapper.vm.trueValue).toBe(true)
      expect(wrapper.vm.falseValue).toBe(false)
    })
  })

  describe('User Interactions', () => {
    it('toggles value when checkbox is clicked', async () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        modelValue: false
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.setChecked(true)
      await checkbox.trigger('change')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(true)
      expect(wrapper.emitted('change')[0][0]).toBe(true)
    })

    it('toggles with custom values', async () => {
      const field = createMockField({
        trueValue: 'On',
        falseValue: 'Off'
      })

      wrapper = mountField(BooleanField, {
        field,
        modelValue: 'Off'
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.setChecked(true)
      await checkbox.trigger('change')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('On')
      expect(wrapper.emitted('change')[0][0]).toBe('On')
    })

    it('emits focus event', async () => {
      wrapper = mountField(BooleanField, { field: createMockField() })

      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(BooleanField, { field: createMockField() })

      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })
  })

  describe('Disabled State', () => {
    it('disables checkbox when disabled prop is true', () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        disabled: true
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.disabled).toBe(true)
    })

    it('does not emit events when disabled', async () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        disabled: true,
        modelValue: false
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.trigger('change')

      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
      expect(wrapper.emitted('change')).toBeFalsy()
    })

    it('applies disabled styling', () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        disabled: true
      })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.classes()).toContain('opacity-50')
      expect(checkbox.classes()).toContain('cursor-not-allowed')
    })
  })

  describe('Readonly State', () => {
    it('shows readonly display instead of checkbox', () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        readonly: true,
        modelValue: true
      })

      expect(wrapper.find('input[type="checkbox"]').exists()).toBe(false)
      expect(wrapper.find('.inline-flex').exists()).toBe(true)
    })

    it('displays correct readonly value for true', () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        readonly: true,
        modelValue: true
      })

      const display = wrapper.find('.inline-flex')
      expect(display.text()).toContain('Yes')
      expect(display.classes()).toContain('bg-green-100')
    })

    it('displays correct readonly value for false', () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        readonly: true,
        modelValue: false
      })

      const display = wrapper.find('.inline-flex')
      expect(display.text()).toContain('No')
      expect(display.classes()).toContain('bg-gray-100')
    })
  })

  describe('Required Field Indicator', () => {
    it('shows required indicator when field has required rule', () => {
      const field = createMockField({
        rules: ['required']
      })

      wrapper = mountField(BooleanField, { field })

      const requiredIndicator = wrapper.find('.text-red-500')
      expect(requiredIndicator.exists()).toBe(true)
      expect(requiredIndicator.text()).toBe('*')
    })

    it('does not show required indicator when field is not required', () => {
      wrapper = mountField(BooleanField, { field: createMockField() })

      const requiredIndicator = wrapper.find('.text-red-500')
      expect(requiredIndicator.exists()).toBe(false)
    })
  })

  describe('Dark Theme Support', () => {
    beforeEach(() => {
      mockAdminStore.isDarkTheme = true
    })

    afterEach(() => {
      mockAdminStore.isDarkTheme = false
    })

    it('applies dark theme classes to checkbox', () => {
      wrapper = mountField(BooleanField, { field: createMockField() })

      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.classes()).toContain('border-gray-600')
      expect(checkbox.classes()).toContain('bg-gray-700')
    })

    it('applies dark theme classes to label', () => {
      wrapper = mountField(BooleanField, { field: createMockField() })

      const label = wrapper.find('label')
      // The label should have dark theme classes applied
      expect(label.classes()).toContain('admin-label-dark')
    })

    it('applies dark theme classes to readonly display', () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        readonly: true,
        modelValue: true
      })

      const display = wrapper.find('.inline-flex')
      expect(display.classes()).toContain('bg-green-900')
      expect(display.classes()).toContain('text-green-200')
    })
  })

  describe('Component Interface', () => {
    it('implements focus method', () => {
      wrapper = mountField(BooleanField, { field: createMockField() })

      expect(typeof wrapper.vm.focus).toBe('function')
      expect(() => wrapper.vm.focus()).not.toThrow()
    })

    it('implements blur method', () => {
      wrapper = mountField(BooleanField, { field: createMockField() })

      expect(typeof wrapper.vm.blur).toBe('function')
      expect(() => wrapper.vm.blur()).not.toThrow()
    })

    it('generates unique field ID', () => {
      const wrapper1 = mountField(BooleanField, { field: createMockField() })
      const wrapper2 = mountField(BooleanField, { field: createMockField() })

      expect(wrapper1.vm.fieldId).not.toBe(wrapper2.vm.fieldId)

      wrapper1.unmount()
      wrapper2.unmount()
    })
  })

  describe('Edge Cases', () => {
    it('handles null value as false', () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        modelValue: null
      })

      expect(wrapper.vm.isChecked).toBe(false)
    })

    it('handles undefined value as false', () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        modelValue: undefined
      })

      expect(wrapper.vm.isChecked).toBe(false)
    })

    it('handles mixed type comparisons correctly', () => {
      const field = createMockField({
        trueValue: '1',
        falseValue: 0
      })

      wrapper = mountField(BooleanField, {
        field,
        modelValue: '1'
      })

      expect(wrapper.vm.isChecked).toBe(true)
    })
  })

  describe('Reactivity', () => {
    it('updates when modelValue changes', async () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        modelValue: false
      })

      expect(wrapper.vm.isChecked).toBe(false)

      await wrapper.setProps({ modelValue: true })

      expect(wrapper.vm.isChecked).toBe(true)
    })

    it('updates when field configuration changes', async () => {
      wrapper = mountField(BooleanField, {
        field: createMockField(),
        modelValue: 'yes'
      })

      expect(wrapper.vm.isChecked).toBe(false) // 'yes' != true

      const newField = createMockField({
        trueValue: 'yes',
        falseValue: 'no'
      })

      await wrapper.setProps({ field: newField })

      expect(wrapper.vm.isChecked).toBe(true) // 'yes' == 'yes'
    })
  })
})
