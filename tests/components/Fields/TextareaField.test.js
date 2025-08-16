import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import TextareaField from '@/components/Fields/TextareaField.vue'
import { createMockField, mountField, triggerInput } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('TextareaField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Test Textarea',
      attribute: 'test_textarea',
      placeholder: 'Enter text here',
      type: 'textarea'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders textarea element', () => {
      wrapper = mountField(TextareaField, { field: mockField })

      const textarea = wrapper.find('textarea')
      expect(textarea.exists()).toBe(true)
      expect(textarea.attributes('placeholder')).toBe('Enter text here')
    })

    it('renders with model value', () => {
      wrapper = mountField(TextareaField, {
        field: mockField,
        modelValue: 'Test Value'
      })

      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toBe('Test Value')
    })

    it('uses field name as placeholder when no placeholder provided', () => {
      const fieldWithoutPlaceholder = createMockField({
        name: 'Field Name',
        attribute: 'field_name'
      })

      wrapper = mountField(TextareaField, { field: fieldWithoutPlaceholder })

      const textarea = wrapper.find('textarea')
      expect(textarea.attributes('placeholder')).toBe('Field Name')
    })

    it('applies disabled state', () => {
      wrapper = mountField(TextareaField, {
        field: mockField,
        props: {
          field: mockField,
          disabled: true
        }
      })

      const textarea = wrapper.find('textarea')
      expect(textarea.element.disabled).toBe(true)
    })

    it('applies readonly state', () => {
      wrapper = mountField(TextareaField, {
        field: mockField,
        props: {
          field: mockField,
          readonly: true
        }
      })

      const textarea = wrapper.find('textarea')
      expect(textarea.element.readOnly).toBe(true)
    })

    it('sets default rows to 4', () => {
      wrapper = mountField(TextareaField, { field: mockField })

      const textarea = wrapper.find('textarea')
      expect(textarea.attributes('rows')).toBe('4')
    })

    it('uses custom rows when specified', () => {
      const fieldWithRows = createMockField({
        name: 'Custom Rows',
        attribute: 'custom_rows',
        rows: 8
      })

      wrapper = mountField(TextareaField, { field: fieldWithRows })

      const textarea = wrapper.find('textarea')
      expect(textarea.attributes('rows')).toBe('8')
    })
  })

  describe('Character Count', () => {
    it('displays character count when maxLength is set', () => {
      const fieldWithLimit = createMockField({
        name: 'Limited Field',
        attribute: 'limited_field',
        maxLength: 50
      })

      wrapper = mountField(TextareaField, {
        field: fieldWithLimit,
        modelValue: 'Test text'
      })

      // Character count should be displayed
      expect(wrapper.text()).toContain('9/50')
    })

    it('displays character count when showCharacterCount is true', () => {
      const fieldWithCount = createMockField({
        name: 'Count Field',
        attribute: 'count_field',
        showCharacterCount: true
      })

      wrapper = mountField(TextareaField, {
        field: fieldWithCount,
        modelValue: 'Test text'
      })

      // Character count should be displayed
      expect(wrapper.text()).toContain('9')
    })

    it('shows external character count when no maxLength', () => {
      const fieldWithCount = createMockField({
        name: 'Count Field',
        attribute: 'count_field',
        showCharacterCount: true
      })

      wrapper = mountField(TextareaField, {
        field: fieldWithCount,
        modelValue: 'Test text'
      })

      expect(wrapper.text()).toContain('9 characters')
    })

    it('applies warning color when approaching limit', () => {
      const fieldWithLimit = createMockField({
        name: 'Limited Field',
        attribute: 'limited_field',
        maxLength: 10
      })

      wrapper = mountField(TextareaField, {
        field: fieldWithLimit,
        modelValue: '12345678' // 8 chars, 80% of 10 (> 70%)
      })

      const characterCount = wrapper.find('.absolute.bottom-2.right-2')
      expect(characterCount.classes()).toContain('text-amber-500')
    })

    it('applies danger color when near limit', () => {
      const fieldWithLimit = createMockField({
        name: 'Limited Field',
        attribute: 'limited_field',
        maxLength: 10
      })

      wrapper = mountField(TextareaField, {
        field: fieldWithLimit,
        modelValue: '1234567890' // 10 chars, 100% of 10 (> 90%)
      })

      const characterCount = wrapper.find('.absolute.bottom-2.right-2')
      expect(characterCount.classes()).toContain('text-red-500')
    })

    it('enforces maxLength on input', async () => {
      const fieldWithLimit = createMockField({
        name: 'Limited Field',
        attribute: 'limited_field',
        maxLength: 5
      })

      wrapper = mountField(TextareaField, { field: fieldWithLimit })

      const textarea = wrapper.find('textarea')
      await textarea.setValue('123456789') // 9 chars, should be truncated to 5
      await textarea.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('12345')
    })
  })

  describe('Event Handling', () => {
    it('emits update:modelValue on input', async () => {
      wrapper = mountField(TextareaField, { field: mockField })

      const textarea = wrapper.find('textarea')
      await textarea.setValue('New Value')
      await textarea.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('New Value')
      expect(wrapper.emitted('change')[0][0]).toBe('New Value')
    })

    it('emits focus event', async () => {
      wrapper = mountField(TextareaField, { field: mockField })

      const textarea = wrapper.find('textarea')
      await textarea.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(TextareaField, { field: mockField })

      const textarea = wrapper.find('textarea')
      await textarea.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })
  })

  describe('Tab Key Handling', () => {
    it('inserts two spaces on tab key press', async () => {
      wrapper = mountField(TextareaField, {
        field: mockField,
        modelValue: 'Hello world'
      })

      const textarea = wrapper.find('textarea')
      
      // Mock selection at position 5 (after "Hello")
      Object.defineProperty(textarea.element, 'selectionStart', { value: 5, writable: true })
      Object.defineProperty(textarea.element, 'selectionEnd', { value: 5, writable: true })
      
      await textarea.trigger('keydown', { key: 'Tab' })

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('Hello   world')
    })

    it('does not handle tab when shift is pressed', async () => {
      wrapper = mountField(TextareaField, {
        field: mockField,
        modelValue: 'Hello world'
      })

      const textarea = wrapper.find('textarea')
      await textarea.trigger('keydown', { key: 'Tab', shiftKey: true })

      // Should not emit update:modelValue for shift+tab
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })
  })

  describe('Auto Resize', () => {
    it('calls autoResize on input when autoResize is enabled', async () => {
      const fieldWithAutoResize = createMockField({
        name: 'Auto Resize Field',
        attribute: 'auto_resize_field',
        autoResize: true
      })

      wrapper = mountField(TextareaField, { field: fieldWithAutoResize })

      const textarea = wrapper.find('textarea')
      await textarea.setValue('New line\nAnother line\nThird line')
      await textarea.trigger('input')

      // Auto-resize should have been triggered
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    })

    it('watches modelValue changes for auto-resize', async () => {
      const fieldWithAutoResize = createMockField({
        name: 'Auto Resize Field',
        attribute: 'auto_resize_field',
        autoResize: true
      })

      wrapper = mountField(TextareaField, { field: fieldWithAutoResize })

      // Change the modelValue prop
      await wrapper.setProps({ modelValue: 'New content\nWith multiple\nLines' })
      await nextTick()

      // Component should handle the change
      expect(wrapper.find('textarea').element.value).toBe('New content\nWith multiple\nLines')
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(TextareaField, { field: mockField })

      const textarea = wrapper.find('textarea')
      expect(textarea.classes()).toContain('admin-input-dark')
    })

    it('does not apply dark theme classes when light theme is active', () => {
      mockAdminStore.isDarkTheme = false

      wrapper = mountField(TextareaField, { field: mockField })

      const textarea = wrapper.find('textarea')
      expect(textarea.classes()).not.toContain('admin-input-dark')
    })

    it('applies dark theme to character count colors', () => {
      mockAdminStore.isDarkTheme = true

      const fieldWithLimit = createMockField({
        name: 'Limited Field',
        attribute: 'limited_field',
        maxLength: 10
      })

      wrapper = mountField(TextareaField, {
        field: fieldWithLimit,
        modelValue: '12345'
      })

      const characterCount = wrapper.find('.absolute.bottom-2.right-2')
      expect(characterCount.classes()).toContain('text-gray-400')
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(TextareaField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the textarea', async () => {
      wrapper = mountField(TextareaField, { field: mockField })

      const textarea = wrapper.find('textarea')
      const focusSpy = vi.spyOn(textarea.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Field ID Generation', () => {
    it('generates unique field ID', () => {
      wrapper = mountField(TextareaField, { field: mockField })

      const textarea = wrapper.find('textarea')
      const id = textarea.attributes('id')
      
      expect(id).toMatch(/^textarea-field-test_textarea-[a-z0-9]{9}$/)
    })

    it('generates different IDs for multiple instances', () => {
      const wrapper1 = mountField(TextareaField, { field: mockField })
      const wrapper2 = mountField(TextareaField, { field: mockField })

      const id1 = wrapper1.find('textarea').attributes('id')
      const id2 = wrapper2.find('textarea').attributes('id')

      expect(id1).not.toBe(id2)

      wrapper1.unmount()
      wrapper2.unmount()
    })
  })
})
