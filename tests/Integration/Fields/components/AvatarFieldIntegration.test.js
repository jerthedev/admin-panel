import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import AvatarField from '@/components/Fields/AvatarField.vue'
import BaseField from '@/components/Fields/BaseField.vue'

/**
 * Avatar Field Integration Tests
 *
 * Tests the integration between the PHP Avatar field class and Vue component,
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

// Mock URL.createObjectURL
global.URL.createObjectURL = vi.fn(() => 'blob:mock-url')
global.URL.revokeObjectURL = vi.fn()

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  CloudArrowUpIcon: {
    name: 'CloudArrowUpIcon',
    template: '<svg data-testid="cloud-arrow-up-icon"></svg>'
  },
  DocumentIcon: {
    name: 'DocumentIcon',
    template: '<svg data-testid="document-icon"></svg>'
  },
  XMarkIcon: {
    name: 'XMarkIcon',
    template: '<svg data-testid="x-mark-icon"></svg>'
  }
}))

// Mock admin store
vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({
    props: {
      value: {
        auth: { user: { id: 1, name: 'Test User' } },
        flash: {},
        errors: {}
      }
    }
  }),
  router: {
    visit: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}))

describe('Avatar Field Integration Tests', () => {
  let wrapper

  beforeEach(() => {
    // Reset all mocks
    vi.clearAllMocks()
  })

  describe('PHP Field Class Integration', () => {
    it('handles PHP field configuration correctly', () => {
      const phpFieldConfig = {
        attribute: 'avatar',
        name: 'Avatar',
        component: 'AvatarField',
        rounded: true,
        squared: false,
        size: 'lg',
        showInIndex: true,
        acceptedTypes: 'image/jpeg,image/png,image/webp',
        maxFileSize: 2048,
        width: 400,
        height: 400,
        quality: 85,
        path: 'avatars',
        disk: 'public'
      }

      wrapper = mount(AvatarField, {
        props: {
          field: phpFieldConfig,
          modelValue: null,
          errors: {},
          readonly: false,
          disabled: false
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.exists()).toBe(true)
      expect(wrapper.vm.field.rounded).toBe(true)
      expect(wrapper.vm.field.squared).toBe(false)
      expect(wrapper.vm.field.size).toBe('lg')
      expect(wrapper.vm.field.acceptedTypes).toBe('image/jpeg,image/png,image/webp')
    })

    it('properly inherits Image field properties', () => {
      const imageFieldConfig = {
        attribute: 'avatar',
        name: 'Avatar',
        component: 'AvatarField',
        // Image field inherited properties
        acceptedTypes: 'image/*',
        maxFileSize: 1024,
        width: 300,
        height: 300,
        quality: 90,
        path: 'images',
        disk: 'local',
        // Avatar-specific properties
        rounded: false,
        squared: true,
        showInIndex: true
      }

      wrapper = mount(AvatarField, {
        props: {
          field: imageFieldConfig,
          modelValue: null
        },
        global: {
          components: { BaseField }
        }
      })

      // Verify Image field properties are accessible
      expect(wrapper.vm.field.acceptedTypes).toBe('image/*')
      expect(wrapper.vm.field.maxFileSize).toBe(1024)
      expect(wrapper.vm.field.width).toBe(300)
      expect(wrapper.vm.field.height).toBe(300)
      expect(wrapper.vm.field.quality).toBe(90)

      // Verify Avatar-specific properties
      expect(wrapper.vm.field.squared).toBe(true)
      expect(wrapper.vm.field.rounded).toBe(false)
    })

    it('handles Nova-style field meta data', () => {
      const fieldWithMeta = {
        attribute: 'avatar',
        name: 'User Avatar',
        component: 'AvatarField',
        meta: {
          rounded: true,
          size: 'xl',
          showInIndex: true,
          placeholder: 'Upload your avatar',
          helpText: 'Recommended size: 400x400px'
        }
      }

      wrapper = mount(AvatarField, {
        props: {
          field: fieldWithMeta,
          modelValue: null
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.vm.field.meta.rounded).toBe(true)
      expect(wrapper.vm.field.meta.size).toBe('xl')
      expect(wrapper.vm.field.meta.showInIndex).toBe(true)
    })
  })

  describe('Data Flow Integration', () => {
    it('emits correct data structure for PHP backend', async () => {
      wrapper = mount(AvatarField, {
        props: {
          field: {
            attribute: 'avatar',
            name: 'Avatar',
            component: 'AvatarField'
          },
          modelValue: null
        },
        global: {
          components: { BaseField }
        }
      })

      // Simulate file selection
      const file = new File(['test'], 'avatar.jpg', { type: 'image/jpeg' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [file],
        writable: false
      })

      await fileInput.trigger('change')

      // Check that the component emits the file in the correct format
      const emitted = wrapper.emitted('update:modelValue')
      expect(emitted).toBeTruthy()
      expect(emitted[0][0]).toBeInstanceOf(File)
      expect(emitted[0][0].name).toBe('avatar.jpg')
    })

    it('handles existing avatar URL from PHP backend', () => {
      const existingAvatarUrl = '/storage/avatars/user-123-avatar.jpg'

      wrapper = mount(AvatarField, {
        props: {
          field: {
            attribute: 'avatar',
            name: 'Avatar',
            component: 'AvatarField'
          },
          modelValue: existingAvatarUrl
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.vm.currentAvatarUrl).toBe(existingAvatarUrl)
      expect(wrapper.find('img').attributes('src')).toBe(existingAvatarUrl)
    })

    it('handles avatar removal correctly', async () => {
      wrapper = mount(AvatarField, {
        props: {
          field: {
            attribute: 'avatar',
            name: 'Avatar',
            component: 'AvatarField'
          },
          modelValue: '/storage/avatars/existing.jpg'
        },
        global: {
          components: { BaseField }
        }
      })

      // Find and click remove button (it's the button with "Remove" text)
      const removeButton = wrapper.find('button')
      await removeButton.trigger('click')

      // Should emit null to indicate removal
      const emitted = wrapper.emitted('update:modelValue')
      expect(emitted).toBeTruthy()
      expect(emitted[emitted.length - 1][0]).toBeNull()
    })
  })

  describe('Nova API Compatibility', () => {
    it('supports Nova-style field configuration', () => {
      // Simulate Nova field configuration
      const novaStyleField = {
        attribute: 'avatar',
        name: 'Avatar',
        component: 'AvatarField',
        squared: () => true,
        rounded: () => false,
        size: (size) => size,
        showInIndex: (show) => show,
        acceptedTypes: (types) => types
      }

      // Convert Nova-style methods to static values for Vue component
      const vueCompatibleField = {
        attribute: novaStyleField.attribute,
        name: novaStyleField.name,
        component: novaStyleField.component,
        squared: true,
        rounded: false,
        size: 'md',
        showInIndex: true,
        acceptedTypes: 'image/*'
      }

      wrapper = mount(AvatarField, {
        props: {
          field: vueCompatibleField,
          modelValue: null
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.vm.field.squared).toBe(true)
      expect(wrapper.vm.field.rounded).toBe(false)
    })

    it('handles Nova-style validation rules', () => {
      const fieldWithValidation = {
        attribute: 'avatar',
        name: 'Avatar',
        component: 'AvatarField',
        rules: ['required', 'image', 'max:2048'],
        acceptedTypes: 'image/jpeg,image/png',
        maxFileSize: 2048
      }

      wrapper = mount(AvatarField, {
        props: {
          field: fieldWithValidation,
          modelValue: null,
          errors: {
            avatar: ['The avatar field is required.']
          }
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.vm.field.rules).toEqual(['required', 'image', 'max:2048'])
      expect(wrapper.vm.field.acceptedTypes).toBe('image/jpeg,image/png')
    })
  })

  describe('CRUD Operations Integration', () => {
    it('integrates with create form correctly', () => {
      wrapper = mount(AvatarField, {
        props: {
          field: {
            attribute: 'avatar',
            name: 'Avatar',
            component: 'AvatarField'
          },
          modelValue: null,
          mode: 'create'
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.find('input[type="file"]').exists()).toBe(true)
      expect(wrapper.text()).not.toContain('Current Avatar')
    })

    it('integrates with edit form correctly', () => {
      wrapper = mount(AvatarField, {
        props: {
          field: {
            attribute: 'avatar',
            name: 'Avatar',
            component: 'AvatarField'
          },
          modelValue: '/storage/avatars/existing.jpg',
          mode: 'edit'
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.find('input[type="file"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Current Avatar')
      expect(wrapper.find('button').exists()).toBe(true)
    })

    it('integrates with detail view correctly', () => {
      wrapper = mount(AvatarField, {
        props: {
          field: {
            attribute: 'avatar',
            name: 'Avatar',
            component: 'AvatarField',
            showInIndex: true
          },
          modelValue: '/storage/avatars/user.jpg',
          mode: 'detail',
          readonly: true
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.find('input[type="file"]').exists()).toBe(false)
      expect(wrapper.find('img').exists()).toBe(true)
      expect(wrapper.find('button').exists()).toBe(false)
    })

    it('integrates with index view correctly', () => {
      wrapper = mount(AvatarField, {
        props: {
          field: {
            attribute: 'avatar',
            name: 'Avatar',
            component: 'AvatarField',
            showInIndex: true,
            size: 'sm'
          },
          modelValue: '/storage/avatars/user.jpg',
          mode: 'index',
          readonly: true
        },
        global: {
          components: { BaseField }
        }
      })

      expect(wrapper.find('img').exists()).toBe(true)
      // Check that the image has proper styling classes
      const imgClasses = wrapper.find('img').classes()
      expect(imgClasses).toContain('object-cover')
      expect(wrapper.find('input[type="file"]').exists()).toBe(false)
    })
  })

  describe('Error Handling Integration', () => {
    it('displays PHP validation errors correctly', () => {
      wrapper = mount(AvatarField, {
        props: {
          field: {
            attribute: 'avatar',
            name: 'Avatar',
            component: 'AvatarField'
          },
          modelValue: null,
          errors: {
            avatar: ['The avatar must be an image.', 'The avatar may not be greater than 2048 kilobytes.']
          }
        },
        global: {
          components: { BaseField }
        }
      })

      const errorMessages = wrapper.findAll('.field-error')
      expect(errorMessages.length).toBeGreaterThan(0)
    })

    it('handles file upload errors gracefully', async () => {
      wrapper = mount(AvatarField, {
        props: {
          field: {
            attribute: 'avatar',
            name: 'Avatar',
            component: 'AvatarField',
            acceptedTypes: 'image/jpeg,image/png',
            maxFileSize: 1024
          },
          modelValue: null
        },
        global: {
          components: { BaseField }
        }
      })

      // Simulate selecting an invalid file
      const invalidFile = new File(['test'], 'document.pdf', { type: 'application/pdf' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [invalidFile],
        writable: false
      })

      await fileInput.trigger('change')

      // Should not emit the invalid file
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })
  })
});
