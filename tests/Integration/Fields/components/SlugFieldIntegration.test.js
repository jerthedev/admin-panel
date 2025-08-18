import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import SlugField from '@/components/Fields/SlugField.vue'
import BaseField from '@/components/Fields/BaseField.vue'

/**
 * Slug Field Integration Tests
 *
 * Tests the integration between the PHP Slug field class and Vue component,
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

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({
    props: {
      auth: { user: { id: 1, name: 'Test User' } },
      flash: {}
    }
  })
}))

describe('SlugField Integration', () => {
  let wrapper

  // Helper function to create a field configuration matching PHP field output
  const createFieldConfig = (overrides = {}) => ({
    name: 'URL Slug',
    attribute: 'url_slug',
    component: 'SlugField',
    fromAttribute: 'title',
    separator: '-',
    maxLength: 100,
    lowercase: true,
    uniqueTable: null,
    uniqueColumn: null,
    placeholder: 'Enter slug...',
    helpText: 'URL-friendly version of the title',
    rules: ['required'],
    ...overrides
  })

  // Helper function to mount the component with proper structure
  const mountSlugField = (fieldConfig = {}, props = {}) => {
    const field = createFieldConfig(fieldConfig)
    
    return mount(SlugField, {
      props: {
        field,
        modelValue: '',
        errors: {},
        disabled: false,
        readonly: false,
        size: 'default',
        formData: { title: 'Sample Title' },
        ...props
      },
      global: {
        components: {
          BaseField
        }
      }
    })
  }

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP-Vue Integration', () => {
    it('renders field with PHP field configuration', () => {
      wrapper = mountSlugField()

      const input = wrapper.find('input[type="text"]')
      expect(input.exists()).toBe(true)
      expect(input.attributes('placeholder')).toBe('Enter slug...')
      expect(input.attributes('maxlength')).toBe('100')
    })

    it('integrates fromAttribute configuration from PHP', () => {
      wrapper = mountSlugField({ fromAttribute: 'name' })

      // Should show generate button when fromAttribute is configured
      const generateButton = wrapper.find('button')
      expect(generateButton.exists()).toBe(true)
      expect(generateButton.text()).toContain('Generate')
    })

    it('integrates separator configuration from PHP', async () => {
      wrapper = mountSlugField({ separator: '_' }, { formData: { title: 'Hello World' } })

      const generateButton = wrapper.find('button')
      await generateButton.trigger('click')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('hello_world')
    })

    it('integrates maxLength configuration from PHP', () => {
      wrapper = mountSlugField({ maxLength: 50 }, { modelValue: 'test-slug' })

      const input = wrapper.find('input')
      expect(input.attributes('maxlength')).toBe('50')
      
      // Should show character count when maxLength is set
      expect(wrapper.text()).toContain('9/50 characters')
    })

    it('integrates lowercase configuration from PHP', async () => {
      wrapper = mountSlugField({ lowercase: true }, { formData: { title: 'UPPERCASE TITLE' } })

      const generateButton = wrapper.find('button')
      await generateButton.trigger('click')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('uppercase-title')
    })
  })

  describe('API Data Flow', () => {
    it('handles field meta data from PHP serialization', () => {
      const phpFieldData = {
        name: 'Article Slug',
        attribute: 'article_slug',
        component: 'SlugField',
        fromAttribute: 'title',
        separator: '_',
        maxLength: 75,
        lowercase: true,
        uniqueTable: 'articles',
        uniqueColumn: 'slug',
        helpText: 'SEO-friendly URL slug',
        rules: ['required', 'unique:articles,slug']
      }

      wrapper = mountSlugField(phpFieldData)

      const input = wrapper.find('input')
      expect(input.attributes('maxlength')).toBe('75')
      expect(wrapper.find('button').exists()).toBe(true) // fromAttribute set
    })

    it('emits proper events for PHP backend processing', async () => {
      wrapper = mountSlugField()

      const input = wrapper.find('input')
      await input.setValue('test-slug-value')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('change')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('test-slug-value')
    })

    it('processes form data for auto-generation', async () => {
      wrapper = mountSlugField(
        { fromAttribute: 'title' },
        { formData: { title: 'My Great Article' }, modelValue: '' }
      )

      // Simulate form data change (like from title field)
      await wrapper.setProps({ formData: { title: 'Updated Article Title' } })

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('updated-article-title')
    })
  })

  describe('Nova Compatibility', () => {
    it('follows Nova field structure and behavior', () => {
      wrapper = mountSlugField()

      // Should have BaseField wrapper (Nova pattern)
      expect(wrapper.findComponent(BaseField).exists()).toBe(true)
      
      // Should have proper input structure
      const input = wrapper.find('input[type="text"]')
      expect(input.exists()).toBe(true)
      expect(input.classes()).toContain('admin-input')
    })

    it('handles Nova-style field validation', async () => {
      wrapper = mountSlugField({}, { modelValue: 'invalid slug!' })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('border-red-300')
      expect(wrapper.text()).toContain('Slug can only contain letters, numbers, hyphens, and underscores')
    })

    it('supports Nova field display states', () => {
      // Test disabled state
      wrapper = mountSlugField({}, { disabled: true })
      expect(wrapper.find('input').attributes('disabled')).toBeDefined()
      expect(wrapper.find('button').exists()).toBe(false)

      wrapper.unmount()

      // Test readonly state
      wrapper = mountSlugField({}, { readonly: true })
      expect(wrapper.find('input').attributes('readonly')).toBeDefined()
      expect(wrapper.find('button').exists()).toBe(false)
    })
  })

  describe('Real-world Integration Scenarios', () => {
    it('handles blog post slug generation workflow', async () => {
      wrapper = mountSlugField(
        { fromAttribute: 'title', maxLength: 50 },
        { modelValue: '', formData: { title: 'How to Build Amazing Web Applications' } }
      )

      // Manual generation
      const generateButton = wrapper.find('button')
      await generateButton.trigger('click')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('how-to-build-amazing-web-applications')

      // Test with new title
      await wrapper.setProps({ modelValue: '', formData: { title: 'New Title Here' } })
      await generateButton.trigger('click')

      expect(wrapper.emitted('update:modelValue')[1][0]).toBe('new-title-here')
    })

    it('handles e-commerce product slug workflow', async () => {
      wrapper = mountSlugField(
        { fromAttribute: 'name', separator: '_', maxLength: 30 },
        { formData: { name: 'Premium Wireless Headphones v2.0' } }
      )

      const generateButton = wrapper.find('button')
      await generateButton.trigger('click')

      // Should use underscore separator and handle version numbers
      const generatedSlug = wrapper.emitted('update:modelValue')[0][0]
      expect(generatedSlug).toBe('premium_wireless_headphones_v2')
      expect(generatedSlug.length).toBeLessThanOrEqual(30)
    })

    it('handles user profile slug with validation', async () => {
      wrapper = mountSlugField(
        { uniqueTable: 'users', uniqueColumn: 'username' },
        { modelValue: 'john-doe-123' }
      )

      // Should show as valid
      const input = wrapper.find('input')
      expect(input.classes()).toContain('border-green-300')
      expect(wrapper.text()).toContain('Preview: /john-doe-123')
    })

    it('integrates with form submission data', async () => {
      wrapper = mountSlugField()

      // Simulate user typing
      const input = wrapper.find('input')
      await input.setValue('my-custom-slug')

      // Should emit clean slug value
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('change')).toBeTruthy()
      
      const emittedValue = wrapper.emitted('update:modelValue')[0][0]
      expect(emittedValue).toBe('my-custom-slug')
      expect(emittedValue).toMatch(/^[a-z0-9-_]+$/) // Valid slug pattern
    })
  })

  describe('Error Handling Integration', () => {
    it('displays server validation errors', () => {
      const errors = { url_slug: ['The slug field is required.'] }
      wrapper = mountSlugField({}, { errors })

      expect(wrapper.text()).toContain('The slug field is required.')
    })

    it('handles edge cases gracefully', async () => {
      wrapper = mountSlugField(
        { fromAttribute: 'title' },
        { formData: { title: '' } } // Empty source
      )

      const generateButton = wrapper.find('button')
      await generateButton.trigger('click')

      // Should not emit anything for empty source
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })
  })
})
