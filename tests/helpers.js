import { mount, shallowMount } from '@vue/test-utils'
import { vi } from 'vitest'

/**
 * Create a mock field configuration for testing field components
 */
export function createMockField(overrides = {}) {
  return {
    name: 'test_field',
    label: 'Test Field',
    type: 'text',
    value: '',
    required: false,
    readonly: false,
    disabled: false,
    placeholder: '',
    helpText: '',
    rules: [],
    errors: [],
    ...overrides
  }
}

/**
 * Create a mock form context for testing field components
 */
export function createMockForm(fields = {}, overrides = {}) {
  return {
    fields,
    errors: {},
    processing: false,
    isDirty: false,
    hasErrors: false,
    submit: vi.fn(),
    reset: vi.fn(),
    setFieldValue: vi.fn(),
    getFieldValue: vi.fn(),
    ...overrides
  }
}

/**
 * Mount a field component with common props and provide context
 */
export function mountField(component, options = {}) {
  const defaultProps = {
    field: createMockField(options.field || {}),
    modelValue: options.modelValue || '',
    errors: options.errors || []
  }

  // Extract common field props from top-level options
  const fieldProps = {}
  if (options.readonly !== undefined) fieldProps.readonly = options.readonly
  if (options.disabled !== undefined) fieldProps.disabled = options.disabled
  if (options.size !== undefined) fieldProps.size = options.size

  const defaultGlobal = {
    provide: {
      form: createMockForm({}, options.form || {})
    },
    mocks: {
      $route: { params: {}, query: {}, path: '/admin' },
      $router: { push: vi.fn(), replace: vi.fn() }
    }
  }

  // Remove field props from options to avoid conflicts
  const { readonly, disabled, size, field, modelValue, errors, props, global, form, ...restOptions } = options

  return mount(component, {
    props: { ...defaultProps, ...fieldProps, ...(props || {}) },
    global: {
      ...defaultGlobal,
      ...(global || {})
    },
    ...restOptions
  })
}

/**
 * Shallow mount a field component with common props
 */
export function shallowMountField(component, options = {}) {
  const defaultProps = {
    field: createMockField(options.field || {}),
    modelValue: options.modelValue || '',
    errors: options.errors || []
  }

  return shallowMount(component, {
    props: { ...defaultProps, ...(options.props || {}) },
    global: options.global || {},
    ...options
  })
}

/**
 * Create a mock theme context for testing theme-aware components
 */
export function createMockTheme(theme = 'light') {
  return {
    current: theme,
    toggle: vi.fn(),
    set: vi.fn(),
    isDark: theme === 'dark',
    isLight: theme === 'light'
  }
}

/**
 * Wait for Vue's nextTick and any pending promises
 */
export async function flushPromises() {
  return new Promise(resolve => setTimeout(resolve, 0))
}

/**
 * Trigger an input event on an element
 */
export async function triggerInput(wrapper, selector, value) {
  const input = wrapper.find(selector)
  await input.setValue(value)
  await input.trigger('input')
  await flushPromises()
}

/**
 * Trigger a change event on an element
 */
export async function triggerChange(wrapper, selector, value) {
  const input = wrapper.find(selector)
  await input.setValue(value)
  await input.trigger('change')
  await flushPromises()
}

/**
 * Mock file for file upload testing
 */
export function createMockFile(name = 'test.txt', type = 'text/plain', size = 1024) {
  const file = new File(['test content'], name, { type })
  Object.defineProperty(file, 'size', { value: size })
  return file
}

/**
 * Mock image file for image upload testing
 */
export function createMockImageFile(name = 'test.jpg', size = 2048) {
  return createMockFile(name, 'image/jpeg', size)
}
