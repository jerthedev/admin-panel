import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import FileField from '@/components/Fields/FileField.vue'
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
  DocumentIcon: { template: '<div data-testid="document-icon"></div>' },
  TrashIcon: { template: '<div data-testid="trash-icon"></div>' },
  ArrowUpTrayIcon: { template: '<div data-testid="arrow-up-tray-icon"></div>' }
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

describe('FileField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Document',
      attribute: 'document',
      type: 'file',
      accept: '.pdf,.doc,.docx',
      maxSize: 5242880, // 5MB
      placeholder: 'Choose a file'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders file input field', () => {
      wrapper = mountField(FileField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.exists()).toBe(true)
    })

    it('shows upload button', () => {
      wrapper = mountField(FileField, { field: mockField })

      const uploadButton = wrapper.find('button')
      expect(uploadButton.exists()).toBe(true)
      expect(uploadButton.text()).toContain('Choose File')
    })

    it('shows upload icon', () => {
      wrapper = mountField(FileField, { field: mockField })

      const uploadIcon = wrapper.find('[data-testid="arrow-up-tray-icon"]')
      expect(uploadIcon.exists()).toBe(true)
    })

    it('applies accept attribute', () => {
      wrapper = mountField(FileField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('accept')).toBe('.pdf,.doc,.docx')
    })

    it('applies disabled state', () => {
      wrapper = mountField(FileField, {
        field: mockField,
        props: { 
          field: mockField,
          disabled: true 
        }
      })

      const fileInput = wrapper.find('input[type="file"]')
      const uploadButton = wrapper.find('button')
      
      expect(fileInput.element.disabled).toBe(true)
      expect(uploadButton.element.disabled).toBe(true)
    })

    it('applies readonly state', () => {
      wrapper = mountField(FileField, {
        field: mockField,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      const uploadButton = wrapper.find('button')
      expect(uploadButton.element.disabled).toBe(true)
    })
  })

  describe('File Selection', () => {
    it('opens file dialog on button click', async () => {
      wrapper = mountField(FileField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      const clickSpy = vi.spyOn(fileInput.element, 'click')

      const uploadButton = wrapper.find('button')
      await uploadButton.trigger('click')

      expect(clickSpy).toHaveBeenCalled()
    })

    it('handles file selection', async () => {
      wrapper = mountField(FileField, { field: mockField })

      const file = new File(['content'], 'test.pdf', { type: 'application/pdf' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [file],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('change')).toBeTruthy()
    })

    it('shows selected file name', async () => {
      wrapper = mountField(FileField, { field: mockField })

      const file = new File(['content'], 'test-document.pdf', { type: 'application/pdf' })
      wrapper.vm.selectedFile = file
      await nextTick()

      expect(wrapper.text()).toContain('test-document.pdf')
    })

    it('shows file size', async () => {
      wrapper = mountField(FileField, { field: mockField })

      const file = new File(['content'], 'test.pdf', { 
        type: 'application/pdf',
        size: 1024 * 1024 // 1MB
      })
      wrapper.vm.selectedFile = file
      await nextTick()

      expect(wrapper.text()).toContain('1.0 MB')
    })

    it('clears selection when clear button clicked', async () => {
      wrapper = mountField(FileField, { field: mockField })

      const file = new File(['content'], 'test.pdf', { type: 'application/pdf' })
      wrapper.vm.selectedFile = file
      await nextTick()

      const clearButton = wrapper.find('[data-testid="trash-icon"]')
      await clearButton.element.parentElement.click()

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.vm.selectedFile).toBe(null)
    })
  })

  describe('File Validation', () => {
    it('validates file size', async () => {
      wrapper = mountField(FileField, { field: mockField })

      const largeFile = new File(['content'], 'large.pdf', { 
        type: 'application/pdf',
        size: 10 * 1024 * 1024 // 10MB (exceeds 5MB limit)
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
      wrapper = mountField(FileField, { field: mockField })

      const invalidFile = new File(['content'], 'test.exe', { type: 'application/x-executable' })

      const fileInput = wrapper.find('input[type="file"]')
      Object.defineProperty(fileInput.element, 'files', {
        value: [invalidFile],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.vm.validationError).toContain('Invalid file type')
    })

    it('shows validation errors', async () => {
      wrapper = mountField(FileField, { field: mockField })

      wrapper.vm.validationError = 'File too large'
      await nextTick()

      expect(wrapper.text()).toContain('File too large')
      expect(wrapper.find('.text-red-600').exists()).toBe(true)
    })

    it('clears validation errors on new selection', async () => {
      wrapper = mountField(FileField, { field: mockField })

      wrapper.vm.validationError = 'Previous error'

      const validFile = new File(['content'], 'test.pdf', { type: 'application/pdf' })
      const fileInput = wrapper.find('input[type="file"]')
      
      Object.defineProperty(fileInput.element, 'files', {
        value: [validFile],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.vm.validationError).toBe(null)
    })
  })

  describe('Existing File Display', () => {
    it('shows existing file when modelValue is provided', () => {
      wrapper = mountField(FileField, {
        field: mockField,
        modelValue: {
          name: 'existing-document.pdf',
          size: 2048,
          url: '/files/existing-document.pdf'
        }
      })

      expect(wrapper.text()).toContain('existing-document.pdf')
    })

    it('shows file icon for existing file', () => {
      wrapper = mountField(FileField, {
        field: mockField,
        modelValue: {
          name: 'document.pdf',
          size: 1024,
          url: '/files/document.pdf'
        }
      })

      const documentIcon = wrapper.find('[data-testid="document-icon"]')
      expect(documentIcon.exists()).toBe(true)
    })

    it('shows download link for existing file', () => {
      wrapper = mountField(FileField, {
        field: mockField,
        modelValue: {
          name: 'document.pdf',
          size: 1024,
          url: '/files/document.pdf'
        }
      })

      const downloadLink = wrapper.find('a[href="/files/document.pdf"]')
      expect(downloadLink.exists()).toBe(true)
    })

    it('allows replacing existing file', async () => {
      wrapper = mountField(FileField, {
        field: mockField,
        modelValue: {
          name: 'old-document.pdf',
          size: 1024,
          url: '/files/old-document.pdf'
        }
      })

      const newFile = new File(['content'], 'new-document.pdf', { type: 'application/pdf' })
      const fileInput = wrapper.find('input[type="file"]')
      
      Object.defineProperty(fileInput.element, 'files', {
        value: [newFile],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    })
  })

  describe('File Size Formatting', () => {
    it('formats bytes correctly', () => {
      wrapper = mountField(FileField, { field: mockField })

      expect(wrapper.vm.formatFileSize(1024)).toBe('1.0 KB')
      expect(wrapper.vm.formatFileSize(1024 * 1024)).toBe('1.0 MB')
      expect(wrapper.vm.formatFileSize(1024 * 1024 * 1024)).toBe('1.0 GB')
    })

    it('handles zero size', () => {
      wrapper = mountField(FileField, { field: mockField })

      expect(wrapper.vm.formatFileSize(0)).toBe('0 B')
    })

    it('handles undefined size', () => {
      wrapper = mountField(FileField, { field: mockField })

      expect(wrapper.vm.formatFileSize(undefined)).toBe('Unknown size')
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(FileField, { field: mockField })

      const uploadButton = wrapper.find('button')
      expect(uploadButton.classes()).toContain('bg-gray-700')
      expect(uploadButton.classes()).toContain('text-gray-200')
    })

    it('applies dark theme to file display', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(FileField, {
        field: mockField,
        modelValue: {
          name: 'document.pdf',
          size: 1024,
          url: '/files/document.pdf'
        }
      })

      const fileDisplay = wrapper.find('.bg-gray-50')
      expect(fileDisplay.exists()).toBe(false) // Should use dark theme classes
    })
  })

  describe('Event Handling', () => {
    it('emits focus event', async () => {
      wrapper = mountField(FileField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      await fileInput.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(FileField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      await fileInput.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('prevents default on drag events', async () => {
      wrapper = mountField(FileField, { field: mockField })

      const uploadArea = wrapper.find('.file-upload-area')
      const preventDefaultSpy = vi.fn()

      await uploadArea.trigger('dragover', { preventDefault: preventDefaultSpy })
      expect(preventDefaultSpy).toHaveBeenCalled()
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(FileField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the file input', async () => {
      wrapper = mountField(FileField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      const focusSpy = vi.spyOn(fileInput.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles null modelValue', () => {
      wrapper = mountField(FileField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.existingFile).toBe(null)
    })

    it('handles undefined modelValue', () => {
      wrapper = mountField(FileField, {
        field: mockField,
        modelValue: undefined
      })

      expect(wrapper.vm.existingFile).toBe(null)
    })

    it('handles file without size', () => {
      wrapper = mountField(FileField, {
        field: mockField,
        modelValue: {
          name: 'document.pdf',
          url: '/files/document.pdf'
        }
      })

      expect(wrapper.text()).toContain('document.pdf')
      expect(wrapper.text()).toContain('Unknown size')
    })

    it('handles very long file names', () => {
      const longFileName = 'a'.repeat(100) + '.pdf'
      
      wrapper = mountField(FileField, {
        field: mockField,
        modelValue: {
          name: longFileName,
          size: 1024,
          url: '/files/' + longFileName
        }
      })

      expect(wrapper.text()).toContain(longFileName)
    })

    it('handles files with special characters', () => {
      const specialFileName = 'file with spaces & symbols!@#.pdf'
      
      wrapper = mountField(FileField, {
        field: mockField,
        modelValue: {
          name: specialFileName,
          size: 1024,
          url: '/files/' + encodeURIComponent(specialFileName)
        }
      })

      expect(wrapper.text()).toContain(specialFileName)
    })
  })
})
