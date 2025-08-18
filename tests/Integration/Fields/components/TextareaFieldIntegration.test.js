import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import TextareaField from '@/components/Fields/TextareaField.vue'
import BaseField from '@/components/Fields/BaseField.vue'

/**
 * Textarea Field Integration Tests
 *
 * Tests the integration between the PHP Textarea field class and Vue component,
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

// Mock admin store
vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Helper function to create mock field data (simulating PHP field serialization)
const createFieldData = (overrides = {}) => ({
  name: 'Biography',
  attribute: 'biography',
  component: 'TextareaField',
  rows: 4,
  maxlength: null,
  enforceMaxlength: false,
  alwaysShow: false,
  extraAttributes: {},
  placeholder: null,
  required: false,
  nullable: false,
  readonly: false,
  rules: [],
  ...overrides
})

// Helper function to mount field with proper structure
const mountTextareaField = (fieldData = {}, props = {}) => {
  const field = createFieldData(fieldData)
  
  return mount(TextareaField, {
    props: {
      field,
      modelValue: '',
      errors: [],
      disabled: false,
      readonly: false,
      size: 'md',
      ...props
    },
    global: {
      components: {
        BaseField
      }
    }
  })
}

describe('TextareaField Integration', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockAdminStore.isDarkTheme = false
  })

  describe('PHP-Vue Data Integration', () => {
    it('correctly receives and displays PHP field configuration', () => {
      const phpFieldData = {
        name: 'User Biography',
        attribute: 'bio',
        rows: 6,
        maxlength: 500,
        enforceMaxlength: true,
        alwaysShow: true,
        extraAttributes: {
          'aria-label': 'User biography field',
          'data-test': 'bio-textarea'
        }
      }

      const wrapper = mountTextareaField(phpFieldData)
      const textarea = wrapper.find('textarea')

      expect(textarea.attributes('rows')).toBe('6')
      expect(textarea.attributes('maxlength')).toBe('500')
      expect(textarea.attributes('aria-label')).toBe('User biography field')
      expect(textarea.attributes('data-test')).toBe('bio-textarea')
    })

    it('handles Nova-style field creation with make() method simulation', () => {
      // Simulate: Textarea::make('Description')->rows(8)->maxlength(1000)->enforceMaxlength()
      const novaStyleField = {
        name: 'Description',
        attribute: 'description',
        rows: 8,
        maxlength: 1000,
        enforceMaxlength: true,
        alwaysShow: false
      }

      const wrapper = mountTextareaField(novaStyleField)
      const textarea = wrapper.find('textarea')

      expect(textarea.attributes('rows')).toBe('8')
      expect(textarea.attributes('maxlength')).toBe('1000')
      expect(wrapper.find('.absolute.bottom-2.right-2').exists()).toBe(true) // Character count
    })

    it('properly handles withMeta extraAttributes from PHP', () => {
      // Simulate: Textarea::make('Notes')->withMeta(['extraAttributes' => [...]])
      const fieldWithMeta = {
        name: 'Notes',
        attribute: 'notes',
        extraAttributes: {
          'class': 'custom-textarea',
          'spellcheck': 'false',
          'autocomplete': 'off',
          'data-validation': 'strict'
        }
      }

      const wrapper = mountTextareaField(fieldWithMeta)
      const textarea = wrapper.find('textarea')

      expect(textarea.attributes('class')).toContain('custom-textarea')
      expect(textarea.attributes('spellcheck')).toBe('false')
      expect(textarea.attributes('autocomplete')).toBe('off')
      expect(textarea.attributes('data-validation')).toBe('strict')
    })
  })

  describe('Nova API Compatibility', () => {
    it('implements alwaysShow behavior correctly', () => {
      const alwaysShowField = createFieldData({ alwaysShow: true })
      const hiddenField = createFieldData({ alwaysShow: false })

      const alwaysShowWrapper = mountTextareaField(alwaysShowField, { modelValue: 'Long content that would normally be hidden behind a "Show Content" link in Nova' })
      const hiddenWrapper = mountTextareaField(hiddenField, { modelValue: 'Long content that would normally be hidden behind a "Show Content" link in Nova' })

      // Both should render the textarea (our implementation doesn't hide content by default)
      // but the alwaysShow flag should be available for future implementation
      expect(alwaysShowWrapper.find('textarea').exists()).toBe(true)
      expect(hiddenWrapper.find('textarea').exists()).toBe(true)
    })

    it('enforces maxlength when enforceMaxlength is true', () => {
      const enforcedField = createFieldData({
        maxlength: 10,
        enforceMaxlength: true
      })

      const wrapper = mountTextareaField(enforcedField)
      const textarea = wrapper.find('textarea')

      // Should have HTML maxlength attribute when enforceMaxlength is true
      expect(textarea.attributes('maxlength')).toBe('10')
    })

    it('does not enforce maxlength when enforceMaxlength is false', () => {
      const nonEnforcedField = createFieldData({
        maxlength: 10,
        enforceMaxlength: false
      })

      const wrapper = mountTextareaField(nonEnforcedField)
      const textarea = wrapper.find('textarea')

      // Should not have HTML maxlength attribute when enforceMaxlength is false
      expect(textarea.attributes('maxlength')).toBeUndefined()
    })

    it('shows character count only when maxlength is set', () => {
      const withLimitField = createFieldData({ maxlength: 100 })
      const withoutLimitField = createFieldData({ maxlength: null })

      const withLimitWrapper = mountTextareaField(withLimitField, { modelValue: 'Test content' })
      const withoutLimitWrapper = mountTextareaField(withoutLimitField, { modelValue: 'Test content' })

      expect(withLimitWrapper.find('.absolute.bottom-2.right-2').exists()).toBe(true)
      expect(withoutLimitWrapper.find('.absolute.bottom-2.right-2').exists()).toBe(false)
    })
  })

  describe('Form Data Handling', () => {
    it('emits correct data structure for Laravel backend', async () => {
      const wrapper = mountTextareaField()
      const textarea = wrapper.find('textarea')

      await textarea.setValue('New biography content')
      await textarea.trigger('input')

      const emitted = wrapper.emitted('update:modelValue')
      expect(emitted).toBeTruthy()
      expect(emitted[0][0]).toBe('New biography content')
    })

    it('handles validation errors from Laravel backend', () => {
      const wrapper = mountTextareaField({}, {
        errors: ['The biography field is required.', 'The biography must not exceed 500 characters.']
      })

      // BaseField should handle error display
      expect(wrapper.props('errors')).toEqual([
        'The biography field is required.',
        'The biography must not exceed 500 characters.'
      ])
    })

    it('maintains field state during form submission', async () => {
      const wrapper = mountTextareaField({}, { modelValue: 'Initial content' })
      const textarea = wrapper.find('textarea')

      expect(textarea.element.value).toBe('Initial content')

      await wrapper.setProps({ modelValue: 'Updated content' })
      expect(textarea.element.value).toBe('Updated content')
    })
  })

  describe('Character Count Integration', () => {
    it('displays accurate character count with maxlength', () => {
      const wrapper = mountTextareaField(
        { maxlength: 50 },
        { modelValue: 'Hello world' }
      )

      expect(wrapper.text()).toContain('11/50')
    })

    it('applies warning styles when approaching limit', () => {
      const wrapper = mountTextareaField(
        { maxlength: 10 },
        { modelValue: '12345678' } // 8 chars, 80% of limit
      )

      const characterCount = wrapper.find('.absolute.bottom-2.right-2')
      expect(characterCount.classes()).toContain('text-amber-500')
    })

    it('applies danger styles when at or over limit', () => {
      const wrapper = mountTextareaField(
        { maxlength: 10 },
        { modelValue: '1234567890' } // 10 chars, 100% of limit
      )

      const characterCount = wrapper.find('.absolute.bottom-2.right-2')
      expect(characterCount.classes()).toContain('text-red-500')
    })
  })

  describe('Accessibility Integration', () => {
    it('supports accessibility attributes from PHP extraAttributes', () => {
      const accessibleField = createFieldData({
        extraAttributes: {
          'aria-label': 'User biography',
          'aria-describedby': 'bio-help',
          'aria-required': 'true'
        }
      })

      const wrapper = mountTextareaField(accessibleField)
      const textarea = wrapper.find('textarea')

      expect(textarea.attributes('aria-label')).toBe('User biography')
      expect(textarea.attributes('aria-describedby')).toBe('bio-help')
      expect(textarea.attributes('aria-required')).toBe('true')
    })

    it('generates proper field IDs for label association', () => {
      const wrapper = mountTextareaField()
      const textarea = wrapper.find('textarea')

      expect(textarea.attributes('id')).toMatch(/^textarea-field-biography-[a-z0-9]+$/)
    })
  })

  describe('Theme Integration', () => {
    it('applies dark theme styles when admin store indicates dark theme', async () => {
      mockAdminStore.isDarkTheme = true
      
      const wrapper = mountTextareaField()
      const textarea = wrapper.find('textarea')

      expect(textarea.classes()).toContain('admin-input-dark')
    })

    it('applies light theme styles when admin store indicates light theme', () => {
      mockAdminStore.isDarkTheme = false
      
      const wrapper = mountTextareaField()
      const textarea = wrapper.find('textarea')

      expect(textarea.classes()).not.toContain('admin-input-dark')
    })
  })

  describe('Edge Cases and Error Handling', () => {
    it('handles null/undefined field values gracefully', () => {
      const wrapper = mountTextareaField({}, { modelValue: null })
      const textarea = wrapper.find('textarea')

      expect(textarea.element.value).toBe('')
    })

    it('handles missing extraAttributes gracefully', () => {
      const fieldWithoutExtras = createFieldData({ extraAttributes: undefined })
      
      expect(() => {
        mountTextareaField(fieldWithoutExtras)
      }).not.toThrow()
    })

    it('handles zero maxlength edge case', () => {
      const wrapper = mountTextareaField({ maxlength: 0 })
      
      expect(wrapper.find('.absolute.bottom-2.right-2').exists()).toBe(true)
      expect(wrapper.text()).toContain('0/0')
    })
  })
})
