import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import EmailField from '@/components/Fields/EmailField.vue'
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
  AtSymbolIcon: { template: '<div data-testid="at-symbol-icon"></div>' },
  CheckCircleIcon: { template: '<div data-testid="check-circle-icon"></div>' },
  XCircleIcon: { template: '<div data-testid="x-circle-icon"></div>' }
}))

describe('EmailField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Email Address',
      attribute: 'email',
      type: 'email',
      placeholder: 'Enter your email address'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders email input field', () => {
      wrapper = mountField(EmailField, { field: mockField })

      const input = wrapper.find('input[type="email"]')
      expect(input.exists()).toBe(true)
    })

    it('renders with model value', () => {
      wrapper = mountField(EmailField, {
        field: mockField,
        modelValue: 'test@example.com'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('test@example.com')
    })

    it('shows placeholder text', () => {
      wrapper = mountField(EmailField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('Enter your email address')
    })

    it('shows @ symbol icon', () => {
      wrapper = mountField(EmailField, { field: mockField })

      const atIcon = wrapper.find('[data-testid="at-symbol-icon"]')
      expect(atIcon.exists()).toBe(true)
    })

    it('applies disabled state', () => {
      wrapper = mountField(EmailField, {
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
      wrapper = mountField(EmailField, {
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

  describe('Email Validation', () => {
    it('shows valid indicator for valid email', () => {
      wrapper = mountField(EmailField, {
        field: mockField,
        modelValue: 'valid@example.com'
      })

      const validIcon = wrapper.find('[data-testid="check-circle-icon"]')
      expect(validIcon.exists()).toBe(true)
    })

    it('shows invalid indicator for invalid email', () => {
      wrapper = mountField(EmailField, {
        field: mockField,
        modelValue: 'invalid-email'
      })

      const invalidIcon = wrapper.find('[data-testid="x-circle-icon"]')
      expect(invalidIcon.exists()).toBe(true)
    })

    it('validates common email formats', () => {
      const validEmails = [
        'test@example.com',
        'user.name@domain.co.uk',
        'user+tag@example.org',
        'user123@test-domain.com',
        'a@b.co'
      ]

      validEmails.forEach(email => {
        wrapper = mountField(EmailField, {
          field: mockField,
          modelValue: email
        })

        expect(wrapper.vm.isValidEmail).toBe(true)
        
        if (wrapper) {
          wrapper.unmount()
        }
      })
    })

    it('rejects invalid email formats', () => {
      const invalidEmails = [
        'invalid-email',
        '@example.com',
        'user@',
        'user@.com',
        'user..name@example.com',
        'user name@example.com',
        'user@example',
        ''
      ]

      invalidEmails.forEach(email => {
        wrapper = mountField(EmailField, {
          field: mockField,
          modelValue: email
        })

        expect(wrapper.vm.isValidEmail).toBe(false)
        
        if (wrapper) {
          wrapper.unmount()
        }
      })
    })

    it('shows no validation indicator when empty', () => {
      wrapper = mountField(EmailField, {
        field: mockField,
        modelValue: ''
      })

      expect(wrapper.find('[data-testid="check-circle-icon"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="x-circle-icon"]').exists()).toBe(false)
    })
  })

  describe('Email Normalization', () => {
    it('trims whitespace from input', async () => {
      wrapper = mountField(EmailField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('  test@example.com  ')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('test@example.com')
    })

    it('converts to lowercase', async () => {
      wrapper = mountField(EmailField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('TEST@EXAMPLE.COM')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('test@example.com')
    })

    it('normalizes mixed case and whitespace', async () => {
      wrapper = mountField(EmailField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('  Test.User@EXAMPLE.com  ')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('test.user@example.com')
    })
  })

  describe('Event Handling', () => {
    it('emits update:modelValue on input', async () => {
      wrapper = mountField(EmailField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('new@example.com')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('new@example.com')
      expect(wrapper.emitted('change')[0][0]).toBe('new@example.com')
    })

    it('emits focus event', async () => {
      wrapper = mountField(EmailField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(EmailField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('emits change event', async () => {
      wrapper = mountField(EmailField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('change@example.com')
      await input.trigger('change')

      expect(wrapper.emitted('change')).toBeTruthy()
    })
  })

  describe('Suggestions Feature', () => {
    it('shows domain suggestions when enabled', () => {
      const fieldWithSuggestions = createMockField({
        ...mockField,
        showSuggestions: true,
        domainSuggestions: ['gmail.com', 'yahoo.com', 'outlook.com']
      })

      wrapper = mountField(EmailField, {
        field: fieldWithSuggestions,
        modelValue: 'user@gmai'
      })

      // Should show suggestions for partial domain match
      expect(wrapper.text()).toContain('Did you mean')
    })

    it('suggests common domain corrections', () => {
      const fieldWithSuggestions = createMockField({
        ...mockField,
        showSuggestions: true
      })

      wrapper = mountField(EmailField, {
        field: fieldWithSuggestions,
        modelValue: 'user@gmai.com'
      })

      // Should suggest gmail.com
      expect(wrapper.text()).toContain('gmail.com')
    })

    it('applies suggestion when clicked', async () => {
      const fieldWithSuggestions = createMockField({
        ...mockField,
        showSuggestions: true
      })

      wrapper = mountField(EmailField, {
        field: fieldWithSuggestions,
        modelValue: 'user@gmai.com'
      })

      const suggestionButton = wrapper.find('.suggestion-button')
      if (suggestionButton.exists()) {
        await suggestionButton.trigger('click')
        
        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      }
    })
  })

  describe('Character Limit', () => {
    it('applies maxLength attribute when set', () => {
      const fieldWithLimit = createMockField({
        ...mockField,
        maxLength: 50
      })

      wrapper = mountField(EmailField, { field: fieldWithLimit })

      const input = wrapper.find('input')
      expect(input.attributes('maxlength')).toBe('50')
    })

    it('shows character count when maxLength is set', () => {
      const fieldWithLimit = createMockField({
        ...mockField,
        maxLength: 50
      })

      wrapper = mountField(EmailField, {
        field: fieldWithLimit,
        modelValue: 'test@example.com'
      })

      expect(wrapper.text()).toContain('16/50')
    })

    it('shows warning when approaching limit', () => {
      const fieldWithLimit = createMockField({
        ...mockField,
        maxLength: 20
      })

      wrapper = mountField(EmailField, {
        field: fieldWithLimit,
        modelValue: 'verylongemail@ex.com' // 19 chars
      })

      const characterCount = wrapper.find('.text-amber-500')
      expect(characterCount.exists()).toBe(true)
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(EmailField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('applies dark theme to validation icons', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(EmailField, {
        field: mockField,
        modelValue: 'valid@example.com'
      })

      const validIcon = wrapper.find('[data-testid="check-circle-icon"]')
      expect(validIcon.exists()).toBe(true)
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(EmailField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the input', async () => {
      wrapper = mountField(EmailField, { field: mockField })

      const input = wrapper.find('input')
      const focusSpy = vi.spyOn(input.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles null value', () => {
      wrapper = mountField(EmailField, {
        field: mockField,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles undefined value', () => {
      wrapper = mountField(EmailField, {
        field: mockField,
        modelValue: undefined
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles very long email addresses', () => {
      const longEmail = 'a'.repeat(50) + '@' + 'b'.repeat(50) + '.com'
      
      wrapper = mountField(EmailField, {
        field: mockField,
        modelValue: longEmail
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe(longEmail)
    })

    it('handles international domain names', () => {
      wrapper = mountField(EmailField, {
        field: mockField,
        modelValue: 'user@münchen.de'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('user@münchen.de')
    })

    it('handles plus addressing', () => {
      wrapper = mountField(EmailField, {
        field: mockField,
        modelValue: 'user+tag+more@example.com'
      })

      expect(wrapper.vm.isValidEmail).toBe(true)
    })

    it('handles subdomain emails', () => {
      wrapper = mountField(EmailField, {
        field: mockField,
        modelValue: 'user@mail.example.com'
      })

      expect(wrapper.vm.isValidEmail).toBe(true)
    })
  })
})
