import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import FileField from '@/components/Fields/FileField.vue'
import BaseField from '@/components/Fields/BaseField.vue'

/**
 * File Field Integration Tests
 *
 * Tests the integration between the PHP File field class and Vue component,
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
  })
}))

describe('FileField Integration', () => {
  let wrapper

  // Helper function to create a field with PHP-like metadata
  const createFieldWithMeta = (overrides = {}) => ({
    name: 'Document',
    label: 'Document',
    attribute: 'document',
    component: 'FileField',
    type: 'file',
    value: '',
    required: false,
    readonly: false,
    disabled: false,
    placeholder: 'Choose a file',
    helpText: '',
    rules: [],
    errors: [],
    // PHP File field metadata
    disk: 'public',
    path: 'files',
    acceptedTypes: '.pdf,.doc,.docx',
    maxSize: 5120, // 5MB in KB
    multiple: false,
    deletable: true,
    prunable: false,
    downloadsDisabled: false,
    originalNameColumn: null,
    sizeColumn: null,
    previewUrl: null,
    thumbnailUrl: null,
    ...overrides
  })

  const mountField = (component, props = {}) => {
    return mount(component, {
      props: {
        field: createFieldWithMeta(),
        modelValue: '',
        errors: [],
        ...props
      },
      global: {
        components: {
          BaseField
        }
      }
    })
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('PHP-Vue Integration', () => {
    it('receives and processes PHP field metadata correctly', () => {
      const phpFieldMeta = {
        disk: 'private',
        path: 'documents',
        acceptedTypes: '.pdf,.docx',
        maxSize: 10240,
        deletable: false,
        prunable: true,
        downloadsDisabled: true,
        originalNameColumn: 'original_name',
        sizeColumn: 'file_size'
      }

      wrapper = mountField(FileField, {
        field: createFieldWithMeta(phpFieldMeta)
      })

      expect(wrapper.vm.field.disk).toBe('private')
      expect(wrapper.vm.field.path).toBe('documents')
      expect(wrapper.vm.field.acceptedTypes).toBe('.pdf,.docx')
      expect(wrapper.vm.field.maxSize).toBe(10240)
      expect(wrapper.vm.field.deletable).toBe(false)
      expect(wrapper.vm.field.prunable).toBe(true)
      expect(wrapper.vm.field.downloadsDisabled).toBe(true)
      expect(wrapper.vm.field.originalNameColumn).toBe('original_name')
      expect(wrapper.vm.field.sizeColumn).toBe('file_size')
    })

    it('respects deletable setting from PHP field', () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta({ deletable: false }),
        modelValue: 'test.pdf'
      })

      const buttons = wrapper.findAll('button')
      const removeButton = buttons.find(btn => btn.text().includes('Remove'))
      expect(removeButton).toBeFalsy()
    })

    it('respects downloads disabled setting from PHP field', () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta({ downloadsDisabled: true }),
        modelValue: 'test.pdf'
      })

      const buttons = wrapper.findAll('button')
      const downloadButton = buttons.find(btn => btn.text().includes('Download'))
      expect(downloadButton).toBeFalsy()
    })

    it('shows download button when downloads enabled', () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta({ downloadsDisabled: false }),
        modelValue: 'test.pdf'
      })

      const buttons = wrapper.findAll('button')
      const downloadButton = buttons.find(btn => btn.text().includes('Download'))
      expect(downloadButton).toBeTruthy()
    })

    it('validates file types based on PHP acceptedTypes', async () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta({ acceptedTypes: '.pdf,.doc' })
      })

      const invalidFile = new File(['content'], 'test.txt', { type: 'text/plain' })
      const fileInput = wrapper.find('input[type="file"]')
      
      Object.defineProperty(fileInput.element, 'files', {
        value: [invalidFile],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.text()).toContain('File type not allowed')
    })

    it('validates file size based on PHP maxSize', async () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta({ maxSize: 1024 }) // 1MB
      })

      const largeFile = new File(['content'], 'large.pdf', { 
        type: 'application/pdf',
        size: 2 * 1024 * 1024 // 2MB
      })
      const fileInput = wrapper.find('input[type="file"]')
      
      Object.defineProperty(fileInput.element, 'files', {
        value: [largeFile],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.text()).toContain('File size exceeds maximum allowed size')
    })

    it('displays accepted types and max size from PHP field', () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta({
          acceptedTypes: '.pdf,.doc,.docx',
          maxSize: 5120
        })
      })

      expect(wrapper.text()).toContain('Accepted types: .pdf,.doc,.docx')
      expect(wrapper.text()).toContain('Max size: 5 MB')
    })

    it('handles file upload with proper form data structure', async () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta()
      })

      const file = new File(['content'], 'test.pdf', { type: 'application/pdf' })
      const fileInput = wrapper.find('input[type="file"]')
      
      Object.defineProperty(fileInput.element, 'files', {
        value: [file],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(file)
    })

    it('handles file removal when deletable', async () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta({ deletable: true }),
        modelValue: 'existing-file.pdf'
      })

      const buttons = wrapper.findAll('button')
      const removeButton = buttons.find(btn => btn.text().includes('Remove'))
      await removeButton.trigger('click')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
    })

    it('prevents file removal when not deletable', () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta({ deletable: false }),
        modelValue: 'existing-file.pdf'
      })

      // Try to call removeFile directly since button won't exist
      wrapper.vm.removeFile()

      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('constructs download URL with proper parameters', async () => {
      const mockOpen = vi.fn()
      global.window.open = mockOpen

      wrapper = mountField(FileField, {
        field: createFieldWithMeta({ 
          downloadsDisabled: false,
          attribute: 'document'
        }),
        modelValue: 'files/test.pdf'
      })

      const buttons = wrapper.findAll('button')
      const downloadButton = buttons.find(btn => btn.text().includes('Download'))
      await downloadButton.trigger('click')

      expect(mockOpen).toHaveBeenCalledWith(
        '/admin/files/download?path=files%2Ftest.pdf&field=document',
        '_blank'
      )
    })

    it('handles File object downloads with blob URLs', async () => {
      const mockCreateObjectURL = vi.fn(() => 'blob:mock-url')
      const mockRevokeObjectURL = vi.fn()
      global.URL.createObjectURL = mockCreateObjectURL
      global.URL.revokeObjectURL = mockRevokeObjectURL

      const mockClick = vi.fn()
      const mockAppendChild = vi.fn()
      const mockRemoveChild = vi.fn()
      const mockAnchor = {
        href: '',
        download: '',
        click: mockClick
      }
      global.document.createElement = vi.fn(() => mockAnchor)
      global.document.body.appendChild = mockAppendChild
      global.document.body.removeChild = mockRemoveChild

      const file = new File(['content'], 'test.pdf', { type: 'application/pdf' })
      wrapper = mountField(FileField, {
        field: createFieldWithMeta({ downloadsDisabled: false }),
        modelValue: file
      })

      const buttons = wrapper.findAll('button')
      const downloadButton = buttons.find(btn => btn.text().includes('Download'))
      await downloadButton.trigger('click')

      expect(mockCreateObjectURL).toHaveBeenCalledWith(file)
      expect(mockAnchor.download).toBe('test.pdf')
      expect(mockClick).toHaveBeenCalled()
      expect(mockRevokeObjectURL).toHaveBeenCalledWith('blob:mock-url')
    })
  })

  describe('Error Handling Integration', () => {
    it('displays validation errors from PHP backend', () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta(),
        errors: ['The document field is required.']
      })

      expect(wrapper.text()).toContain('The document field is required.')
    })

    it('clears errors on successful file selection', async () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta(),
        errors: ['Previous error']
      })

      const file = new File(['content'], 'test.pdf', { type: 'application/pdf' })
      const fileInput = wrapper.find('input[type="file"]')
      
      Object.defineProperty(fileInput.element, 'files', {
        value: [file],
        writable: false
      })

      await fileInput.trigger('change')

      expect(wrapper.vm.uploadError).toBe(null)
    })
  })

  describe('Accessibility Integration', () => {
    it('provides proper ARIA labels and descriptions', () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta({
          name: 'Document',
          helpText: 'Upload your document here'
        })
      })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('aria-label')).toContain('Document')
    })

    it('supports keyboard navigation', async () => {
      wrapper = mountField(FileField, {
        field: createFieldWithMeta()
      })

      const fileInput = wrapper.find('input[type="file"]')
      await fileInput.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })
  })
})
