import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import RichTextField from '@/components/Fields/RichTextField.vue'
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
  BoldIcon: { template: '<div data-testid="bold-icon"></div>' },
  ItalicIcon: { template: '<div data-testid="italic-icon"></div>' },
  UnderlineIcon: { template: '<div data-testid="underline-icon"></div>' },
  StrikethroughIcon: { template: '<div data-testid="strikethrough-icon"></div>' },
  ListBulletIcon: { template: '<div data-testid="list-bullet-icon"></div>' },
  NumberedListIcon: { template: '<div data-testid="numbered-list-icon"></div>' },
  LinkIcon: { template: '<div data-testid="link-icon"></div>' },
  PhotoIcon: { template: '<div data-testid="photo-icon"></div>' },
  CodeBracketIcon: { template: '<div data-testid="code-bracket-icon"></div>' },
  ArrowsPointingOutIcon: { template: '<div data-testid="arrows-pointing-out-icon"></div>' }
}))

// Mock Quill editor
vi.mock('quill', () => ({
  default: vi.fn(() => ({
    root: { innerHTML: '' },
    on: vi.fn(),
    off: vi.fn(),
    getContents: vi.fn(() => ({ ops: [] })),
    setContents: vi.fn(),
    getText: vi.fn(() => ''),
    getHTML: vi.fn(() => ''),
    focus: vi.fn(),
    blur: vi.fn(),
    enable: vi.fn(),
    disable: vi.fn(),
    format: vi.fn(),
    insertEmbed: vi.fn(),
    insertText: vi.fn(),
    deleteText: vi.fn(),
    getSelection: vi.fn(),
    setSelection: vi.fn()
  }))
}))

