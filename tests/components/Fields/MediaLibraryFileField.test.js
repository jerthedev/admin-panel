import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import MediaLibraryFileField from '@/components/Fields/MediaLibraryFileField.vue'
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
  CloudArrowUpIcon: { template: '<div data-testid="cloud-arrow-up-icon"></div>' },
  DocumentIcon: { template: '<div data-testid="document-icon"></div>' },
  TrashIcon: { template: '<div data-testid="trash-icon"></div>' },
  EyeIcon: { template: '<div data-testid="eye-icon"></div>' },
  ArrowDownTrayIcon: { template: '<div data-testid="arrow-down-tray-icon"></div>' }
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

describe('MediaLibraryFileField', () => {
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
      maxFiles: 10
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders file upload area', () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const uploadArea = wrapper.find('.border-dashed')
      expect(uploadArea.exists()).toBe(true)
    })

    it('shows upload icon and text', () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const uploadIcon = wrapper.find('[data-testid="cloud-arrow-up-icon"]')
      expect(uploadIcon.exists()).toBe(true)
      expect(wrapper.text()).toContain('Click to upload')
    })

    it('shows accepted file types', () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      expect(wrapper.text()).toContain('PDF, TXT')
    })

    it('shows max file size', () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      expect(wrapper.text()).toContain('5 MB')
    })

    it('applies disabled state', () => {
      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        props: { 
          field: mockField,
          disabled: true 
        }
      })

      const uploadArea = wrapper.find('.border-dashed')
      expect(uploadArea.classes()).toContain('opacity-50')
      expect(uploadArea.classes()).toContain('cursor-not-allowed')
    })

    it('applies readonly state', () => {
      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      const uploadArea = wrapper.find('.border-dashed')
      expect(uploadArea.classes()).toContain('cursor-not-allowed')
    })
  })

  describe('File Upload', () => {
    it('opens file dialog on click', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      const clickSpy = vi.spyOn(fileInput.element, 'click')

      const uploadArea = wrapper.find('.border-dashed')
      await uploadArea.trigger('click')

      expect(clickSpy).toHaveBeenCalled()
    })

    it('does not open file dialog when disabled', async () => {
      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        props: { 
          field: mockField,
          disabled: true 
        }
      })

      const fileInput = wrapper.find('input[type="file"]')
      const clickSpy = vi.spyOn(fileInput.element, 'click')

      const uploadArea = wrapper.find('.border-dashed')
      await uploadArea.trigger('click')

      expect(clickSpy).not.toHaveBeenCalled()
    })

    it('accepts multiple files when multiple is true', () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('multiple')).toBeDefined()
    })

    it('does not accept multiple files when multiple is false', () => {
      const singleFileField = createMockField({
        ...mockField,
        multiple: false
      })

      wrapper = mountField(MediaLibraryFileField, { field: singleFileField })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('multiple')).toBeUndefined()
    })

    it('sets correct accept attribute', () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('accept')).toBe('application/pdf,text/plain')
    })

    it('handles file selection', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const file = new File(['content'], 'test.pdf', { type: 'application/pdf' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [file],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.vm.handleFileSelect).toBeDefined()
    })
  })

  describe('Drag and Drop', () => {
    it('handles drag enter', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const uploadArea = wrapper.find('.border-dashed')
      await uploadArea.trigger('dragenter')

      expect(wrapper.vm.isDragOver).toBe(true)
    })

    it('handles drag leave', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const uploadArea = wrapper.find('.border-dashed')
      await uploadArea.trigger('dragenter')
      await uploadArea.trigger('dragleave')

      expect(wrapper.vm.isDragOver).toBe(false)
    })

    it('handles file drop', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const file = new File(['content'], 'test.pdf', { type: 'application/pdf' })
      const uploadArea = wrapper.find('.border-dashed')

      const dropEvent = new Event('drop')
      dropEvent.dataTransfer = {
        files: [file]
      }

      await uploadArea.trigger('drop', dropEvent)

      expect(wrapper.vm.isDragOver).toBe(false)
    })

    it('shows drag over state visually', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      wrapper.vm.isDragOver = true
      await nextTick()

      const uploadArea = wrapper.find('.border-dashed')
      expect(uploadArea.classes()).toContain('border-blue-400')
      expect(uploadArea.classes()).toContain('bg-blue-50')
    })
  })

  describe('File Validation', () => {
    it('validates file type', () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const validFile = new File(['content'], 'test.pdf', { type: 'application/pdf' })
      const invalidFile = new File(['content'], 'test.exe', { type: 'application/x-executable' })

      expect(wrapper.vm.isValidFileType(validFile)).toBe(true)
      expect(wrapper.vm.isValidFileType(invalidFile)).toBe(false)
    })

    it('validates file size', () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const validFile = new File(['content'], 'test.pdf', { 
        type: 'application/pdf', 
        size: 1024 * 1024 // 1MB
      })
      const invalidFile = new File(['content'], 'large.pdf', { 
        type: 'application/pdf', 
        size: 10 * 1024 * 1024 // 10MB
      })

      expect(wrapper.vm.isValidFileSize(validFile)).toBe(true)
      expect(wrapper.vm.isValidFileSize(invalidFile)).toBe(false)
    })

    it('shows validation errors', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      wrapper.vm.uploadError = 'File too large'
      await nextTick()

      expect(wrapper.text()).toContain('File too large')
    })

    it('clears validation errors on new upload', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      wrapper.vm.uploadError = 'Previous error'
      
      const file = new File(['content'], 'test.pdf', { type: 'application/pdf' })
      await wrapper.vm.handleFileSelect({ target: { files: [file] } })

      expect(wrapper.vm.uploadError).toBe(null)
    })
  })

  describe('Existing Files Display', () => {
    it('shows existing files', () => {
      const existingFiles = [
        { id: 1, name: 'document1.pdf', size: 1024, mime_type: 'application/pdf' },
        { id: 2, name: 'document2.txt', size: 2048, mime_type: 'text/plain' }
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: existingFiles
      })

      expect(wrapper.text()).toContain('document1.pdf')
      expect(wrapper.text()).toContain('document2.txt')
    })

    it('shows file icons for different types', () => {
      const existingFiles = [
        { id: 1, name: 'document.pdf', size: 1024, mime_type: 'application/pdf' }
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: existingFiles
      })

      const documentIcon = wrapper.find('[data-testid="document-icon"]')
      expect(documentIcon.exists()).toBe(true)
    })

    it('shows file sizes in human readable format', () => {
      const existingFiles = [
        { id: 1, name: 'document.pdf', size: 1024 * 1024, mime_type: 'application/pdf' }
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: existingFiles
      })

      expect(wrapper.text()).toContain('1.0 MB')
    })

    it('shows file actions', () => {
      const existingFiles = [
        { id: 1, name: 'document.pdf', size: 1024, mime_type: 'application/pdf' }
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: existingFiles
      })

      const viewIcon = wrapper.find('[data-testid="eye-icon"]')
      const downloadIcon = wrapper.find('[data-testid="arrow-down-tray-icon"]')
      const deleteIcon = wrapper.find('[data-testid="trash-icon"]')

      expect(viewIcon.exists()).toBe(true)
      expect(downloadIcon.exists()).toBe(true)
      expect(deleteIcon.exists()).toBe(true)
    })
  })

  describe('File Actions', () => {
    it('previews file when view button clicked', async () => {
      const existingFiles = [
        { id: 1, name: 'document.pdf', size: 1024, mime_type: 'application/pdf', url: '/files/1' }
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: existingFiles
      })

      const previewSpy = vi.spyOn(wrapper.vm, 'previewFile')
      const viewButton = wrapper.find('[data-testid="eye-icon"]')
      
      await viewButton.element.parentElement.click()
      expect(previewSpy).toHaveBeenCalledWith(existingFiles[0])
    })

    it('downloads file when download button clicked', async () => {
      const existingFiles = [
        { id: 1, name: 'document.pdf', size: 1024, mime_type: 'application/pdf', download_url: '/download/1' }
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: existingFiles
      })

      const downloadSpy = vi.spyOn(wrapper.vm, 'downloadFile')
      const downloadButton = wrapper.find('[data-testid="arrow-down-tray-icon"]')
      
      await downloadButton.element.parentElement.click()
      expect(downloadSpy).toHaveBeenCalledWith(existingFiles[0])
    })

    it('removes file when delete button clicked', async () => {
      const existingFiles = [
        { id: 1, name: 'document.pdf', size: 1024, mime_type: 'application/pdf' }
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: existingFiles
      })

      const removeSpy = vi.spyOn(wrapper.vm, 'removeFile')
      const deleteButton = wrapper.find('[data-testid="trash-icon"]')
      
      await deleteButton.element.parentElement.click()
      expect(removeSpy).toHaveBeenCalledWith(existingFiles[0])
    })

    it('does not show delete button when readonly', () => {
      const existingFiles = [
        { id: 1, name: 'document.pdf', size: 1024, mime_type: 'application/pdf' }
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: existingFiles,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      expect(wrapper.find('[data-testid="trash-icon"]').exists()).toBe(false)
    })
  })

  describe('Upload Progress', () => {
    it('shows upload progress during upload', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      wrapper.vm.uploadProgress = 50
      await nextTick()

      expect(wrapper.text()).toContain('50%')
    })

    it('shows progress bar during upload', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      wrapper.vm.uploadProgress = 75
      await nextTick()

      const progressBar = wrapper.find('.bg-blue-600')
      expect(progressBar.exists()).toBe(true)
    })

    it('hides progress when upload complete', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      wrapper.vm.uploadProgress = 0
      await nextTick()

      expect(wrapper.text()).not.toContain('%')
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const uploadArea = wrapper.find('.border-dashed')
      expect(uploadArea.classes()).toContain('border-gray-600')
      expect(uploadArea.classes()).toContain('bg-gray-800')
    })

    it('applies dark theme to file items', () => {
      mockAdminStore.isDarkTheme = true

      const existingFiles = [
        { id: 1, name: 'document.pdf', size: 1024, mime_type: 'application/pdf' }
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: existingFiles
      })

      const fileItem = wrapper.find('.bg-gray-50')
      expect(fileItem.exists()).toBe(false) // Should use dark theme classes instead
    })
  })

  describe('Event Handling', () => {
    it('emits update:modelValue when files change', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const newFiles = [
        { id: 1, name: 'new-file.pdf', size: 1024, mime_type: 'application/pdf' }
      ]

      wrapper.vm.updateFiles(newFiles)

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('change')).toBeTruthy()
    })

    it('emits focus event', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      await fileInput.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(MediaLibraryFileField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      await fileInput.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })
  })

  describe('Edge Cases', () => {
    it('handles null modelValue', () => {
      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.existingFiles).toEqual([])
    })

    it('handles single file as object', () => {
      const singleFile = { id: 1, name: 'document.pdf', size: 1024, mime_type: 'application/pdf' }

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: singleFile
      })

      expect(wrapper.vm.existingFiles).toEqual([singleFile])
    })

    it('handles empty file list', () => {
      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: []
      })

      expect(wrapper.vm.existingFiles).toEqual([])
    })

    it('handles files without size information', () => {
      const filesWithoutSize = [
        { id: 1, name: 'document.pdf', mime_type: 'application/pdf' }
      ]

      wrapper = mountField(MediaLibraryFileField, {
        field: mockField,
        modelValue: filesWithoutSize
      })

      expect(wrapper.text()).toContain('document.pdf')
    })
  })
})
