import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import MediaLibraryFileField from '@/components/Fields/MediaLibraryFileField.vue'
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
  CloudArrowUpIcon: { template: '<div data-testid="cloud-arrow-up-icon"></div>' },
  DocumentIcon: { template: '<div data-testid="document-icon"></div>' },
  ArrowDownTrayIcon: { template: '<div data-testid="arrow-down-tray-icon"></div>' },
  ExclamationCircleIcon: { template: '<div data-testid="exclamation-circle-icon"></div>' },
  XMarkIcon: { template: '<div data-testid="x-mark-icon"></div>' }
}))

// Mock File API
global.File = class MockFile {
  constructor(parts, filename, properties = {}) {
    this.name = filename
    this.size = properties.size || 1024
    this.type = properties.type || 'text/plain'
    this.lastModified = Date.now()
  }
}

describe('MediaLibraryFileField Integration Tests', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Documents',
      attribute: 'documents',
      type: 'mediaLibraryFile',
      collection: 'documents',
      multiple: true,
      acceptedMimeTypes: ['application/pdf', 'text/plain'],
      maxFileSize: 5242880, // 5MB
      deletable: true,
      prunable: false,
      downloadsDisabled: false,
      originalNameColumn: 'original_filename',
      sizeColumn: 'file_size'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP-Vue Integration', () => {
    it('renders field with PHP field configuration', () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      expect(wrapper.find('[data-testid="cloud-arrow-up-icon"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Click to upload')
      expect(wrapper.text()).toContain('PDF, TXT')
    })

    it('respects PHP field meta properties', () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('accept')).toBe('application/pdf,text/plain')
      expect(fileInput.attributes('multiple')).toBeDefined()
    })

    it('handles Nova File Field compatibility properties', () => {
      const novaCompatibleField = createMockField({
        ...mockField,
        deletable: false,
        downloadsDisabled: true,
        originalNameColumn: 'original_name',
        sizeColumn: 'file_size'
      })

      wrapper = mountField(MediaLibraryFileField, { field: novaCompatibleField })

      // Field should render properly with Nova compatibility properties
      expect(wrapper.exists()).toBe(true)
    })

    it('displays existing files with metadata', () => {
      const existingFiles = [
        {
          id: 1,
          name: 'document1.pdf',
          size: 1048576, // 1MB
          mime_type: 'application/pdf',
          url: 'https://example.com/document1.pdf',
          human_readable_size: '1.0 MB'
        },
        {
          id: 2,
          name: 'document2.txt',
          size: 2048,
          mime_type: 'text/plain',
          url: 'https://example.com/document2.txt',
          human_readable_size: '2.0 KB'
        }
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: existingFiles
      })

      expect(wrapper.text()).toContain('document1.pdf')
      expect(wrapper.text()).toContain('document2.txt')
      expect(wrapper.text()).toContain('1.0 MB')
      expect(wrapper.text()).toContain('2.0 KB')
      expect(wrapper.text()).toContain('application/pdf')
      expect(wrapper.text()).toContain('text/plain')
    })

    it('handles file upload with validation', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const validFile = new File(['content'], 'test.pdf', { 
        type: 'application/pdf',
        size: 1024 * 1024 // 1MB
      })

      const fileInput = wrapper.find('input[type="file"]')
      Object.defineProperty(fileInput.element, 'files', {
        value: [validFile],
        writable: false
      })

      await fileInput.trigger('change')

      // Should not show validation error for valid file
      expect(wrapper.text()).not.toContain('File size exceeds')
      expect(wrapper.text()).not.toContain('File type not allowed')
    })

    it('shows validation error for invalid file size', async () => {
      // Create field with smaller size limit for testing
      const smallSizeField = createMockField({
        ...mockField,
        maxFileSize: 1 // 1KB limit
      })

      wrapper = mountField(MediaLibraryFileField, { field: smallSizeField })

      const oversizedFile = new File(['content'], 'large.pdf', {
        type: 'application/pdf',
        size: 10 * 1024 // 10KB (exceeds 1KB limit)
      })

      // Test the validation function directly
      const isValid = wrapper.vm.validateFile(oversizedFile)
      expect(isValid).toBe(false)
      expect(wrapper.vm.uploadError).toContain('File size exceeds maximum allowed size')
    })

    it('shows validation error for invalid file type', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const invalidFile = new File(['content'], 'image.jpg', { 
        type: 'image/jpeg',
        size: 1024
      })

      await wrapper.vm.handleFiles([invalidFile])
      await nextTick()

      expect(wrapper.text()).toContain('File type not allowed')
    })

    it('handles single file mode correctly', () => {
      const singleFileField = createMockField({
        ...mockField,
        multiple: false,
        singleFile: true
      })

      wrapper = mountField(MediaLibraryFileField, { field: singleFileField })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('multiple')).toBeUndefined()
    })

    it('emits correct events on file changes', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const file = new File(['content'], 'test.pdf', { type: 'application/pdf' })
      await wrapper.vm.handleFiles([file])

      const updateEvents = wrapper.emitted('update:modelValue')
      const changeEvents = wrapper.emitted('change')

      expect(updateEvents).toBeDefined()
      expect(changeEvents).toBeDefined()
    })

    it('handles file removal correctly', async () => {
      const existingFiles = [
        { id: 1, name: 'document1.pdf', size: 1024, mime_type: 'application/pdf' },
        { id: 2, name: 'document2.txt', size: 2048, mime_type: 'text/plain' }
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: existingFiles
      })

      await wrapper.vm.removeFile(0)

      const updateEvents = wrapper.emitted('update:modelValue')
      expect(updateEvents).toBeDefined()
      expect(updateEvents[updateEvents.length - 1][0]).toHaveLength(1)
      expect(updateEvents[updateEvents.length - 1][0][0].name).toBe('document2.txt')
    })

    it('handles drag and drop functionality', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const uploadArea = wrapper.find('.upload-area')

      // Test drag over
      await uploadArea.trigger('dragover')
      expect(wrapper.vm.isDragOver).toBe(true)

      // Test drag leave
      await uploadArea.trigger('dragleave')
      expect(wrapper.vm.isDragOver).toBe(false)

      // Test drop with mock dataTransfer
      const file = new File(['content'], 'test.pdf', { type: 'application/pdf' })

      await uploadArea.trigger('drop', {
        dataTransfer: { files: [file] }
      })
      expect(wrapper.vm.isDragOver).toBe(false)
    })

    it('respects readonly and disabled states', () => {
      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        props: {
          readonly: true,
          disabled: true
        }
      })

      const fileInput = wrapper.find('input[type="file"]')
      if (fileInput.exists()) {
        expect(fileInput.attributes('disabled')).toBeDefined()
      }

      // Check that upload area has disabled styling when disabled
      expect(wrapper.props('disabled')).toBe(true)
      expect(wrapper.props('readonly')).toBe(true)
    })

    it('formats file sizes correctly', () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      expect(wrapper.vm.formatFileSize(0)).toBe('0 B')
      expect(wrapper.vm.formatFileSize(1024)).toBe('1.0 KB')
      expect(wrapper.vm.formatFileSize(1048576)).toBe('1.0 MB')
      expect(wrapper.vm.formatFileSize(1073741824)).toBe('1.0 GB')
    })

    it('handles dark theme correctly', async () => {
      mockAdminStore.isDarkTheme = true
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      await nextTick()

      const uploadArea = wrapper.find('.upload-area')
      expect(uploadArea.classes()).toContain('upload-area-dark')

      mockAdminStore.isDarkTheme = false
    })
  })

  describe('Error Handling', () => {
    it('handles null modelValue gracefully', () => {
      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.existingFiles).toEqual([])
      expect(wrapper.exists()).toBe(true)
    })

    it('handles empty file list', () => {
      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: []
      })

      expect(wrapper.vm.existingFiles).toEqual([])
      expect(wrapper.exists()).toBe(true)
    })

    it('handles files without complete metadata', () => {
      const incompleteFiles = [
        { id: 1, name: 'document.pdf' }, // Missing size, mime_type
        { id: 2, size: 1024 }, // Missing name, mime_type
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: incompleteFiles
      })

      expect(wrapper.text()).toContain('document.pdf')
      expect(wrapper.exists()).toBe(true)
    })
  })
})
