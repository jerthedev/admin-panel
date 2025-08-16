import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import AvatarField from '@/components/Fields/AvatarField.vue'
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

// Mock URL.createObjectURL
global.URL.createObjectURL = vi.fn(() => 'blob:mock-url')
global.URL.revokeObjectURL = vi.fn()

describe('AvatarField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Avatar',
      attribute: 'avatar',
      type: 'avatar',
      acceptedTypes: 'image/*',
      size: 80
    })

    // Reset URL mocks
    global.URL.createObjectURL.mockClear()
    global.URL.revokeObjectURL.mockClear()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders upload area when no avatar is present', () => {
      wrapper = mountField(AvatarField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.find('label[for]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="cloud-arrow-up-icon"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Click to upload')
      expect(wrapper.text()).toContain('or drag and drop')
    })

    it('displays current avatar when modelValue is provided', () => {
      wrapper = mountField(AvatarField, {
        field: mockField,
        modelValue: 'https://example.com/avatar.jpg'
      })

      const img = wrapper.find('img')
      expect(img.exists()).toBe(true)
      expect(img.attributes('src')).toBe('https://example.com/avatar.jpg')
      expect(img.attributes('alt')).toBe(mockField.name)
      expect(wrapper.text()).toContain('Current Avatar')
    })

    it('shows accepted file types text', () => {
      wrapper = mountField(AvatarField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.text()).toContain('PNG, JPG, WEBP up to 10MB')
    })

    it('shows custom accepted types when specified', () => {
      const fieldWithCustomTypes = createMockField({
        ...mockField,
        acceptedTypes: 'image/png,image/jpeg'
      })

      wrapper = mountField(AvatarField, {
        field: fieldWithCustomTypes,
        modelValue: null
      })

      expect(wrapper.text()).toContain('PNG,JPEG')
    })


  })

  describe('Avatar Display', () => {


    it('shows remove button when not readonly or disabled', () => {
      wrapper = mountField(AvatarField, {
        field: mockField,
        modelValue: 'https://example.com/avatar.jpg'
      })

      const removeButton = wrapper.find('button')
      expect(removeButton.exists()).toBe(true)
      expect(removeButton.text()).toBe('Remove')
    })



    it('displays avatar name from URL', () => {
      wrapper = mountField(AvatarField, {
        field: mockField,
        modelValue: 'https://example.com/path/to/avatar.jpg'
      })

      expect(wrapper.text()).toContain('avatar.jpg')
    })
  })

  describe('File Selection', () => {
    beforeEach(() => {
      wrapper = mountField(AvatarField, {
        field: mockField,
        modelValue: null
      })
    })

    it('handles file selection via input', async () => {
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' })
      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [file],
        writable: false
      })

      await input.trigger('change')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(file)
      expect(wrapper.emitted('change')[0][0]).toBe(file)
    })

    it('displays selected file info', async () => {
      const file = new File(['test content'], 'test.jpg', { type: 'image/jpeg' })
      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [file],
        writable: false
      })

      await input.trigger('change')
      await nextTick()

      expect(wrapper.find('[data-testid="document-icon"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('test.jpg')
      expect(wrapper.text()).toContain('12 Bytes') // "test content" is 12 bytes
    })

    it('creates object URL for selected file preview', async () => {
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' })
      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [file],
        writable: false
      })

      await input.trigger('change')
      await nextTick()

      expect(global.URL.createObjectURL).toHaveBeenCalledWith(file)

      const img = wrapper.find('img')
      expect(img.attributes('src')).toBe('blob:mock-url')
    })

    it('clears selected file when clear button is clicked', async () => {
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' })
      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [file],
        writable: false
      })

      await input.trigger('change')
      await nextTick()

      const clearButton = wrapper.find('[data-testid="x-mark-icon"]').element.closest('button')
      await clearButton.click()

      expect(wrapper.vm.selectedFile).toBe(null)
      expect(input.element.value).toBe('')
    })
  })



  describe('File Validation', () => {
    beforeEach(() => {
      wrapper = mountField(AvatarField, {
        field: mockField,
        modelValue: null
      })
    })

    it('accepts valid image files', async () => {
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' })
      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [file],
        writable: false
      })

      await input.trigger('change')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(file)
    })


  })

  describe('Remove Avatar', () => {
    it('removes current avatar when remove button is clicked', async () => {
      wrapper = mountField(AvatarField, {
        field: mockField,
        modelValue: 'https://example.com/avatar.jpg'
      })

      const removeButton = wrapper.find('button')
      await removeButton.trigger('click')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
      expect(wrapper.emitted('change')[0][0]).toBe(null)
    })

    it('clears file input when removing avatar', async () => {
      wrapper = mountField(AvatarField, {
        field: mockField,
        modelValue: 'https://example.com/avatar.jpg'
      })

      const removeButton = wrapper.find('button')
      await removeButton.trigger('click')

      const input = wrapper.find('input[type="file"]')
      expect(input.element.value).toBe('')
    })

    it('removes selected file when remove button is clicked', async () => {
      wrapper = mountField(AvatarField, {
        field: mockField,
        modelValue: null
      })

      // First select a file
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' })
      const input = wrapper.find('input[type="file"]')

      Object.defineProperty(input.element, 'files', {
        value: [file],
        writable: false
      })

      await input.trigger('change')
      await nextTick()

      // Then remove it
      const removeButton = wrapper.find('button')
      await removeButton.trigger('click')

      expect(wrapper.emitted('update:modelValue')[1][0]).toBe(null)
      expect(wrapper.emitted('change')[1][0]).toBe(null)
      expect(wrapper.vm.selectedFile).toBe(null)
    })
  })







  describe('Edge Cases', () => {
    it('handles null modelValue gracefully', () => {
      wrapper = mountField(AvatarField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.find('img').exists()).toBe(false)
    })

    it('handles empty string modelValue gracefully', () => {
      wrapper = mountField(AvatarField, {
        field: mockField,
        modelValue: ''
      })

      expect(wrapper.find('img').exists()).toBe(false)
    })
  })
})
