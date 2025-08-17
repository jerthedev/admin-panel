import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CodeField from '@/components/Fields/CodeField.vue'
import BaseField from '@/components/Fields/BaseField.vue'

/**
 * Code Field Vue Component Tests
 *
 * Tests for CodeField Vue component with 100% Nova API compatibility.
 * Tests all Nova Code field features including language() and json() methods.
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
  name: 'Code',
  attribute: 'code',
  component: 'CodeField',
  language: 'htmlmixed',
  isJson: false,
  supportedLanguages: [
    'dockerfile',
    'htmlmixed',
    'javascript',
    'markdown',
    'nginx',
    'php',
    'ruby',
    'sass',
    'shell',
    'sql',
    'twig',
    'vim',
    'vue',
    'xml',
    'yaml-frontmatter',
    'yaml',
  ],
  rules: [],
  ...overrides
})

// Helper function to mount field component
const mountField = (component, props = {}) => {
  return mount(component, {
    props: {
      field: createMockField(),
      modelValue: '',
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

describe('CodeField', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders code field with BaseField wrapper', () => {
      wrapper = mountField(CodeField, { field: createMockField() })

      expect(wrapper.findComponent(BaseField).exists()).toBe(true)
      expect(wrapper.find('textarea').exists()).toBe(true)
    })

    it('renders with default htmlmixed language', () => {
      const field = createMockField({ language: 'htmlmixed' })

      wrapper = mountField(CodeField, { field })

      const textarea = wrapper.find('textarea')
      expect(textarea.exists()).toBe(true)
      expect(wrapper.vm.language).toBe('htmlmixed')
    })

    it('renders with custom language', () => {
      const field = createMockField({ language: 'php' })

      wrapper = mountField(CodeField, { field })

      expect(wrapper.vm.language).toBe('php')
      expect(wrapper.text()).toContain('PHP')
    })

    it('renders with default empty value', () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        modelValue: ''
      })

      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toBe('')
    })

    it('renders with string value', () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        modelValue: 'console.log("Hello World");'
      })

      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toBe('console.log("Hello World");')
    })
  })

  describe('Nova API Compatibility - Language', () => {
    it('handles all Nova-supported languages correctly', () => {
      const novaLanguages = [
        'dockerfile',
        'htmlmixed',
        'javascript',
        'markdown',
        'nginx',
        'php',
        'ruby',
        'sass',
        'shell',
        'sql',
        'twig',
        'vim',
        'vue',
        'xml',
        'yaml-frontmatter',
        'yaml',
      ]

      novaLanguages.forEach(language => {
        const field = createMockField({ language })
        wrapper = mountField(CodeField, { field })

        expect(wrapper.vm.language).toBe(language)
        
        if (wrapper) {
          wrapper.unmount()
        }
      })
    })

    it('displays correct language labels', () => {
      const languageTests = [
        { language: 'php', expectedLabel: 'PHP' },
        { language: 'javascript', expectedLabel: 'JavaScript' },
        { language: 'markdown', expectedLabel: 'Markdown' },
        { language: 'dockerfile', expectedLabel: 'Dockerfile' },
        { language: 'yaml', expectedLabel: 'YAML' },
      ]

      languageTests.forEach(({ language, expectedLabel }) => {
        const field = createMockField({ language })
        wrapper = mountField(CodeField, { field })

        expect(wrapper.vm.languageLabel).toBe(expectedLabel)
        
        if (wrapper) {
          wrapper.unmount()
        }
      })
    })

    it('shows language indicator for non-default languages', () => {
      const field = createMockField({ language: 'php' })

      wrapper = mountField(CodeField, { field })

      expect(wrapper.text()).toContain('PHP')
    })

    it('does not show language indicator for htmlmixed', () => {
      const field = createMockField({ language: 'htmlmixed' })

      wrapper = mountField(CodeField, { field })

      // Should not show language indicator for default htmlmixed
      const languageIndicator = wrapper.find('.absolute.top-2.right-2')
      expect(languageIndicator.exists()).toBe(false)
    })
  })

  describe('Nova API Compatibility - JSON', () => {
    it('handles JSON field configuration correctly', () => {
      const field = createMockField({ 
        isJson: true,
        language: 'javascript' // JSON uses JavaScript highlighting
      })

      wrapper = mountField(CodeField, { field })

      expect(wrapper.vm.isJson).toBe(true)
      expect(wrapper.vm.language).toBe('javascript')
      expect(wrapper.text()).toContain('JSON')
    })

    it('shows JSON indicator when isJson is true', () => {
      const field = createMockField({ isJson: true })

      wrapper = mountField(CodeField, { field })

      expect(wrapper.text()).toContain('JSON')
      expect(wrapper.text()).toContain('Enter valid JSON format')
    })

    it('handles JSON object as modelValue', () => {
      const field = createMockField({ isJson: true })
      const jsonValue = { name: 'test', value: 123 }

      wrapper = mountField(CodeField, { 
        field, 
        modelValue: jsonValue 
      })

      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toContain('"name": "test"')
      expect(textarea.element.value).toContain('"value": 123')
    })

    it('formats JSON objects nicely', () => {
      const field = createMockField({ isJson: true })
      const jsonValue = { name: 'test', nested: { value: 123 } }

      wrapper = mountField(CodeField, { 
        field, 
        modelValue: jsonValue 
      })

      const textarea = wrapper.find('textarea')
      const value = textarea.element.value
      
      // Should be formatted with proper indentation
      expect(value).toContain('{\n  "name": "test"')
      expect(value).toContain('  "nested": {\n    "value": 123')
    })

    it('handles invalid JSON gracefully', () => {
      const field = createMockField({ isJson: true })

      wrapper = mountField(CodeField, { 
        field, 
        modelValue: '{ invalid json' 
      })

      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toBe('{ invalid json')
    })
  })

  describe('User Interactions', () => {
    it('emits update:modelValue when text changes', async () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        modelValue: ''
      })

      const textarea = wrapper.find('textarea')
      await textarea.setValue('console.log("test");')
      await textarea.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('console.log("test");')
      expect(wrapper.emitted('change')[0][0]).toBe('console.log("test");')
    })

    it('emits parsed JSON object for valid JSON input', async () => {
      const field = createMockField({ isJson: true })

      wrapper = mountField(CodeField, { field, modelValue: {} })

      const textarea = wrapper.find('textarea')
      await textarea.setValue('{"name": "test", "value": 123}')
      await textarea.trigger('input')

      const emittedValue = wrapper.emitted('update:modelValue')[0][0]
      expect(emittedValue).toEqual({ name: 'test', value: 123 })
    })

    it('emits string for invalid JSON input', async () => {
      const field = createMockField({ isJson: true })

      wrapper = mountField(CodeField, { field, modelValue: {} })

      const textarea = wrapper.find('textarea')
      await textarea.setValue('{ invalid json')
      await textarea.trigger('input')

      const emittedValue = wrapper.emitted('update:modelValue')[0][0]
      expect(emittedValue).toBe('{ invalid json')
    })

    it('emits focus and blur events', async () => {
      wrapper = mountField(CodeField, { field: createMockField() })

      const textarea = wrapper.find('textarea')
      
      await textarea.trigger('focus')
      expect(wrapper.emitted('focus')).toBeTruthy()

      await textarea.trigger('blur')
      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('handles null and undefined values correctly', () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        modelValue: null
      })

      expect(wrapper.vm.currentValue).toBe('')

      wrapper.setProps({ modelValue: undefined })
      expect(wrapper.vm.currentValue).toBe('')
    })
  })

  describe('Disabled State', () => {
    it('disables textarea when disabled prop is true', () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        disabled: true
      })

      const textarea = wrapper.find('textarea')
      expect(textarea.element.disabled).toBe(true)
      expect(textarea.classes()).toContain('bg-gray-50')
      expect(textarea.classes()).toContain('cursor-not-allowed')
    })

    it('does not emit events when disabled', async () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        disabled: true,
        modelValue: ''
      })

      const textarea = wrapper.find('textarea')
      await textarea.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
      expect(wrapper.emitted('change')).toBeFalsy()
    })
  })

  describe('Readonly State', () => {
    it('shows readonly display instead of textarea', () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        readonly: true,
        modelValue: 'console.log("readonly");'
      })

      expect(wrapper.find('textarea').exists()).toBe(false)
      expect(wrapper.find('pre').exists()).toBe(true)
      expect(wrapper.text()).toContain('console.log("readonly");')
    })

    it('displays formatted JSON in readonly mode', () => {
      const field = createMockField({ isJson: true })
      const jsonValue = { name: 'test', value: 123 }

      wrapper = mountField(CodeField, {
        field,
        readonly: true,
        modelValue: jsonValue
      })

      const pre = wrapper.find('pre')
      expect(pre.text()).toContain('"name": "test"')
      expect(pre.text()).toContain('"value": 123')
    })

    it('shows "No content" for empty readonly field', () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        readonly: true,
        modelValue: ''
      })

      expect(wrapper.text()).toContain('No content')
    })
  })

  describe('Required Field Indicator', () => {
    it('shows required indicator when field has required rule', () => {
      const field = createMockField({
        rules: ['required']
      })

      wrapper = mountField(CodeField, { field })

      expect(wrapper.text()).toContain('* Required')
    })

    it('does not show required indicator when field is not required', () => {
      wrapper = mountField(CodeField, { field: createMockField() })

      expect(wrapper.text()).not.toContain('* Required')
    })
  })

  describe('Error Handling', () => {
    it('applies error styling when errors are present', () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        errors: ['This field is required']
      })

      const textarea = wrapper.find('textarea')
      expect(textarea.classes()).toContain('border-red-300')
    })

    it('does not apply error styling when no errors', () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        errors: []
      })

      const textarea = wrapper.find('textarea')
      expect(textarea.classes()).not.toContain('border-red-300')
    })
  })

  describe('Dark Theme Support', () => {
    beforeEach(() => {
      mockAdminStore.isDarkTheme = true
    })

    afterEach(() => {
      mockAdminStore.isDarkTheme = false
    })

    it('applies dark theme classes to textarea', () => {
      wrapper = mountField(CodeField, { field: createMockField() })

      const textarea = wrapper.find('textarea')
      expect(textarea.classes()).toContain('border-gray-600')
      expect(textarea.classes()).toContain('bg-gray-700')
      expect(textarea.classes()).toContain('text-white')
    })

    it('applies dark theme classes to readonly display', () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        readonly: true,
        modelValue: 'test code'
      })

      const container = wrapper.find('.bg-gray-50')
      expect(container.classes()).toContain('bg-gray-800')
      expect(container.classes()).toContain('border-gray-600')
    })

    it('applies dark theme classes to indicators', () => {
      const field = createMockField({ language: 'php', isJson: true })

      wrapper = mountField(CodeField, { field })

      // Language indicator
      const languageIndicator = wrapper.find('.bg-gray-100')
      expect(languageIndicator.classes()).toContain('bg-gray-600')

      // JSON indicator
      const jsonIndicator = wrapper.find('.bg-blue-100')
      expect(jsonIndicator.classes()).toContain('bg-blue-900')
    })
  })

  describe('Component Interface', () => {
    it('implements focus method', () => {
      wrapper = mountField(CodeField, { field: createMockField() })

      expect(typeof wrapper.vm.focus).toBe('function')
      expect(() => wrapper.vm.focus()).not.toThrow()
    })

    it('implements blur method', () => {
      wrapper = mountField(CodeField, { field: createMockField() })

      expect(typeof wrapper.vm.blur).toBe('function')
      expect(() => wrapper.vm.blur()).not.toThrow()
    })

    it('generates unique field ID', () => {
      const wrapper1 = mountField(CodeField, { field: createMockField() })
      const wrapper2 = mountField(CodeField, { field: createMockField() })

      expect(wrapper1.vm.fieldId).not.toBe(wrapper2.vm.fieldId)
      
      wrapper1.unmount()
      wrapper2.unmount()
    })
  })

  describe('Placeholder Text', () => {
    it('shows appropriate placeholder for different languages', () => {
      const field = createMockField({ language: 'php' })

      wrapper = mountField(CodeField, { field })

      const textarea = wrapper.find('textarea')
      expect(textarea.attributes('placeholder')).toContain('PHP code')
    })

    it('shows JSON placeholder for JSON fields', () => {
      const field = createMockField({ isJson: true })

      wrapper = mountField(CodeField, { field })

      const textarea = wrapper.find('textarea')
      expect(textarea.attributes('placeholder')).toBe('Enter valid JSON...')
    })
  })

  describe('Edge Cases', () => {
    it('handles non-string modelValue gracefully', () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        modelValue: 123
      })

      expect(wrapper.vm.currentValue).toBe('123')
    })

    it('handles array modelValue for non-JSON field', () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        modelValue: [1, 2, 3]
      })

      expect(wrapper.vm.currentValue).toBe('1,2,3')
    })

    it('handles complex nested JSON objects', () => {
      const field = createMockField({ isJson: true })
      const complexJson = {
        users: [
          { id: 1, name: 'John', settings: { theme: 'dark' } },
          { id: 2, name: 'Jane', settings: { theme: 'light' } }
        ],
        config: {
          api: { version: 'v1', timeout: 5000 },
          features: ['auth', 'logging']
        }
      }

      wrapper = mountField(CodeField, { 
        field, 
        modelValue: complexJson 
      })

      const textarea = wrapper.find('textarea')
      const value = textarea.element.value
      
      expect(value).toContain('"users"')
      expect(value).toContain('"config"')
      expect(value).toContain('"theme": "dark"')
    })
  })

  describe('Reactivity', () => {
    it('updates when modelValue changes', async () => {
      wrapper = mountField(CodeField, {
        field: createMockField(),
        modelValue: 'initial code'
      })

      expect(wrapper.find('textarea').element.value).toBe('initial code')

      await wrapper.setProps({ modelValue: 'updated code' })

      expect(wrapper.find('textarea').element.value).toBe('updated code')
    })

    it('updates when field language changes', async () => {
      wrapper = mountField(CodeField, {
        field: createMockField({ language: 'php' }),
        modelValue: ''
      })

      expect(wrapper.vm.language).toBe('php')

      const newField = createMockField({ language: 'javascript' })
      await wrapper.setProps({ field: newField })

      expect(wrapper.vm.language).toBe('javascript')
    })

    it('updates when field isJson changes', async () => {
      wrapper = mountField(CodeField, {
        field: createMockField({ isJson: false }),
        modelValue: ''
      })

      expect(wrapper.vm.isJson).toBe(false)

      const newField = createMockField({ isJson: true })
      await wrapper.setProps({ field: newField })

      expect(wrapper.vm.isJson).toBe(true)
      expect(wrapper.text()).toContain('JSON')
    })
  })
})
