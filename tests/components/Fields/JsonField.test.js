import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import JsonField from '@/components/Fields/JsonField.vue'
import { createMockField, mountField } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  CheckIcon: { template: '<div data-testid="check-icon"></div>' },
  ExclamationTriangleIcon: { template: '<div data-testid="exclamation-triangle-icon"></div>' },
  DocumentDuplicateIcon: { template: '<div data-testid="document-duplicate-icon"></div>' },
  ArrowsPointingOutIcon: { template: '<div data-testid="arrows-pointing-out-icon"></div>' },
  BeautifyIcon: { template: '<div data-testid="beautify-icon"></div>' },
  MinifyIcon: { template: '<div data-testid="minify-icon"></div>' }
}))

describe('JsonField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Configuration',
      attribute: 'config',
      type: 'json',
      height: 300,
      showValidation: true,
      showFormatting: true,
      placeholder: 'Enter JSON configuration...'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders JSON editor container', () => {
      wrapper = mountField(JsonField, { field: mockField })

      const editorContainer = wrapper.find('.json-editor-container')
      expect(editorContainer.exists()).toBe(true)
    })

    it('shows JSON textarea', () => {
      wrapper = mountField(JsonField, { field: mockField })

      const textarea = wrapper.find('textarea')
      expect(textarea.exists()).toBe(true)
    })

    it('applies custom height', () => {
      wrapper = mountField(JsonField, { field: mockField })

      const textarea = wrapper.find('textarea')
      expect(textarea.attributes('style')).toContain('height: 300px')
    })

    it('shows placeholder text', () => {
      wrapper = mountField(JsonField, { field: mockField })

      const textarea = wrapper.find('textarea')
      expect(textarea.attributes('placeholder')).toBe('Enter JSON configuration...')
    })

    it('applies disabled state', () => {
      wrapper = mountField(JsonField, {
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
      wrapper = mountField(JsonField, {
        field: mockField,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      const textarea = wrapper.find('textarea')
      expect(textarea.element.readOnly).toBe(true)
    })
  })

  describe('JSON Validation', () => {
    it('validates correct JSON syntax', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const validJson = '{"name": "John", "age": 30, "active": true}'
      const textarea = wrapper.find('textarea')
      
      await textarea.setValue(validJson)
      await textarea.trigger('input')

      expect(wrapper.vm.isValidJson).toBe(true)
      expect(wrapper.vm.validationError).toBe(null)
    })

    it('detects invalid JSON syntax', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const invalidJson = '{"name": "John", "age": 30, "active": true'
      const textarea = wrapper.find('textarea')
      
      await textarea.setValue(invalidJson)
      await textarea.trigger('input')

      expect(wrapper.vm.isValidJson).toBe(false)
      expect(wrapper.vm.validationError).toBeTruthy()
    })

    it('shows validation success indicator', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      wrapper.vm.isValidJson = true
      wrapper.vm.validationError = null
      await nextTick()

      const successIcon = wrapper.find('[data-testid="check-icon"]')
      expect(successIcon.exists()).toBe(true)
    })

    it('shows validation error indicator', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      wrapper.vm.isValidJson = false
      wrapper.vm.validationError = 'Unexpected token'
      await nextTick()

      const errorIcon = wrapper.find('[data-testid="exclamation-triangle-icon"]')
      expect(errorIcon.exists()).toBe(true)
    })

    it('displays validation error message', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      wrapper.vm.validationError = 'Unexpected token } in JSON at position 45'
      await nextTick()

      expect(wrapper.text()).toContain('Unexpected token } in JSON at position 45')
    })

    it('hides validation when showValidation is false', () => {
      const fieldWithoutValidation = createMockField({
        ...mockField,
        showValidation: false
      })

      wrapper = mountField(JsonField, { field: fieldWithoutValidation })

      wrapper.vm.isValidJson = false
      wrapper.vm.validationError = 'Error'

      expect(wrapper.find('[data-testid="exclamation-triangle-icon"]').exists()).toBe(false)
    })
  })

  describe('JSON Formatting', () => {
    it('shows formatting buttons when showFormatting is true', () => {
      wrapper = mountField(JsonField, { field: mockField })

      const beautifyButton = wrapper.find('[data-testid="beautify-icon"]')
      const minifyButton = wrapper.find('[data-testid="minify-icon"]')

      expect(beautifyButton.exists()).toBe(true)
      expect(minifyButton.exists()).toBe(true)
    })

    it('hides formatting buttons when showFormatting is false', () => {
      const fieldWithoutFormatting = createMockField({
        ...mockField,
        showFormatting: false
      })

      wrapper = mountField(JsonField, { field: fieldWithoutFormatting })

      expect(wrapper.find('[data-testid="beautify-icon"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="minify-icon"]').exists()).toBe(false)
    })

    it('beautifies JSON when beautify button clicked', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const compactJson = '{"name":"John","age":30,"active":true}'
      wrapper.vm.jsonContent = compactJson

      const beautifyButton = wrapper.find('[data-testid="beautify-icon"]')
      await beautifyButton.element.parentElement.click()

      const beautifiedJson = wrapper.vm.jsonContent
      expect(beautifiedJson).toContain('  "name": "John"')
      expect(beautifiedJson).toContain('  "age": 30')
      expect(beautifiedJson).toContain('  "active": true')
    })

    it('minifies JSON when minify button clicked', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const formattedJson = `{
  "name": "John",
  "age": 30,
  "active": true
}`
      wrapper.vm.jsonContent = formattedJson

      const minifyButton = wrapper.find('[data-testid="minify-icon"]')
      await minifyButton.element.parentElement.click()

      const minifiedJson = wrapper.vm.jsonContent
      expect(minifiedJson).toBe('{"name":"John","age":30,"active":true}')
    })

    it('handles formatting errors gracefully', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const invalidJson = '{"invalid": json}'
      wrapper.vm.jsonContent = invalidJson

      const beautifyButton = wrapper.find('[data-testid="beautify-icon"]')
      await beautifyButton.element.parentElement.click()

      // Should not crash and should show error
      expect(wrapper.vm.validationError).toBeTruthy()
    })
  })

  describe('Copy Functionality', () => {
    it('shows copy button', () => {
      wrapper = mountField(JsonField, { field: mockField })

      const copyButton = wrapper.find('[data-testid="document-duplicate-icon"]')
      expect(copyButton.exists()).toBe(true)
    })

    it('copies JSON to clipboard when copy button clicked', async () => {
      // Mock clipboard API
      Object.assign(navigator, {
        clipboard: {
          writeText: vi.fn().mockResolvedValue()
        }
      })

      wrapper = mountField(JsonField, {
        field: mockField,
        modelValue: '{"name": "John", "age": 30}'
      })

      const copyButton = wrapper.find('[data-testid="document-duplicate-icon"]')
      await copyButton.element.parentElement.click()

      expect(navigator.clipboard.writeText).toHaveBeenCalledWith('{"name": "John", "age": 30}')
    })

    it('shows copy success feedback', async () => {
      Object.assign(navigator, {
        clipboard: {
          writeText: vi.fn().mockResolvedValue()
        }
      })

      wrapper = mountField(JsonField, { field: mockField })

      wrapper.vm.copySuccess = true
      await nextTick()

      expect(wrapper.text()).toContain('Copied!')
    })

    it('handles copy errors gracefully', async () => {
      Object.assign(navigator, {
        clipboard: {
          writeText: vi.fn().mockRejectedValue(new Error('Copy failed'))
        }
      })

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})

      wrapper = mountField(JsonField, { field: mockField })

      await wrapper.vm.copyToClipboard()

      expect(consoleSpy).toHaveBeenCalled()
      consoleSpy.mockRestore()
    })
  })

  describe('Fullscreen Mode', () => {
    it('shows fullscreen button', () => {
      wrapper = mountField(JsonField, { field: mockField })

      const fullscreenButton = wrapper.find('[data-testid="arrows-pointing-out-icon"]')
      expect(fullscreenButton.exists()).toBe(true)
    })

    it('toggles fullscreen mode when button clicked', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const toggleSpy = vi.spyOn(wrapper.vm, 'toggleFullscreen')
      const fullscreenButton = wrapper.find('[data-testid="arrows-pointing-out-icon"]')
      
      await fullscreenButton.element.parentElement.click()
      expect(toggleSpy).toHaveBeenCalled()
    })

    it('applies fullscreen classes when in fullscreen mode', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      wrapper.vm.isFullscreen = true
      await nextTick()

      const container = wrapper.find('.json-editor-container')
      expect(container.classes()).toContain('fixed')
      expect(container.classes()).toContain('inset-0')
      expect(container.classes()).toContain('z-50')
    })

    it('exits fullscreen on escape key', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      wrapper.vm.isFullscreen = true
      
      const handleKeydownSpy = vi.spyOn(wrapper.vm, 'handleKeydown')
      await wrapper.trigger('keydown', { key: 'Escape' })

      expect(handleKeydownSpy).toHaveBeenCalled()
    })
  })

  describe('Content Management', () => {
    it('handles object input and converts to JSON string', () => {
      const objectValue = { name: 'John', age: 30, active: true }
      
      wrapper = mountField(JsonField, {
        field: mockField,
        modelValue: objectValue
      })

      expect(wrapper.vm.jsonContent).toBe(JSON.stringify(objectValue, null, 2))
    })

    it('handles array input and converts to JSON string', () => {
      const arrayValue = [{ id: 1, name: 'Item 1' }, { id: 2, name: 'Item 2' }]
      
      wrapper = mountField(JsonField, {
        field: mockField,
        modelValue: arrayValue
      })

      expect(wrapper.vm.jsonContent).toBe(JSON.stringify(arrayValue, null, 2))
    })

    it('handles string input as-is', () => {
      const stringValue = '{"name": "John", "age": 30}'
      
      wrapper = mountField(JsonField, {
        field: mockField,
        modelValue: stringValue
      })

      expect(wrapper.vm.jsonContent).toBe(stringValue)
    })

    it('emits parsed object when valid JSON is entered', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const validJson = '{"name": "John", "age": 30}'
      const textarea = wrapper.find('textarea')
      
      await textarea.setValue(validJson)
      await textarea.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toEqual({ name: 'John', age: 30 })
    })

    it('emits string when invalid JSON is entered', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const invalidJson = '{"name": "John", "age": 30'
      const textarea = wrapper.find('textarea')
      
      await textarea.setValue(invalidJson)
      await textarea.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(invalidJson)
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(JsonField, { field: mockField })

      const textarea = wrapper.find('textarea')
      expect(textarea.classes()).toContain('bg-gray-800')
      expect(textarea.classes()).toContain('text-gray-100')
      expect(textarea.classes()).toContain('border-gray-600')
    })

    it('applies dark theme to container', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(JsonField, { field: mockField })

      const container = wrapper.find('.json-editor-container')
      expect(container.classes()).toContain('bg-gray-800')
    })

    it('applies dark theme to validation messages', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(JsonField, { field: mockField })

      wrapper.vm.validationError = 'JSON Error'

      const errorMessage = wrapper.find('.text-red-400')
      expect(errorMessage.exists()).toBe(true)
    })
  })

  describe('Event Handling', () => {
    it('emits focus event', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const textarea = wrapper.find('textarea')
      await textarea.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const textarea = wrapper.find('textarea')
      await textarea.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('emits change event when content changes', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const textarea = wrapper.find('textarea')
      await textarea.setValue('{"new": "content"}')
      await textarea.trigger('input')

      expect(wrapper.emitted('change')).toBeTruthy()
    })

    it('debounces validation', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const validateSpy = vi.spyOn(wrapper.vm, 'validateJson')
      const textarea = wrapper.find('textarea')
      
      // Trigger multiple rapid changes
      await textarea.setValue('{"test": 1}')
      await textarea.trigger('input')
      await textarea.setValue('{"test": 2}')
      await textarea.trigger('input')
      await textarea.setValue('{"test": 3}')
      await textarea.trigger('input')

      // Wait for debounce
      await new Promise(resolve => setTimeout(resolve, 350))

      expect(validateSpy).toHaveBeenCalledTimes(1)
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(JsonField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the textarea', async () => {
      wrapper = mountField(JsonField, { field: mockField })

      const textarea = wrapper.find('textarea')
      const focusSpy = vi.spyOn(textarea.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles null modelValue', () => {
      wrapper = mountField(JsonField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.jsonContent).toBe('')
    })

    it('handles undefined modelValue', () => {
      wrapper = mountField(JsonField, {
        field: mockField,
        modelValue: undefined
      })

      expect(wrapper.vm.jsonContent).toBe('')
    })

    it('handles empty object', () => {
      wrapper = mountField(JsonField, {
        field: mockField,
        modelValue: {}
      })

      expect(wrapper.vm.jsonContent).toBe('{}')
    })

    it('handles empty array', () => {
      wrapper = mountField(JsonField, {
        field: mockField,
        modelValue: []
      })

      expect(wrapper.vm.jsonContent).toBe('[]')
    })

    it('handles nested objects', () => {
      const nestedObject = {
        user: {
          name: 'John',
          profile: {
            age: 30,
            preferences: ['coding', 'reading']
          }
        }
      }

      wrapper = mountField(JsonField, {
        field: mockField,
        modelValue: nestedObject
      })

      expect(wrapper.vm.jsonContent).toContain('"name": "John"')
      expect(wrapper.vm.jsonContent).toContain('"age": 30')
      expect(wrapper.vm.jsonContent).toContain('["coding", "reading"]')
    })

    it('handles very large JSON objects', () => {
      const largeObject = {}
      for (let i = 0; i < 1000; i++) {
        largeObject[`key${i}`] = `value${i}`
      }

      wrapper = mountField(JsonField, {
        field: mockField,
        modelValue: largeObject
      })

      expect(wrapper.vm.jsonContent).toContain('"key0": "value0"')
      expect(wrapper.vm.jsonContent).toContain('"key999": "value999"')
    })
  })
})
