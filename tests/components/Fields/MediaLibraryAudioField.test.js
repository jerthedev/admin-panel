import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import MediaLibraryAudioField from '@/components/Fields/MediaLibraryAudioField.vue'
import { createMockField, mountField } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock URL.createObjectURL and revokeObjectURL
global.URL.createObjectURL = vi.fn(() => 'blob:mock-audio-url')
global.URL.revokeObjectURL = vi.fn()

// Mock BaseField component
vi.mock('@/components/Fields/BaseField.vue', () => ({
  default: {
    name: 'BaseField',
    template: '<div class="base-field"><slot /></div>',
    props: ['field', 'modelValue', 'errors', 'disabled', 'readonly', 'size']
  }
}))

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  MusicalNoteIcon: {
    name: 'MusicalNoteIcon',
    template: '<svg data-testid="musical-note-icon"></svg>'
  },
  ArrowDownTrayIcon: {
    name: 'ArrowDownTrayIcon',
    template: '<svg data-testid="arrow-down-tray-icon"></svg>'
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

// Mock File API
global.File = class MockFile {
  constructor(parts, filename, properties = {}) {
    this.name = filename
    this.size = properties.size || 1024
    this.type = properties.type || 'audio/mpeg'
    this.lastModified = Date.now()
  }
}

describe('MediaLibraryAudioField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Theme Song',
      attribute: 'theme_song',
      component: 'MediaLibraryAudioField',
      collection: 'audio',
      disk: 'public',
      acceptedMimeTypes: ['audio/mpeg', 'audio/wav', 'audio/ogg'],
      maxFileSize: 51200, // 50MB
      singleFile: true,
      preload: 'metadata',
      downloadsDisabled: false,
      audioUrl: null,
      audioMetadata: {}
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
    vi.clearAllMocks()
  })

  describe('Basic Rendering', () => {
    it('renders correctly with default props', () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      expect(wrapper.find('.media-library-audio-field').exists()).toBe(true)
      expect(wrapper.find('.upload-area').exists()).toBe(true)
      expect(wrapper.find('[data-testid="musical-note-icon"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Upload audio file')
    })

    it('shows correct upload text and instructions', () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      expect(wrapper.text()).toContain('Upload audio file')
      expect(wrapper.text()).toContain('Drag and drop or click to browse')
      expect(wrapper.text()).toContain('Supported formats: MPEG, WAV, OGG')
      expect(wrapper.text()).toContain('Max size: 50.0 MB')
    })

    it('shows accepted MIME types correctly', () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      expect(wrapper.text()).toContain('Supported formats: MPEG, WAV, OGG')
    })

    it('shows max file size correctly', () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      expect(wrapper.text()).toContain('Max size: 50.0 MB')
    })

    it('shows replace text when existing audio is present and multiple files allowed', () => {
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      const multipleField = createMockField({
        ...mockField,
        singleFile: false,
        multiple: true
      })
      wrapper = mountField(MediaLibraryAudioField, {
        field: multipleField,
        modelValue: audioFile
      })

      // When audio exists and multiple files are allowed, upload area should show "Replace audio file"
      expect(wrapper.find('.upload-area').exists()).toBe(true)
      expect(wrapper.text()).toContain('Replace audio file')
    })
  })

  describe('Audio Preview and Playback', () => {
    it('displays audio preview when modelValue is a File', async () => {
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg', size: 5242880 })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile
      })

      expect(wrapper.find('audio').exists()).toBe(true)
      expect(wrapper.find('audio').attributes('src')).toBe('blob:mock-audio-url')
      expect(wrapper.text()).toContain('theme.mp3')
      expect(wrapper.text()).toContain('5.0 MB')
    })

    it('displays audio preview when modelValue is an object with URL', async () => {
      const audioObject = {
        url: 'https://example.com/audio/theme.mp3',
        name: 'theme.mp3',
        size: 3145728
      }
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioObject
      })

      expect(wrapper.find('audio').exists()).toBe(true)
      expect(wrapper.find('audio').attributes('src')).toBe('https://example.com/audio/theme.mp3')
      // The name should be displayed in the audio info section
      expect(wrapper.find('.flex.items-center.space-x-4').exists()).toBe(true)
    })

    it('sets correct preload attribute on audio element', async () => {
      mockField.preload = 'auto'
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile
      })

      const audioElement = wrapper.find('audio')
      expect(audioElement.attributes('preload')).toBe('auto')
    })

    it('uses metadata preload by default', async () => {
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile
      })

      const audioElement = wrapper.find('audio')
      expect(audioElement.attributes('preload')).toBe('metadata')
    })

    it('displays audio metadata when available', async () => {
      const audioObject = {
        url: 'https://example.com/audio/podcast.mp3',
        name: 'podcast.mp3',
        size: 10485760,
        custom_properties: {
          duration: 180.5,
          bitrate: 320,
          sample_rate: 44100
        }
      }
      mockField.audioMetadata = {
        name: 'podcast.mp3',
        size: 10485760,
        human_readable_size: '10.0 MB',
        formatted_duration: '3:00',
        bitrate: 320
      }

      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioObject
      })

      expect(wrapper.text()).toContain('podcast.mp3')
      expect(wrapper.text()).toContain('10.0 MB')
      expect(wrapper.text()).toContain('3:00')
      expect(wrapper.text()).toContain('320kbps')
    })
  })

  describe('Download Functionality', () => {
    it('shows download button when downloads are enabled', async () => {
      mockField.downloadsDisabled = false
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile
      })

      expect(wrapper.find('[data-testid="arrow-down-tray-icon"]').exists()).toBe(true)
    })

    it('hides download button when downloads are disabled', async () => {
      mockField.downloadsDisabled = true
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile
      })

      expect(wrapper.find('[data-testid="arrow-down-tray-icon"]').exists()).toBe(false)
    })

    it('triggers download when download button is clicked', async () => {
      // Mock document.createElement and appendChild/removeChild
      const mockLink = {
        href: '',
        download: '',
        click: vi.fn()
      }
      const createElement = vi.spyOn(document, 'createElement').mockReturnValue(mockLink)
      const appendChild = vi.spyOn(document.body, 'appendChild').mockImplementation(() => {})
      const removeChild = vi.spyOn(document.body, 'removeChild').mockImplementation(() => {})

      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile
      })

      const downloadButton = wrapper.find('[data-testid="arrow-down-tray-icon"]').element.closest('button')
      await downloadButton.click()

      expect(createElement).toHaveBeenCalledWith('a')
      expect(mockLink.href).toBe('blob:mock-audio-url')
      expect(mockLink.download).toBe('theme.mp3')
      expect(mockLink.click).toHaveBeenCalled()
      expect(appendChild).toHaveBeenCalledWith(mockLink)
      expect(removeChild).toHaveBeenCalledWith(mockLink)

      // Cleanup
      createElement.mockRestore()
      appendChild.mockRestore()
      removeChild.mockRestore()
    })
  })

  describe('File Upload', () => {
    it('accepts audio files via file input', async () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      const file = new File(['audio content'], 'test.mp3', { type: 'audio/mpeg' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [file],
        writable: false,
      })

      await fileInput.trigger('change')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(file)
      expect(wrapper.emitted('change')).toBeTruthy()
    })

    it('validates file type correctly', async () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      const invalidFile = new File(['content'], 'test.txt', { type: 'text/plain' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [invalidFile],
        writable: false,
      })

      await fileInput.trigger('change')
      await nextTick()

      expect(wrapper.find('[data-testid="exclamation-circle-icon"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Invalid file type')
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('validates file size correctly', async () => {
      mockField.maxFileSize = 1 // 1KB limit
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      const largeFile = new File(['x'.repeat(2000)], 'large.mp3', { type: 'audio/mpeg', size: 2000 })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [largeFile],
        writable: false,
      })

      await fileInput.trigger('change')
      await nextTick()

      expect(wrapper.find('[data-testid="exclamation-circle-icon"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('File size exceeds maximum limit')
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('handles drag and drop functionality', async () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      const uploadArea = wrapper.find('.upload-area')
      const file = new File(['audio content'], 'test.mp3', { type: 'audio/mpeg' })

      // Test dragover
      await uploadArea.trigger('dragover')
      expect(wrapper.find('.upload-area-dragover').exists()).toBe(true)

      // Test dragleave
      await uploadArea.trigger('dragleave')
      expect(wrapper.find('.upload-area-dragover').exists()).toBe(false)

      // Test drop
      const dropEvent = new Event('drop')
      dropEvent.dataTransfer = { files: [file] }

      await uploadArea.element.dispatchEvent(dropEvent)
      await nextTick()

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(file)
    })

    it('opens file dialog when upload area is clicked', async () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      const clickSpy = vi.spyOn(fileInput.element, 'click')

      const uploadArea = wrapper.find('.upload-area')
      await uploadArea.trigger('click')

      expect(clickSpy).toHaveBeenCalled()
    })
  })

  describe('Remove Functionality', () => {
    it('shows remove button when audio is present', async () => {
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile
      })

      expect(wrapper.find('[data-testid="x-mark-icon"]').exists()).toBe(true)
    })

    it('removes audio when remove button is clicked', async () => {
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile
      })

      const removeButton = wrapper.find('[data-testid="x-mark-icon"]').element.closest('button')
      await removeButton.click()

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBeNull()
      expect(wrapper.emitted('change')).toBeTruthy()
      expect(wrapper.emitted('change')[0][0]).toBeNull()
    })

    it('does not show remove button when readonly', async () => {
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile,
        readonly: true
      })

      expect(wrapper.find('[data-testid="x-mark-icon"]').exists()).toBe(false)
    })
  })

  describe('Media Library Integration', () => {
    it('sets correct accept attribute for audio files', () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('accept')).toBe('audio/mpeg,audio/wav,audio/ogg')
    })

    it('uses default audio/* accept when no MIME types specified', () => {
      const fieldWithoutMimeTypes = createMockField({
        ...mockField,
        acceptedMimeTypes: null
      })
      wrapper = mountField(MediaLibraryAudioField, { field: fieldWithoutMimeTypes })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('accept')).toBe('audio/*')
    })

    it('supports single file mode by default', () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('multiple')).toBeUndefined()
    })

    it('supports multiple file mode when configured', () => {
      const multipleField = createMockField({
        ...mockField,
        multiple: true,
        singleFile: false
      })
      wrapper = mountField(MediaLibraryAudioField, { field: multipleField })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('multiple')).toBeDefined()
    })

    it('handles multiple file uploads correctly', async () => {
      const multipleField = createMockField({
        ...mockField,
        multiple: true,
        singleFile: false
      })
      wrapper = mountField(MediaLibraryAudioField, { field: multipleField })

      const file1 = new File(['audio1'], 'audio1.mp3', { type: 'audio/mpeg' })
      const file2 = new File(['audio2'], 'audio2.wav', { type: 'audio/wav' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [file1, file2],
        writable: false,
      })

      await fileInput.trigger('change')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toEqual([file1, file2])
    })
  })

  describe('Disabled and Readonly States', () => {
    it('disables upload when disabled prop is true', () => {
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        disabled: true
      })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('disabled')).toBeDefined()
      expect(wrapper.find('.upload-area-disabled').exists()).toBe(true)
    })

    it('hides upload area when readonly and no existing audio', () => {
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        readonly: true
      })

      expect(wrapper.find('.upload-area').exists()).toBe(false)
    })

    it('shows existing audio but no upload area when readonly', () => {
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile,
        readonly: true
      })

      expect(wrapper.find('audio').exists()).toBe(true)
      expect(wrapper.find('.upload-area').exists()).toBe(false)
      expect(wrapper.find('[data-testid="x-mark-icon"]').exists()).toBe(false)
    })

    it('does not respond to drag events when disabled', async () => {
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        disabled: true
      })

      const uploadArea = wrapper.find('.upload-area')
      await uploadArea.trigger('dragover')

      expect(wrapper.find('.upload-area-dragover').exists()).toBe(false)
    })

    it('does not open file dialog when disabled', async () => {
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        disabled: true
      })

      const fileInput = wrapper.find('input[type="file"]')
      const clickSpy = vi.spyOn(fileInput.element, 'click')

      const uploadArea = wrapper.find('.upload-area')
      await uploadArea.trigger('click')

      expect(clickSpy).not.toHaveBeenCalled()
    })
  })

  describe('Dark Theme Support', () => {
    beforeEach(() => {
      mockAdminStore.isDarkTheme = true
    })

    afterEach(() => {
      mockAdminStore.isDarkTheme = false
    })

    it('applies dark theme classes when dark theme is enabled', () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      expect(wrapper.find('.upload-area-dark').exists()).toBe(true)
    })

    it('applies dark theme classes to existing audio display', () => {
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile
      })

      // Check for dark theme classes in audio display
      expect(wrapper.html()).toContain('bg-gray-800')
      expect(wrapper.html()).toContain('border-gray-700')
    })
  })

  describe('Error Handling', () => {
    it('displays error when audio fails to load', async () => {
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile
      })

      const audioElement = wrapper.find('audio')
      await audioElement.trigger('error')
      await nextTick()

      expect(wrapper.find('[data-testid="exclamation-circle-icon"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Unable to load audio file for playback')
    })

    it('clears errors when new file is selected', async () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      // First, trigger an error
      const invalidFile = new File(['content'], 'test.txt', { type: 'text/plain' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [invalidFile],
        writable: false,
      })

      await fileInput.trigger('change')
      await nextTick()

      expect(wrapper.find('[data-testid="exclamation-circle-icon"]').exists()).toBe(true)

      // Then, select a valid file
      const validFile = new File(['audio content'], 'test.mp3', { type: 'audio/mpeg' })
      Object.defineProperty(fileInput.element, 'files', {
        value: [validFile],
        writable: false,
      })

      await fileInput.trigger('change')
      await nextTick()

      expect(wrapper.find('[data-testid="exclamation-circle-icon"]').exists()).toBe(false)
    })
  })

  describe('Utility Functions', () => {
    it('formats file sizes correctly', () => {
      wrapper = mountField(MediaLibraryAudioField, { field: mockField })

      // Test with different file sizes
      const testCases = [
        { size: 0, expected: '0 B' },
        { size: 1024, expected: '1.0 KB' },
        { size: 1048576, expected: '1.0 MB' },
        { size: 1073741824, expected: '1.0 GB' },
        { size: 1536, expected: '1.5 KB' }
      ]

      testCases.forEach(({ size, expected }) => {
        const file = new File(['x'.repeat(size)], 'test.mp3', { type: 'audio/mpeg', size })
        wrapper = mountField(MediaLibraryAudioField, {
          field: mockField,
          modelValue: file
        })

        expect(wrapper.text()).toContain(expected)
      })
    })

    it('generates unique field IDs', () => {
      const wrapper1 = mountField(MediaLibraryAudioField, { field: mockField })
      const wrapper2 = mountField(MediaLibraryAudioField, { field: mockField })

      const input1 = wrapper1.find('input[type="file"]')
      const input2 = wrapper2.find('input[type="file"]')

      expect(input1.attributes('id')).not.toBe(input2.attributes('id'))
      expect(input1.attributes('id')).toContain('media-library-audio-field-theme_song')
      expect(input2.attributes('id')).toContain('media-library-audio-field-theme_song')

      wrapper1.unmount()
      wrapper2.unmount()
    })
  })

  describe('Cleanup', () => {
    it('revokes object URLs on unmount', () => {
      const audioFile = new File(['audio content'], 'theme.mp3', { type: 'audio/mpeg' })
      wrapper = mountField(MediaLibraryAudioField, {
        field: mockField,
        modelValue: audioFile
      })

      wrapper.unmount()

      expect(global.URL.revokeObjectURL).toHaveBeenCalledWith('blob:mock-audio-url')
    })
  })
})
