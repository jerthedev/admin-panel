import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import PasswordConfirmationField from '@/components/Fields/PasswordConfirmationField.vue'
import BaseField from '@/components/Fields/BaseField.vue'

/**
 * PasswordConfirmation Field Integration Tests
 *
 * Tests the integration between the PHP PasswordConfirmation field class and Vue component,
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

// Mock admin store
vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
  router: {
    visit: vi.fn(),
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    reload: vi.fn()
  },
  usePage: () => ({
    props: {
      auth: {
        user: { id: 1, name: 'Test User', email: 'test@example.com' }
      },
      flash: {},
      errors: {}
    }
  })
}))

describe('PasswordConfirmationField Integration', () => {
  let wrapper

  // Helper function to create a field configuration that matches PHP field output
  const createFieldConfig = (overrides = {}) => ({
    component: 'PasswordConfirmationField',
    name: 'Password Confirmation',
    attribute: 'password_confirmation',
    value: null, // Always null for security
    sortable: false,
    searchable: false,
    nullable: false,
    readonly: false,
    helpText: null,
    placeholder: null,
    suffix: null,
    default: null,
    rules: [],
    showOnIndex: false,
    showOnDetail: false,
    showOnCreation: true,
    showOnUpdate: true,
    immutable: false,
    filterable: false,
    copyable: false,
    asHtml: false,
    textAlign: 'left',
    stacked: false,
    fullWidth: false,
    ...overrides
  })

  beforeEach(() => {
    // Reset mocks
    vi.clearAllMocks()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP-Vue Integration', () => {
    it('renders correctly with PHP field configuration', () => {
      const field = createFieldConfig()
      
      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: '',
          errors: {},
          disabled: false,
          readonly: false
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      expect(wrapper.find('input[type="password"]').exists()).toBe(true)
      expect(wrapper.find('button[type="button"]').exists()).toBe(true) // visibility toggle
    })

    it('handles field configuration with validation rules', () => {
      const field = createFieldConfig({
        rules: ['required', 'confirmed'],
        helpText: 'Must match the password above'
      })

      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: '',
          errors: {},
          disabled: false,
          readonly: false
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Component should render without errors
      expect(wrapper.find('input').exists()).toBe(true)
    })

    it('handles field configuration with placeholder', () => {
      const field = createFieldConfig({
        placeholder: 'Confirm your password'
      })

      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: '',
          errors: {},
          disabled: false,
          readonly: false
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('Confirm your password')
    })

    it('handles readonly state from PHP field', () => {
      const field = createFieldConfig({
        readonly: true
      })

      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: '',
          errors: {},
          disabled: false,
          readonly: true
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      const input = wrapper.find('input')
      expect(input.attributes('readonly')).toBeDefined()
    })

    it('handles disabled state', () => {
      const field = createFieldConfig()

      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: '',
          errors: {},
          disabled: true,
          readonly: false
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      const input = wrapper.find('input')
      expect(input.attributes('disabled')).toBeDefined()
    })
  })

  describe('Data Flow Integration', () => {
    it('emits correct events for form submission', async () => {
      const field = createFieldConfig()

      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: '',
          errors: {},
          disabled: false,
          readonly: false
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      const input = wrapper.find('input')
      await input.setValue('testpassword')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('testpassword')
      expect(wrapper.emitted('change')).toBeTruthy()
      expect(wrapper.emitted('change')[0][0]).toBe('testpassword')
    })

    it('handles focus and blur events', async () => {
      const field = createFieldConfig()

      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: '',
          errors: {},
          disabled: false,
          readonly: false
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      const input = wrapper.find('input')
      
      await input.trigger('focus')
      expect(wrapper.emitted('focus')).toBeTruthy()

      await input.trigger('blur')
      expect(wrapper.emitted('blur')).toBeTruthy()
    })
  })

  describe('Error Handling Integration', () => {
    it('displays validation errors from backend', () => {
      const field = createFieldConfig()
      const errors = {
        password_confirmation: ['The password confirmation does not match.']
      }

      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: '',
          errors,
          disabled: false,
          readonly: false
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('border-red-300')
    })

    it('clears error styling when errors are resolved', async () => {
      const field = createFieldConfig()

      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: '',
          errors: { password_confirmation: ['Error'] },
          disabled: false,
          readonly: false
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      let input = wrapper.find('input')
      expect(input.classes()).toContain('border-red-300')

      // Clear errors
      await wrapper.setProps({ errors: {} })
      
      input = wrapper.find('input')
      expect(input.classes()).not.toContain('border-red-300')
    })
  })

  describe('Nova API Compatibility', () => {
    it('matches Nova field structure expectations', () => {
      const field = createFieldConfig({
        rules: ['required', 'confirmed'],
        helpText: 'Must match password',
        placeholder: 'Confirm password',
        nullable: true
      })

      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: '',
          errors: {},
          disabled: false,
          readonly: false
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Should render without errors and handle all Nova field properties
      expect(wrapper.find('input').exists()).toBe(true)
      expect(wrapper.find('input').attributes('placeholder')).toBe('Confirm password')
    })

    it('supports Nova field visibility patterns', () => {
      // Test that the component works with Nova's visibility settings
      const field = createFieldConfig({
        showOnIndex: false,
        showOnDetail: false,
        showOnCreation: true,
        showOnUpdate: true
      })

      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: '',
          errors: {},
          disabled: false,
          readonly: false
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      // Component should render (visibility is handled by parent components)
      expect(wrapper.find('input').exists()).toBe(true)
    })
  })

  describe('Security Integration', () => {
    it('never displays resolved values from backend', () => {
      // Even if backend accidentally sends a value, it should not be displayed
      const field = createFieldConfig({
        value: 'should-never-be-shown'
      })

      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: '', // Component should use modelValue, not field.value
          errors: {},
          disabled: false,
          readonly: false
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('handles password visibility toggle securely', async () => {
      const field = createFieldConfig()

      wrapper = mount(PasswordConfirmationField, {
        props: {
          field,
          modelValue: 'secretpassword',
          errors: {},
          disabled: false,
          readonly: false
        },
        global: {
          components: {
            BaseField
          }
        }
      })

      const input = wrapper.find('input')
      const toggleButton = wrapper.find('button[type="button"]')

      // Initially password type
      expect(input.element.type).toBe('password')

      // Toggle to text
      await toggleButton.trigger('click')
      expect(input.element.type).toBe('text')

      // Toggle back to password
      await toggleButton.trigger('click')
      expect(input.element.type).toBe('password')
    })
  })
})
