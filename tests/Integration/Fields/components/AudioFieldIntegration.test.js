import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import AudioField from '@/components/Fields/AudioField.vue'
import { createMockField } from '../../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock URL.createObjectURL
global.URL.createObjectURL = vi.fn(() => 'blob:mock-url')
global.URL.revokeObjectURL = vi.fn()

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  CloudArrowUpIcon: {
    name: 'CloudArrowUpIcon',
    template: '<svg data-testid="cloud-arrow-up-icon"></svg>'
  },
  XMarkIcon: {
    name: 'XMarkIcon',
    template: '<svg data-testid="x-mark-icon"></svg>'
  }
}))

/**
 * Audio Field Integration Tests
 *
 * Tests the integration between PHP Audio field class and Vue AudioField component.
 * Validates that all Nova API options work correctly between backend and frontend.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
describe('AudioField Integration Tests', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Theme Song',
      attribute: 'theme_song',
      component: 'AudioField',
      acceptedTypes: 'audio/mpeg,audio/wav,audio/ogg,.mp3,.wav,.ogg',
      maxSize: 10240, // 10MB
      meta: {
        preload: 'metadata',
        downloadsDisabled: false
      }
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
    vi.clearAllMocks()
  })

  describe('PHP to Vue Integration', () => {
    it('correctly passes PHP field configuration to Vue component', () => {
      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: '',
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Verify PHP field properties are accessible in Vue
      expect(wrapper.props('field').name).toBe('Theme Song')
      expect(wrapper.props('field').attribute).toBe('theme_song')
      expect(wrapper.props('field').component).toBe('AudioField')
      expect(wrapper.props('field').acceptedTypes).toBe('audio/mpeg,audio/wav,audio/ogg,.mp3,.wav,.ogg')
      expect(wrapper.props('field').maxSize).toBe(10240)
    })

    it('correctly handles PHP meta properties in Vue component', () => {
      mockField.meta = {
        preload: 'auto',
        downloadsDisabled: true
      }

      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: 'https://example.com/audio.mp3',
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Verify meta properties are used correctly
      const audioElement = wrapper.find('audio')
      expect(audioElement.attributes('preload')).toBe('auto')
      expect(wrapper.find('.download-btn').exists()).toBe(false) // Downloads disabled
    })

    it('handles Nova API preload constants correctly', async () => {
      // Test PRELOAD_METADATA constant
      mockField.meta.preload = 'metadata'
      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: 'https://example.com/audio.mp3',
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      expect(wrapper.find('audio').attributes('preload')).toBe('metadata')

      // Test PRELOAD_AUTO constant
      await wrapper.setProps({
        field: { ...mockField, meta: { ...mockField.meta, preload: 'auto' } }
      })
      expect(wrapper.find('audio').attributes('preload')).toBe('auto')

      // Test PRELOAD_NONE constant
      await wrapper.setProps({
        field: { ...mockField, meta: { ...mockField.meta, preload: 'none' } }
      })
      expect(wrapper.find('audio').attributes('preload')).toBe('none')
    })
  })

  describe('Nova API Compatibility Integration', () => {
    it('integrates disableDownload() method correctly', () => {
      mockField.meta.downloadsDisabled = true

      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: 'https://example.com/audio.mp3',
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Download button should be hidden when downloads are disabled
      expect(wrapper.find('.download-btn').exists()).toBe(false)
    })

    it('integrates preload() method correctly', () => {
      mockField.meta.preload = 'auto'

      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: 'https://example.com/audio.mp3',
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Audio element should have correct preload attribute
      expect(wrapper.find('audio').attributes('preload')).toBe('auto')
    })

    it('inherits File field functionality correctly', () => {
      // Test inherited File field properties
      mockField.disk = 'audio-storage'
      mockField.path = 'podcasts'
      mockField.acceptedTypes = 'audio/mpeg,audio/wav'
      mockField.maxSize = 51200 // 50MB

      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: '',
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Verify inherited properties are accessible
      expect(wrapper.props('field').disk).toBe('audio-storage')
      expect(wrapper.props('field').path).toBe('podcasts')
      expect(wrapper.props('field').acceptedTypes).toBe('audio/mpeg,audio/wav')
      expect(wrapper.props('field').maxSize).toBe(51200)

      // Verify accepted types are displayed
      expect(wrapper.text()).toContain('AUDIO/MPEG, AUDIO/WAV')
      expect(wrapper.text()).toContain('Maximum file size: 50.0 MB')
    })
  })

  describe('CRUD Operations Integration', () => {
    it('handles create operation with audio upload', async () => {
      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: null,
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Simulate file upload
      const file = new File(['audio content'], 'test.mp3', { type: 'audio/mpeg' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [file],
        writable: false,
      })

      await fileInput.trigger('change')

      // Verify file is emitted for create operation
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(file)
    })

    it('handles update operation with existing audio', async () => {
      const existingAudioUrl = 'https://example.com/existing-audio.mp3'

      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: existingAudioUrl,
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Verify existing audio is displayed
      expect(wrapper.find('.audio-preview-container').exists()).toBe(true)
      expect(wrapper.find('audio').attributes('src')).toBe(existingAudioUrl)

      // Simulate replacing with new file
      const newFile = new File(['new audio content'], 'new-audio.mp3', { type: 'audio/mpeg' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [newFile],
        writable: false,
      })

      await fileInput.trigger('change')

      // Verify new file is emitted for update operation
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(newFile)
    })

    it('handles delete operation by removing audio', async () => {
      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: 'https://example.com/audio.mp3',
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Click remove button
      const removeButton = wrapper.find('.remove-audio-btn')
      await removeButton.trigger('click')

      // Verify null is emitted for delete operation
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
    })

    it('handles read operation by displaying audio preview', () => {
      const audioUrl = 'https://example.com/readonly-audio.mp3'

      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: audioUrl,
          readonly: true,
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Verify audio is displayed in readonly mode
      expect(wrapper.find('.audio-preview-container').exists()).toBe(true)
      expect(wrapper.find('audio').attributes('src')).toBe(audioUrl)
      expect(wrapper.find('.file-upload-container').exists()).toBe(false)
      expect(wrapper.find('.remove-audio-btn').exists()).toBe(false)
    })
  })

  describe('Validation Integration', () => {
    it('integrates file type validation from PHP field', async () => {
      mockField.acceptedTypes = 'audio/mpeg,audio/wav'

      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: '',
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Try to upload invalid file type
      const invalidFile = new File(['content'], 'test.txt', { type: 'text/plain' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [invalidFile],
        writable: false,
      })

      await fileInput.trigger('change')
      await nextTick()

      // Verify validation error is shown
      expect(wrapper.find('.upload-error').exists()).toBe(true)
      expect(wrapper.text()).toContain('Invalid file type')
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('integrates file size validation from PHP field', async () => {
      mockField.maxSize = 1 // 1KB limit

      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: '',
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Create a file larger than the limit
      const largeFile = new File(['x'.repeat(2000)], 'large.mp3', { type: 'audio/mpeg' })
      const fileInput = wrapper.find('input[type="file"]')

      Object.defineProperty(fileInput.element, 'files', {
        value: [largeFile],
        writable: false,
      })

      await fileInput.trigger('change')
      await nextTick()

      // Verify validation error is shown
      expect(wrapper.find('.upload-error').exists()).toBe(true)
      expect(wrapper.text()).toContain('File size exceeds maximum limit')
      expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('displays server-side validation errors correctly', () => {
      const validationErrors = {
        theme_song: ['The theme song field is required.']
      }

      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: '',
          errors: validationErrors
        },
        global: {
          stubs: {
            BaseField: {
              props: ['field', 'modelValue', 'errors'],
              template: '<div class="field-wrapper"><div v-if="errors.theme_song" class="error">{{ errors.theme_song[0] }}</div><slot /></div>'
            }
          }
        }
      })

      // Verify server validation error is displayed
      expect(wrapper.text()).toContain('The theme song field is required.')
    })
  })

  describe('Advanced Integration Scenarios', () => {
    it('handles complex field configuration from PHP', () => {
      // Simulate complex PHP field configuration
      const complexField = createMockField({
        name: 'Podcast Episode',
        attribute: 'podcast_episode',
        component: 'AudioField',
        disk: 'podcasts',
        path: 'episodes',
        acceptedTypes: 'audio/mpeg,audio/wav,audio/ogg',
        maxSize: 51200, // 50MB
        required: true,
        help: 'Upload your podcast episode in MP3, WAV, or OGG format',
        meta: {
          preload: 'auto',
          downloadsDisabled: false
        }
      })

      wrapper = mount(AudioField, {
        props: {
          field: complexField,
          modelValue: '',
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Verify all complex configurations are handled
      expect(wrapper.props('field').name).toBe('Podcast Episode')
      expect(wrapper.props('field').disk).toBe('podcasts')
      expect(wrapper.props('field').path).toBe('episodes')
      expect(wrapper.props('field').maxSize).toBe(51200)
      expect(wrapper.text()).toContain('Maximum file size: 50.0 MB')
      expect(wrapper.text()).toContain('AUDIO/MPEG, AUDIO/WAV, AUDIO/OGG')
    })

    it('handles thumbnail preview display correctly', async () => {
      const audioUrl = 'https://example.com/audio-with-thumbnail.mp3'

      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: audioUrl,
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper"><slot /></div>'
            }
          }
        }
      })

      // Verify thumbnail preview is displayed (Nova requirement)
      expect(wrapper.find('.audio-preview-container').exists()).toBe(true)
      expect(wrapper.find('.audio-icon').exists()).toBe(true)
      expect(wrapper.find('audio').exists()).toBe(true)
      expect(wrapper.text()).toContain('Current Audio')
    })

    it('integrates with Nova search results display', () => {
      // This would be tested in actual Nova integration
      // Here we verify the field can be displayed in compact mode
      wrapper = mount(AudioField, {
        props: {
          field: mockField,
          modelValue: 'https://example.com/search-result-audio.mp3',
          size: 'compact',
          errors: {}
        },
        global: {
          stubs: {
            BaseField: {
              template: '<div class="field-wrapper compact"><slot /></div>'
            }
          }
        }
      })

      // Verify field can handle compact display mode
      expect(wrapper.props('size')).toBe('compact')
      expect(wrapper.find('.audio-preview-container').exists()).toBe(true)
    })
  })
})
