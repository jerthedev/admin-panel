import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import HeadingField from '@/components/Fields/HeadingField.vue'
import BaseField from '@/components/Fields/BaseField.vue'
import { createMockField } from '../../../helpers.js'

/**
 * Heading Field Integration Tests
 *
 * Tests the integration between the PHP Heading field class and Vue component,
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

describe('HeadingField Integration', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP to Vue Integration', () => {
    it('receives and processes PHP field configuration correctly', async () => {
      const phpFieldConfig = createMockField({
        name: 'User Information',
        attribute: 'user_information',
        component: 'HeadingField',
        asHtml: false,
        isHeading: true,
        showOnIndex: false,
        showOnDetail: true,
        showOnCreation: true,
        showOnUpdate: true,
        nullable: true,
        readonly: true
      })

      wrapper = mount(HeadingField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Verify PHP configuration is properly received
      expect(wrapper.vm.field.name).toBe('User Information')
      expect(wrapper.vm.field.attribute).toBe('user_information')
      expect(wrapper.vm.field.component).toBe('HeadingField')
      expect(wrapper.vm.field.asHtml).toBe(false)
      expect(wrapper.vm.field.isHeading).toBe(true)
      expect(wrapper.vm.field.showOnIndex).toBe(false)
      expect(wrapper.vm.field.showOnDetail).toBe(true)
      expect(wrapper.vm.field.showOnCreation).toBe(true)
      expect(wrapper.vm.field.showOnUpdate).toBe(true)
      expect(wrapper.vm.field.nullable).toBe(true)
      expect(wrapper.vm.field.readonly).toBe(true)
    })

    it('handles HTML content from PHP field configuration', async () => {
      const phpFieldConfig = createMockField({
        name: '<div class="bg-blue-100 p-4"><h3>Important Section</h3><p>Please review carefully.</p></div>',
        attribute: 'important_section',
        component: 'HeadingField',
        asHtml: true,
        isHeading: true
      })

      wrapper = mount(HeadingField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Verify HTML content is properly handled
      expect(wrapper.vm.field.asHtml).toBe(true)
      expect(wrapper.vm.field.name).toContain('<div class="bg-blue-100 p-4">')
      expect(wrapper.vm.field.name).toContain('<h3>Important Section</h3>')
      expect(wrapper.vm.field.name).toContain('<p>Please review carefully.</p>')

      // Verify HTML is rendered in the component
      const htmlContent = wrapper.find('.heading-content')
      expect(htmlContent.html()).toContain('<div class="bg-blue-100 p-4">')
      expect(htmlContent.html()).toContain('<h3>Important Section</h3>')
      expect(htmlContent.html()).toContain('<p>Please review carefully.</p>')
    })

    it('handles plain text content from PHP field configuration', async () => {
      const phpFieldConfig = createMockField({
        name: 'Simple Section Header',
        attribute: 'simple_section_header',
        component: 'HeadingField',
        asHtml: false,
        isHeading: true
      })

      wrapper = mount(HeadingField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Verify plain text content is properly handled
      expect(wrapper.vm.field.asHtml).toBe(false)
      expect(wrapper.vm.field.name).toBe('Simple Section Header')

      // Verify text is rendered in the component
      const textContent = wrapper.find('.heading-text')
      expect(textContent.exists()).toBe(true)
      expect(textContent.text()).toBe('Simple Section Header')
    })
  })

  describe('Vue to PHP Integration', () => {
    it('maintains field state without emitting data changes', async () => {
      const phpFieldConfig = createMockField({
        name: 'Meta Information',
        attribute: 'meta_information',
        component: 'HeadingField',
        asHtml: false,
        isHeading: true
      })

      wrapper = mount(HeadingField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Heading fields should not emit any data changes
      expect(wrapper.emitted()).toEqual({})

      // Field should maintain its display state
      const headingContent = wrapper.find('.heading-content')
      expect(headingContent.text()).toBe('Meta Information')
    })

    it('respects visibility settings from PHP field', async () => {
      const phpFieldConfig = createMockField({
        name: 'Hidden Section',
        attribute: 'hidden_section',
        component: 'HeadingField',
        asHtml: false,
        isHeading: true,
        showOnIndex: true, // Overridden from default
        showOnDetail: false // Overridden from default
      })

      wrapper = mount(HeadingField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Verify visibility settings are respected
      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('field').showOnIndex).toBe(true)
      expect(baseField.props('field').showOnDetail).toBe(false)
    })
  })

  describe('Nova API Compatibility Integration', () => {
    it('integrates all Nova Heading field features correctly', async () => {
      const phpFieldConfig = createMockField({
        name: '<div class="border-l-4 border-yellow-400 bg-yellow-50 p-4"><h4>Warning</h4><p>This action cannot be undone.</p></div>',
        attribute: 'warning_section',
        component: 'HeadingField',
        asHtml: true,
        isHeading: true,
        showOnIndex: true,
        showOnDetail: true,
        showOnCreation: true,
        showOnUpdate: false,
        helpText: 'This is a warning heading',
        nullable: true,
        readonly: true
      })

      wrapper = mount(HeadingField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Verify all Nova features are integrated
      expect(wrapper.vm.field.asHtml).toBe(true)
      expect(wrapper.vm.field.isHeading).toBe(true)
      expect(wrapper.vm.field.showOnIndex).toBe(true)
      expect(wrapper.vm.field.showOnDetail).toBe(true)
      expect(wrapper.vm.field.showOnCreation).toBe(true)
      expect(wrapper.vm.field.showOnUpdate).toBe(false)
      expect(wrapper.vm.field.helpText).toBe('This is a warning heading')
      expect(wrapper.vm.field.nullable).toBe(true)
      expect(wrapper.vm.field.readonly).toBe(true)

      // Verify HTML content is rendered
      const htmlContent = wrapper.find('.heading-content')
      expect(htmlContent.html()).toContain('border-l-4 border-yellow-400')
      expect(htmlContent.html()).toContain('<h4>Warning</h4>')
      expect(htmlContent.html()).toContain('<p>This action cannot be undone.</p>')
    })

    it('handles complex HTML structures from PHP', async () => {
      const complexHtml = `
        <div class="rounded-md bg-blue-50 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3 flex-1 md:flex md:justify-between">
              <p class="text-sm text-blue-700">Information about this form section.</p>
              <p class="mt-3 text-sm md:mt-0 md:ml-6">
                <a href="#" class="whitespace-nowrap font-medium text-blue-700 hover:text-blue-600">
                  Learn more <span aria-hidden="true">&rarr;</span>
                </a>
              </p>
            </div>
          </div>
        </div>
      `

      const phpFieldConfig = createMockField({
        name: complexHtml,
        attribute: 'complex_info_section',
        component: 'HeadingField',
        asHtml: true,
        isHeading: true
      })

      wrapper = mount(HeadingField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Verify complex HTML is properly integrated
      const htmlContent = wrapper.find('.heading-content')
      expect(htmlContent.html()).toContain('rounded-md bg-blue-50 p-4')
      expect(htmlContent.html()).toContain('text-blue-700')
      expect(htmlContent.html()).toContain('Information about this form section.')
      expect(htmlContent.html()).toContain('Learn more')
      expect(htmlContent.html()).toContain('→')
    })

    it('maintains proper component hierarchy with BaseField', async () => {
      const phpFieldConfig = createMockField({
        name: 'Form Section',
        attribute: 'form_section',
        component: 'HeadingField',
        asHtml: false,
        isHeading: true
      })

      wrapper = mount(HeadingField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Verify proper component hierarchy
      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.exists()).toBe(true)
      expect(baseField.props('showLabel')).toBe(false)
      expect(baseField.props('readonly')).toBe(true)

      // Verify heading-specific elements
      const headingField = wrapper.find('.heading-field')
      expect(headingField.exists()).toBe(true)

      const headingContent = wrapper.find('.heading-content')
      expect(headingContent.exists()).toBe(true)
      expect(headingContent.text()).toBe('Form Section')
    })
  })

  describe('Theme Integration', () => {
    it('integrates with dark theme from admin store', async () => {
      mockAdminStore.isDarkTheme = true

      const phpFieldConfig = createMockField({
        name: 'Dark Theme Section',
        attribute: 'dark_theme_section',
        component: 'HeadingField',
        asHtml: false,
        isHeading: true
      })

      wrapper = mount(HeadingField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Verify dark theme classes are applied
      const headingField = wrapper.find('.heading-field')
      expect(headingField.classes()).toContain('border-gray-700')
    })

    it('integrates with light theme from admin store', async () => {
      mockAdminStore.isDarkTheme = false

      const phpFieldConfig = createMockField({
        name: 'Light Theme Section',
        attribute: 'light_theme_section',
        component: 'HeadingField',
        asHtml: false,
        isHeading: true
      })

      wrapper = mount(HeadingField, {
        props: {
          field: phpFieldConfig,
          modelValue: null
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Verify light theme classes are applied
      const headingField = wrapper.find('.heading-field')
      expect(headingField.classes()).toContain('border-gray-200')
      expect(headingField.classes()).not.toContain('border-gray-700')
    })
  })

  describe('Error Handling Integration', () => {
    it('handles missing field properties gracefully', async () => {
      const incompleteFieldConfig = createMockField({
        name: 'Incomplete Field',
        component: 'HeadingField'
        // Missing some properties
      })

      wrapper = mount(HeadingField, {
        props: {
          field: incompleteFieldConfig,
          modelValue: null
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Should render without errors
      expect(wrapper.exists()).toBe(true)
      const headingContent = wrapper.find('.heading-content')
      expect(headingContent.text()).toBe('Incomplete Field')
    })

    it('handles null or undefined field name gracefully', async () => {
      const nullFieldConfig = createMockField({
        name: null,
        component: 'HeadingField',
        asHtml: false,
        isHeading: true
      })

      wrapper = mount(HeadingField, {
        props: {
          field: nullFieldConfig,
          modelValue: null
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Should render without errors
      expect(wrapper.exists()).toBe(true)
    })
  })
})
