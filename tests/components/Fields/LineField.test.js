import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import LineField from '@/components/Fields/LineField.vue'
import BaseField from '@/components/Fields/BaseField.vue'
import { createMockField, mountField } from '../../helpers.js'

/**
 * Line Field Vue Component Tests
 *
 * Tests for LineField Vue component with 100% Nova API compatibility.
 * Tests all Nova Line field features including asSmall(), asHeading(),
 * asSubText(), and proper display functionality.
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

describe('LineField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Status',
      attribute: 'status',
      component: 'LineField',
      value: 'Active',
      asSmall: false,
      asHeading: false,
      asSubText: false,
      asHtml: false,
      isLine: true,
      readonly: true
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders line field with BaseField wrapper', () => {
      wrapper = mountField(LineField, { field: mockField })

      expect(wrapper.findComponent(BaseField).exists()).toBe(true)
      expect(wrapper.find('.line-field').exists()).toBe(true)
    })

    it('renders with field value as text content', () => {
      wrapper = mountField(LineField, { field: mockField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.exists()).toBe(true)
      expect(lineContent.text()).toBe('Active')
    })

    it('falls back to field name when no value', () => {
      const fieldWithoutValue = createMockField({
        name: 'Default Text',
        component: 'LineField',
        value: null
      })

      wrapper = mountField(LineField, { field: fieldWithoutValue })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.text()).toBe('Default Text')
    })

    it('does not show label from BaseField', () => {
      wrapper = mountField(LineField, { field: mockField })

      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('showLabel')).toBe(false)
    })
  })

  describe('Formatting Options', () => {
    it('applies small text formatting', () => {
      const smallField = createMockField({
        name: 'Small Text',
        component: 'LineField',
        value: 'Small content',
        asSmall: true,
        asHeading: false,
        asSubText: false
      })

      wrapper = mountField(LineField, { field: smallField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.classes()).toContain('text-xs')
      expect(lineContent.classes()).toContain('text-gray-600')
    })

    it('applies heading text formatting', () => {
      const headingField = createMockField({
        name: 'Heading Text',
        component: 'LineField',
        value: 'Heading content',
        asSmall: false,
        asHeading: true,
        asSubText: false
      })

      wrapper = mountField(LineField, { field: headingField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.classes()).toContain('text-lg')
      expect(lineContent.classes()).toContain('font-semibold')
    })

    it('applies sub text formatting', () => {
      const subTextField = createMockField({
        name: 'Sub Text',
        component: 'LineField',
        value: 'Sub content',
        asSmall: false,
        asHeading: false,
        asSubText: true
      })

      wrapper = mountField(LineField, { field: subTextField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.classes()).toContain('text-sm')
      expect(lineContent.classes()).toContain('text-gray-700')
    })

    it('applies default text formatting when no specific format', () => {
      wrapper = mountField(LineField, { field: mockField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.classes()).toContain('text-sm')
      expect(lineContent.classes()).toContain('text-gray-900')
    })
  })

  describe('HTML Content', () => {
    it('renders HTML content when asHtml is true', () => {
      const htmlField = createMockField({
        name: 'HTML Content',
        component: 'LineField',
        value: '<strong>Bold Text</strong>',
        asHtml: true
      })

      wrapper = mountField(LineField, { field: htmlField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.html()).toContain('<strong>Bold Text</strong>')
    })

    it('escapes HTML content when asHtml is false', () => {
      const textField = createMockField({
        name: 'Text Content',
        component: 'LineField',
        value: '<strong>Bold Text</strong>',
        asHtml: false
      })

      wrapper = mountField(LineField, { field: textField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.text()).toBe('<strong>Bold Text</strong>')
      expect(lineContent.html()).not.toContain('<strong>Bold Text</strong>')
    })
  })

  describe('Dark Theme Support', () => {
    beforeEach(() => {
      mockAdminStore.isDarkTheme = true
    })

    afterEach(() => {
      mockAdminStore.isDarkTheme = false
    })

    it('applies dark theme classes for default text', () => {
      wrapper = mountField(LineField, { field: mockField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.classes()).toContain('text-gray-100')
    })

    it('applies dark theme classes for small text', () => {
      const smallField = createMockField({
        name: 'Small Text',
        component: 'LineField',
        asSmall: true
      })

      wrapper = mountField(LineField, { field: smallField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.classes()).toContain('text-gray-400')
    })

    it('applies dark theme classes for sub text', () => {
      const subTextField = createMockField({
        name: 'Sub Text',
        component: 'LineField',
        asSubText: true
      })

      wrapper = mountField(LineField, { field: subTextField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.classes()).toContain('text-gray-300')
    })
  })

  describe('Props and State', () => {
    it('handles disabled state', () => {
      wrapper = mountField(LineField, { 
        field: mockField,
        disabled: true
      })

      const lineField = wrapper.find('.line-field')
      expect(lineField.classes()).toContain('opacity-75')
    })

    it('is readonly by default', () => {
      wrapper = mountField(LineField, { field: mockField })

      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('readonly')).toBe(true)
    })

    it('handles different sizes', () => {
      wrapper = mountField(LineField, { 
        field: mockField,
        size: 'large'
      })

      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.props('size')).toBe('large')
    })
  })

  describe('Events and Methods', () => {
    it('does not emit data change events', () => {
      wrapper = mountField(LineField, { field: mockField })

      // Line fields should not emit any data changes
      expect(wrapper.emitted()).toEqual({})
    })

    it('exposes focus method', () => {
      wrapper = mountField(LineField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method does not throw error', () => {
      wrapper = mountField(LineField, { field: mockField })

      expect(() => wrapper.vm.focus()).not.toThrow()
    })
  })

  describe('Component Structure', () => {
    it('has correct component hierarchy', () => {
      wrapper = mountField(LineField, { field: mockField })

      // Should have BaseField > .line-field > .line-content structure
      const baseField = wrapper.findComponent(BaseField)
      expect(baseField.exists()).toBe(true)
      
      const lineField = wrapper.find('.line-field')
      expect(lineField.exists()).toBe(true)
      
      const lineContent = wrapper.find('.line-content')
      expect(lineContent.exists()).toBe(true)
    })

    it('applies proper CSS classes', () => {
      wrapper = mountField(LineField, { field: mockField })

      const lineField = wrapper.find('.line-field')
      expect(lineField.classes()).toContain('py-1')

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.classes()).toContain('text-sm')
      expect(lineContent.classes()).toContain('text-gray-900')
    })
  })

  describe('Edge Cases', () => {
    it('handles empty string value', () => {
      const emptyField = createMockField({
        name: 'Empty Field',
        component: 'LineField',
        value: ''
      })

      wrapper = mountField(LineField, { field: emptyField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.text()).toBe('Empty Field') // Falls back to name
    })

    it('handles null value', () => {
      const nullField = createMockField({
        name: 'Null Field',
        component: 'LineField',
        value: null
      })

      wrapper = mountField(LineField, { field: nullField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.text()).toBe('Null Field') // Falls back to name
    })

    it('handles undefined value', () => {
      const undefinedField = createMockField({
        name: 'Undefined Field',
        component: 'LineField',
        value: undefined
      })

      wrapper = mountField(LineField, { field: undefinedField })

      const lineContent = wrapper.find('.line-content')
      expect(lineContent.text()).toBe('Undefined Field') // Falls back to name
    })
  })
})
