import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import MediaLibraryAvatarField from '@/components/Fields/MediaLibraryAvatarField.vue'
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

// Mock URL.createObjectURL
global.URL.createObjectURL = vi.fn(() => 'blob:mock-url')
global.URL.revokeObjectURL = vi.fn()

describe('MediaLibraryAvatarField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Avatar',
      attribute: 'avatar',
      type: 'media_library_avatar',
      acceptedMimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
      maxFileSize: 2048, // 2MB in KB
      fallbackUrl: '/images/default-avatar.png',
      conversions: {
        thumb: { width: 64, height: 64 },
        medium: { width: 128, height: 128 },
        large: { width: 256, height: 256 }
      }
    })

    // Reset URL mocks
    global.URL.createObjectURL.mockClear()
    global.URL.revokeObjectURL.mockClear()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders avatar container with default fallback image', () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      const img = wrapper.find('img')
      expect(img.exists()).toBe(true)
      expect(img.attributes('src')).toBe('/images/default-avatar.png')
      expect(img.attributes('alt')).toBe('Avatar')
    })

    it('displays current avatar when modelValue is a media object', () => {
      const mediaObject = {
        url: 'https://example.com/avatar.jpg',
        medium_url: 'https://example.com/avatar-medium.jpg',
        large_url: 'https://example.com/avatar-large.jpg',
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

      const img = wrapper.find('img')
      expect(img.attributes('src')).toBe('https://example.com/avatar-medium.jpg')
    })

    it('displays current avatar when modelValue is a File object', () => {
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' })

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: file
      })

      expect(global.URL.createObjectURL).toHaveBeenCalledWith(file)

      const img = wrapper.find('img')
      expect(img.attributes('src')).toBe('blob:mock-url')
    })

    it('shows upload area when no avatar is present', () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.find('[data-testid="user-circle-icon"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Upload avatar')
      expect(wrapper.text()).toContain('JPEG, PNG, WebP images')
      expect(wrapper.text()).toContain('Max 2.0 MB')
    })

    it('shows avatar metadata when available', () => {
      const mediaObject = {
        url: 'https://example.com/avatar.jpg',
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

      expect(wrapper.text()).toContain('1.0 MB')
      expect(wrapper.text()).toContain('512 × 512')
      expect(wrapper.text()).toContain('Uploaded')
    })

    it('shows no avatar message when no avatar is uploaded', () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.text()).toContain('No avatar uploaded')
    })



    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      const avatarContainer = wrapper.find('.avatar-container')
      expect(avatarContainer.classes()).toContain('avatar-container-dark')
    })
  })

  describe('Avatar Display', () => {
    it('shows remove button when avatar exists and not readonly', () => {
      const mediaObject = {
        url: 'https://example.com/avatar.jpg'
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: mediaObject
      })

      const removeButton = wrapper.find('button')
      expect(removeButton.exists()).toBe(true)
      expect(removeButton.attributes('title')).toBe('Remove avatar')
      expect(wrapper.find('[data-testid="x-mark-icon"]').exists()).toBe(true)
    })



    it('shows camera overlay when not readonly', () => {
      const mediaObject = {
        url: 'https://example.com/avatar.jpg'
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: mediaObject
      })

      expect(wrapper.find('[data-testid="camera-icon"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Change')
    })



    it('shows available size conversions when available', () => {
      const mediaObject = {
        url: 'https://example.com/avatar.jpg',
        thumb_url: 'https://example.com/avatar-thumb.jpg',
        medium_url: 'https://example.com/avatar-medium.jpg',
        large_url: 'https://example.com/avatar-large.jpg'
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: mediaObject
      })

      expect(wrapper.text()).toContain('Available sizes:')
      expect(wrapper.text()).toContain('thumb')
      expect(wrapper.text()).toContain('medium')
      expect(wrapper.text()).toContain('large')

      const sizeImages = wrapper.findAll('.avatar-size-preview')
      expect(sizeImages).toHaveLength(3)
    })
  })

  describe('File Upload', () => {
    beforeEach(() => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })
    })

    it('handles file selection via input', async () => {
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' })
      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [file],
        writable: false
      })

      await input.trigger('change')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(file)
      expect(wrapper.emitted('change')[0][0]).toBe(file)
    })

    it('opens file dialog when upload area is clicked', async () => {
      const clickSpy = vi.spyOn(HTMLInputElement.prototype, 'click')

      const uploadArea = wrapper.find('.upload-area')
      await uploadArea.trigger('click')

      expect(clickSpy).toHaveBeenCalled()
    })



    it('validates file type', async () => {
      const textFile = new File(['test'], 'test.txt', { type: 'text/plain' })
      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [textFile],
        writable: false
      })

      await input.trigger('change')

      expect(wrapper.text()).toContain('Please select a valid image file')
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('validates file size', async () => {
      // Create a file larger than maxFileSize (2MB)
      const largeFile = new File(['x'.repeat(3 * 1024 * 1024)], 'large.jpg', { type: 'image/jpeg' })
      Object.defineProperty(largeFile, 'size', { value: 3 * 1024 * 1024 })

      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [largeFile],
        writable: false
      })

      await input.trigger('change')

      expect(wrapper.text()).toContain('Avatar size exceeds maximum allowed size')
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('validates MIME type against accepted types', async () => {
      const gifFile = new File(['test'], 'test.gif', { type: 'image/gif' })
      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [gifFile],
        writable: false
      })

      await input.trigger('change')

      expect(wrapper.text()).toContain('Avatar type not allowed')
      expect(wrapper.text()).toContain('JPEG, PNG, WebP images')
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('shows upload error when validation fails', async () => {
      const textFile = new File(['test'], 'test.txt', { type: 'text/plain' })
      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [textFile],
        writable: false
      })

      await input.trigger('change')
      await nextTick()

      expect(wrapper.find('[data-testid="exclamation-circle-icon"]').exists()).toBe(true)
      expect(wrapper.find('.bg-red-50').exists()).toBe(true)
    })


  })

  describe('Drag and Drop', () => {
    beforeEach(() => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })
    })

    it('handles dragover event', async () => {
      const uploadArea = wrapper.find('.upload-area')

      await uploadArea.trigger('dragover')

      expect(wrapper.vm.isDragOver).toBe(true)
      expect(uploadArea.classes()).toContain('upload-area-dragover')
    })

    it('handles dragleave event', async () => {
      const uploadArea = wrapper.find('.upload-area')

      // First set drag over state
      await uploadArea.trigger('dragover')
      expect(wrapper.vm.isDragOver).toBe(true)

      // Then trigger drag leave
      await uploadArea.trigger('dragleave')
      expect(wrapper.vm.isDragOver).toBe(false)
    })


  })

  describe('Remove Avatar', () => {
    it('removes avatar when remove button is clicked', async () => {
      const mediaObject = {
        url: 'https://example.com/avatar.jpg'
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: mediaObject
      })

      const removeButton = wrapper.find('button')
      await removeButton.trigger('click')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
      expect(wrapper.emitted('change')[0][0]).toBe(null)
    })
  })

  describe('Lightbox', () => {
    it('opens lightbox when avatar image is clicked', async () => {
      const mediaObject = {
        url: 'https://example.com/avatar.jpg',
        large_url: 'https://example.com/avatar-large.jpg'
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: mediaObject
      })

      const img = wrapper.find('img')
      await img.trigger('click')

      expect(wrapper.vm.showLightbox).toBe(true)
    })



    it('closes lightbox when background is clicked', async () => {
      const mediaObject = {
        url: 'https://example.com/avatar.jpg'
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: mediaObject
      })

      wrapper.vm.showLightbox = true
      await nextTick()

      const background = wrapper.find('.fixed.inset-0')
      await background.trigger('click')

      expect(wrapper.vm.showLightbox).toBe(false)
    })
  })

  describe('Utility Methods', () => {
    beforeEach(() => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })
    })

    it('formats file size correctly', () => {
      expect(wrapper.vm.formatFileSize(0)).toBe('0 B')
      expect(wrapper.vm.formatFileSize(1024)).toBe('1.0 KB')
      expect(wrapper.vm.formatFileSize(1048576)).toBe('1.0 MB')
      expect(wrapper.vm.formatFileSize(1073741824)).toBe('1.0 GB')
    })

    it('formats date correctly', () => {
      const testDate = '2024-01-01T00:00:00Z'
      const result = wrapper.vm.formatDate(testDate)

      // Should return a formatted date string
      expect(result).toBeTruthy()
      expect(typeof result).toBe('string')
    })

    it('handles invalid date gracefully', () => {
      expect(wrapper.vm.formatDate('')).toBe('')
      expect(wrapper.vm.formatDate(null)).toBe('')
    })

    it('gets avatar URL for different conversions', () => {
      const mediaObject = {
        url: 'https://example.com/avatar.jpg',
        thumb_url: 'https://example.com/avatar-thumb.jpg',
        medium_url: 'https://example.com/avatar-medium.jpg',
        large_url: 'https://example.com/avatar-large.jpg'
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: mediaObject
      })

      expect(wrapper.vm.getAvatarUrl('thumb')).toBe('https://example.com/avatar-thumb.jpg')
      expect(wrapper.vm.getAvatarUrl('medium')).toBe('https://example.com/avatar-medium.jpg')
      expect(wrapper.vm.getAvatarUrl('large')).toBe('https://example.com/avatar-large.jpg')
    })

    it('handles image error by setting fallback URL', () => {
      const mediaObject = {
        url: 'https://example.com/broken-image.jpg'
      }

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: mediaObject
      })

      const img = wrapper.find('img')
      const mockEvent = { target: { src: '' } }

      wrapper.vm.handleImageError(mockEvent)

      expect(mockEvent.target.src).toBe('/images/default-avatar.png')
    })
  })

  describe('Edge Cases', () => {
    it('handles missing field properties gracefully', () => {
      const minimalField = createMockField({
        name: 'Avatar',
        attribute: 'avatar',
        type: 'media_library_avatar'
      })

      wrapper = mountField(MediaLibraryAvatarField, {
        field: minimalField,
        modelValue: null
      })

      expect(wrapper.vm.acceptedTypes).toBe('image/*')
      expect(wrapper.text()).toContain('Image files')
    })

    it('handles null modelValue gracefully', () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.currentAvatarUrl).toBe('/images/default-avatar.png')
    })

    it('handles empty object modelValue gracefully', () => {
      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: {}
      })

      expect(wrapper.vm.currentAvatarUrl).toBe('/images/default-avatar.png')
    })

    it('shows crop aspect ratio hint when specified', () => {
      const fieldWithCrop = createMockField({
        ...mockField,
        cropAspectRatio: '1:1'
      })

      wrapper = mountField(MediaLibraryAvatarField, {
        field: fieldWithCrop,
        modelValue: null
      })

      expect(wrapper.text()).toContain('Recommended ratio: 1:1')
    })



    it('applies dark theme styling to upload area', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(MediaLibraryAvatarField, {
        field: mockField,
        modelValue: null
      })

      const uploadArea = wrapper.find('.upload-area')
      expect(uploadArea.classes()).toContain('upload-area-dark')
    })
  })
})
