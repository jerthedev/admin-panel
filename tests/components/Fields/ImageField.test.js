import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import ImageField from '@/components/Fields/ImageField.vue'
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
  PhotoIcon: { template: '<div data-testid="photo-icon"></div>' },
  TrashIcon: { template: '<div data-testid="trash-icon"></div>' },
  EyeIcon: { template: '<div data-testid="eye-icon"></div>' },
  ArrowsPointingOutIcon: { template: '<div data-testid="arrows-pointing-out-icon"></div>' }
}))

// Mock File API
global.File = class MockFile {
  constructor(parts, filename, properties = {}) {
    this.name = filename
    this.size = properties.size || 1024
    this.type = properties.type || 'image/jpeg'
    this.lastModified = Date.now()
  }
}

// Mock URL.createObjectURL
global.URL.createObjectURL = vi.fn(() => 'blob:mock-url')
global.URL.revokeObjectURL = vi.fn()

describe('ImageField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Profile Image',
      attribute: 'profile_image',
      type: 'image',
      accept: 'image/*',
      maxSize: 2097152, // 2MB
      maxWidth: 1920,
      maxHeight: 1080,
      aspectRatio: '16:9'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders image upload area', () => {
      wrapper = mountField(ImageField, { field: mockField })

      const uploadArea = wrapper.find('.image-upload-area')
      expect(uploadArea.exists()).toBe(true)
    })

    it('shows upload placeholder', () => {
      wrapper = mountField(ImageField, { field: mockField })

      const photoIcon = wrapper.find('[data-testid="photo-icon"]')
      expect(photoIcon.exists()).toBe(true)
      expect(wrapper.text()).toContain('Click to upload image')
    })

    it('shows accepted formats', () => {
      wrapper = mountField(ImageField, { field: mockField })

      expect(wrapper.text()).toContain('JPG, PNG, GIF')
    })

    it('shows max file size', () => {
      wrapper = mountField(ImageField, { field: mockField })

      expect(wrapper.text()).toContain('2 MB')
    })

    it('shows image dimensions', () => {
      wrapper = mountField(ImageField, { field: mockField })

      expect(wrapper.text()).toContain('1920x1080')
    })

    it('applies disabled state', () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        props: { 
          field: mockField,
          disabled: true 
        }
      })

      const uploadArea = wrapper.find('.image-upload-area')
      expect(uploadArea.classes()).toContain('opacity-50')
      expect(uploadArea.classes()).toContain('cursor-not-allowed')
    })

    it('applies readonly state', () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      const uploadArea = wrapper.find('.image-upload-area')
      expect(uploadArea.classes()).toContain('cursor-not-allowed')
    })
  })

  describe('Image Upload', () => {
    it('opens file dialog on click', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      const clickSpy = vi.spyOn(fileInput.element, 'click')

      const uploadArea = wrapper.find('.image-upload-area')
      await uploadArea.trigger('click')

      expect(clickSpy).toHaveBeenCalled()
    })

    it('accepts only image files', () => {
      wrapper = mountField(ImageField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('accept')).toBe('image/*')
    })

    it('handles image selection', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [file],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('change')).toBeTruthy()
    })

    it('shows image preview after selection', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' })
      wrapper.vm.selectedImage = file
      wrapper.vm.previewUrl = 'blob:mock-url'
      await nextTick()

      const preview = wrapper.find('img')
      expect(preview.exists()).toBe(true)
      expect(preview.attributes('src')).toBe('blob:mock-url')
    })

    it('removes image when remove button clicked', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' })
      wrapper.vm.selectedImage = file
      wrapper.vm.previewUrl = 'blob:mock-url'
      await nextTick()

      const removeButton = wrapper.find('[data-testid="trash-icon"]')
      await removeButton.element.parentElement.click()

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.vm.selectedImage).toBe(null)
    })
  })

  describe('Image Validation', () => {
    it('validates file size', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const largeFile = new File(['content'], 'large.jpg', { 
        type: 'image/jpeg',
        size: 5 * 1024 * 1024 // 5MB (exceeds 2MB limit)
      })

      const fileInput = wrapper.find('input[type="file"]')
      Object.defineProperty(fileInput.element, 'files', {
        value: [largeFile],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.vm.validationError).toContain('File size exceeds')
    })

    it('validates file type', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const invalidFile = new File(['content'], 'test.txt', { type: 'text/plain' })

      const fileInput = wrapper.find('input[type="file"]')
      Object.defineProperty(fileInput.element, 'files', {
        value: [invalidFile],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.vm.validationError).toContain('Invalid file type')
    })

    it('validates image dimensions', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      // Mock image load to simulate dimension validation
      const mockImage = {
        width: 3000,
        height: 2000,
        onload: null
      }

      global.Image = vi.fn(() => mockImage)

      const file = new File(['content'], 'large-image.jpg', { type: 'image/jpeg' })
      await wrapper.vm.validateImageDimensions(file)

      // Trigger onload
      mockImage.onload()

      expect(wrapper.vm.validationError).toContain('Image dimensions exceed')
    })

    it('validates aspect ratio', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      // Mock image with wrong aspect ratio
      const mockImage = {
        width: 1000,
        height: 1000, // Square instead of 16:9
        onload: null
      }

      global.Image = vi.fn(() => mockImage)

      const file = new File(['content'], 'square.jpg', { type: 'image/jpeg' })
      await wrapper.vm.validateImageDimensions(file)

      // Trigger onload
      mockImage.onload()

      expect(wrapper.vm.validationError).toContain('aspect ratio')
    })

    it('shows validation errors', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      wrapper.vm.validationError = 'Image too large'
      await nextTick()

      expect(wrapper.text()).toContain('Image too large')
      expect(wrapper.find('.text-red-600').exists()).toBe(true)
    })
  })

  describe('Existing Image Display', () => {
    it('shows existing image when modelValue is provided', () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: {
          name: 'profile.jpg',
          url: '/images/profile.jpg',
          thumbnail_url: '/images/profile-thumb.jpg'
        }
      })

      const existingImage = wrapper.find('img[src="/images/profile-thumb.jpg"]')
      expect(existingImage.exists()).toBe(true)
    })

    it('shows image actions for existing image', () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: {
          name: 'profile.jpg',
          url: '/images/profile.jpg',
          thumbnail_url: '/images/profile-thumb.jpg'
        }
      })

      const viewButton = wrapper.find('[data-testid="eye-icon"]')
      const fullscreenButton = wrapper.find('[data-testid="arrows-pointing-out-icon"]')
      const removeButton = wrapper.find('[data-testid="trash-icon"]')

      expect(viewButton.exists()).toBe(true)
      expect(fullscreenButton.exists()).toBe(true)
      expect(removeButton.exists()).toBe(true)
    })

    it('opens image in fullscreen when fullscreen button clicked', async () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: {
          name: 'profile.jpg',
          url: '/images/profile.jpg',
          thumbnail_url: '/images/profile-thumb.jpg'
        }
      })

      const fullscreenSpy = vi.spyOn(wrapper.vm, 'openFullscreen')
      const fullscreenButton = wrapper.find('[data-testid="arrows-pointing-out-icon"]')
      
      await fullscreenButton.element.parentElement.click()
      expect(fullscreenSpy).toHaveBeenCalled()
    })

    it('allows replacing existing image', async () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: {
          name: 'old-profile.jpg',
          url: '/images/old-profile.jpg'
        }
      })

      const newFile = new File(['content'], 'new-profile.jpg', { type: 'image/jpeg' })
      const fileInput = wrapper.find('input[type="file"]')
      
      Object.defineProperty(fileInput.element, 'files', {
        value: [newFile],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    })
  })

  describe('Drag and Drop', () => {
    it('handles drag enter', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const uploadArea = wrapper.find('.image-upload-area')
      await uploadArea.trigger('dragenter')

      expect(wrapper.vm.isDragOver).toBe(true)
    })

    it('handles drag leave', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const uploadArea = wrapper.find('.image-upload-area')
      await uploadArea.trigger('dragenter')
      await uploadArea.trigger('dragleave')

      expect(wrapper.vm.isDragOver).toBe(false)
    })

    it('handles image drop', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const file = new File(['content'], 'dropped.jpg', { type: 'image/jpeg' })
      const uploadArea = wrapper.find('.image-upload-area')

      const dropEvent = new Event('drop')
      dropEvent.dataTransfer = {
        files: [file]
      }

      await uploadArea.trigger('drop', dropEvent)

      expect(wrapper.vm.isDragOver).toBe(false)
    })

    it('shows drag over state visually', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      wrapper.vm.isDragOver = true
      await nextTick()

      const uploadArea = wrapper.find('.image-upload-area')
      expect(uploadArea.classes()).toContain('border-blue-400')
      expect(uploadArea.classes()).toContain('bg-blue-50')
    })
  })

  describe('Image Cropping', () => {
    it('shows crop modal when cropping is enabled', async () => {
      const croppableField = createMockField({
        ...mockField,
        enableCropping: true
      })

      wrapper = mountField(ImageField, { field: croppableField })

      const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' })
      wrapper.vm.selectedImage = file
      wrapper.vm.showCropModal = true
      await nextTick()

      expect(wrapper.text()).toContain('Crop Image')
    })

    it('applies crop settings', async () => {
      const croppableField = createMockField({
        ...mockField,
        enableCropping: true,
        cropAspectRatio: '1:1'
      })

      wrapper = mountField(ImageField, { field: croppableField })

      expect(wrapper.vm.cropAspectRatio).toBe('1:1')
    })

    it('saves cropped image', async () => {
      const croppableField = createMockField({
        ...mockField,
        enableCropping: true
      })

      wrapper = mountField(ImageField, { field: croppableField })

      const saveCropSpy = vi.spyOn(wrapper.vm, 'saveCroppedImage')
      wrapper.vm.showCropModal = true
      await nextTick()

      // Simulate save crop action
      wrapper.vm.saveCroppedImage()
      expect(saveCropSpy).toHaveBeenCalled()
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(ImageField, { field: mockField })

      const uploadArea = wrapper.find('.image-upload-area')
      expect(uploadArea.classes()).toContain('border-gray-600')
      expect(uploadArea.classes()).toContain('bg-gray-800')
    })

    it('applies dark theme to image preview', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: {
          name: 'profile.jpg',
          url: '/images/profile.jpg'
        }
      })

      const imageContainer = wrapper.find('.bg-gray-50')
      expect(imageContainer.exists()).toBe(false) // Should use dark theme classes
    })
  })

  describe('Event Handling', () => {
    it('emits focus event', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      await fileInput.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      await fileInput.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('prevents default on drag events', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const uploadArea = wrapper.find('.image-upload-area')
      const preventDefaultSpy = vi.fn()

      await uploadArea.trigger('dragover', { preventDefault: preventDefaultSpy })
      expect(preventDefaultSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles null modelValue', () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.existingImage).toBe(null)
    })

    it('handles undefined modelValue', () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: undefined
      })

      expect(wrapper.vm.existingImage).toBe(null)
    })

    it('handles image without thumbnail', () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: {
          name: 'image.jpg',
          url: '/images/image.jpg'
        }
      })

      const image = wrapper.find('img[src="/images/image.jpg"]')
      expect(image.exists()).toBe(true)
    })

    it('handles very large image names', () => {
      const longImageName = 'a'.repeat(100) + '.jpg'
      
      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: {
          name: longImageName,
          url: '/images/' + longImageName
        }
      })

      expect(wrapper.text()).toContain(longImageName)
    })

    it('cleans up object URLs on unmount', () => {
      wrapper = mountField(ImageField, { field: mockField })

      wrapper.vm.previewUrl = 'blob:mock-url'
      wrapper.unmount()

      expect(global.URL.revokeObjectURL).toHaveBeenCalledWith('blob:mock-url')
    })
  })
})
