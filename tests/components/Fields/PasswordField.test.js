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

describe('PasswordField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Password',
      attribute: 'password',
      component: 'PasswordField',
      placeholder: 'Enter password'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  it('renders password input field', () => {
    wrapper = mountField(PasswordField, { field: mockField })
    expect(wrapper.find('input[type="password"]').exists()).toBe(true)
  })

  it('displays field name as placeholder when no placeholder provided', () => {
    const field = createMockField({ ...mockField, placeholder: undefined })
    wrapper = mountField(PasswordField, { field })

    expect(wrapper.find('input').attributes('placeholder')).toBe('Password')
  })

  it('displays custom placeholder when provided', () => {
    wrapper = mountField(PasswordField, { field: mockField })
    expect(wrapper.find('input').attributes('placeholder')).toBe('Enter password')
  })

  it('emits update:modelValue when input changes', async () => {
    wrapper = mountField(PasswordField, { field: mockField })
    const input = wrapper.find('input[type="password"]')
    await input.setValue('newpassword')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['newpassword'])
  })

  it('emits change event when input changes', async () => {
    wrapper = mountField(PasswordField, { field: mockField })
    const input = wrapper.find('input[type="password"]')
    await input.setValue('newpassword')

    expect(wrapper.emitted('change')).toBeTruthy()
    expect(wrapper.emitted('change')[0]).toEqual(['newpassword'])
  })

  it('emits focus event when input is focused', async () => {
    wrapper = mountField(PasswordField, { field: mockField })
    const input = wrapper.find('input[type="password"]')
    await input.trigger('focus')

    expect(wrapper.emitted('focus')).toBeTruthy()
  })

  it('emits blur event when input loses focus', async () => {
    wrapper = mountField(PasswordField, { field: mockField })
    const input = wrapper.find('input[type="password"]')
    await input.trigger('blur')

    expect(wrapper.emitted('blur')).toBeTruthy()
  })

  it('applies disabled attribute when disabled prop is true', () => {
    wrapper = mountField(PasswordField, { field: mockField, disabled: true })

    expect(wrapper.find('input').attributes('disabled')).toBeDefined()
  })

  it('applies readonly attribute when readonly prop is true', () => {
    wrapper = mountField(PasswordField, { field: mockField, readonly: true })

    expect(wrapper.find('input').attributes('readonly')).toBeDefined()
  })

  it('applies dark theme classes when dark theme is enabled', () => {
    mockAdminStore.isDarkTheme = true
    wrapper = mountField(PasswordField, { field: mockField })

    expect(wrapper.find('input').classes()).toContain('admin-input-dark')
    mockAdminStore.isDarkTheme = false // Reset for other tests
  })

  it('renders only one password input (no confirmation field)', () => {
    wrapper = mountField(PasswordField, { field: mockField })
    const inputs = wrapper.findAll('input[type="password"]')
    expect(inputs).toHaveLength(1)
  })

  it('does not show password toggle button (simple Nova-compatible field)', () => {
    wrapper = mountField(PasswordField, { field: mockField })
    expect(wrapper.find('button').exists()).toBe(false)
  })

  it('does not show password strength meter (simple Nova-compatible field)', () => {
    wrapper = mountField(PasswordField, { field: mockField, modelValue: 'password123' })
    expect(wrapper.find('.h-2.rounded-full').exists()).toBe(false)
  })

  it('does not show password requirements (simple Nova-compatible field)', () => {
    wrapper = mountField(PasswordField, { field: mockField })
    expect(wrapper.text()).not.toContain('At least')
    expect(wrapper.text()).not.toContain('Include')
  })

  it('does not show character count (simple Nova-compatible field)', () => {
    wrapper = mountField(PasswordField, { field: mockField, modelValue: 'password' })
    expect(wrapper.text()).not.toContain('/20')
  })

  it('exposes focus method', () => {
    wrapper = mountField(PasswordField, { field: mockField })
    expect(typeof wrapper.vm.focus).toBe('function')
  })

  it('focuses input when focus method is called', () => {
    wrapper = mountField(PasswordField, { field: mockField })
    const input = wrapper.find('input')
    const focusSpy = vi.spyOn(input.element, 'focus')

    wrapper.vm.focus()

    expect(focusSpy).toHaveBeenCalled()
  })

  it('generates unique field ID', () => {
    const wrapper1 = mountField(PasswordField, { field: mockField })
    const wrapper2 = mountField(PasswordField, { field: mockField })

    expect(wrapper1.vm.fieldId).not.toBe(wrapper2.vm.fieldId)
    expect(wrapper1.vm.fieldId).toContain('password-field-password-')

    wrapper1.unmount()
    wrapper2.unmount()
  })

  it('handles empty modelValue correctly', () => {
    wrapper = mountField(PasswordField, { field: mockField, modelValue: '' })
    const input = wrapper.find('input')

    expect(input.element.value).toBe('')
  })

  it('handles null modelValue correctly', () => {
    wrapper = mountField(PasswordField, { field: mockField, modelValue: null })
    const input = wrapper.find('input')

    expect(input.element.value).toBe('')
  })

  it('applies correct CSS classes', () => {
    wrapper = mountField(PasswordField, { field: mockField })
    const input = wrapper.find('input')

    expect(input.classes()).toContain('admin-input')
    expect(input.classes()).toContain('w-full')
  })

  it('applies dark theme CSS classes when enabled', () => {
    mockAdminStore.isDarkTheme = true
    wrapper = mountField(PasswordField, { field: mockField })
    const input = wrapper.find('input')

    expect(input.classes()).toContain('admin-input-dark')
    mockAdminStore.isDarkTheme = false // Reset for other tests
  })
})