describe('RichTextField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Article Content',
      attribute: 'content',
      type: 'richText',
      toolbar: 'full',
      height: 400,
      placeholder: 'Start writing your article...',
      enableImages: true,
      enableLinks: true,
      enableTables: true
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders rich text editor container', () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const editorContainer = wrapper.find('.rich-text-editor')
      expect(editorContainer.exists()).toBe(true)
    })

    it('shows toolbar when toolbar is enabled', () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const toolbar = wrapper.find('.ql-toolbar')
      expect(toolbar.exists()).toBe(true)
    })

    it('hides toolbar when toolbar is disabled', () => {
      const fieldWithoutToolbar = createMockField({
        ...mockField,
        toolbar: false
      })

      wrapper = mountField(RichTextField, { field: fieldWithoutToolbar })

      expect(wrapper.find('.ql-toolbar').exists()).toBe(false)
    })

    it('shows editor content area', () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const editorContent = wrapper.find('.ql-editor')
      expect(editorContent.exists()).toBe(true)
    })

    it('applies custom height', () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const editorContainer = wrapper.find('.rich-text-editor')
      expect(editorContainer.attributes('style')).toContain('height: 400px')
    })

    it('applies disabled state', () => {
      wrapper = mountField(RichTextField, {
        field: mockField,
        props: { 
          field: mockField,
          disabled: true 
        }
      })

      const editorContainer = wrapper.find('.rich-text-editor')
      expect(editorContainer.classes()).toContain('opacity-50')
    })

    it('applies readonly state', () => {
      wrapper = mountField(RichTextField, {
        field: mockField,
        props: { 
          field: mockField,
          readonly: true 
        }
      })

      expect(wrapper.vm.isReadOnly).toBe(true)
    })
  })

  describe('Toolbar Configuration', () => {
    it('shows full toolbar with all formatting options', () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const boldButton = wrapper.find('[data-testid="bold-icon"]')
      const italicButton = wrapper.find('[data-testid="italic-icon"]')
      const underlineButton = wrapper.find('[data-testid="underline-icon"]')
      const listButton = wrapper.find('[data-testid="list-bullet-icon"]')

      expect(boldButton.exists()).toBe(true)
      expect(italicButton.exists()).toBe(true)
      expect(underlineButton.exists()).toBe(true)
      expect(listButton.exists()).toBe(true)
    })

    it('shows minimal toolbar with basic formatting only', () => {
      const minimalField = createMockField({
        ...mockField,
        toolbar: 'minimal'
      })

      wrapper = mountField(RichTextField, { field: minimalField })

      const boldButton = wrapper.find('[data-testid="bold-icon"]')
      const italicButton = wrapper.find('[data-testid="italic-icon"]')
      
      expect(boldButton.exists()).toBe(true)
      expect(italicButton.exists()).toBe(true)
    })

    it('shows custom toolbar configuration', () => {
      const customField = createMockField({
        ...mockField,
        toolbar: ['bold', 'italic', 'link']
      })

      wrapper = mountField(RichTextField, { field: customField })

      expect(wrapper.vm.toolbarConfig).toEqual(['bold', 'italic', 'link'])
    })

    it('executes formatting commands when toolbar buttons clicked', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const formatSpy = vi.spyOn(wrapper.vm, 'formatText')
      const boldButton = wrapper.find('[data-testid="bold-icon"]')
      
      await boldButton.element.parentElement.click()
      expect(formatSpy).toHaveBeenCalledWith('bold')
    })
  })

  describe('Content Management', () => {
    it('updates content when typing', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      wrapper.vm.editorContent = '<p>New content</p>'
      await wrapper.vm.updateContent()

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('change')).toBeTruthy()
    })

    it('handles HTML content input', () => {
      wrapper = mountField(RichTextField, {
        field: mockField,
        modelValue: '<p><strong>Bold text</strong> and <em>italic text</em></p>'
      })

      expect(wrapper.vm.editorContent).toContain('<strong>Bold text</strong>')
      expect(wrapper.vm.editorContent).toContain('<em>italic text</em>')
    })

    it('handles plain text input', () => {
      wrapper = mountField(RichTextField, {
        field: mockField,
        modelValue: 'Plain text content'
      })

      expect(wrapper.vm.editorContent).toContain('Plain text content')
    })

    it('sanitizes dangerous HTML content', () => {
      wrapper = mountField(RichTextField, {
        field: mockField,
        modelValue: '<script>alert("xss")</script><p>Safe content</p>'
      })

      expect(wrapper.vm.editorContent).not.toContain('<script>')
      expect(wrapper.vm.editorContent).toContain('Safe content')
    })

    it('preserves allowed HTML tags', () => {
      wrapper = mountField(RichTextField, {
        field: mockField,
        modelValue: '<p><strong>Bold</strong> <em>italic</em> <u>underline</u></p>'
      })

      expect(wrapper.vm.editorContent).toContain('<strong>Bold</strong>')
      expect(wrapper.vm.editorContent).toContain('<em>italic</em>')
      expect(wrapper.vm.editorContent).toContain('<u>underline</u>')
    })
  })

  describe('Image Handling', () => {
    it('shows image button when images are enabled', () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const imageButton = wrapper.find('[data-testid="photo-icon"]')
      expect(imageButton.exists()).toBe(true)
    })

    it('hides image button when images are disabled', () => {
      const fieldWithoutImages = createMockField({
        ...mockField,
        enableImages: false
      })

      wrapper = mountField(RichTextField, { field: fieldWithoutImages })

      expect(wrapper.find('[data-testid="photo-icon"]').exists()).toBe(false)
    })

    it('opens image upload dialog when image button clicked', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const insertImageSpy = vi.spyOn(wrapper.vm, 'insertImage')
      const imageButton = wrapper.find('[data-testid="photo-icon"]')
      
      await imageButton.element.parentElement.click()
      expect(insertImageSpy).toHaveBeenCalled()
    })

    it('inserts image at cursor position', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const imageUrl = 'https://example.com/image.jpg'
      await wrapper.vm.insertImage(imageUrl)

      expect(wrapper.vm.editorContent).toContain(`<img src="${imageUrl}"`)
    })

    it('handles image upload and insertion', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const file = new File(['image'], 'test.jpg', { type: 'image/jpeg' })
      const uploadSpy = vi.spyOn(wrapper.vm, 'uploadImage').mockResolvedValue('uploaded-url.jpg')

      await wrapper.vm.handleImageUpload(file)

      expect(uploadSpy).toHaveBeenCalledWith(file)
    })
  })

  describe('Link Handling', () => {
    it('shows link button when links are enabled', () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const linkButton = wrapper.find('[data-testid="link-icon"]')
      expect(linkButton.exists()).toBe(true)
    })

    it('hides link button when links are disabled', () => {
      const fieldWithoutLinks = createMockField({
        ...mockField,
        enableLinks: false
      })

      wrapper = mountField(RichTextField, { field: fieldWithoutLinks })

      expect(wrapper.find('[data-testid="link-icon"]').exists()).toBe(false)
    })

    it('opens link dialog when link button clicked', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const insertLinkSpy = vi.spyOn(wrapper.vm, 'insertLink')
      const linkButton = wrapper.find('[data-testid="link-icon"]')
      
      await linkButton.element.parentElement.click()
      expect(insertLinkSpy).toHaveBeenCalled()
    })

    it('inserts link with selected text', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const linkUrl = 'https://example.com'
      const linkText = 'Example Link'
      
      await wrapper.vm.insertLink(linkUrl, linkText)

      expect(wrapper.vm.editorContent).toContain(`<a href="${linkUrl}">${linkText}</a>`)
    })

    it('validates link URLs', () => {
      wrapper = mountField(RichTextField, { field: mockField })

      expect(wrapper.vm.isValidUrl('https://example.com')).toBe(true)
      expect(wrapper.vm.isValidUrl('http://example.com')).toBe(true)
      expect(wrapper.vm.isValidUrl('invalid-url')).toBe(false)
      expect(wrapper.vm.isValidUrl('javascript:alert("xss")')).toBe(false)
    })
  })

  describe('Table Support', () => {
    it('shows table button when tables are enabled', () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const tableButton = wrapper.find('.table-button')
      expect(tableButton.exists()).toBe(true)
    })

    it('hides table button when tables are disabled', () => {
      const fieldWithoutTables = createMockField({
        ...mockField,
        enableTables: false
      })

      wrapper = mountField(RichTextField, { field: fieldWithoutTables })

      expect(wrapper.find('.table-button').exists()).toBe(false)
    })

    it('inserts table when table button clicked', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const insertTableSpy = vi.spyOn(wrapper.vm, 'insertTable')
      const tableButton = wrapper.find('.table-button')
      
      if (tableButton.exists()) {
        await tableButton.trigger('click')
        expect(insertTableSpy).toHaveBeenCalled()
      }
    })

    it('creates table with specified dimensions', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      await wrapper.vm.insertTable(3, 2) // 3 columns, 2 rows

      expect(wrapper.vm.editorContent).toContain('<table>')
      expect(wrapper.vm.editorContent).toContain('<tr>')
      expect(wrapper.vm.editorContent).toContain('<td>')
    })
  })

  describe('Fullscreen Mode', () => {
    it('shows fullscreen button', () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const fullscreenButton = wrapper.find('[data-testid="arrows-pointing-out-icon"]')
      expect(fullscreenButton.exists()).toBe(true)
    })

    it('toggles fullscreen mode when button clicked', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const toggleSpy = vi.spyOn(wrapper.vm, 'toggleFullscreen')
      const fullscreenButton = wrapper.find('[data-testid="arrows-pointing-out-icon"]')
      
      await fullscreenButton.element.parentElement.click()
      expect(toggleSpy).toHaveBeenCalled()
    })

    it('applies fullscreen classes when in fullscreen mode', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      wrapper.vm.isFullscreen = true
      await nextTick()

      const editorContainer = wrapper.find('.rich-text-editor')
      expect(editorContainer.classes()).toContain('fixed')
      expect(editorContainer.classes()).toContain('inset-0')
      expect(editorContainer.classes()).toContain('z-50')
    })

    it('exits fullscreen on escape key', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      wrapper.vm.isFullscreen = true
      
      const handleKeydownSpy = vi.spyOn(wrapper.vm, 'handleKeydown')
      await wrapper.trigger('keydown', { key: 'Escape' })

      expect(handleKeydownSpy).toHaveBeenCalled()
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(RichTextField, { field: mockField })

      const editorContainer = wrapper.find('.rich-text-editor')
      expect(editorContainer.classes()).toContain('bg-gray-800')
      expect(editorContainer.classes()).toContain('border-gray-600')
    })

    it('applies dark theme to toolbar', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(RichTextField, { field: mockField })

      const toolbar = wrapper.find('.ql-toolbar')
      expect(toolbar.classes()).toContain('bg-gray-700')
    })

    it('applies dark theme to editor content', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(RichTextField, { field: mockField })

      const editorContent = wrapper.find('.ql-editor')
      expect(editorContent.classes()).toContain('text-gray-100')
    })
  })

  describe('Event Handling', () => {
    it('emits focus event', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      await wrapper.vm.handleFocus()
      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      await wrapper.vm.handleBlur()
      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('emits change event when content changes', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      wrapper.vm.editorContent = '<p>New content</p>'
      await wrapper.vm.updateContent()

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('change')).toBeTruthy()
    })

    it('debounces content updates', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const updateSpy = vi.spyOn(wrapper.vm, 'updateContent')
      
      // Trigger multiple rapid changes
      wrapper.vm.editorContent = '<p>Change 1</p>'
      wrapper.vm.editorContent = '<p>Change 2</p>'
      wrapper.vm.editorContent = '<p>Change 3</p>'

      // Wait for debounce
      await new Promise(resolve => setTimeout(resolve, 350))

      expect(updateSpy).toHaveBeenCalledTimes(1)
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(RichTextField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the editor', async () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const focusEditorSpy = vi.spyOn(wrapper.vm, 'focusEditor')
      wrapper.vm.focus()

      expect(focusEditorSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles null modelValue', () => {
      wrapper = mountField(RichTextField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.editorContent).toBe('')
    })

    it('handles undefined modelValue', () => {
      wrapper = mountField(RichTextField, {
        field: mockField,
        modelValue: undefined
      })

      expect(wrapper.vm.editorContent).toBe('')
    })

    it('handles very large content', () => {
      const largeContent = '<p>' + 'A'.repeat(100000) + '</p>'
      
      wrapper = mountField(RichTextField, {
        field: mockField,
        modelValue: largeContent
      })

      expect(wrapper.vm.editorContent).toBe(largeContent)
    })

    it('handles malformed HTML gracefully', () => {
      const malformedHtml = '<p><strong>Bold text</strong><em>Unclosed italic'
      
      wrapper = mountField(RichTextField, {
        field: mockField,
        modelValue: malformedHtml
      })

      // Should clean up malformed HTML
      expect(wrapper.vm.editorContent).toContain('<strong>Bold text</strong>')
    })

    it('cleans up editor on unmount', () => {
      wrapper = mountField(RichTextField, { field: mockField })

      const destroySpy = vi.spyOn(wrapper.vm, 'destroyEditor')
      wrapper.unmount()

      expect(destroySpy).toHaveBeenCalled()
    })
  })
})
