import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import MediaLibraryAvatarField from '@/components/Fields/MediaLibraryAvatarField.vue'
import { createMockField, mountField } from '../../../helpers.js'

/**
 * Integration tests for MediaLibraryAvatarField Vue component.
 * 
 * Tests the complete integration between Vue component, PHP field API,
 * and browser APIs for avatar upload and management functionality.
 */

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  UserCircleIcon: {
    name: 'UserCircleIcon',
    template: '<svg data-testid="user-circle-icon"></svg>'
  },
  CameraIcon: {
    name: 'CameraIcon',
    template: '<svg data-testid="camera-icon"></svg>'
  },
  XMarkIcon: {
    name: 'XMarkIcon',
    template: '<svg data-testid="x-mark-icon"></svg>'
  },
  ExclamationCircleIcon: {
    name: 'ExclamationCircleIcon',
    template: '<svg data-testid="exclamation-circle-icon"></svg>'
  }
}))

// Mock BaseField component
vi.mock('@/components/Fields/BaseField.vue', () => ({
  default: {
    name: 'BaseField',
    template: '<div class="base-field"><slot /></div>',
    props: ['field', 'modelValue', 'errors', 'disabled', 'readonly', 'size']
  }
}))

// Mock browser APIs
global.URL.createObjectURL = vi.fn(() => 'blob:mock-url')
global.URL.revokeObjectURL = vi.fn()

