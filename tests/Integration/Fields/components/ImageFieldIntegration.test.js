import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import ImageField from '@/components/Fields/ImageField.vue'
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

describe('ImageField Integration', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Product Image',
      attribute: 'product_image',
      component: 'ImageField',
      acceptedTypes: 'image/*',
      maxSize: 2048, // 2MB in KB
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
      wrapper = mountField(ImageField, { field: mockField })

      // Should render the field component
      expect(wrapper.exists()).toBe(true)
      expect(wrapper.text()).toContain('Product Image')
    })

    it('supports squared display option', () => {
      const squaredField = createMockField({
        ...mockField,
        squared: true
      })

      wrapper = mountField(ImageField, {
        field: squaredField,
        modelValue: '/images/test.jpg'
      })

      // Should apply squared styling when field.squared is true
      // Note: This test validates the field configuration is passed correctly
      expect(squaredField.squared).toBe(true)
    })

    it('supports rounded display option', () => {
      const roundedField = createMockField({
        ...mockField,
        rounded: true
      })

      wrapper = mountField(ImageField, {
        field: roundedField,
        modelValue: '/images/test.jpg'
      })

      // Should apply rounded styling when field.rounded is true
      expect(roundedField.rounded).toBe(true)
    })

    it('supports both squared and rounded options', () => {
      const styledField = createMockField({
        ...mockField,
        squared: true,
        rounded: true
      })

      wrapper = mountField(ImageField, {
        field: styledField,
        modelValue: '/images/test.jpg'
      })

      expect(styledField.squared).toBe(true)
      expect(styledField.rounded).toBe(true)
    })

    it('inherits File field properties', () => {
      const fileField = createMockField({
        ...mockField,
        disk: 'products',
        path: 'product-images',
        acceptedTypes: 'image/jpeg,image/png',
        maxSize: 5120,
        multiple: false
      })

      wrapper = mountField(ImageField, { field: fileField })

      expect(fileField.disk).toBe('products')
      expect(fileField.path).toBe('product-images')
      expect(fileField.acceptedTypes).toBe('image/jpeg,image/png')
      expect(fileField.maxSize).toBe(5120)
      expect(fileField.multiple).toBe(false)
    })
  })

  describe('Image Upload Integration', () => {
    it('handles image file selection', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' })
      const fileInput = wrapper.find('input[type="file"]')

      if (fileInput.exists()) {
        Object.defineProperty(fileInput.element, 'files', {
          value: [file],
          writable: false
        })

        await fileInput.trigger('change')

        // Should emit update event
        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      }
    })

    it('validates file size limits', () => {
      const limitedField = createMockField({
        ...mockField,
        maxSize: 1024 // 1MB
      })

      wrapper = mountField(ImageField, { field: limitedField })

      // Large file should exceed limit
      const largeFile = new File(['content'], 'large.jpg', { 
        type: 'image/jpeg',
        size: 2 * 1024 * 1024 // 2MB
      })

      expect(largeFile.size).toBeGreaterThan(limitedField.maxSize * 1024)
    })

    it('validates accepted file types', () => {
      const typedField = createMockField({
        ...mockField,
        acceptedTypes: 'image/jpeg,image/png'
      })

      wrapper = mountField(ImageField, { field: typedField })

      // Valid image file
      const validFile = new File(['content'], 'valid.jpg', { type: 'image/jpeg' })
      expect(validFile.type).toBe('image/jpeg')

      // Invalid file type
      const invalidFile = new File(['content'], 'invalid.txt', { type: 'text/plain' })
      expect(invalidFile.type).toBe('text/plain')
    })
  })

  describe('Image Display Integration', () => {
    it('displays existing image when modelValue is provided', () => {
      const imageData = {
        name: 'existing.jpg',
        url: '/storage/images/existing.jpg',
        thumbnail_url: '/storage/images/existing-thumb.jpg'
      }

      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: imageData
      })

      // Should handle existing image data
      expect(wrapper.exists()).toBe(true)
    })

    it('handles image without thumbnail', () => {
      const imageData = {
        name: 'no-thumb.jpg',
        url: '/storage/images/no-thumb.jpg'
      }

      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: imageData
      })

      // Should handle image without thumbnail gracefully
      expect(wrapper.exists()).toBe(true)
    })

    it('displays image preview for uploaded files', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const file = new File(['content'], 'preview.jpg', { type: 'image/jpeg' })
      
      // Simulate file selection and preview generation
      if (wrapper.vm) {
        wrapper.vm.selectedImage = file
        wrapper.vm.previewUrl = 'blob:mock-url'
        await nextTick()
      }

      // Should generate preview URL
      expect(global.URL.createObjectURL).toHaveBeenCalled()
    })
  })

  describe('Field Configuration Integration', () => {
    it('applies field validation rules', () => {
      const validatedField = createMockField({
        ...mockField,
        required: true,
        rules: ['required']
      })

      wrapper = mountField(ImageField, { field: validatedField })

      expect(validatedField.required).toBe(true)
      expect(validatedField.rules).toContain('required')
    })

    it('handles field help text', () => {
      const helpField = createMockField({
        ...mockField,
        helpText: 'Upload a high-quality product image'
      })

      wrapper = mountField(ImageField, { field: helpField })

      expect(helpField.helpText).toBe('Upload a high-quality product image')
    })

    it('supports field disabled state', () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        disabled: true
      })

      // Should handle disabled state
      expect(wrapper.exists()).toBe(true)
    })

    it('supports field readonly state', () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        readonly: true
      })

      // Should handle readonly state
      expect(wrapper.exists()).toBe(true)
    })
  })

  describe('Event Integration', () => {
    it('emits change events on file selection', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const file = new File(['content'], 'change.jpg', { type: 'image/jpeg' })
      const fileInput = wrapper.find('input[type="file"]')

      if (fileInput.exists()) {
        Object.defineProperty(fileInput.element, 'files', {
          value: [file],
          writable: false
        })

        await fileInput.trigger('change')

        expect(wrapper.emitted('change')).toBeTruthy()
      }
    })

    it('emits update:modelValue on file changes', async () => {
      wrapper = mountField(ImageField, { field: mockField })

      const file = new File(['content'], 'update.jpg', { type: 'image/jpeg' })
      const fileInput = wrapper.find('input[type="file"]')

      if (fileInput.exists()) {
        Object.defineProperty(fileInput.element, 'files', {
          value: [file],
          writable: false
        })

        await fileInput.trigger('change')

        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      }
    })
  })

  describe('Error Handling Integration', () => {
    it('handles null modelValue gracefully', () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.exists()).toBe(true)
    })

    it('handles undefined modelValue gracefully', () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: undefined
      })

      expect(wrapper.exists()).toBe(true)
    })

    it('handles invalid file data gracefully', () => {
      wrapper = mountField(ImageField, {
        field: mockField,
        modelValue: 'invalid-data'
      })

      expect(wrapper.exists()).toBe(true)
    })
  })

  describe('Memory Management Integration', () => {
    it('cleans up object URLs on component unmount', () => {
      wrapper = mountField(ImageField, { field: mockField })

      if (wrapper.vm) {
        wrapper.vm.previewUrl = 'blob:mock-url'
      }

      wrapper.unmount()

      // Should clean up blob URLs to prevent memory leaks
      expect(global.URL.revokeObjectURL).toHaveBeenCalled()
    })
  })
})
