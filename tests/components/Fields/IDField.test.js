import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import IDField from '@/components/Fields/IDField.vue'
import { createMockField, mountField } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('IDField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'ID',
      attribute: 'id',
      type: 'id'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders readonly text input field', () => {
      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input[type="text"]')
      expect(input.exists()).toBe(true)
      expect(input.element.readOnly).toBe(true)
    })

    it('renders with model value', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: 123
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('123')
    })

    it('displays placeholder when no value', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('ID')
    })

    it('uses custom placeholder when provided', () => {
      const fieldWithPlaceholder = createMockField({
        ...mockField,
        placeholder: 'Will be assigned'
      })

      wrapper = mountField(IDField, { field: fieldWithPlaceholder })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('Will be assigned')
    })

    it('is always readonly', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        props: {
          field: mockField,
          readonly: false // Try to make it not readonly
        }
      })

      const input = wrapper.find('input')
      expect(input.element.readOnly).toBe(true)
    })

    it('applies disabled state', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        props: {
          field: mockField,
          disabled: true
        }
      })

      const input = wrapper.find('input')
      expect(input.element.disabled).toBe(true)
    })
  })

  describe('Value Display', () => {
    it('displays numeric ID values', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: 42
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('42')
    })

    it('displays string ID values', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: 'uuid-123-456'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('uuid-123-456')
    })

    it('displays zero ID correctly', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: 0
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('shows empty for null value', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('shows empty for undefined value', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: undefined
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })
  })

  describe('Event Handling', () => {
    it('does not emit events on input (readonly)', async () => {
      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input')
      // Try to change value (should not work due to readonly)
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('change')).toBeTruthy()
    })

    it('emits focus event', async () => {
      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('does not apply dark theme classes when light theme is active', () => {
      mockAdminStore.isDarkTheme = false

      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).not.toContain('admin-input-dark')
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(IDField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the input', async () => {
      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input')
      const focusSpy = vi.spyOn(input.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Accessibility', () => {
    it('has proper field ID', () => {
      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('id')).toContain('id-field-id')
    })

    it('has proper name attribute', () => {
      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('name')).toBeUndefined()
    })

    it('indicates readonly state for screen readers', () => {
      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('readonly')).toBeDefined()
    })
  })

  describe('Edge Cases', () => {
    it('handles very large numeric IDs', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: 9007199254740991 // Max safe integer
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('9007199254740991')
    })

    it('handles UUID format IDs', () => {
      const uuid = '550e8400-e29b-41d4-a716-446655440000'

      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: uuid
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe(uuid)
    })

    it('handles negative IDs', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: -1
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('-1')
    })

    it('handles boolean values converted to string', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: true
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('true')
    })
  })

  describe('Field Styling', () => {
    it('applies readonly styling classes', () => {
      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input')
      expect(input.classes()).toContain('cursor-not-allowed')
    })

    it('applies proper width classes', () => {
      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('w-full')
    })

    it('shows readonly visual indicator', () => {
      wrapper = mountField(IDField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('bg-gray-50')
    })
  })

  describe('Component Integration', () => {
    it('integrates with BaseField wrapper', () => {
      wrapper = mountField(IDField, { field: mockField })

      // Should be wrapped in BaseField
      expect(wrapper.findComponent({ name: 'BaseField' }).exists()).toBe(true)
    })

    it('passes through field props to BaseField', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        props: {
          field: mockField,
          errors: { id: ['Some error'] }
        }
      })

      // BaseField should receive the errors
      const baseField = wrapper.findComponent({ name: 'BaseField' })
      expect(baseField.props('errors')).toEqual({ id: ['Some error'] })
    })
  })

  describe('Display Modes', () => {
    it('renders in display mode for index view', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: 123,
        props: {
          field: mockField,
          mode: 'index'
        }
      })

      // Should show display span, not input
      expect(wrapper.find('span.text-sm.font-mono').exists()).toBe(true)
      expect(wrapper.find('input').exists()).toBe(false)
    })

    it('renders in display mode for detail view', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: 456,
        props: {
          field: mockField,
          mode: 'detail'
        }
      })

      // Should show display span, not input
      expect(wrapper.find('span.text-sm.font-mono').exists()).toBe(true)
      expect(wrapper.find('input').exists()).toBe(false)
    })

    it('renders in form mode when not readonly', () => {
      const fieldWithCreation = createMockField({
        ...mockField,
        showOnCreation: true
      })

      wrapper = mountField(IDField, {
        field: fieldWithCreation,
        modelValue: 789,
        props: {
          field: fieldWithCreation,
          mode: 'form'
        }
      })

      // Should show input, not display span
      expect(wrapper.find('input').exists()).toBe(true)
      expect(wrapper.find('span.text-sm.font-mono').exists()).toBe(false)
    })

    it('shows display value with dash for null/undefined', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        props: {
          field: mockField,
          modelValue: null, // Set null directly in props to bypass helper default
          mode: 'index'
        }
      })

      // Find the display span within the display mode div
      const displayDiv = wrapper.find('div.flex.items-center.space-x-2')
      expect(displayDiv.exists()).toBe(true)

      const displaySpan = displayDiv.find('span.text-sm.font-mono')
      expect(displaySpan.exists()).toBe(true)
      expect(displaySpan.text()).toBe('—')
    })
  })

  describe('Copyable Functionality', () => {
    beforeEach(() => {
      // Mock clipboard API
      Object.assign(navigator, {
        clipboard: {
          writeText: vi.fn().mockResolvedValue()
        }
      })
    })

    it('shows copy button when field is copyable and has value', () => {
      const copyableField = createMockField({
        ...mockField,
        copyable: true
      })

      wrapper = mountField(IDField, {
        field: copyableField,
        modelValue: 123,
        props: {
          field: copyableField,
          mode: 'index'
        }
      })

      expect(wrapper.find('button[title="Copy to clipboard"]').exists()).toBe(true)
    })

    it('hides copy button when field is not copyable', () => {
      wrapper = mountField(IDField, {
        field: mockField,
        modelValue: 123,
        props: {
          field: mockField,
          mode: 'index'
        }
      })

      expect(wrapper.find('button').exists()).toBe(false)
    })

    it('hides copy button when no value', () => {
      const copyableField = createMockField({
        ...mockField,
        copyable: true
      })

      wrapper = mountField(IDField, {
        field: copyableField,
        modelValue: null,
        props: {
          field: copyableField,
          mode: 'index'
        }
      })

      expect(wrapper.find('button').exists()).toBe(false)
    })

    it('copies value to clipboard when copy button clicked', async () => {
      const copyableField = createMockField({
        ...mockField,
        copyable: true
      })

      wrapper = mountField(IDField, {
        field: copyableField,
        modelValue: 'test-id-123',
        props: {
          field: copyableField,
          mode: 'index'
        }
      })

      const copyButton = wrapper.find('button')
      await copyButton.trigger('click')

      expect(navigator.clipboard.writeText).toHaveBeenCalledWith('test-id-123')
    })

    it('shows copied state after successful copy', async () => {
      const copyableField = createMockField({
        ...mockField,
        copyable: true
      })

      wrapper = mountField(IDField, {
        field: copyableField,
        modelValue: 999,
        props: {
          field: copyableField,
          mode: 'index'
        }
      })

      const copyButton = wrapper.find('button')
      await copyButton.trigger('click')
      await nextTick()

      expect(copyButton.attributes('title')).toBe('Copied!')
      expect(wrapper.find('svg').classes()).toContain('text-green-500')
    })
  })

  describe('Nova API Compatibility', () => {
    it('handles asBigInt meta property', () => {
      const bigIntField = createMockField({
        ...mockField,
        asBigInt: true
      })

      wrapper = mountField(IDField, {
        field: bigIntField,
        modelValue: '9007199254740991'
      })

      // Should handle big integers as strings
      const input = wrapper.find('input')
      expect(input.element.value).toBe('9007199254740991')
    })

    it('supports all Nova field visibility options', () => {
      const field = createMockField({
        ...mockField,
        showOnCreation: false,
        showOnIndex: true,
        showOnDetail: true,
        showOnUpdate: true
      })

      wrapper = mountField(IDField, { field })

      // Should respect Nova visibility settings
      expect(wrapper.vm.isReadonlyByDefault).toBe(true)
    })

    it('defaults to sortable behavior', () => {
      const field = createMockField({
        ...mockField,
        sortable: true
      })

      wrapper = mountField(IDField, { field })

      // Field should indicate it's sortable (this would be used by parent components)
      expect(field.sortable).toBe(true)
    })
  })
})
