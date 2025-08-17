import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import GravatarField from '@/components/Fields/GravatarField.vue'
import BaseField from '@/components/Fields/BaseField.vue'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock btoa for email hashing
global.btoa = vi.fn((str) => Buffer.from(str).toString('base64'))

/**
 * Gravatar Field Integration Tests
 * 
 * Tests the integration between PHP backend field configuration
 * and Vue component rendering, ensuring Nova API compatibility.
 */
describe('GravatarField Integration', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
    vi.clearAllMocks()
  })

  describe('PHP-Vue Integration', () => {
    it('renders correctly with PHP field configuration', () => {
      // Simulate PHP field serialization
      const phpFieldData = {
        name: 'Profile Avatar',
        attribute: null, // Gravatar fields don't have database attributes
        component: 'GravatarField',
        emailColumn: 'email',
        squared: false,
        rounded: true,
        helpText: 'Gravatar based on email address'
      }

      wrapper = mount(GravatarField, {
        props: {
          field: phpFieldData,
          modelValue: '',
          formData: { email: 'test@example.com' }
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.exists()).toBe(true)
      expect(wrapper.find('img').exists()).toBe(true)
    })

    it('handles Nova-style field configuration', () => {
      // Test Nova's Gravatar::make('Avatar', 'email_address') configuration
      const novaFieldData = {
        name: 'Avatar',
        attribute: null,
        component: 'GravatarField',
        emailColumn: 'email_address',
        squared: false,
        rounded: true
      }

      wrapper = mount(GravatarField, {
        props: {
          field: novaFieldData,
          modelValue: '',
          formData: { email_address: 'user@company.com' }
        },
        global: {
          components: { BaseField }
        }
      })

      // Should use the custom email column
      const img = wrapper.find('img')
      expect(img.exists()).toBe(true)
      expect(img.attributes('src')).toContain('gravatar.com/avatar')
    })

    it('applies squared styling from PHP configuration', () => {
      const squaredFieldData = {
        name: 'Avatar',
        attribute: null,
        component: 'GravatarField',
        emailColumn: 'email',
        squared: true,
        rounded: false
      }

      wrapper = mount(GravatarField, {
        props: {
          field: squaredFieldData,
          modelValue: '',
          formData: { email: 'test@example.com' }
        },
        global: {
          components: { BaseField }
        }
      })

      const img = wrapper.find('img')
      expect(img.classes()).toContain('rounded-none')
      expect(img.classes()).not.toContain('rounded-full')
    })

    it('applies rounded styling from PHP configuration', () => {
      const roundedFieldData = {
        name: 'Avatar',
        attribute: null,
        component: 'GravatarField',
        emailColumn: 'email',
        squared: false,
        rounded: true
      }

      wrapper = mount(GravatarField, {
        props: {
          field: roundedFieldData,
          modelValue: '',
          formData: { email: 'test@example.com' }
        },
        global: {
          components: { BaseField }
        }
      })

      const img = wrapper.find('img')
      expect(img.classes()).toContain('rounded-full')
      expect(img.classes()).not.toContain('rounded-none')
    })
  })

  describe('Email Column Integration', () => {
    it('uses default email column when not specified', () => {
      const defaultFieldData = {
        name: 'Gravatar',
        attribute: null,
        component: 'GravatarField',
        emailColumn: 'email', // Default from PHP
        squared: false,
        rounded: true
      }

      wrapper = mount(GravatarField, {
        props: {
          field: defaultFieldData,
          modelValue: '',
          formData: { email: 'default@example.com' }
        },
        global: {
          components: { BaseField }
        }
      })

      const img = wrapper.find('img')
      expect(img.exists()).toBe(true)
      expect(img.attributes('src')).toContain('gravatar.com/avatar')
    })

    it('uses custom email column from PHP configuration', () => {
      const customFieldData = {
        name: 'Avatar',
        attribute: null,
        component: 'GravatarField',
        emailColumn: 'work_email',
        squared: false,
        rounded: true
      }

      wrapper = mount(GravatarField, {
        props: {
          field: customFieldData,
          modelValue: '',
          formData: { work_email: 'work@company.com' }
        },
        global: {
          components: { BaseField }
        }
      })

      const img = wrapper.find('img')
      expect(img.exists()).toBe(true)
      expect(img.attributes('src')).toContain('gravatar.com/avatar')
    })

    it('shows email input when no email column data is available', () => {
      const fieldData = {
        name: 'Avatar',
        attribute: null,
        component: 'GravatarField',
        emailColumn: null, // No email column specified
        squared: false,
        rounded: true
      }

      wrapper = mount(GravatarField, {
        props: {
          field: fieldData,
          modelValue: '',
          formData: {}
        },
        global: {
          components: { BaseField }
        }
      })

      const emailInput = wrapper.find('input[type="email"]')
      expect(emailInput.exists()).toBe(true)
    })
  })

  describe('Gravatar URL Generation', () => {
    it('generates Nova-compatible Gravatar URLs', async () => {
      const fieldData = {
        name: 'Avatar',
        attribute: null,
        component: 'GravatarField',
        emailColumn: 'email',
        squared: false,
        rounded: true
      }

      wrapper = mount(GravatarField, {
        props: {
          field: fieldData,
          modelValue: '',
          formData: { email: 'test@example.com' }
        },
        global: {
          components: { BaseField }
        }
      })

      const img = wrapper.find('img')
      const src = img.attributes('src')
      
      // Should be a simple Gravatar URL without parameters (Nova-compatible)
      expect(src).toMatch(/^https:\/\/www\.gravatar\.com\/avatar\/[a-zA-Z0-9]+$/)
      expect(src).not.toContain('s=') // No size parameter
      expect(src).not.toContain('d=') // No default parameter
      expect(src).not.toContain('r=') // No rating parameter
    })

    it('handles email normalization consistently with PHP', () => {
      const fieldData = {
        name: 'Avatar',
        attribute: null,
        component: 'GravatarField',
        emailColumn: 'email',
        squared: false,
        rounded: true
      }

      // Test with different email formats
      const emails = [
        'TEST@EXAMPLE.COM',
        'test@example.com',
        '  test@example.com  '
      ]

      const urls = emails.map(email => {
        wrapper = mount(GravatarField, {
          props: {
            field: fieldData,
            modelValue: '',
            formData: { email }
          },
          global: {
            components: { BaseField }
          }
        })

        const img = wrapper.find('img')
        const url = img.attributes('src')
        wrapper.unmount()
        return url
      })

      // All URLs should be the same (normalized)
      expect(urls[0]).toBe(urls[1])
      expect(urls[1]).toBe(urls[2])
    })
  })

  describe('Form Integration', () => {
    it('emits model value updates for form integration', async () => {
      const fieldData = {
        name: 'Avatar',
        attribute: null,
        component: 'GravatarField',
        emailColumn: null,
        squared: false,
        rounded: true
      }

      wrapper = mount(GravatarField, {
        props: {
          field: fieldData,
          modelValue: '',
          formData: {}
        },
        global: {
          components: { BaseField }
        }
      })

      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('new@example.com')
      await emailInput.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      const emittedValue = wrapper.emitted('update:modelValue')[0][0]
      expect(emittedValue).toContain('gravatar.com/avatar')
    })

    it('integrates with form validation', () => {
      const fieldData = {
        name: 'Avatar',
        attribute: null,
        component: 'GravatarField',
        emailColumn: 'email',
        squared: false,
        rounded: true
      }

      const errors = {
        email: ['The email field is required.']
      }

      wrapper = mount(GravatarField, {
        props: {
          field: fieldData,
          modelValue: '',
          formData: {},
          errors
        },
        global: {
          components: { BaseField }
        }
      })

      // Should pass errors to BaseField for display
      expect(wrapper.props('errors')).toEqual(errors)
    })
  })
})
