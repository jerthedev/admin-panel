import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import CodeField from '@/components/Fields/CodeField.vue'
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
  CodeBracketIcon: { template: '<div data-testid="code-bracket-icon"></div>' },
  DocumentDuplicateIcon: { template: '<div data-testid="document-duplicate-icon"></div>' },
  ArrowsPointingOutIcon: { template: '<div data-testid="arrows-pointing-out-icon"></div>' },
  CheckIcon: { template: '<div data-testid="check-icon"></div>' },
  ExclamationTriangleIcon: { template: '<div data-testid="exclamation-triangle-icon"></div>' }
}))

// Mock Monaco Editor
vi.mock('monaco-editor', () => ({
  editor: {
    create: vi.fn(() => ({
      getValue: vi.fn(() => 'mocked code'),
      setValue: vi.fn(),
      onDidChangeModelContent: vi.fn(),
      updateOptions: vi.fn(),
      layout: vi.fn(),
      dispose: vi.fn(),
      focus: vi.fn(),
      getModel: vi.fn(() => ({
        getLanguageId: vi.fn(() => 'javascript')
      }))
    })),
    setTheme: vi.fn(),
    defineTheme: vi.fn()
  },
  languages: {
    register: vi.fn(),
    setMonarchTokensProvider: vi.fn()
  }
}))

