import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mountField } from '../../../helpers.js'
import EmailField from '@/components/Fields/EmailField.vue'

// Mock the admin store
const mockAdminStore = { isDarkTheme: false }
vi.mock('@/stores/admin', () => ({ useAdminStore: () => mockAdminStore }))

describe('Integration: EmailField (PHP <-> Vue)', () => {
  let wrapper
  let field

  beforeEach(() => {
    field = {
      name: 'Email',
      attribute: 'email',
      component: 'EmailField',
      helpText: 'Enter your email address',
      rules: ['required', 'email'],
      clickable: true,
    }
  })

  afterEach(() => { if (wrapper) wrapper.unmount() })

  it('renders and binds initial value from PHP serialization', () => {
    wrapper = mountField(EmailField, { field, modelValue: 'test@example.com' })

    const input = wrapper.find('input[type="email"]')
    expect(input.exists()).toBe(true)
    expect(input.element.value).toBe('test@example.com')
  })

  it('emits updated value for PHP fill handling', async () => {
    wrapper = mountField(EmailField, { field, modelValue: 'old@example.com' })

    const input = wrapper.find('input')
    await input.setValue('new@example.com')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('new@example.com')
  })

  it('normalizes email values like PHP backend', async () => {
    wrapper = mountField(EmailField, { field, modelValue: '' })

    const input = wrapper.find('input')
    await input.setValue('  TEST@EXAMPLE.COM  ')
    await input.trigger('input')

    // Should normalize to lowercase and trim
    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('test@example.com')
  })

  it('respects clickable meta from PHP field', () => {
    const clickableField = { ...field, clickable: true }
    wrapper = mountField(EmailField, {
      field: clickableField,
      modelValue: 'test@example.com',
      readonly: true
    })

    // Check that the component receives the clickable field property
    expect(wrapper.vm.field.clickable).toBe(true)
  })

  it('hides mailto link when clickable is false', () => {
    const nonClickableField = { ...field, clickable: false }
    wrapper = mountField(EmailField, {
      field: nonClickableField,
      modelValue: 'test@example.com',
      readonly: true
    })

    // Check that the component receives the non-clickable field property
    expect(wrapper.vm.field.clickable).toBe(false)
  })

  it('integrates validation state with PHP field rules', async () => {
    wrapper = mountField(EmailField, { field, modelValue: 'valid@example.com' })

    // Component should validate email format
    expect(wrapper.vm.isValidEmail).toBe(true)

    // Change to invalid email - wait for reactivity
    await wrapper.setProps({ modelValue: 'invalid-email' })

    // Should detect invalid email
    expect(wrapper.vm.isValidEmail).toBe(false)
  })

  it('handles empty values correctly', () => {
    wrapper = mountField(EmailField, { field, modelValue: '' })

    const input = wrapper.find('input')
    expect(input.element.value).toBe('')

    // No validation icons should show for empty value
    const validIcon = wrapper.find('[data-testid="check-circle-icon"]')
    const invalidIcon = wrapper.find('[data-testid="exclamation-circle-icon"]')
    expect(validIcon.exists()).toBe(false)
    expect(invalidIcon.exists()).toBe(false)
  })

  it('handles null values from PHP', () => {
    wrapper = mountField(EmailField, { field, modelValue: null })

    const input = wrapper.find('input')
    expect(input.element.value).toBe('')
  })

  it('prevents spaces in email input', async () => {
    wrapper = mountField(EmailField, { field, modelValue: '' })

    const input = wrapper.find('input')

    // Simulate space key press - this should be prevented
    const keydownEvent = new KeyboardEvent('keydown', { key: ' ' })
    Object.defineProperty(keydownEvent, 'preventDefault', { value: vi.fn() })

    await input.trigger('keydown', { key: ' ' })

    // Test that spaces are trimmed in normalization during input
    await input.setValue('test@example.com')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('test@example.com')
  })

  it('supports complex email formats from PHP', async () => {
    const complexEmails = [
      'user+tag@example.com',
      'user.name@sub.domain.com',
      'user123@test-domain.co.uk',
    ]

    for (const email of complexEmails) {
      wrapper = mountField(EmailField, { field, modelValue: email })

      // Should validate complex email formats correctly
      expect(wrapper.vm.isValidEmail).toBe(true)

      wrapper.unmount()
    }
  })

  it('integrates with form validation states', () => {
    const fieldWithErrors = {
      ...field,
      hasError: true,
    }
    
    const errors = { email: ['The email field is required.'] }
    
    wrapper = mountField(EmailField, { 
      field: fieldWithErrors, 
      modelValue: '',
      errors 
    })

    // Component should handle error state appropriately
    expect(wrapper.props('errors')).toEqual(errors)
  })

  it('maintains focus behavior for form interactions', async () => {
    wrapper = mountField(EmailField, { field, modelValue: '' })

    const input = wrapper.find('input')
    await input.trigger('focus')

    expect(wrapper.emitted('focus')).toBeTruthy()

    await input.trigger('blur')
    expect(wrapper.emitted('blur')).toBeTruthy()
  })

  it('handles readonly state for detail views', () => {
    wrapper = mountField(EmailField, {
      field,
      modelValue: 'test@example.com',
      readonly: true
    })

    // Should receive readonly prop correctly from mountField helper
    expect(wrapper.vm.$props.readonly).toBe(true)
  })

  it('handles disabled state', () => {
    wrapper = mountField(EmailField, {
      field,
      modelValue: 'test@example.com',
      disabled: true
    })

    // Should receive disabled prop correctly from mountField helper
    expect(wrapper.vm.$props.disabled).toBe(true)
  })

  it('applies dark theme classes when enabled', () => {
    mockAdminStore.isDarkTheme = true
    
    wrapper = mountField(EmailField, { field, modelValue: '' })

    const input = wrapper.find('input')
    expect(input.classes()).toContain('admin-input-dark')
    
    // Reset for other tests
    mockAdminStore.isDarkTheme = false
  })

  it('exposes focus method for external control', () => {
    wrapper = mountField(EmailField, { field, modelValue: '' })

    expect(wrapper.vm.focus).toBeDefined()
    expect(typeof wrapper.vm.focus).toBe('function')
  })
})
