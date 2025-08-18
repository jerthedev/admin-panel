import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import URLField from '@/components/Fields/URLField.vue'
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
  LinkIcon: { template: '<div data-testid="link-icon"></div>' }
}))

describe('URLField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Website URL',
      attribute: 'website_url',
      type: 'url',
      placeholder: 'https://example.com'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders URL input field', () => {
      wrapper = mountField(URLField, { field: mockField })

      const input = wrapper.find('input[type="url"]')
      expect(input.exists()).toBe(true)
    })

    it('renders with model value', () => {
      wrapper = mountField(URLField, {
        field: mockField,
        modelValue: 'https://example.com'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('https://example.com')
    })

    it('shows placeholder text', () => {
      wrapper = mountField(URLField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('https://example.com')
    })

    it('shows link icon', () => {
      wrapper = mountField(URLField, { field: mockField })

      const linkIcon = wrapper.find('[data-testid="link-icon"]')
      expect(linkIcon.exists()).toBe(true)
    })

    it('applies disabled state', () => {
      wrapper = mountField(URLField, {
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
      wrapper = mountField(URLField, {
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

  describe('Event Handling', () => {
    it('emits update:modelValue on input', async () => {
      wrapper = mountField(URLField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('https://new-site.com')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('https://new-site.com')
    })

    it('emits focus event', async () => {
      wrapper = mountField(URLField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(URLField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('emits change event', async () => {
      wrapper = mountField(URLField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('https://changed.com')
      await input.trigger('change')

      expect(wrapper.emitted('change')).toBeTruthy()
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(URLField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    afterEach(() => {
      mockAdminStore.isDarkTheme = false
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(URLField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the input', async () => {
      wrapper = mountField(URLField, { field: mockField })

      const input = wrapper.find('input')
      const focusSpy = vi.spyOn(input.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles null value', () => {
      wrapper = mountField(URLField, {
        field: mockField,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles undefined value', () => {
      wrapper = mountField(URLField, {
        field: mockField,
        modelValue: undefined
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles very long URLs', () => {
      const longUrl = 'https://example.com/' + 'a'.repeat(1000)

      wrapper = mountField(URLField, {
        field: mockField,
        modelValue: longUrl
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe(longUrl)
    })
  })
})