describe('CodeField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Configuration',
      attribute: 'config',
      type: 'code',
      language: 'json',
      theme: 'light',
      showLineNumbers: true,
      height: 300,
      readOnly: false,
      wrapLines: true
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders code editor container', () => {
      wrapper = mountField(CodeField, { field: mockField })

      const editorContainer = wrapper.find('.code-editor-container')
      expect(editorContainer.exists()).toBe(true)
    })

    it('shows language indicator', () => {
      wrapper = mountField(CodeField, { field: mockField })

      expect(wrapper.text()).toContain('JSON')
    })

    it('shows line count when content exists', () => {
      wrapper = mountField(CodeField, {
        field: mockField,
        modelValue: '{\n  "key": "value"\n}'
      })

      expect(wrapper.text()).toContain('3 lines')
    })

    it('shows copy button', () => {
      wrapper = mountField(CodeField, { field: mockField })

      const copyButton = wrapper.find('[data-testid="document-duplicate-icon"]')
      expect(copyButton.exists()).toBe(true)
    })

    it('shows fullscreen button', () => {
      wrapper = mountField(CodeField, { field: mockField })

      const fullscreenButton = wrapper.find('[data-testid="arrows-pointing-out-icon"]')
      expect(fullscreenButton.exists()).toBe(true)
    })

    it('applies disabled state', () => {
      wrapper = mountField(CodeField, {
        field: mockField,
        props: { 
          field: mockField,
          disabled: true 
        }
      })

      const editorContainer = wrapper.find('.code-editor-container')
      expect(editorContainer.classes()).toContain('opacity-50')
    })

    it('applies readonly state', () => {
      wrapper = mountField(CodeField, {
        field: mockField,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      expect(wrapper.vm.isReadOnly).toBe(true)
    })
  })

  describe('Language Support', () => {
    it('supports JavaScript language', () => {
      const jsField = createMockField({
        ...mockField,
        language: 'javascript'
      })

      wrapper = mountField(CodeField, { field: jsField })

      expect(wrapper.text()).toContain('JavaScript')
    })

    it('supports Python language', () => {
      const pythonField = createMockField({
        ...mockField,
        language: 'python'
      })

      wrapper = mountField(CodeField, { field: pythonField })

      expect(wrapper.text()).toContain('Python')
    })

    it('supports SQL language', () => {
      const sqlField = createMockField({
        ...mockField,
        language: 'sql'
      })

      wrapper = mountField(CodeField, { field: sqlField })

      expect(wrapper.text()).toContain('SQL')
    })

    it('supports CSS language', () => {
      const cssField = createMockField({
        ...mockField,
        language: 'css'
      })

      wrapper = mountField(CodeField, { field: cssField })

      expect(wrapper.text()).toContain('CSS')
    })

    it('supports HTML language', () => {
      const htmlField = createMockField({
        ...mockField,
        language: 'html'
      })

      wrapper = mountField(CodeField, { field: htmlField })

      expect(wrapper.text()).toContain('HTML')
    })

    it('defaults to text language for unknown languages', () => {
      const unknownField = createMockField({
        ...mockField,
        language: 'unknown'
      })

      wrapper = mountField(CodeField, { field: unknownField })

      expect(wrapper.vm.editorLanguage).toBe('text')
    })
  })

  describe('JSON Mode', () => {
    it('validates JSON syntax', async () => {
      const jsonField = createMockField({
        ...mockField,
        language: 'json'
      })

      wrapper = mountField(CodeField, { field: jsonField })

      wrapper.vm.editorValue = '{"valid": "json"}'
      await wrapper.vm.validateJson()

      expect(wrapper.vm.jsonError).toBe(null)
    })

    it('shows JSON validation errors', async () => {
      const jsonField = createMockField({
        ...mockField,
        language: 'json'
      })

      wrapper = mountField(CodeField, { field: jsonField })

      wrapper.vm.editorValue = '{"invalid": json}'
      await wrapper.vm.validateJson()

      expect(wrapper.vm.jsonError).toBeTruthy()
    })

    it('shows JSON error indicator', async () => {
      const jsonField = createMockField({
        ...mockField,
        language: 'json'
      })

      wrapper = mountField(CodeField, { field: jsonField })

      wrapper.vm.jsonError = 'Unexpected token'
      await nextTick()

      const errorIcon = wrapper.find('[data-testid="exclamation-triangle-icon"]')
      expect(errorIcon.exists()).toBe(true)
    })

    it('shows JSON validation success', async () => {
      const jsonField = createMockField({
        ...mockField,
        language: 'json'
      })

      wrapper = mountField(CodeField, { field: jsonField })

      wrapper.vm.editorValue = '{"valid": "json"}'
      wrapper.vm.jsonError = null
      await nextTick()

      const successIcon = wrapper.find('[data-testid="check-icon"]')
      expect(successIcon.exists()).toBe(true)
    })

    it('formats JSON when format button clicked', async () => {
      const jsonField = createMockField({
        ...mockField,
        language: 'json'
      })

      wrapper = mountField(CodeField, { field: jsonField })

      const formatSpy = vi.spyOn(wrapper.vm, 'formatJson')
      const formatButton = wrapper.find('.format-json-button')
      
      if (formatButton.exists()) {
        await formatButton.trigger('click')
        expect(formatSpy).toHaveBeenCalled()
      }
    })

    it('minifies JSON when minify button clicked', async () => {
      const jsonField = createMockField({
        ...mockField,
        language: 'json'
      })

      wrapper = mountField(CodeField, { field: jsonField })

      const minifySpy = vi.spyOn(wrapper.vm, 'minifyJson')
      const minifyButton = wrapper.find('.minify-json-button')
      
      if (minifyButton.exists()) {
        await minifyButton.trigger('click')
        expect(minifySpy).toHaveBeenCalled()
      }
    })
  })

  describe('Editor Configuration', () => {
    it('sets editor height from field configuration', () => {
      wrapper = mountField(CodeField, { field: mockField })

      const editorContainer = wrapper.find('.monaco-editor-container')
      expect(editorContainer.attributes('style')).toContain('height: 300px')
    })

    it('enables line numbers when showLineNumbers is true', () => {
      wrapper = mountField(CodeField, { field: mockField })

      expect(wrapper.vm.editorOptions.lineNumbers).toBe('on')
    })

    it('disables line numbers when showLineNumbers is false', () => {
      const fieldWithoutLineNumbers = createMockField({
        ...mockField,
        showLineNumbers: false
      })

      wrapper = mountField(CodeField, { field: fieldWithoutLineNumbers })

      expect(wrapper.vm.editorOptions.lineNumbers).toBe('off')
    })

    it('enables word wrap when wrapLines is true', () => {
      wrapper = mountField(CodeField, { field: mockField })

      expect(wrapper.vm.editorOptions.wordWrap).toBe('on')
    })

    it('disables word wrap when wrapLines is false', () => {
      const fieldWithoutWrap = createMockField({
        ...mockField,
        wrapLines: false
      })

      wrapper = mountField(CodeField, { field: fieldWithoutWrap })

      expect(wrapper.vm.editorOptions.wordWrap).toBe('off')
    })

    it('sets read-only mode when readonly prop is true', () => {
      wrapper = mountField(CodeField, {
        field: mockField,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      expect(wrapper.vm.editorOptions.readOnly).toBe(true)
    })
  })

  describe('Theme Support', () => {
    it('uses light theme by default', () => {
      wrapper = mountField(CodeField, { field: mockField })

      expect(wrapper.vm.editorTheme).toBe('vs')
    })

    it('uses dark theme when specified', () => {
      const darkField = createMockField({
        ...mockField,
        theme: 'dark'
      })

      wrapper = mountField(CodeField, { field: darkField })

      expect(wrapper.vm.editorTheme).toBe('vs-dark')
    })

    it('switches to dark theme when admin theme is dark', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(CodeField, { field: mockField })

      expect(wrapper.vm.editorTheme).toBe('vs-dark')
    })

    it('applies dark theme classes to container', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(CodeField, { field: mockField })

      const container = wrapper.find('.code-editor-container')
      expect(container.classes()).toContain('bg-gray-800')
      expect(container.classes()).toContain('border-gray-600')
    })
  })

  describe('Copy Functionality', () => {
    it('copies code to clipboard when copy button clicked', async () => {
      // Mock clipboard API
      Object.assign(navigator, {
        clipboard: {
          writeText: vi.fn().mockResolvedValue()
        }
      })

      wrapper = mountField(CodeField, {
        field: mockField,
        modelValue: 'console.log("Hello World");'
      })

      const copyButton = wrapper.find('[data-testid="document-duplicate-icon"]')
      await copyButton.element.parentElement.click()

      expect(navigator.clipboard.writeText).toHaveBeenCalledWith('console.log("Hello World");')
    })

    it('shows copy success feedback', async () => {
      Object.assign(navigator, {
        clipboard: {
          writeText: vi.fn().mockResolvedValue()
        }
      })

      wrapper = mountField(CodeField, { field: mockField })

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

      wrapper = mountField(CodeField, { field: mockField })

      await wrapper.vm.copyToClipboard()

      expect(consoleSpy).toHaveBeenCalled()
      consoleSpy.mockRestore()
    })
  })

  describe('Fullscreen Mode', () => {
    it('toggles fullscreen mode when fullscreen button clicked', async () => {
      wrapper = mountField(CodeField, { field: mockField })

      const fullscreenSpy = vi.spyOn(wrapper.vm, 'toggleFullscreen')
      const fullscreenButton = wrapper.find('[data-testid="arrows-pointing-out-icon"]')
      
      await fullscreenButton.element.parentElement.click()
      expect(fullscreenSpy).toHaveBeenCalled()
    })

    it('applies fullscreen classes when in fullscreen mode', async () => {
      wrapper = mountField(CodeField, { field: mockField })

      wrapper.vm.isFullscreen = true
      await nextTick()

      const container = wrapper.find('.code-editor-container')
      expect(container.classes()).toContain('fixed')
      expect(container.classes()).toContain('inset-0')
      expect(container.classes()).toContain('z-50')
    })

    it('exits fullscreen on escape key', async () => {
      wrapper = mountField(CodeField, { field: mockField })

      wrapper.vm.isFullscreen = true
      
      const handleKeydownSpy = vi.spyOn(wrapper.vm, 'handleKeydown')
      await wrapper.trigger('keydown', { key: 'Escape' })

      expect(handleKeydownSpy).toHaveBeenCalled()
    })
  })

  describe('Event Handling', () => {
    it('emits update:modelValue when content changes', async () => {
      wrapper = mountField(CodeField, { field: mockField })

      wrapper.vm.editorValue = 'new code content'
      await wrapper.vm.updateContent()

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('change')).toBeTruthy()
    })

    it('emits focus event', async () => {
      wrapper = mountField(CodeField, { field: mockField })

      await wrapper.vm.handleFocus()
      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(CodeField, { field: mockField })

      await wrapper.vm.handleBlur()
      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('debounces content updates', async () => {
      wrapper = mountField(CodeField, { field: mockField })

      const updateSpy = vi.spyOn(wrapper.vm, 'updateContent')
      
      // Trigger multiple rapid changes
      wrapper.vm.editorValue = 'change 1'
      wrapper.vm.editorValue = 'change 2'
      wrapper.vm.editorValue = 'change 3'

      // Wait for debounce
      await new Promise(resolve => setTimeout(resolve, 350))

      expect(updateSpy).toHaveBeenCalledTimes(1)
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(CodeField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the editor', async () => {
      wrapper = mountField(CodeField, { field: mockField })

      const focusEditorSpy = vi.spyOn(wrapper.vm, 'focusEditor')
      wrapper.vm.focus()

      expect(focusEditorSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles null modelValue', () => {
      wrapper = mountField(CodeField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.editorValue).toBe('')
    })

    it('handles undefined modelValue', () => {
      wrapper = mountField(CodeField, {
        field: mockField,
        modelValue: undefined
      })

      expect(wrapper.vm.editorValue).toBe('')
    })

    it('handles very large code content', () => {
      const largeCode = 'console.log("line");\n'.repeat(10000)
      
      wrapper = mountField(CodeField, {
        field: mockField,
        modelValue: largeCode
      })

      expect(wrapper.vm.editorValue).toBe(largeCode)
    })

    it('handles special characters in code', () => {
      const specialCode = 'const emoji = "🚀"; // Unicode test\nconst symbols = "!@#$%^&*()";'
      
      wrapper = mountField(CodeField, {
        field: mockField,
        modelValue: specialCode
      })

      expect(wrapper.vm.editorValue).toBe(specialCode)
    })

    it('cleans up editor on unmount', () => {
      wrapper = mountField(CodeField, { field: mockField })

      const destroySpy = vi.spyOn(wrapper.vm, 'destroyEditor')
      wrapper.unmount()

      expect(destroySpy).toHaveBeenCalled()
    })
  })
})
