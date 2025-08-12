import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import SlugField from '@/components/Fields/SlugField.vue'
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
  ArrowPathIcon: { template: '<div data-testid="arrow-path-icon"></div>' }
}))

describe('SlugField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Slug',
      attribute: 'slug',
      type: 'slug',
      maxLength: 100,
      fromAttribute: 'title'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders text input field', () => {
      wrapper = mountField(SlugField, { field: mockField })

      const input = wrapper.find('input[type="text"]')
      expect(input.exists()).toBe(true)
    })

    it('renders with model value', () => {
      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: 'test-slug'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('test-slug')
    })

    it('applies maxLength attribute', () => {
      wrapper = mountField(SlugField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('maxlength')).toBe('100')
    })

    it('uses default placeholder when none provided', () => {
      wrapper = mountField(SlugField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('Enter slug...')
    })

    it('uses custom placeholder when provided', () => {
      const fieldWithPlaceholder = createMockField({
        ...mockField,
        placeholder: 'Custom slug placeholder'
      })

      wrapper = mountField(SlugField, { field: fieldWithPlaceholder })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('Custom slug placeholder')
    })

    it('applies disabled state', () => {
      wrapper = mountField(SlugField, {
        field: mockField,
        props: { 
          field: mockField,
          disabled: true 
        }
      })

      const input = wrapper.find('input')
      expect(input.element.disabled).toBe(true)
    })

    it('applies readonly state', () => {
      wrapper = mountField(SlugField, {
        field: mockField,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      const input = wrapper.find('input')
      expect(input.element.readOnly).toBe(true)
    })
  })

  describe('Slug Generation', () => {
    it('shows generate button when fromAttribute is set', () => {
      wrapper = mountField(SlugField, { field: mockField })

      const generateButton = wrapper.find('[data-testid="arrow-path-icon"]').element.parentElement
      expect(generateButton).toBeTruthy()
    })

    it('hides generate button when fromAttribute is not set', () => {
      const fieldWithoutFrom = createMockField({
        ...mockField,
        fromAttribute: undefined
      })

      wrapper = mountField(SlugField, { field: fieldWithoutFrom })

      expect(wrapper.find('[data-testid="arrow-path-icon"]').exists()).toBe(false)
    })

    it('generates slug from form data when button clicked', async () => {
      const formData = { title: 'Hello World Test' }

      wrapper = mountField(SlugField, {
        field: mockField,
        props: {
          field: mockField,
          formData: formData
        }
      })

      const generateButton = wrapper.find('[data-testid="arrow-path-icon"]').element.parentElement
      await generateButton.click()

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('hello-world-test')
      expect(wrapper.emitted('change')[0][0]).toBe('hello-world-test')
    })

    it('handles special characters in slug generation', async () => {
      const formData = { title: 'Hello & World! @#$%' }

      wrapper = mountField(SlugField, {
        field: mockField,
        props: {
          field: mockField,
          formData: formData
        }
      })

      const generateButton = wrapper.find('[data-testid="arrow-path-icon"]').element.parentElement
      await generateButton.click()

      const emittedValue = wrapper.emitted('update:modelValue')[0][0]
      expect(emittedValue).toMatch(/^[a-z0-9-]+$/) // Should only contain lowercase letters, numbers, and hyphens
    })

    it('does not generate when source field is empty', async () => {
      const formData = { title: '' }

      wrapper = mountField(SlugField, {
        field: mockField,
        props: {
          field: mockField,
          formData: formData
        }
      })

      const generateButton = wrapper.find('[data-testid="arrow-path-icon"]').element.parentElement
      await generateButton.click()

      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })
  })

  describe('Slug Validation', () => {
    it('shows green border for valid slug', () => {
      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: 'valid-slug-123'
      })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('border-green-300')
    })

    it('shows red border for invalid slug', () => {
      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: 'Invalid Slug!'
      })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('border-red-300')
    })

    it('shows validation message for invalid slug', () => {
      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: 'Invalid Slug!'
      })

      expect(wrapper.text()).toContain('Slug can only contain letters, numbers, hyphens, and underscores')
    })

    it('shows length warning when over limit', () => {
      const longSlug = 'a'.repeat(101) // Over the 100 character limit

      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: longSlug
      })

      expect(wrapper.text()).toContain('Slug is too long (maximum 100 characters)')
    })

    it('accepts valid slug patterns', () => {
      const validSlugs = [
        'simple-slug',
        'slug_with_underscores',
        'slug123',
        'a-b-c-d-e',
        'test_slug_123'
      ]

      validSlugs.forEach(slug => {
        wrapper = mountField(SlugField, {
          field: mockField,
          modelValue: slug
        })

        const input = wrapper.find('input')
        expect(input.classes()).toContain('border-green-300')
        
        if (wrapper) {
          wrapper.unmount()
        }
      })
    })

    it('rejects invalid slug patterns', () => {
      const invalidSlugs = [
        'slug with spaces',
        'slug!@#$%',
        'UPPERCASE',
        'slug.',
        '-starting-with-dash',
        'ending-with-dash-',
        'double--dash'
      ]

      invalidSlugs.forEach(slug => {
        wrapper = mountField(SlugField, {
          field: mockField,
          modelValue: slug
        })

        const input = wrapper.find('input')
        expect(input.classes()).toContain('border-red-300')
        
        if (wrapper) {
          wrapper.unmount()
        }
      })
    })
  })

  describe('Character Count and Limits', () => {
    it('shows character count when maxLength is set', () => {
      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: 'test-slug'
      })

      expect(wrapper.text()).toContain('9/100')
    })

    it('shows character count in red when over limit', () => {
      const longSlug = 'a'.repeat(101)

      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: longSlug
      })

      const characterCount = wrapper.find('.text-red-500')
      expect(characterCount.exists()).toBe(true)
      expect(characterCount.text()).toContain('101/100')
    })

    it('does not show character count when maxLength is not set', () => {
      const fieldWithoutLimit = createMockField({
        ...mockField,
        maxLength: undefined
      })

      wrapper = mountField(SlugField, {
        field: fieldWithoutLimit,
        modelValue: 'test-slug'
      })

      expect(wrapper.text()).not.toContain('/')
    })
  })

  describe('Slug Preview', () => {
    it('shows slug preview when value exists and is valid', () => {
      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: 'test-slug'
      })

      expect(wrapper.text()).toContain('/test-slug')
    })

    it('does not show preview for invalid slug', () => {
      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: 'Invalid Slug!'
      })

      expect(wrapper.text()).not.toContain('/Invalid Slug!')
    })

    it('does not show preview when value is empty', () => {
      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: ''
      })

      expect(wrapper.text()).not.toContain('/')
    })
  })

  describe('Event Handling', () => {
    it('emits update:modelValue on input', async () => {
      wrapper = mountField(SlugField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('new-slug')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('new-slug')
      expect(wrapper.emitted('change')[0][0]).toBe('new-slug')
    })

    it('emits focus event', async () => {
      wrapper = mountField(SlugField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(SlugField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('prevents invalid characters on keydown', async () => {
      wrapper = mountField(SlugField, { field: mockField })

      const input = wrapper.find('input')
      const preventDefaultSpy = vi.fn()

      await input.trigger('keydown', { 
        key: ' ', 
        preventDefault: preventDefaultSpy 
      })

      expect(preventDefaultSpy).toHaveBeenCalled()
    })

    it('allows valid characters on keydown', async () => {
      wrapper = mountField(SlugField, { field: mockField })

      const input = wrapper.find('input')
      const preventDefaultSpy = vi.fn()

      await input.trigger('keydown', { 
        key: 'a', 
        preventDefault: preventDefaultSpy 
      })

      expect(preventDefaultSpy).not.toHaveBeenCalled()
    })
  })

  describe('Auto-generation from Source Field', () => {
    it('auto-generates slug when source field changes and current slug is empty', async () => {
      const formData = { title: '' }

      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: '',
        props: {
          field: mockField,
          formData: formData
        }
      })

      // Simulate source field change
      formData.title = 'New Title'
      await wrapper.setProps({ formData: { ...formData } })

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('new-title')
    })

    it('does not auto-generate when slug already exists', async () => {
      const formData = { title: '' }

      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: 'existing-slug',
        props: {
          field: mockField,
          formData: formData
        }
      })

      // Simulate source field change
      formData.title = 'New Title'
      await wrapper.setProps({ formData: { ...formData } })

      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(SlugField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('applies dark theme to validation message', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(SlugField, {
        field: mockField,
        modelValue: 'Invalid Slug!'
      })

      const validationMessage = wrapper.find('.text-red-400')
      expect(validationMessage.exists()).toBe(true)
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(SlugField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the input', async () => {
      wrapper = mountField(SlugField, { field: mockField })

      const input = wrapper.find('input')
      const focusSpy = vi.spyOn(input.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })
})
