import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import AudioField from '@/components/Fields/AudioField.vue'
import { createMockField, mountField } from '../../helpers.js'

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

describe('AudioField', () => {
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

  it('renders correctly with default props', () => {
    wrapper = mountField(AudioField, { field: mockField })

    expect(wrapper.find('.audio-field').exists()).toBe(true)
    expect(wrapper.find('.file-upload-dropzone').exists()).toBe(true)
    expect(wrapper.text()).toContain('Upload an audio file')
  })

  it('displays audio preview when modelValue is provided', async () => {
    const audioUrl = 'https://example.com/audio.mp3'
    wrapper = mountField(AudioField, {
      field: mockField,
      modelValue: audioUrl
    })

    expect(wrapper.find('.audio-preview-container').exists()).toBe(true)
    expect(wrapper.find('audio').exists()).toBe(true)
    expect(wrapper.find('audio').attributes('src')).toBe(audioUrl)
    expect(wrapper.text()).toContain('Current Audio')
  })

  it('sets correct preload attribute on audio element', async () => {
    const audioUrl = 'https://example.com/audio.mp3'
    mockField.meta.preload = 'auto'

    wrapper = mountField(AudioField, {
      field: mockField,
      modelValue: audioUrl
    })

    const audioElement = wrapper.find('audio')
    expect(audioElement.attributes('preload')).toBe('auto')
  })

  it('hides download button when downloads are disabled', async () => {
    const audioUrl = 'https://example.com/audio.mp3'
    mockField.meta.downloadsDisabled = true

    wrapper = mountField(AudioField, {
      field: mockField,
      modelValue: audioUrl
    })

    expect(wrapper.find('.download-btn').exists()).toBe(false)
  })

  it('shows download button when downloads are enabled', async () => {
    const audioUrl = 'https://example.com/audio.mp3'
    mockField.meta.downloadsDisabled = false

    wrapper = mountField(AudioField, {
      field: mockField,
      modelValue: audioUrl
    })

    expect(wrapper.find('.download-btn').exists()).toBe(true)
  })

  it('handles file selection via input', async () => {
    wrapper = mountField(AudioField, { field: mockField })

    const file = new File(['audio content'], 'test.mp3', { type: 'audio/mpeg' })
    const fileInput = wrapper.find('input[type="file"]')

    // Mock the file input change event
    Object.defineProperty(fileInput.element, 'files', {
      value: [file],
      writable: false,
    })

    await fileInput.trigger('change')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(file)
  })

  it('validates file type correctly', async () => {
    wrapper = mountField(AudioField, { field: mockField })

    // Try to upload invalid file type
    const invalidFile = new File(['content'], 'test.txt', { type: 'text/plain' })
    const fileInput = wrapper.find('input[type="file"]')

    Object.defineProperty(fileInput.element, 'files', {
      value: [invalidFile],
      writable: false,
    })

    await fileInput.trigger('change')
    await nextTick()

    expect(wrapper.find('.upload-error').exists()).toBe(true)
    expect(wrapper.text()).toContain('Invalid file type')
    expect(wrapper.emitted('update:modelValue')).toBeFalsy()
  })

  it('validates file size correctly', async () => {
    mockField.maxSize = 1 // 1KB limit
    wrapper = mountField(AudioField, { field: mockField })

    // Create a file larger than the limit
    const largeFile = new File(['x'.repeat(2000)], 'large.mp3', { type: 'audio/mpeg' })
    const fileInput = wrapper.find('input[type="file"]')

    Object.defineProperty(fileInput.element, 'files', {
      value: [largeFile],
      writable: false,
    })

    await fileInput.trigger('change')
    await nextTick()

    expect(wrapper.find('.upload-error').exists()).toBe(true)
    expect(wrapper.text()).toContain('File size exceeds maximum limit')
    expect(wrapper.emitted('update:modelValue')).toBeFalsy()
  })

  it('handles drag and drop functionality', async () => {
    wrapper = mountField(AudioField, { field: mockField })

    const dropzone = wrapper.find('.file-upload-dropzone')
    const file = new File(['audio content'], 'test.mp3', { type: 'audio/mpeg' })

    // Test dragover
    await dropzone.trigger('dragover')
    expect(wrapper.find('.file-upload-dropzone-dragover').exists()).toBe(true)

    // Test drop
    const dropEvent = new Event('drop')
    dropEvent.dataTransfer = { files: [file] }

    await dropzone.element.dispatchEvent(dropEvent)
    await nextTick()

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(file)
  })

  it('removes audio when remove button is clicked', async () => {
    const audioUrl = 'https://example.com/audio.mp3'
    wrapper = mountField(AudioField, {
      field: mockField,
      modelValue: audioUrl
    })

    const removeButton = wrapper.find('.remove-audio-btn')
    await removeButton.trigger('click')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
  })

  it('handles readonly state correctly', async () => {
    wrapper = mount(AudioField, {
      props: {
        field: mockField,
        readonly: true,
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

    // File upload should be hidden in readonly mode
    expect(wrapper.find('.file-upload-container').exists()).toBe(false)

    // Test with audio present to check remove button is hidden
    await wrapper.setProps({ modelValue: 'https://example.com/audio.mp3' })
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.remove-audio-btn').exists()).toBe(false)
  })

  it('handles disabled state correctly', async () => {
    wrapper = mount(AudioField, {
      props: {
        field: mockField,
        disabled: true,
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

    // File upload container should not exist when disabled
    expect(wrapper.find('.file-upload-container').exists()).toBe(false)
  })

  it('displays accepted file types', async () => {
    wrapper = mountField(AudioField, { field: mockField })

    expect(wrapper.text()).toContain('AUDIO/MPEG, AUDIO/WAV, AUDIO/OGG, .MP3, .WAV, .OGG')
  })

  it('displays maximum file size', async () => {
    wrapper = mountField(AudioField, { field: mockField })

    expect(wrapper.text()).toContain('Maximum file size: 10.0 MB')
  })

  it('formats file sizes correctly', async () => {
    // Test KB formatting
    mockField.maxSize = 512
    wrapper = mountField(AudioField, { field: mockField })
    expect(wrapper.text()).toContain('512 KB')

    // Test MB formatting
    await wrapper.setProps({ field: { ...mockField, maxSize: 2048 } })
    expect(wrapper.text()).toContain('2.0 MB')
  })

  it('handles File object as modelValue', async () => {
    const file = new File(['audio content'], 'test.mp3', { type: 'audio/mpeg' })
    wrapper = mountField(AudioField, {
      field: mockField,
      modelValue: file
    })

    expect(wrapper.find('.audio-preview-container').exists()).toBe(true)
    expect(wrapper.text()).toContain('test.mp3')
    expect(global.URL.createObjectURL).toHaveBeenCalledWith(file)
  })

  it('handles audio error correctly', async () => {
    const audioUrl = 'https://example.com/broken-audio.mp3'
    wrapper = mountField(AudioField, {
      field: mockField,
      modelValue: audioUrl
    })

    const audioElement = wrapper.find('audio')
    await audioElement.trigger('error')
    await nextTick()

    expect(wrapper.find('.upload-error').exists()).toBe(true)
    expect(wrapper.text()).toContain('Unable to load audio file')
  })

  it('triggers download when download button is clicked', async () => {
    const audioUrl = 'https://example.com/audio.mp3'
    wrapper = mountField(AudioField, {
      field: mockField,
      modelValue: audioUrl
    })

    // Mock document methods
    const mockLink = {
      click: vi.fn(),
      href: '',
      download: ''
    }
    const createElementSpy = vi.spyOn(document, 'createElement').mockReturnValue(mockLink)
    const appendChildSpy = vi.spyOn(document.body, 'appendChild').mockImplementation(() => {})
    const removeChildSpy = vi.spyOn(document.body, 'removeChild').mockImplementation(() => {})

    const downloadButton = wrapper.find('.download-btn')
    await downloadButton.trigger('click')

    expect(createElementSpy).toHaveBeenCalledWith('a')
    expect(mockLink.href).toBe(audioUrl)
    expect(mockLink.click).toHaveBeenCalled()
    expect(appendChildSpy).toHaveBeenCalledWith(mockLink)
    expect(removeChildSpy).toHaveBeenCalledWith(mockLink)

    createElementSpy.mockRestore()
    appendChildSpy.mockRestore()
    removeChildSpy.mockRestore()
  })

  it('applies dark theme classes correctly', async () => {
    mockAdminStore.isDarkTheme = true
    wrapper = mountField(AudioField, { field: mockField })

    expect(wrapper.find('.file-upload-dropzone-dark').exists()).toBe(true)
  })

  it('cleans up object URLs on unmount', async () => {
    const file = new File(['audio content'], 'test.mp3', { type: 'audio/mpeg' })
    wrapper = mountField(AudioField, {
      field: mockField,
      modelValue: file
    })

    wrapper.unmount()

    expect(global.URL.revokeObjectURL).toHaveBeenCalled()
  })
})