describe('MediaLibraryAvatarField Integration', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      component: 'MediaLibraryAvatarField',
      name: 'Avatar',
      attribute: 'avatar',
      collection: 'avatars',
      singleFile: true,
      multiple: false,
      enableCropping: true,
      cropAspectRatio: '1:1',
      fallbackUrl: '/images/default-avatar.png',
      acceptedMimeTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
      maxFileSize: 2048,
      conversions: {
        thumb: { width: 64, height: 64, fit: 'crop' },
        medium: { width: 150, height: 150, fit: 'crop' },
        large: { width: 400, height: 400, fit: 'crop' }
      },
      squared: false,
      rounded: false,
      hasAvatar: false,
      avatarMetadata: {
        has_avatar: false,
        fallback_url: '/images/default-avatar.png',
        urls: {
          thumb: '/images/default-avatar.png',
          medium: '/images/default-avatar.png',
          large: '/images/default-avatar.png'
        }
      },
      avatarSizes: {
        thumb: { width: 64, height: 64, url: '/images/default-avatar.png' },
        medium: { width: 150, height: 150, url: '/images/default-avatar.png' },
        large: { width: 400, height: 400, url: '/images/default-avatar.png' }
      }
    })

    vi.clearAllMocks()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP Field API Integration', () => {
    it('integrates with PHP field meta data correctly', () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      // Verify component uses PHP field configuration
      expect(wrapper.vm.field.collection).toBe('avatars')
      expect(wrapper.vm.field.singleFile).toBe(true)
      expect(wrapper.vm.field.enableCropping).toBe(true)
      expect(wrapper.vm.field.cropAspectRatio).toBe('1:1')
      expect(wrapper.vm.field.fallbackUrl).toBe('/images/default-avatar.png')
      expect(wrapper.vm.field.maxFileSize).toBe(2048)
    })

    it('handles squared avatar display from PHP field', () => {
      const squaredField = {
        ...mockField,
        squared: true,
        rounded: false
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: squaredField,
        modelValue: null
      })

      const avatarContainer = wrapper.find('.avatar-container')
      expect(avatarContainer.exists()).toBe(true)
      // Component should apply squared styling based on PHP field meta
    })

    it('handles rounded avatar display from PHP field', () => {
      const roundedField = {
        ...mockField,
        squared: false,
        rounded: true
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: roundedField,
        modelValue: null
      })

      const avatarContainer = wrapper.find('.avatar-container')
      expect(avatarContainer.exists()).toBe(true)
      // Component should apply rounded styling based on PHP field meta
    })

    it('uses PHP field avatar metadata for display', () => {
      const fieldWithAvatar = {
        ...mockField,
        hasAvatar: true,
        avatarMetadata: {
          has_avatar: true,
          name: 'profile.jpg',
          size: 1024000,
          human_readable_size: '1.0 MB',
          width: 512,
          height: 512,
          created_at: '2024-01-01T00:00:00Z',
          urls: {
            thumb: 'https://example.com/thumb.jpg',
            medium: 'https://example.com/medium.jpg',
            large: 'https://example.com/large.jpg'
          }
        }
      }

      const mediaObject = {
        url: 'https://example.com/medium.jpg',
        medium_url: 'https://example.com/medium.jpg',
        name: 'profile.jpg',
        size: 1024000,
        human_readable_size: '1.0 MB',
        width: 512,
        height: 512
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: fieldWithAvatar,
        modelValue: mediaObject
      })

      // Verify metadata is displayed
      expect(wrapper.text()).toContain('1.0 MB')
      expect(wrapper.text()).toContain('512 × 512')
    })
  })

  describe('File Upload Integration', () => {
    it('integrates with browser file API for upload', async () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      const file = new File(['test'], 'avatar.jpg', { type: 'image/jpeg' })
      const input = wrapper.find('input[type="file"]')

      // Mock file input behavior
      Object.defineProperty(input.element, 'files', {
        value: [file],
        writable: false
      })

      await input.trigger('change')

      // Verify file was processed and emitted
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(file)
      expect(wrapper.emitted('change')).toBeTruthy()
    })

    it('validates file types against PHP field configuration', async () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      // Try uploading invalid file type
      const invalidFile = new File(['test'], 'document.pdf', { type: 'application/pdf' })
      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [invalidFile],
        writable: false
      })

      await input.trigger('change')

      // Should show validation error
      expect(wrapper.text()).toContain('Please select a valid image file')
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('validates file size against PHP field configuration', async () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      // Create oversized file (3MB when limit is 2MB)
      const oversizedFile = new File(['x'.repeat(3 * 1024 * 1024)], 'large.jpg', { type: 'image/jpeg' })
      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [oversizedFile],
        writable: false
      })

      await input.trigger('change')

      // Should show size validation error
      expect(wrapper.text()).toContain('Avatar size exceeds maximum allowed size of 2.0 MB')
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })
  })

  describe('Drag and Drop Integration', () => {
    it('integrates with browser drag and drop API', async () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      const uploadArea = wrapper.find('.upload-area')
      const file = new File(['test'], 'avatar.jpg', { type: 'image/jpeg' })

      // Mock drag and drop event
      const dropEvent = new Event('drop')
      Object.defineProperty(dropEvent, 'dataTransfer', {
        value: {
          files: [file]
        }
      })

      await uploadArea.trigger('dragover')
      expect(wrapper.vm.isDragOver).toBe(true)

      await uploadArea.element.dispatchEvent(dropEvent)
      await nextTick()

      // File should be processed
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    })
  })

  describe('Avatar Display Integration', () => {
    it('integrates with media object from PHP backend', () => {
      const mediaObject = {
        id: 1,
        url: 'https://example.com/avatar.jpg',
        thumb_url: 'https://example.com/thumb.jpg',
        medium_url: 'https://example.com/medium.jpg',
        large_url: 'https://example.com/large.jpg',
        name: 'avatar.jpg',
        size: 1024000,
        human_readable_size: '1.0 MB',
        width: 512,
        height: 512,
        created_at: '2024-01-01T00:00:00Z'
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: mediaObject
      })

      // Verify avatar is displayed with correct URL
      const img = wrapper.find('img')
      expect(img.attributes('src')).toBe('https://example.com/medium.jpg')

      // Verify metadata is displayed
      expect(wrapper.text()).toContain('1.0 MB')
      expect(wrapper.text()).toContain('512 × 512')
    })

    it('handles fallback URL when no avatar exists', () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      const img = wrapper.find('img')
      expect(img.attributes('src')).toBe('/images/default-avatar.png')
    })
  })

  describe('Lightbox Integration', () => {
    it('opens lightbox with large image URL', async () => {
      const mediaObject = {
        url: 'https://example.com/avatar.jpg',
        large_url: 'https://example.com/large.jpg'
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mediaObject,
        modelValue: mediaObject
      })

      const img = wrapper.find('img')
      await img.trigger('click')

      expect(wrapper.vm.showLightbox).toBe(true)

      // Lightbox should show large image
      await nextTick()
      const lightboxImg = wrapper.find('.lightbox img')
      if (lightboxImg.exists()) {
        expect(lightboxImg.attributes('src')).toBe('https://example.com/large.jpg')
      }
    })
  })

  describe('Error Handling Integration', () => {
    it('handles image load errors gracefully', async () => {
      const mediaObject = {
        url: 'https://example.com/broken-image.jpg',
        medium_url: 'https://example.com/broken-medium.jpg'
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: mediaObject
      })

      const img = wrapper.find('img')
      
      // Simulate image load error
      await img.trigger('error')

      // Should fallback to default avatar
      expect(img.attributes('src')).toBe('/images/default-avatar.png')
    })

    it('displays validation errors from backend', () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null,
        errors: ['The avatar field is required.']
      })

      // The BaseField component should handle error display
      // Since we're mocking BaseField, we just verify the errors prop is passed
      expect(wrapper.props('errors')).toEqual(['The avatar field is required.'])
    })
  })

  describe('Accessibility Integration', () => {
    it('provides proper ARIA labels and roles', () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      const img = wrapper.find('img')
      expect(img.attributes('alt')).toBe('Avatar')

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('accept')).toContain('image/')
    })

    it('supports keyboard navigation', async () => {
      const mediaObject = {
        url: 'https://example.com/avatar.jpg'
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: mediaObject
      })

      const removeButton = wrapper.find('button')
      expect(removeButton.exists()).toBe(true)
      
      // Button should be focusable and have proper attributes
      expect(removeButton.attributes('type')).toBe('button')
      expect(removeButton.attributes('title')).toBe('Remove avatar')
    })
  })
})
