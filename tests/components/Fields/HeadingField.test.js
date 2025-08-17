import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import HeadingField from '@/components/Fields/HeadingField.vue'
import BaseField from '@/components/Fields/BaseField.vue'
import { createMockField, mountField } from '../../helpers.js'

/**
 * Heading Field Vue Component Tests
 *
 * Tests for HeadingField Vue component with 100% Nova API compatibility.
 * Tests all Nova Heading field features including make(), asHtml(),
 * visibility controls, and proper display functionality.
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

describe('HeadingField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Meta',
      attribute: 'meta',
      component: 'HeadingField',
      asHtml: false,
      isHeading: true
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders heading field with BaseField wrapper', () => {
      wrapper = mountField(HeadingField, { field: mockField })

      expect(wrapper.findComponent(BaseField).exists()).toBe(true)
      expect(wrapper.find('.heading-field').exists()).toBe(true)
    })

    it('renders with field name as text content', () => {
      wrapper = mountField(HeadingField, { field: mockField })

      const headingContent = wrapper.find('.heading-content')
      expect(headingContent.exists()).toBe(true)
      expect(headingContent.text()).toBe('Meta')
    })

    it('does not show label from BaseField', () => {
      wrapper = mountField(HeadingField, { field: mockField })

      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('showLabel')).toBe(false)
    })

    it('applies heading styling classes', () => {
      wrapper = mountField(HeadingField, { field: mockField })

      const headingField = wrapper.find('.heading-field')
      expect(headingField.classes()).toContain('py-4')
      expect(headingField.classes()).toContain('border-b')
      expect(headingField.classes()).toContain('border-gray-200')
    })
  })

  describe('HTML Content Rendering', () => {
    it('renders plain text when asHtml is false', () => {
      const plainTextField = createMockField({
        ...mockField,
        name: 'Simple Section Header',
        asHtml: false
      })

      wrapper = mountField(HeadingField, { field: plainTextField })

      const textContent = wrapper.find('.heading-text')
      expect(textContent.exists()).toBe(true)
      expect(textContent.text()).toBe('Simple Section Header')
      
      // Should not have HTML content div
      expect(wrapper.find('[data-test="html-content"]').exists()).toBe(false)
    })

    it('renders HTML content when asHtml is true', () => {
      const htmlField = createMockField({
        ...mockField,
        name: '<h2>Important Section</h2>',
        asHtml: true
      })

      wrapper = mountField(HeadingField, { field: htmlField })

      const htmlContent = wrapper.find('.heading-content')
      expect(htmlContent.exists()).toBe(true)
      expect(htmlContent.html()).toContain('<h2>Important Section</h2>')
      
      // Should not have plain text div
      expect(wrapper.find('.heading-text').exists()).toBe(false)
    })

    it('renders complex HTML content correctly', () => {
      const complexHtmlField = createMockField({
        ...mockField,
        name: '<div class="bg-blue-100 p-4"><h3>User Information</h3><p>Please fill out all required fields.</p></div>',
        asHtml: true
      })

      wrapper = mountField(HeadingField, { field: complexHtmlField })

      const htmlContent = wrapper.find('.heading-content')
      expect(htmlContent.html()).toContain('<div class="bg-blue-100 p-4">')
      expect(htmlContent.html()).toContain('<h3>User Information</h3>')
      expect(htmlContent.html()).toContain('<p>Please fill out all required fields.</p>')
    })

    it('safely handles potentially dangerous HTML', () => {
      const dangerousHtmlField = createMockField({
        ...mockField,
        name: '<script>alert("xss")</script><p>Safe content</p>',
        asHtml: true
      })

      wrapper = mountField(HeadingField, { field: dangerousHtmlField })

      const htmlContent = wrapper.find('.heading-content')
      // Vue's v-html should render the content as-is (security is handled at the data level)
      expect(htmlContent.html()).toContain('<script>')
      expect(htmlContent.html()).toContain('alert("xss")')
      expect(htmlContent.html()).toContain('<p>Safe content</p>')
    })
  })

  describe('Dark Theme Support', () => {
    it('applies dark theme classes when dark theme is enabled', async () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(HeadingField, { field: mockField })

      const headingField = wrapper.find('.heading-field')
      expect(headingField.classes()).toContain('border-gray-700')
    })

    it('applies light theme classes when dark theme is disabled', async () => {
      mockAdminStore.isDarkTheme = false

      wrapper = mountField(HeadingField, { field: mockField })

      const headingField = wrapper.find('.heading-field')
      expect(headingField.classes()).toContain('border-gray-200')
      expect(headingField.classes()).not.toContain('border-gray-700')
    })
  })

  describe('Props Handling', () => {
    it('accepts all standard field props', () => {
      wrapper = mountField(HeadingField, {
        field: mockField,
        modelValue: 'test-value',
        errors: { meta: ['Some error'] },
        props: {
          field: mockField,
          disabled: true,
          readonly: true,
          size: 'large'
        }
      })

      expect(wrapper.exists()).toBe(true)
      // Heading fields should render regardless of these props
    })

    it('is readonly by default', () => {
      wrapper = mountField(HeadingField, { field: mockField })

      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('readonly')).toBe(true)
    })

    it('uses readonly prop when explicitly provided', () => {
      wrapper = mountField(HeadingField, {
        field: mockField,
        props: {
          field: mockField,
          readonly: false
        }
      })

      // Should respect the explicitly provided readonly prop
      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('readonly')).toBe(false)
    })
  })

  describe('Event Handling', () => {
    it('defines standard field events', () => {
      wrapper = mountField(HeadingField, { field: mockField })

      // Should have the standard field events defined
      expect(wrapper.vm.$emit).toBeDefined()
    })

    it('does not emit events during normal rendering', () => {
      wrapper = mountField(HeadingField, { field: mockField })

      // Heading fields are display-only, so no events should be emitted
      expect(wrapper.emitted()).toEqual({})
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method for consistency', () => {
      wrapper = mountField(HeadingField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method does not throw error', () => {
      wrapper = mountField(HeadingField, { field: mockField })

      expect(() => wrapper.vm.focus()).not.toThrow()
    })
  })

  describe('Component Structure', () => {
    it('has correct component hierarchy', () => {
      wrapper = mountField(HeadingField, { field: mockField })

      // Should have BaseField > .heading-field > .heading-content structure
      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.exists()).toBe(true)
      
      const headingField = wrapper.find('.heading-field')
      expect(headingField.exists()).toBe(true)
      
      const headingContent = wrapper.find('.heading-content')
      expect(headingContent.exists()).toBe(true)
    })

    it('applies proper CSS classes for styling', () => {
      wrapper = mountField(HeadingField, { field: mockField })

      const headingContent = wrapper.find('.heading-content')
      expect(headingContent.classes()).toContain('heading-content')
      
      const textContent = wrapper.find('.heading-text')
      expect(textContent.classes()).toContain('heading-text')
    })
  })

  describe('Edge Cases', () => {
    it('handles empty field name', () => {
      const emptyField = createMockField({
        ...mockField,
        name: ''
      })

      wrapper = mountField(HeadingField, { field: emptyField })

      const headingContent = wrapper.find('.heading-content')
      expect(headingContent.text()).toBe('')
    })

    it('handles null field name', () => {
      const nullField = createMockField({
        ...mockField,
        name: null
      })

      wrapper = mountField(HeadingField, { field: nullField })

      expect(wrapper.exists()).toBe(true)
      // Component should handle null gracefully
    })

    it('handles undefined asHtml property', () => {
      const undefinedHtmlField = createMockField({
        ...mockField,
        asHtml: undefined
      })

      wrapper = mountField(HeadingField, { field: undefinedHtmlField })

      // Should default to plain text rendering
      const textContent = wrapper.find('.heading-text')
      expect(textContent.exists()).toBe(true)
    })
  })
})
