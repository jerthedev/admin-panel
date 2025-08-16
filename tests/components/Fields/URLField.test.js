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
  LinkIcon: { template: '<div data-testid="link-icon"></div>' },
  ArrowTopRightOnSquareIcon: { template: '<div data-testid="external-link-icon"></div>' },
  ClipboardIcon: { template: '<div data-testid="clipboard-icon"></div>' },
  CheckCircleIcon: { template: '<div data-testid="check-circle-icon"></div>' },
  XCircleIcon: { template: '<div data-testid="x-circle-icon"></div>' },
  ExclamationTriangleIcon: { template: '<div data-testid="exclamation-triangle-icon"></div>' }
}))

describe('URLField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Website URL',
      attribute: 'website_url',
      type: 'url',
      placeholder: 'https://example.com',
      clickable: true,
      target: '_blank'
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

  describe('URL Validation', () => {
    it('shows valid indicator for valid URL', async () => {
      const fieldWithValidation = createMockField({
        ...mockField,
        validateUrl: true
      })

      wrapper = mountField(URLField, {
        field: fieldWithValidation,
        modelValue: 'https://example.com'
      })

      await nextTick()

      const validIcon = wrapper.find('[data-testid="check-circle-icon"]')
      expect(validIcon.exists()).toBe(true)
    })

    it('shows invalid indicator for invalid URL', async () => {
      const fieldWithValidation = createMockField({
        ...mockField,
        validateUrl: true
      })

      wrapper = mountField(URLField, {
        field: fieldWithValidation,
        modelValue: 'http://'
      })

      await nextTick()

      const invalidIcon = wrapper.find('[data-testid="x-circle-icon"]')
      expect(invalidIcon.exists()).toBe(true)
    })

    it('validates common URL formats', () => {
      const validUrls = [
        'https://example.com',
        'http://test.org',
        'https://sub.domain.co.uk',
        'https://example.com/path',
        'https://example.com/path?query=value',
        'https://example.com:8080',
        'ftp://files.example.com'
      ]

      validUrls.forEach(url => {
        const fieldWithValidation = createMockField({
          ...mockField,
          validateUrl: true
        })

        wrapper = mountField(URLField, {
          field: fieldWithValidation,
          modelValue: url
        })

        expect(wrapper.vm.validationStatus).toBe('valid')

        if (wrapper) {
          wrapper.unmount()
        }
      })
    })

    it('rejects invalid URL formats', () => {
      const invalidUrls = [
        'http://',
        'https://',
        'ftp://'
      ]

      invalidUrls.forEach(url => {
        const fieldWithValidation = createMockField({
          ...mockField,
          validateUrl: true
        })

        wrapper = mountField(URLField, {
          field: fieldWithValidation,
          modelValue: url
        })

        expect(wrapper.vm.validationStatus).toBe('invalid')

        if (wrapper) {
          wrapper.unmount()
        }
      })
    })
  })

  describe('URL Normalization', () => {
    it('normalizes URL for display when normalizeProtocol is enabled', async () => {
      const normalizingField = createMockField({
        ...mockField,
        normalizeProtocol: true
      })

      wrapper = mountField(URLField, {
        field: normalizingField,
        modelValue: 'example.com'
      })

      // The input should emit the raw value
      const input = wrapper.find('input')
      await input.setValue('example.com')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('example.com')

      // But the normalized URL should be available for display
      expect(wrapper.vm.normalizedUrl).toBe('https://example.com')
    })

    it('does not add protocol when already present', async () => {
      const normalizingField = createMockField({
        ...mockField,
        normalizeProtocol: true
      })

      wrapper = mountField(URLField, { field: normalizingField })

      const input = wrapper.find('input')
      await input.setValue('http://example.com')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('http://example.com')
    })

    it('trims whitespace from input', async () => {
      wrapper = mountField(URLField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('  https://example.com  ')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('https://example.com')
    })
  })

  describe('Clickable Links', () => {
    it('shows clickable link when URL is valid and clickable is true', () => {
      wrapper = mountField(URLField, {
        field: mockField,
        modelValue: 'https://example.com'
      })

      const link = wrapper.find('a[href="https://example.com"]')
      expect(link.exists()).toBe(true)
    })

    it('shows external link icon when target is _blank', () => {
      wrapper = mountField(URLField, {
        field: mockField,
        modelValue: 'https://example.com'
      })

      const externalIcon = wrapper.find('[data-testid="external-link-icon"]')
      expect(externalIcon.exists()).toBe(true)
    })

    it('does not show clickable link when clickable is false', () => {
      const nonClickableField = createMockField({
        ...mockField,
        clickable: false
      })

      wrapper = mountField(URLField, {
        field: nonClickableField,
        modelValue: 'https://example.com'
      })

      expect(wrapper.find('a').exists()).toBe(false)
    })

    it('does not show clickable link in readonly mode', () => {
      wrapper = mountField(URLField, {
        field: mockField,
        modelValue: 'https://example.com',
        props: {
          field: mockField,
          readonly: true
        }
      })

      expect(wrapper.find('a').exists()).toBe(false)
    })

    it('allows link to be clicked', async () => {
      wrapper = mountField(URLField, {
        field: mockField,
        modelValue: 'https://example.com'
      })

      const link = wrapper.find('a')
      await link.trigger('click')

      // The component doesn't emit a custom event, just allows default link behavior
      expect(link.attributes('href')).toBe('https://example.com')
    })
  })

  describe('URL Preview', () => {
    it('shows URL preview when value exists', () => {
      wrapper = mountField(URLField, {
        field: mockField,
        modelValue: 'https://example.com/very/long/path'
      })

      expect(wrapper.text()).toContain('example.com')
    })

    it('truncates long URLs in preview', () => {
      const longUrl = 'https://example.com/' + 'a'.repeat(100)

      wrapper = mountField(URLField, {
        field: mockField,
        modelValue: longUrl
      })

      // Should show truncated version
      expect(wrapper.vm.linkText.length).toBeLessThan(longUrl.length)
    })

    it('does not show preview when showPreview is false', () => {
      const fieldWithoutPreview = createMockField({
        ...mockField,
        clickable: false,
        showPreview: false
      })

      wrapper = mountField(URLField, {
        field: fieldWithoutPreview,
        modelValue: 'https://example.com'
      })

      expect(wrapper.find('.url-preview').exists()).toBe(false)
    })
  })

  describe('Event Handling', () => {
    it('emits update:modelValue on input', async () => {
      wrapper = mountField(URLField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('https://new-site.com')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('https://new-site.com')
      expect(wrapper.emitted('change')[0][0]).toBe('https://new-site.com')
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

  describe('Character Limit', () => {
    it('applies maxLength attribute when set', () => {
      const fieldWithLimit = createMockField({
        ...mockField,
        maxLength: 100
      })

      wrapper = mountField(URLField, { field: fieldWithLimit })

      const input = wrapper.find('input')
      expect(input.attributes('maxlength')).toBe('100')
    })

    it('shows character count when maxLength is set', () => {
      const fieldWithLimit = createMockField({
        ...mockField,
        maxLength: 100
      })

      wrapper = mountField(URLField, {
        field: fieldWithLimit,
        modelValue: 'https://example.com'
      })

      expect(wrapper.text()).toContain('19 / 100')
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(URLField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('applies dark theme to clickable links', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(URLField, {
        field: mockField,
        modelValue: 'https://example.com'
      })

      const link = wrapper.find('a')
      expect(link.classes()).toContain('text-blue-400')
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

    it('handles URLs with special characters', () => {
      const specialUrl = 'https://example.com/path?query=value&other=test#anchor'
      const fieldWithValidation = createMockField({
        ...mockField,
        validateUrl: true
      })

      wrapper = mountField(URLField, {
        field: fieldWithValidation,
        modelValue: specialUrl
      })

      expect(wrapper.vm.validationStatus).toBe('valid')
    })

    it('handles international domain names', () => {
      const fieldWithValidation = createMockField({
        ...mockField,
        validateUrl: true
      })

      wrapper = mountField(URLField, {
        field: fieldWithValidation,
        modelValue: 'https://münchen.de'
      })

      expect(wrapper.vm.validationStatus).toBe('valid')
    })

    it('handles localhost URLs', () => {
      const fieldWithValidation = createMockField({
        ...mockField,
        validateUrl: true
      })

      wrapper = mountField(URLField, {
        field: fieldWithValidation,
        modelValue: 'http://localhost:3000'
      })

      expect(wrapper.vm.validationStatus).toBe('valid')
    })

    it('handles IP address URLs', () => {
      const fieldWithValidation = createMockField({
        ...mockField,
        validateUrl: true
      })

      wrapper = mountField(URLField, {
        field: fieldWithValidation,
        modelValue: 'http://192.168.1.1:8080'
      })

      expect(wrapper.vm.validationStatus).toBe('valid')
    })
  })
})
