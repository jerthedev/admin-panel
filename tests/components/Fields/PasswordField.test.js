import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import PasswordField from '@/components/Fields/PasswordField.vue'
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
  EyeIcon: { template: '<div data-testid="eye-icon"></div>' },
  EyeSlashIcon: { template: '<div data-testid="eye-slash-icon"></div>' },
  LockClosedIcon: { template: '<div data-testid="lock-closed-icon"></div>' }
}))

describe('PasswordField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Password',
      attribute: 'password',
      type: 'password',
      placeholder: 'Enter your password',
      minLength: 8,
      maxLength: 128,
      showStrengthMeter: true,
      showToggle: true
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders password input field', () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const input = wrapper.find('input[type="password"]')
      expect(input.exists()).toBe(true)
    })

    it('renders with model value', () => {
      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: 'secretpassword'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('secretpassword')
    })

    it('shows placeholder text', () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('Enter your password')
    })

    it('shows lock icon', () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const lockIcon = wrapper.find('[data-testid="lock-closed-icon"]')
      expect(lockIcon.exists()).toBe(true)
    })

    it('applies disabled state', () => {
      wrapper = mountField(PasswordField, {
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
      wrapper = mountField(PasswordField, {
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

  describe('Password Visibility Toggle', () => {
    it('shows toggle button when showToggle is true', () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const toggleButton = wrapper.find('[data-testid="eye-icon"]')
      expect(toggleButton.exists()).toBe(true)
    })

    it('hides toggle button when showToggle is false', () => {
      const fieldWithoutToggle = createMockField({
        ...mockField,
        showToggle: false
      })

      wrapper = mountField(PasswordField, { field: fieldWithoutToggle })

      expect(wrapper.find('[data-testid="eye-icon"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="eye-slash-icon"]').exists()).toBe(false)
    })

    it('toggles password visibility on button click', async () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const toggleButton = wrapper.find('[data-testid="eye-icon"]').element.parentElement
      await toggleButton.click()

      const input = wrapper.find('input')
      expect(input.attributes('type')).toBe('text')

      const eyeSlashIcon = wrapper.find('[data-testid="eye-slash-icon"]')
      expect(eyeSlashIcon.exists()).toBe(true)
    })

    it('toggles back to password type', async () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const toggleButton = wrapper.find('[data-testid="eye-icon"]').element.parentElement
      
      // Toggle to text
      await toggleButton.click()
      expect(wrapper.find('input').attributes('type')).toBe('text')

      // Toggle back to password
      const hideButton = wrapper.find('[data-testid="eye-slash-icon"]').element.parentElement
      await hideButton.click()
      
      expect(wrapper.find('input').attributes('type')).toBe('password')
    })
  })

  describe('Password Strength Meter', () => {
    it('shows strength meter when showStrengthMeter is true', () => {
      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: 'password123'
      })

      expect(wrapper.find('.strength-meter').exists()).toBe(true)
    })

    it('hides strength meter when showStrengthMeter is false', () => {
      const fieldWithoutMeter = createMockField({
        ...mockField,
        showStrengthMeter: false
      })

      wrapper = mountField(PasswordField, {
        field: fieldWithoutMeter,
        modelValue: 'password123'
      })

      expect(wrapper.find('.strength-meter').exists()).toBe(false)
    })

    it('shows weak strength for simple passwords', () => {
      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: '123456'
      })

      expect(wrapper.text()).toContain('Weak')
      expect(wrapper.find('.bg-red-500').exists()).toBe(true)
    })

    it('shows medium strength for moderate passwords', () => {
      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: 'password123'
      })

      expect(wrapper.text()).toContain('Medium')
      expect(wrapper.find('.bg-yellow-500').exists()).toBe(true)
    })

    it('shows strong strength for complex passwords', () => {
      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: 'MyStr0ng!P@ssw0rd'
      })

      expect(wrapper.text()).toContain('Strong')
      expect(wrapper.find('.bg-green-500').exists()).toBe(true)
    })

    it('does not show strength meter for empty password', () => {
      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: ''
      })

      expect(wrapper.find('.strength-meter').exists()).toBe(false)
    })
  })

  describe('Length Validation', () => {
    it('applies minlength attribute', () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('minlength')).toBe('8')
    })

    it('applies maxlength attribute', () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.attributes('maxlength')).toBe('128')
    })

    it('shows character count when maxLength is set', () => {
      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: 'password123'
      })

      expect(wrapper.text()).toContain('11/128')
    })

    it('shows warning when approaching max length', () => {
      const shortLimitField = createMockField({
        ...mockField,
        maxLength: 15
      })

      wrapper = mountField(PasswordField, {
        field: shortLimitField,
        modelValue: 'verylongpassword' // 15 chars
      })

      const characterCount = wrapper.find('.text-red-500')
      expect(characterCount.exists()).toBe(true)
    })
  })

  describe('Password Requirements', () => {
    it('shows requirements when specified', () => {
      const fieldWithRequirements = createMockField({
        ...mockField,
        requirements: [
          'At least 8 characters',
          'One uppercase letter',
          'One lowercase letter',
          'One number',
          'One special character'
        ]
      })

      wrapper = mountField(PasswordField, { field: fieldWithRequirements })

      expect(wrapper.text()).toContain('At least 8 characters')
      expect(wrapper.text()).toContain('One uppercase letter')
    })

    it('marks requirements as met when password satisfies them', () => {
      const fieldWithRequirements = createMockField({
        ...mockField,
        requirements: ['At least 8 characters']
      })

      wrapper = mountField(PasswordField, {
        field: fieldWithRequirements,
        modelValue: 'password123'
      })

      const metRequirement = wrapper.find('.text-green-600')
      expect(metRequirement.exists()).toBe(true)
    })

    it('marks requirements as unmet when password does not satisfy them', () => {
      const fieldWithRequirements = createMockField({
        ...mockField,
        requirements: ['At least 8 characters']
      })

      wrapper = mountField(PasswordField, {
        field: fieldWithRequirements,
        modelValue: '123'
      })

      const unmetRequirement = wrapper.find('.text-red-600')
      expect(unmetRequirement.exists()).toBe(true)
    })
  })

  describe('Event Handling', () => {
    it('emits update:modelValue on input', async () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('newpassword')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('newpassword')
      expect(wrapper.emitted('change')[0][0]).toBe('newpassword')
    })

    it('emits focus event', async () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('emits change event', async () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('changedpassword')
      await input.trigger('change')

      expect(wrapper.emitted('change')).toBeTruthy()
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(PasswordField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('applies dark theme to strength meter', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: 'password123'
      })

      const strengthMeter = wrapper.find('.strength-meter')
      expect(strengthMeter.exists()).toBe(true)
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(PasswordField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the input', async () => {
      wrapper = mountField(PasswordField, { field: mockField })

      const input = wrapper.find('input')
      const focusSpy = vi.spyOn(input.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles null value', () => {
      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: null
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles undefined value', () => {
      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: undefined
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles very long passwords', () => {
      const longPassword = 'a'.repeat(200)
      
      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: longPassword
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe(longPassword.substring(0, 128)) // Truncated to maxLength
    })

    it('handles special characters in passwords', () => {
      const specialPassword = '!@#$%^&*()_+-=[]{}|;:,.<>?'
      
      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: specialPassword
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe(specialPassword)
    })

    it('handles unicode characters in passwords', () => {
      const unicodePassword = 'pássw0rd🔒'
      
      wrapper = mountField(PasswordField, {
        field: mockField,
        modelValue: unicodePassword
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe(unicodePassword)
    })
  })
})
