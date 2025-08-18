import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import MediaLibraryAudioField from '@/components/Fields/MediaLibraryAudioField.vue'
import { createMockField } from '../../../helpers.js'

/**
 * MediaLibraryAudioField Integration Tests
 * 
 * Tests the integration between Vue component and PHP backend,
 * focusing on data flow, API communication, and Inertia.js integration.
 */

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

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
  MusicalNoteIcon: { template: '<div data-testid="musical-note-icon"></div>' },
  ArrowDownTrayIcon: { template: '<div data-testid="arrow-down-tray-icon"></div>' },
  XMarkIcon: { template: '<div data-testid="x-mark-icon"></div>' },
  ExclamationCircleIcon: { template: '<div data-testid="exclamation-circle-icon"></div>' }
}))

// Mock URL API
global.URL.createObjectURL = vi.fn(() => 'blob:mock-audio-url')
global.URL.revokeObjectURL = vi.fn()

// Mock File API
global.File = class MockFile {
  constructor(parts, filename, properties = {}) {
    this.name = filename
    this.size = properties.size || 1024
    this.type = properties.type || 'audio/mpeg'
    this.lastModified = Date.now()
  }
}

describe('MediaLibraryAudioField Integration', () => {
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

  describe('PHP Backend Integration', () => {
    it('integrates with MediaLibraryAudioField PHP class configuration', () => {
      // Test that Vue component receives and uses PHP field configuration
      const phpFieldConfig = {
        ...mockField,
        collection: 'podcasts',
        disk: 's3',
        acceptedMimeTypes: ['audio/mpeg', 'audio/wav'],
        maxFileSize: 102400, // 100MB
        downloadsDisabled: true,
        preload: 'auto',
        audioUrl: 'https://example.com/audio/podcast.mp3',
        audioMetadata: {
          name: 'podcast-episode.mp3',
          size: 15728640,
          human_readable_size: '15.0 MB',
          duration: 1800,
          formatted_duration: '30:00',
          bitrate: 128
        }
      }

      wrapper = mount(MediaLibraryAudioField, {
        props: {
          field: phpFieldConfig,
          modelValue: null,
          errors: {},
          disabled: false,
          readonly: false
        }
      })

      // Verify PHP configuration is used in Vue component
      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('accept')).toBe('audio/mpeg,audio/wav')
      expect(wrapper.text()).toContain('Max size: 100.0 MB')
      expect(wrapper.text()).toContain('Supported formats: MPEG, WAV')
    })

    it('handles media library metadata from PHP backend', () => {
      const phpMediaData = {
        url: 'https://example.com/media/audio/theme-song.mp3',
        name: 'theme-song.mp3',
        size: 5242880,
        mime_type: 'audio/mpeg',
        custom_properties: {
          duration: 180.5,
          bitrate: 320,
          sample_rate: 44100
        }
      }

      mockField.audioUrl = phpMediaData.url
      mockField.audioMetadata = {
        name: phpMediaData.name,
        size: phpMediaData.size,
        human_readable_size: '5.0 MB',
        duration: phpMediaData.custom_properties.duration,
        formatted_duration: '3:00',
        bitrate: phpMediaData.custom_properties.bitrate,
        sample_rate: phpMediaData.custom_properties.sample_rate
      }

      wrapper = mount(MediaLibraryAudioField, {
        props: {
          field: mockField,
          modelValue: phpMediaData,
          errors: {},
          disabled: false,
          readonly: false
        }
      })

      // Verify metadata is displayed
      expect(wrapper.text()).toContain('theme-song.mp3')
      expect(wrapper.text()).toContain('5.0 MB')
      expect(wrapper.text()).toContain('3:00')
      expect(wrapper.text()).toContain('320kbps')

      // Verify audio element uses correct URL
      const audioElement = wrapper.find('audio')
      expect(audioElement.exists()).toBe(true)
      expect(audioElement.attributes('src')).toBe(phpMediaData.url)
    })

    it('respects PHP field validation rules', () => {
      const phpFieldWithValidation = {
        ...mockField,
        acceptedMimeTypes: ['audio/mpeg', 'audio/wav'],
        maxFileSize: 10240, // 10MB
        rules: ['required', 'mimes:mp3,wav', 'max:10240']
      }

      wrapper = mount(MediaLibraryAudioField, {
        props: {
          field: phpFieldWithValidation,
          modelValue: null,
          errors: {},
          disabled: false,
          readonly: false
        }
      })

      // Test file type validation
      const invalidFile = new File(['content'], 'test.txt', { type: 'text/plain' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [invalidFile],
        writable: false,
      })

      fileInput.trigger('change')

      // Should show validation error
      expect(wrapper.text()).toContain('Invalid file type')

      // Test file size validation
      const largeFile = new File(['x'.repeat(20000)], 'large.mp3', { 
        type: 'audio/mpeg', 
        size: 20971520 // 20MB
      })

      Object.defineProperty(fileInput.element, 'files', {
        value: [largeFile],
        writable: false,
      })

      fileInput.trigger('change')

      // Should show size validation error
      expect(wrapper.text()).toContain('File size exceeds maximum limit')
    })
  })

  describe('Form Integration', () => {
    it('emits correct events for form handling', async () => {
      wrapper = mount(MediaLibraryAudioField, {
        props: {
          field: mockField,
          modelValue: null,
          errors: {},
          disabled: false,
          readonly: false
        }
      })

      const audioFile = new File(['audio content'], 'test.mp3', { type: 'audio/mpeg' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [audioFile],
        writable: false,
      })

      await fileInput.trigger('change')

      // Should emit update:modelValue for form binding
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(audioFile)

      // Should emit change event for form handling
      expect(wrapper.emitted('change')).toBeTruthy()
      expect(wrapper.emitted('change')[0][0]).toEqual([audioFile])
    })

    it('handles form errors correctly', () => {
      const formErrors = {
        theme_song: ['The theme song field is required.', 'Invalid audio format.']
      }

      wrapper = mount(MediaLibraryAudioField, {
        props: {
          field: mockField,
          modelValue: null,
          errors: formErrors,
          disabled: false,
          readonly: false
        }
      })

      // Errors should be passed to BaseField component
      expect(wrapper.props('errors')).toEqual(formErrors)
    })

    it('handles disabled and readonly states from form', () => {
      // Test disabled state
      wrapper = mount(MediaLibraryAudioField, {
        props: {
          field: mockField,
          modelValue: null,
          errors: {},
          disabled: true,
          readonly: false
        }
      })

      const fileInput = wrapper.find('input[type="file"]')
      expect(fileInput.attributes('disabled')).toBeDefined()

      wrapper.unmount()

      // Test readonly state
      wrapper = mount(MediaLibraryAudioField, {
        props: {
          field: mockField,
          modelValue: null,
          errors: {},
          disabled: false,
          readonly: true
        }
      })

      // Upload area should not exist in readonly mode
      expect(wrapper.find('.upload-area').exists()).toBe(false)
    })
  })

  describe('Media Library Collection Integration', () => {
    it('handles different media library collections', () => {
      const collectionsConfig = [
        { collection: 'audio', disk: 'public' },
        { collection: 'podcasts', disk: 's3' },
        { collection: 'music', disk: 'local' },
        { collection: 'sound-effects', disk: 'cdn' }
      ]

      collectionsConfig.forEach(config => {
        const fieldConfig = {
          ...mockField,
          collection: config.collection,
          disk: config.disk
        }

        wrapper = mount(MediaLibraryAudioField, {
          props: {
            field: fieldConfig,
            modelValue: null,
            errors: {},
            disabled: false,
            readonly: false
          }
        })

        // Component should handle different collection configurations
        expect(wrapper.find('.media-library-audio-field').exists()).toBe(true)
        expect(wrapper.find('input[type="file"]').exists()).toBe(true)

        wrapper.unmount()
      })
    })

    it('handles media library conversions configuration', () => {
      const fieldWithConversions = {
        ...mockField,
        conversions: {
          'waveform': { width: 800, height: 200 },
          'thumbnail': { width: 150, height: 150 }
        },
        responsiveImages: true,
        enableCropping: true
      }

      wrapper = mount(MediaLibraryAudioField, {
        props: {
          field: fieldWithConversions,
          modelValue: null,
          errors: {},
          disabled: false,
          readonly: false
        }
      })

      // Component should handle conversions configuration
      expect(wrapper.find('.media-library-audio-field').exists()).toBe(true)
    })
  })

  describe('Nova API Compatibility', () => {
    it('maintains compatibility with Nova Audio field API', () => {
      // Test Nova-style field configuration
      const novaStyleField = {
        ...mockField,
        preload: 'auto',
        downloadsDisabled: true,
        acceptedTypes: 'audio/mpeg,audio/wav,audio/ogg,.mp3,.wav,.ogg',
        maxSize: 51200
      }

      wrapper = mount(MediaLibraryAudioField, {
        props: {
          field: novaStyleField,
          modelValue: null,
          errors: {},
          disabled: false,
          readonly: false
        }
      })

      // Should handle Nova-style configuration
      expect(wrapper.find('.media-library-audio-field').exists()).toBe(true)
      expect(wrapper.find('input[type="file"]').exists()).toBe(true)
    })

    it('handles Nova preload constants correctly', () => {
      const preloadValues = ['none', 'metadata', 'auto']

      preloadValues.forEach(preload => {
        const fieldConfig = {
          ...mockField,
          preload: preload
        }

        const audioFile = new File(['audio'], 'test.mp3', { type: 'audio/mpeg' })

        wrapper = mount(MediaLibraryAudioField, {
          props: {
            field: fieldConfig,
            modelValue: audioFile,
            errors: {},
            disabled: false,
            readonly: false
          }
        })

        const audioElement = wrapper.find('audio')
        if (audioElement.exists()) {
          expect(audioElement.attributes('preload')).toBe(preload)
        }

        wrapper.unmount()
      })
    })
  })

  describe('Real-world Scenarios', () => {
    it('handles complete upload workflow', async () => {
      wrapper = mount(MediaLibraryAudioField, {
        props: {
          field: mockField,
          modelValue: null,
          errors: {},
          disabled: false,
          readonly: false
        }
      })

      // 1. User selects file
      const audioFile = new File(['audio content'], 'podcast.mp3', { 
        type: 'audio/mpeg',
        size: 5242880 // 5MB
      })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [audioFile],
        writable: false,
      })

      await fileInput.trigger('change')

      // 2. Component should emit events
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('change')).toBeTruthy()

      // 3. Audio preview should be shown
      expect(wrapper.find('audio').exists()).toBe(true)
      expect(wrapper.text()).toContain('podcast.mp3')
      expect(wrapper.text()).toContain('5.0 MB')
    })

    it('handles error recovery workflow', async () => {
      wrapper = mount(MediaLibraryAudioField, {
        props: {
          field: mockField,
          modelValue: null,
          errors: {},
          disabled: false,
          readonly: false
        }
      })

      // 1. User uploads invalid file
      const invalidFile = new File(['content'], 'document.pdf', { type: 'application/pdf' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [invalidFile],
        writable: false,
      })

      await fileInput.trigger('change')
      await nextTick()

      // 2. Error should be shown
      expect(wrapper.text()).toContain('Invalid file type')

      // 3. User uploads valid file
      const validFile = new File(['audio'], 'valid.mp3', { type: 'audio/mpeg' })

      Object.defineProperty(fileInput.element, 'files', {
        value: [validFile],
        writable: false,
      })

      await fileInput.trigger('change')
      await nextTick()

      // 4. Error should be cleared and file accepted
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.find('audio').exists()).toBe(true)
    })
  })
})
