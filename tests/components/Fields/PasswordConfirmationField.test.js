import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import PasswordConfirmationField from '@/components/Fields/PasswordConfirmationField.vue'
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
  EyeIcon: {
    name: 'EyeIcon',
    template: '<svg data-testid="eye-icon"></svg>'
  },
  EyeSlashIcon: {
    name: 'EyeSlashIcon',
    template: '<svg data-testid="eye-slash-icon"></svg>'
  }
}))

describe('PasswordConfirmationField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Confirm Password',
      attribute: 'password_confirmation',
      placeholder: 'Confirm your password',
      type: 'password_confirmation'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders password input element', () => {
      wrapper = mountField(PasswordConfirmationField, { field: mockField })

      const input = wrapper.find('input[type="password"]')
      expect(input.exists()).toBe(true)
      expect(input.attributes('placeholder')).toBe('Confirm your password')
    })

    it('renders with model value', () => {
      wrapper = mountField(PasswordConfirmationField, {
        field: mockField,
        modelValue: 'test123'
      })

      const input = wrapper.find('input[type="password"]')
      expect(input.element.value).toBe('test123')
    })

    it('uses field name as placeholder when no placeholder provided', () => {
      const fieldWithoutPlaceholder = createMockField({
        name: 'Confirm Password',
        attribute: 'password_confirmation'
      })

      wrapper = mountField(PasswordConfirmationField, { field: fieldWithoutPlaceholder })

      const input = wrapper.find('input[type="password"]')
      expect(input.attributes('placeholder')).toBe('Confirm Password')
    })




  })

  describe('Password Visibility Toggle', () => {
    beforeEach(() => {
      wrapper = mountField(PasswordConfirmationField, { field: mockField })
    })

    it('renders visibility toggle button', () => {
      const toggleButton = wrapper.find('button[type="button"]')
      expect(toggleButton.exists()).toBe(true)
    })

    it('shows eye-slash icon initially (password hidden)', () => {
      const eyeSlashIcon = wrapper.find('[data-testid="eye-slash-icon"]')
      expect(eyeSlashIcon.exists()).toBe(true)
    })

    it('toggles password visibility when button is clicked', async () => {
      const toggleButton = wrapper.find('button[type="button"]')
      const input = wrapper.find('input')

      // Initially password type
      expect(input.element.type).toBe('password')

      // Click to show password
      await toggleButton.trigger('click')
      expect(input.element.type).toBe('text')

      // Click to hide password
      await toggleButton.trigger('click')
      expect(input.element.type).toBe('password')
    })

    it('shows correct icon based on visibility state', async () => {
      const toggleButton = wrapper.find('button[type="button"]')

      // Initially shows eye-slash (password hidden)
      expect(wrapper.find('[data-testid="eye-slash-icon"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="eye-icon"]').exists()).toBe(false)

      // After clicking, shows eye (password visible)
      await toggleButton.trigger('click')
      expect(wrapper.find('[data-testid="eye-icon"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="eye-slash-icon"]').exists()).toBe(false)
    })
  })





  describe('Validation States', () => {
    it('applies error styling when errors are present', () => {
      wrapper = mountField(PasswordConfirmationField, {
        field: mockField,
        errors: { password_confirmation: ['Passwords do not match'] }
      })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('border-red-300')
    })

    it('does not apply validation styling when no errors', () => {
      wrapper = mountField(PasswordConfirmationField, {
        field: mockField,
        modelValue: 'somepassword'
      })

      const input = wrapper.find('input')
      expect(input.classes()).not.toContain('border-red-300')
      expect(input.classes()).not.toContain('border-green-300')
    })
  })

  describe('Event Handling', () => {
    beforeEach(() => {
      wrapper = mountField(PasswordConfirmationField, { field: mockField })
    })

    it('emits update:modelValue on input', async () => {
      const input = wrapper.find('input')
      await input.setValue('newpassword')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('newpassword')
      expect(wrapper.emitted('change')[0][0]).toBe('newpassword')
    })

    it('emits focus event', async () => {
      const input = wrapper.find('input')
      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      const input = wrapper.find('input')
      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('handles keydown events without errors', async () => {
      const input = wrapper.find('input')

      // Should not throw errors when keydown events are triggered
      await expect(input.trigger('keydown', { key: 'Enter' })).resolves.not.toThrow()
    })

    it('allows keyboard shortcuts without errors', async () => {
      const input = wrapper.find('input')

      // Should not throw errors when keyboard shortcuts are used
      await expect(input.trigger('keydown', { key: 'a', ctrlKey: true })).resolves.not.toThrow()
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(PasswordConfirmationField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('does not apply dark theme classes when light theme is active', () => {
      mockAdminStore.isDarkTheme = false

      wrapper = mountField(PasswordConfirmationField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).not.toContain('admin-input-dark')
    })

    it('applies dark theme to toggle button icons', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(PasswordConfirmationField, { field: mockField })

      const eyeSlashIcon = wrapper.find('[data-testid="eye-slash-icon"]')
      expect(eyeSlashIcon.classes()).toContain('text-gray-500')
      expect(eyeSlashIcon.classes()).toContain('hover:text-gray-300')
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(PasswordConfirmationField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the input', async () => {
      wrapper = mountField(PasswordConfirmationField, { field: mockField })

      const input = wrapper.find('input')
      const focusSpy = vi.spyOn(input.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Field ID Generation', () => {
    it('generates unique field ID', () => {
      wrapper = mountField(PasswordConfirmationField, { field: mockField })

      const input = wrapper.find('input')
      const id = input.attributes('id')

      expect(id).toMatch(/^password-confirmation-field-password_confirmation-[a-z0-9]{9}$/)
    })

    it('generates different IDs for multiple instances', () => {
      const wrapper1 = mountField(PasswordConfirmationField, { field: mockField })
      const wrapper2 = mountField(PasswordConfirmationField, { field: mockField })

      const id1 = wrapper1.find('input').attributes('id')
      const id2 = wrapper2.find('input').attributes('id')

      expect(id1).not.toBe(id2)

      wrapper1.unmount()
      wrapper2.unmount()
    })
  })
})
