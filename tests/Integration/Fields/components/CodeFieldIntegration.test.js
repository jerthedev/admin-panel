import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CodeField from '@/components/Fields/CodeField.vue'
import BaseField from '@/components/Fields/BaseField.vue'
import { createMockField } from '../../../helpers.js'

/**
 * Code Field Integration Tests
 *
 * Tests the integration between the PHP Code field class and Vue component,
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

describe('CodeField Integration', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP to Vue Integration', () => {
    it('receives and processes PHP field configuration correctly', () => {
      const phpFieldConfig = createMockField({
        name: 'Source Code',
        attribute: 'source_code',
        component: 'CodeField',
        language: 'php',
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
        rules: ['required']
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: '<?php echo "Hello World"; ?>'
        }
      })

      // Verify PHP configuration is properly received
      expect(wrapper.vm.field.name).toBe('Source Code')
      expect(wrapper.vm.field.language).toBe('php')
      expect(wrapper.vm.field.isJson).toBe(false)
      expect(wrapper.vm.field.rules).toContain('required')
      expect(wrapper.vm.field.supportedLanguages).toContain('php')
    })

    it('correctly processes Nova API language() method output', () => {
      const phpFieldConfig = createMockField({
        language: 'javascript',
        isJson: false
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'console.log("Hello World");'
        }
      })

      expect(wrapper.vm.language).toBe('javascript')
      expect(wrapper.vm.languageLabel).toBe('JavaScript')
      expect(wrapper.text()).toContain('JavaScript')

      // Test textarea contains the code
      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toBe('console.log("Hello World");')
    })

    it('correctly processes Nova API json() method output', () => {
      const phpFieldConfig = createMockField({
        language: 'javascript', // JSON uses JavaScript highlighting
        isJson: true
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: { name: 'test', value: 123 }
        }
      })

      expect(wrapper.vm.language).toBe('javascript')
      expect(wrapper.vm.isJson).toBe(true)
      expect(wrapper.text()).toContain('JSON')
      expect(wrapper.text()).toContain('Enter valid JSON format')

      // Test textarea contains formatted JSON
      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toContain('"name": "test"')
      expect(textarea.element.value).toContain('"value": 123')
    })

    it('handles all Nova-supported languages from PHP', () => {
      const novaLanguages = [
        { lang: 'php', label: 'PHP', code: '<?php echo "test"; ?>' },
        { lang: 'javascript', label: 'JavaScript', code: 'console.log("test");' },
        { lang: 'python', label: 'PYTHON', code: 'print("test")' },
        { lang: 'sql', label: 'SQL', code: 'SELECT * FROM users;' },
        { lang: 'yaml', label: 'YAML', code: 'name: test\nvalue: 123' },
      ]

      novaLanguages.forEach(({ lang, label, code }) => {
        const phpFieldConfig = createMockField({
          language: lang,
          isJson: false
        })

        wrapper = mount(CodeField, {
          props: {
            field: phpFieldConfig,
            modelValue: code
          }
        })

        expect(wrapper.vm.language).toBe(lang)
        expect(wrapper.vm.languageLabel).toBe(label)

        const textarea = wrapper.find('textarea')
        expect(textarea.element.value).toBe(code)

        if (wrapper) {
          wrapper.unmount()
        }
      })
    })

    it('handles empty and null values from PHP correctly', () => {
      const phpFieldConfig = createMockField({
        language: 'php',
        isJson: false
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        }
      })

      expect(wrapper.vm.currentValue).toBe('')

      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toBe('')
    })
  })

  describe('Nova API Compatibility Integration', () => {
    it('integrates all Nova API methods correctly', () => {
      const phpFieldConfig = createMockField({
        language: 'yaml',
        isJson: true, // This should override language to 'javascript'
        rules: ['required'],
        nullable: false
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: { config: { debug: true, timeout: 5000 } }
        }
      })

      // Test complete integration
      expect(wrapper.vm.language).toBe('javascript') // JSON overrides language
      expect(wrapper.vm.isJson).toBe(true)
      expect(wrapper.vm.isRequired).toBe(true)

      // Test JSON formatting
      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toContain('"config"')
      expect(textarea.element.value).toContain('"debug": true')
      expect(textarea.element.value).toContain('"timeout": 5000')

      // Test UI indicators
      expect(wrapper.text()).toContain('JSON')
      expect(wrapper.text()).toContain('* Required')
    })

    it('handles fallback behavior correctly', () => {
      const phpFieldConfig = createMockField({
        // No custom configuration provided, should use defaults
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: '<h1>Hello World</h1>'
        }
      })

      // Should fall back to defaults
      expect(wrapper.vm.language).toBe('htmlmixed')
      expect(wrapper.vm.isJson).toBe(false)
      expect(wrapper.vm.languageLabel).toBe('HTML')
    })

    it('handles undefined configuration values correctly', () => {
      const phpFieldConfig = createMockField({
        language: undefined,
        isJson: undefined
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'test code'
        }
      })

      // Should fall back to defaults when undefined
      expect(wrapper.vm.language).toBe('htmlmixed')
      expect(wrapper.vm.isJson).toBe(false)
    })
  })

  describe('User Interaction Integration', () => {
    it('emits correct values based on PHP configuration', async () => {
      const phpFieldConfig = createMockField({
        language: 'javascript',
        isJson: false
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: ''
        }
      })

      const textarea = wrapper.find('textarea')
      await textarea.setValue('const x = 42;')
      await textarea.trigger('input')

      // Should emit the string value for non-JSON field
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('const x = 42;')
      expect(wrapper.emitted('change')[0][0]).toBe('const x = 42;')
    })

    it('handles JSON field interactions with PHP structure', async () => {
      const phpFieldConfig = createMockField({
        language: 'javascript',
        isJson: true
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: {}
        }
      })

      const textarea = wrapper.find('textarea')
      await textarea.setValue('{"name": "test", "active": true}')
      await textarea.trigger('input')

      // Should emit parsed JSON object
      const emittedValue = wrapper.emitted('update:modelValue')[0][0]
      expect(emittedValue).toEqual({ name: 'test', active: true })
    })

    it('handles invalid JSON gracefully', async () => {
      const phpFieldConfig = createMockField({
        language: 'javascript',
        isJson: true
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: {}
        }
      })

      const textarea = wrapper.find('textarea')
      await textarea.setValue('{ invalid json }')
      await textarea.trigger('input')

      // Should emit as string when JSON is invalid
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('{ invalid json }')
    })
  })

  describe('CRUD Operations Integration', () => {
    it('handles create operation with code field', () => {
      const phpFieldConfig = createMockField({
        language: 'php',
        isJson: false
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: null // New record
        }
      })

      // Should show empty textarea for new record
      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toBe('')
      expect(textarea.attributes('placeholder')).toContain('PHP code')
    })

    it('handles read operation with code field', () => {
      const phpFieldConfig = createMockField({
        language: 'sql',
        isJson: false
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'SELECT * FROM users WHERE active = 1;', // Existing record
          readonly: true
        }
      })

      // Should display readonly code
      expect(wrapper.find('textarea').exists()).toBe(false)
      expect(wrapper.find('pre').exists()).toBe(true)
      expect(wrapper.text()).toContain('SELECT * FROM users WHERE active = 1;')
    })

    it('handles update operation with code field', async () => {
      const phpFieldConfig = createMockField({
        language: 'javascript',
        isJson: false
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'console.log("old");' // Current value
        }
      })

      // Simulate update
      await wrapper.setProps({
        modelValue: 'console.log("updated");'
      })

      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toBe('console.log("updated");')
    })
  })

  describe('Validation Integration', () => {
    it('displays required indicator based on PHP field rules', () => {
      const phpFieldConfig = createMockField({
        language: 'php',
        rules: ['required', 'string']
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: ''
        }
      })

      expect(wrapper.text()).toContain('* Required')
    })

    it('handles validation errors correctly', () => {
      const phpFieldConfig = createMockField({
        language: 'javascript'
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: '',
          errors: ['This field is required'] // Validation errors
        }
      })

      // Code field should still display correctly even with errors
      const textarea = wrapper.find('textarea')
      expect(textarea.exists()).toBe(true)
      expect(textarea.classes()).toContain('border-red-300')
    })
  })

  describe('Advanced Integration Scenarios', () => {
    it('handles dynamic field configuration changes', async () => {
      let phpFieldConfig = createMockField({
        language: 'php',
        isJson: false
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: '<?php echo "test"; ?>'
        }
      })

      expect(wrapper.vm.language).toBe('php')
      expect(wrapper.vm.isJson).toBe(false)

      // Simulate field configuration change (e.g., from PHP backend)
      phpFieldConfig = createMockField({
        language: 'javascript',
        isJson: true
      })

      await wrapper.setProps({
        field: phpFieldConfig,
        modelValue: { message: 'test' }
      })

      expect(wrapper.vm.language).toBe('javascript')
      expect(wrapper.vm.isJson).toBe(true)
      expect(wrapper.text()).toContain('JSON')
    })

    it('handles complex Nova configuration from PHP', () => {
      const phpFieldConfig = createMockField({
        name: 'Configuration File',
        attribute: 'config_file',
        language: 'yaml',
        isJson: true, // This should override language
        rules: ['required', 'json'],
        nullable: false,
        readonly: false,
        helpText: 'Enter configuration in JSON format'
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: {
            database: { host: 'localhost', port: 3306 },
            cache: { driver: 'redis', ttl: 3600 }
          }
        }
      })

      // Test all configurations are processed correctly
      expect(wrapper.vm.language).toBe('javascript') // JSON overrides YAML
      expect(wrapper.vm.isJson).toBe(true)
      expect(wrapper.vm.isRequired).toBe(true)

      // Test textarea contains formatted JSON
      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toContain('"database"')
      expect(textarea.element.value).toContain('"cache"')
      expect(textarea.element.value).toContain('"host": "localhost"')
    })

    it('integrates with BaseField wrapper correctly', () => {
      const phpFieldConfig = createMockField({
        name: 'Source Code',
        helpText: 'Enter your source code here',
        language: 'php'
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: '<?php echo "Hello"; ?>'
        }
      })

      // Test BaseField integration
      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.exists()).toBe(true)
      expect(baseField.props('field')).toEqual(phpFieldConfig)
      expect(baseField.props('modelValue')).toBe('<?php echo "Hello"; ?>')
    })
  })

  describe('Type Handling and Edge Cases', () => {
    it('handles complex JSON structures correctly', () => {
      const phpFieldConfig = createMockField({
        language: 'javascript',
        isJson: true
      })

      const complexJson = {
        users: [
          { id: 1, name: 'John', roles: ['admin', 'user'] },
          { id: 2, name: 'Jane', roles: ['user'] }
        ],
        settings: {
          theme: 'dark',
          notifications: {
            email: true,
            push: false,
            sms: null
          }
        },
        metadata: {
          version: '1.0.0',
          created_at: '2023-01-01T00:00:00Z',
          features: ['auth', 'logging', 'caching']
        }
      }

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: complexJson
        }
      })

      const textarea = wrapper.find('textarea')
      const value = textarea.element.value

      // Should contain all nested structures
      expect(value).toContain('"users"')
      expect(value).toContain('"settings"')
      expect(value).toContain('"notifications"')
      expect(value).toContain('"admin"')
      expect(value).toContain('"user"')
      expect(value).toContain('"sms": null')
    })

    it('handles null and undefined values from PHP correctly', () => {
      const phpFieldConfig = createMockField({
        language: 'php'
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        }
      })

      // Should handle null gracefully
      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toBe('')
    })

    it('handles different data types from PHP', () => {
      const testCases = [
        { value: '', expected: '' },
        { value: 'string value', expected: 'string value' },
        { value: 123, expected: '123' },
        { value: true, expected: 'true' },
        { value: [1, 2, 3], expected: '1,2,3' },
      ]

      testCases.forEach(({ value, expected }) => {
        const phpFieldConfig = createMockField({
          language: 'javascript',
          isJson: false
        })

        wrapper = mount(CodeField, {
          props: {
            field: phpFieldConfig,
            modelValue: value
          }
        })

        expect(wrapper.vm.currentValue).toBe(expected)

        if (wrapper) {
          wrapper.unmount()
        }
      })
    })
  })

  describe('Performance and Reactivity', () => {
    it('updates efficiently when props change', async () => {
      const phpFieldConfig = createMockField({
        language: 'javascript',
        isJson: false
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'initial code'
        }
      })

      expect(wrapper.find('textarea').element.value).toBe('initial code')

      // Multiple rapid changes should work correctly
      await wrapper.setProps({ modelValue: 'updated code 1' })
      expect(wrapper.find('textarea').element.value).toBe('updated code 1')

      await wrapper.setProps({ modelValue: 'updated code 2' })
      expect(wrapper.find('textarea').element.value).toBe('updated code 2')

      await wrapper.setProps({ modelValue: 'final code' })
      expect(wrapper.find('textarea').element.value).toBe('final code')
    })

    it('maintains reactivity with complex field changes', async () => {
      let phpFieldConfig = createMockField({
        language: 'php',
        isJson: false
      })

      wrapper = mount(CodeField, {
        props: {
          field: phpFieldConfig,
          modelValue: '<?php echo "test"; ?>'
        }
      })

      expect(wrapper.vm.language).toBe('php')
      expect(wrapper.vm.isJson).toBe(false)

      // Change both field config and value simultaneously
      phpFieldConfig = createMockField({
        language: 'javascript',
        isJson: true
      })

      await wrapper.setProps({
        field: phpFieldConfig,
        modelValue: { message: 'hello' }
      })

      expect(wrapper.vm.language).toBe('javascript')
      expect(wrapper.vm.isJson).toBe(true)
      expect(wrapper.text()).toContain('JSON')

      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toContain('"message": "hello"')
    })
  })
})
