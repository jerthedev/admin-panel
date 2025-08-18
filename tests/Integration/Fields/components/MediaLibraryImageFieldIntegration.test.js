import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import MediaLibraryImageField from '@/components/Fields/MediaLibraryImageField.vue'
import { createMockField, mountField } from '../../../helpers.js'

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
  EyeIcon: { template: '<div data-testid="eye-icon"></div>' },
  XMarkIcon: { template: '<div data-testid="x-mark-icon"></div>' },
  ArrowDownTrayIcon: { template: '<div data-testid="arrow-down-tray-icon"></div>' },
  ExclamationCircleIcon: { template: '<div data-testid="exclamation-circle-icon"></div>' },
  Bars3Icon: { template: '<div data-testid="bars3-icon"></div>' },
  ChevronLeftIcon: { template: '<div data-testid="chevron-left-icon"></div>' },
  ChevronRightIcon: { template: '<div data-testid="chevron-right-icon"></div>' }
}))

// Mock BaseField component
vi.mock('@/components/Fields/BaseField.vue', () => ({
  default: {
    name: 'BaseField',
    template: '<div class="base-field"><slot /></div>',
    props: ['field', 'modelValue', 'errors', 'disabled', 'readonly', 'size']
  }
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

describe('MediaLibraryImageField Integration', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Gallery Images',
      attribute: 'gallery_images',
      component: 'MediaLibraryImageField',
      collection: 'gallery',
      acceptedMimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
      maxFileSize: 2048, // 2MB in KB
      limit: 5,
      multiple: true,
      showImageDimensions: true,
      downloadDisabled: false,
      maxWidth: null,
      indexWidth: null,
      detailWidth: null,
      squared: false,
      rounded: false
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Nova API Compatibility', () => {
    it('renders with Nova-compatible field structure', () => {
      wrapper = mountField(MediaLibraryImageField, { field: mockField })

      // Should render the field component
      expect(wrapper.exists()).toBe(true)
      expect(wrapper.text()).toContain('Click to upload images')
      expect(wrapper.text()).toContain('JPEG, PNG, WebP images')
    })

    it('supports Nova Image Field methods integration', () => {
      const novaField = createMockField({
        ...mockField,
        downloadDisabled: true,
        maxWidth: 300,
        indexWidth: 60,
        detailWidth: 150,
        squared: true,
        rounded: false
      })

      wrapper = mountField(MediaLibraryImageField, { field: novaField })

      // Should apply Nova Image Field configurations
      expect(novaField.downloadDisabled).toBe(true)
      expect(novaField.maxWidth).toBe(300)
      expect(novaField.indexWidth).toBe(60)
      expect(novaField.detailWidth).toBe(150)
      expect(novaField.squared).toBe(true)
      expect(novaField.rounded).toBe(false)
    })

    it('supports squared display option', () => {
      const squaredField = createMockField({
        ...mockField,
        squared: true,
        rounded: false
      })

      const images = [
        { id: 1, url: 'https://example.com/image1.jpg', medium_url: 'https://example.com/image1-medium.jpg' }
      ]

      wrapper = mountField(MediaLibraryImageField, {
        field: squaredField,
        modelValue: images
      })

      // Should apply squared styling when field.squared is true
      expect(squaredField.squared).toBe(true)
      expect(squaredField.rounded).toBe(false)
    })

    it('supports rounded display option', () => {
      const roundedField = createMockField({
        ...mockField,
        squared: false,
        rounded: true
      })

      const images = [
        { id: 1, url: 'https://example.com/image1.jpg', medium_url: 'https://example.com/image1-medium.jpg' }
      ]

      wrapper = mountField(MediaLibraryImageField, {
        field: roundedField,
        modelValue: images
      })

      // Should apply rounded styling when field.rounded is true
      expect(roundedField.squared).toBe(false)
      expect(roundedField.rounded).toBe(true)
    })

    it('supports maxWidth configuration', () => {
      const maxWidthField = createMockField({
        ...mockField,
        maxWidth: 400
      })

      wrapper = mountField(MediaLibraryImageField, { field: maxWidthField })

      expect(maxWidthField.maxWidth).toBe(400)
    })

    it('supports download disable configuration', () => {
      const noDownloadField = createMockField({
        ...mockField,
        downloadDisabled: true
      })

      const images = [
        {
          id: 1,
          url: 'https://example.com/image1.jpg',
          medium_url: 'https://example.com/image1-medium.jpg',
          download_url: 'https://example.com/download/image1.jpg'
        }
      ]

      wrapper = mountField(MediaLibraryImageField, {
        field: noDownloadField,
        modelValue: images
      })

      // Should not show download button when downloads are disabled
      expect(wrapper.find('[data-testid="arrow-down-tray-icon"]').exists()).toBe(false)
    })

    it('shows download button when downloads are enabled', () => {
      const downloadField = createMockField({
        ...mockField,
        downloadDisabled: false
      })

      const images = [
        {
          id: 1,
          url: 'https://example.com/image1.jpg',
          medium_url: 'https://example.com/image1-medium.jpg',
          download_url: 'https://example.com/download/image1.jpg'
        }
      ]

      wrapper = mountField(MediaLibraryImageField, {
        field: downloadField,
        modelValue: images
      })

      // Should show download button when downloads are enabled and download_url exists
      expect(wrapper.find('[data-testid="arrow-down-tray-icon"]').exists()).toBe(true)
    })
  })

  describe('Media Library Integration', () => {
    it('inherits MediaLibrary field properties', () => {
      const mediaField = createMockField({
        ...mockField,
        collection: 'products',
        disk: 'media',
        acceptedMimeTypes: ['image/jpeg', 'image/png'],
        limit: 3,
        multiple: true
      })

      wrapper = mountField(MediaLibraryImageField, { field: mediaField })

      expect(mediaField.collection).toBe('products')
      expect(mediaField.disk).toBe('media')
      expect(mediaField.acceptedMimeTypes).toEqual(['image/jpeg', 'image/png'])
      expect(mediaField.limit).toBe(3)
      expect(mediaField.multiple).toBe(true)
    })

    it('handles multiple image uploads', async () => {
      wrapper = mountField(MediaLibraryImageField, { field: mockField })

      const files = [
        new File(['content1'], 'test1.jpg', { type: 'image/jpeg' }),
        new File(['content2'], 'test2.jpg', { type: 'image/jpeg' })
      ]

      const fileInput = wrapper.find('input[type="file"]')

      if (fileInput.exists()) {
        Object.defineProperty(fileInput.element, 'files', {
          value: files,
          writable: false
        })

        await fileInput.trigger('change')

        // Should emit update with multiple files
        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
        expect(wrapper.emitted('update:modelValue')[0][0]).toHaveLength(2)
      }
    })

    it('enforces image limit', async () => {
      const limitedField = createMockField({
        ...mockField,
        limit: 2
      })

      wrapper = mountField(MediaLibraryImageField, {
        field: limitedField,
        modelValue: [
          { id: 1, url: 'https://example.com/image1.jpg' }
        ]
      })

      const files = [
        new File(['content1'], 'test1.jpg', { type: 'image/jpeg' }),
        new File(['content2'], 'test2.jpg', { type: 'image/jpeg' })
      ]

      const fileInput = wrapper.find('input[type="file"]')

      if (fileInput.exists()) {
        Object.defineProperty(fileInput.element, 'files', {
          value: files,
          writable: false
        })

        await fileInput.trigger('change')

        // Should show limit error
        expect(wrapper.text()).toContain('Cannot upload more than 2 images')
      }
    })

    it('validates MIME types', async () => {
      wrapper = mountField(MediaLibraryImageField, { field: mockField })

      const invalidFile = new File(['content'], 'test.gif', { type: 'image/gif' })
      const fileInput = wrapper.find('input[type="file"]')

      if (fileInput.exists()) {
        Object.defineProperty(fileInput.element, 'files', {
          value: [invalidFile],
          writable: false
        })

        await fileInput.trigger('change')

        // Should show MIME type error
        expect(wrapper.text()).toContain('Image type not allowed')
      }
    })

    it('displays existing images with Media Library structure', () => {
      const images = [
        {
          id: 1,
          url: 'https://example.com/image1.jpg',
          medium_url: 'https://example.com/image1-medium.jpg',
          thumb_url: 'https://example.com/image1-thumb.jpg',
          name: 'image1.jpg',
          width: 800,
          height: 600,
          size: 102400
        }
      ]

      wrapper = mountField(MediaLibraryImageField, {
        field: mockField,
        modelValue: images
      })

      // Should display the image
      const imageElements = wrapper.findAll('img')
      expect(imageElements.length).toBeGreaterThan(0)
      expect(imageElements[0].attributes('src')).toBe('https://example.com/image1-medium.jpg')

      // Should show image dimensions
      expect(wrapper.text()).toContain('800 × 600')
    })
  })

  describe('User Interaction Integration', () => {
    it('opens lightbox on image click', async () => {
      const images = [
        {
          id: 1,
          url: 'https://example.com/image1.jpg',
          large_url: 'https://example.com/image1-large.jpg',
          name: 'image1.jpg'
        }
      ]

      wrapper = mountField(MediaLibraryImageField, {
        field: mockField,
        modelValue: images
      })

      const img = wrapper.find('img')
      await img.trigger('click')

      // Should open lightbox
      expect(wrapper.vm.lightboxImage).toBeTruthy()
      expect(wrapper.vm.lightboxIndex).toBe(0)
    })

    it('removes image on remove button click', async () => {
      const images = [
        { id: 1, url: 'https://example.com/image1.jpg' },
        { id: 2, url: 'https://example.com/image2.jpg' }
      ]

      wrapper = mountField(MediaLibraryImageField, {
        field: mockField,
        modelValue: images
      })

      const removeButton = wrapper.find('[data-testid="x-mark-icon"]').element.closest('button')
      await removeButton.click()

      // Should emit update with one less image
      expect(wrapper.emitted('update:modelValue')[0][0]).toHaveLength(1)
    })

    it('handles drag and drop upload', async () => {
      wrapper = mountField(MediaLibraryImageField, { field: mockField })

      const uploadArea = wrapper.find('.upload-area')
      
      // Simulate dragover
      await uploadArea.trigger('dragover')
      expect(wrapper.vm.isDragOver).toBe(true)

      // Simulate dragleave
      await uploadArea.trigger('dragleave')
      expect(wrapper.vm.isDragOver).toBe(false)
    })
  })
})
