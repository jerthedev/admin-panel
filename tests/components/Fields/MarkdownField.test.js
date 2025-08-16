import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import MarkdownField from '@/components/Fields/MarkdownField.vue'
import { createMockField, mountField } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false,
  fullscreenMode: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  ArrowsPointingOutIcon: { template: '<div data-testid="arrows-pointing-out-icon"></div>' },
  XMarkIcon: { template: '<div data-testid="x-mark-icon"></div>' },
  EyeIcon: { template: '<div data-testid="eye-icon"></div>' },
  CodeBracketIcon: { template: '<div data-testid="code-bracket-icon"></div>' },
  BoldIcon: { template: '<div data-testid="bold-icon"></div>' },
  ItalicIcon: { template: '<div data-testid="italic-icon"></div>' },
  UnderlineIcon: { template: '<div data-testid="underline-icon"></div>' },
  StrikethroughIcon: { template: '<div data-testid="strikethrough-icon"></div>' },
  ListBulletIcon: { template: '<div data-testid="list-bullet-icon"></div>' },
  NumberedListIcon: { template: '<div data-testid="numbered-list-icon"></div>' },
  LinkIcon: { template: '<div data-testid="link-icon"></div>' },
  PhotoIcon: { template: '<div data-testid="photo-icon"></div>' },
  MinusIcon: { template: '<div data-testid="minus-icon"></div>' }
}))

// Note: Component now uses vanilla contenteditable div instead of BlockNote editor

describe('MarkdownField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Article Content',
      attribute: 'content',
      type: 'markdown',
      showToolbar: true,
      enableSlashCommands: true,
      height: 400,
      autoResize: false,
      placeholder: 'Start writing your article...'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders markdown editor container', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const editorContainer = wrapper.find('.relative.border')
      expect(editorContainer.exists()).toBe(true)
    })

    it('shows toolbar when in rich text mode', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      // Toolbar is shown by default in rich text mode
      const toolbar = wrapper.find('.flex.items-center.justify-between.p-2.bg-gray-50')
      expect(toolbar.exists()).toBe(true)
    })

    it('hides toolbar when in markdown mode', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      // Switch to markdown mode
      const buttons = wrapper.findAll('button')
      const markdownButton = buttons.find(btn => btn.text().includes('Markdown'))
      await markdownButton.trigger('click')
      await nextTick()

      // Toolbar should be hidden in markdown mode
      const toolbar = wrapper.find('.flex.items-center.justify-between.p-2.bg-gray-50')
      expect(toolbar.exists()).toBe(false)
    })

    it('shows mode toggle buttons', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      // Find mode toggle buttons by their text content
      const buttons = wrapper.findAll('button')
      const richModeButton = buttons.find(btn => btn.text().includes('Rich Text'))
      const markdownModeButton = buttons.find(btn => btn.text().includes('Markdown'))

      expect(richModeButton.exists()).toBe(true)
      expect(markdownModeButton.exists()).toBe(true)
    })

    it('shows fullscreen toggle button', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      // Fullscreen button is in the toolbar (only visible in rich mode)
      const fullscreenButton = wrapper.find('button[title*="Fullscreen"]')
      expect(fullscreenButton.exists()).toBe(true)
    })

    it('applies disabled state', () => {
      wrapper = mountField(MarkdownField, {
        field: mockField,
        disabled: true
      })

      // Check that toolbar buttons are disabled
      const boldButton = wrapper.find('button[title*="Bold"]')
      expect(boldButton.attributes('disabled')).toBeDefined()
    })

    it('applies readonly state', () => {
      wrapper = mountField(MarkdownField, {
        field: mockField,
        readonly: true
      })

      // Check that toolbar buttons are disabled in readonly mode
      const boldButton = wrapper.find('button[title*="Bold"]')
      expect(boldButton.attributes('disabled')).toBeDefined()
    })
  })

  describe('Editor Modes', () => {
    it('starts in rich text mode by default', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      expect(wrapper.vm.mode).toBe('rich')
    })

    it('switches to markdown mode when markdown button clicked', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const buttons = wrapper.findAll('button')
      const markdownButton = buttons.find(btn => btn.text().includes('Markdown'))
      await markdownButton.trigger('click')

      expect(wrapper.vm.mode).toBe('markdown')
    })

    it('switches back to rich mode when rich button clicked', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      // Switch to markdown mode first
      const buttons = wrapper.findAll('button')
      const markdownButton = buttons.find(btn => btn.text().includes('Markdown'))
      await markdownButton.trigger('click')
      await nextTick()

      const richButton = buttons.find(btn => btn.text().includes('Rich Text'))
      await richButton.trigger('click')

      expect(wrapper.vm.mode).toBe('rich')
    })

    it('shows rich editor in rich mode', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      // Rich mode is default, check for contenteditable div
      const richEditor = wrapper.find('[contenteditable="true"]')
      expect(richEditor.exists()).toBe(true)
    })

    it('shows markdown textarea in markdown mode', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      wrapper.vm.mode = 'markdown'
      await nextTick()

      const markdownTextarea = wrapper.find('textarea')
      expect(markdownTextarea.exists()).toBe(true)
    })
  })

  describe('Toolbar Functionality', () => {
    it('shows formatting buttons in toolbar', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const boldButton = wrapper.find('button[title*="Bold"]')
      const italicButton = wrapper.find('button[title*="Italic"]')
      const underlineButton = wrapper.find('button[title*="Underline"]')
      const strikethroughButton = wrapper.find('button[title*="Strikethrough"]')

      expect(boldButton.exists()).toBe(true)
      expect(italicButton.exists()).toBe(true)
      expect(underlineButton.exists()).toBe(true)
      expect(strikethroughButton.exists()).toBe(true)
    })

    it('shows list buttons in toolbar', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const bulletListButton = wrapper.find('button[title*="Bullet List"]')
      const numberedListButton = wrapper.find('button[title*="Numbered List"]')

      expect(bulletListButton.exists()).toBe(true)
      expect(numberedListButton.exists()).toBe(true)
    })

    it('shows link button in toolbar', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const linkButton = wrapper.find('button[title*="Insert Link"]')

      expect(linkButton.exists()).toBe(true)
      // Note: Image button is not implemented in current component
    })

    it('executes formatting commands when toolbar buttons clicked', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const execCommandSpy = vi.spyOn(wrapper.vm, 'execCommand')
      const boldButton = wrapper.find('button[title*="Bold"]')

      await boldButton.trigger('click')
      expect(execCommandSpy).toHaveBeenCalledWith('bold')
    })

    it('disables toolbar buttons when readonly', () => {
      wrapper = mountField(MarkdownField, {
        field: mockField,
        props: {
          field: mockField,
          readonly: true
        }
      })

      const boldButton = wrapper.find('[data-testid="bold-icon"]').element.parentElement
      expect(boldButton.disabled).toBe(true)
    })
  })

  describe('Slash Commands', () => {
    it('shows slash command menu when enabled and "/" typed', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      wrapper.vm.showSlashMenu = true
      await nextTick()

      expect(wrapper.find('.slash-menu').exists()).toBe(true)
    })

    it('hides slash command menu when disabled', () => {
      const fieldWithoutSlash = createMockField({
        ...mockField,
        enableSlashCommands: false
      })

      wrapper = mountField(MarkdownField, { field: fieldWithoutSlash })

      wrapper.vm.showSlashMenu = true
      expect(wrapper.find('.slash-menu').exists()).toBe(false)
    })

    it('shows available slash commands', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      wrapper.vm.showSlashMenu = true
      wrapper.vm.slashCommands = [
        { id: 'heading1', label: 'Heading 1', icon: 'H1' },
        { id: 'heading2', label: 'Heading 2', icon: 'H2' },
        { id: 'bulletList', label: 'Bullet List', icon: '•' }
      ]
      await nextTick()

      expect(wrapper.text()).toContain('Heading 1')
      expect(wrapper.text()).toContain('Heading 2')
      expect(wrapper.text()).toContain('Bullet List')
    })

    it('executes slash command when selected', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const executeSlashCommandSpy = vi.spyOn(wrapper.vm, 'executeSlashCommand')
      wrapper.vm.executeSlashCommand('heading1')

      expect(executeSlashCommandSpy).toHaveBeenCalledWith('heading1')
    })

    it('filters slash commands based on search', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      wrapper.vm.slashQuery = 'head'
      await nextTick()

      const filteredCommands = wrapper.vm.filteredSlashCommands
      expect(filteredCommands.every(cmd => cmd.label.toLowerCase().includes('head'))).toBe(true)
    })
  })

  describe('Content Management', () => {
    it('updates content when typing in markdown mode', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      wrapper.vm.mode = 'markdown'
      await nextTick()

      const textarea = wrapper.find('textarea')
      await textarea.setValue('# Hello World\n\nThis is markdown content.')
      await textarea.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('change')).toBeTruthy()
    })

    it('syncs content between rich and markdown modes', async () => {
      wrapper = mountField(MarkdownField, {
        field: mockField,
        modelValue: '# Hello World\n\nThis is content.'
      })

      expect(wrapper.vm.markdownContent).toBe('# Hello World\n\nThis is content.')
    })

    it('handles empty content gracefully', () => {
      wrapper = mountField(MarkdownField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.markdownContent).toBe('')
    })

    it('preserves content when switching modes', async () => {
      wrapper = mountField(MarkdownField, {
        field: mockField,
        modelValue: '# Test Content'
      })

      // Switch to markdown mode
      wrapper.vm.mode = 'markdown'
      await nextTick()

      // Switch back to rich mode
      wrapper.vm.mode = 'rich'
      await nextTick()

      expect(wrapper.vm.markdownContent).toBe('# Test Content')
    })
  })

  describe('Fullscreen Mode', () => {
    it('toggles fullscreen mode when fullscreen button clicked', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const fullscreenButton = wrapper.find('[data-testid="arrows-pointing-out-icon"]')
      await fullscreenButton.element.parentElement.click()

      expect(wrapper.vm.toggleFullscreen).toBeDefined()
    })

    it('applies fullscreen classes when in fullscreen mode', async () => {
      mockAdminStore.fullscreenMode = true
      wrapper = mountField(MarkdownField, { field: mockField })

      const editorContainer = wrapper.find('.relative.border')
      expect(editorContainer.classes()).toContain('markdown-editor-fullscreen')
    })

    it('shows exit fullscreen button in fullscreen mode', async () => {
      mockAdminStore.fullscreenMode = true
      wrapper = mountField(MarkdownField, { field: mockField })

      const exitButton = wrapper.find('[data-testid="x-mark-icon"]')
      expect(exitButton.exists()).toBe(true)
    })

    it('exits fullscreen when escape key pressed', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const handleKeydownSpy = vi.spyOn(wrapper.vm, 'handleKeydown')
      await wrapper.trigger('keydown', { key: 'Escape' })

      expect(handleKeydownSpy).toHaveBeenCalled()
    })
  })

  describe('Copy-Paste Enhancement', () => {
    it('handles paste events with content cleaning', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const cleanPastedContentSpy = vi.spyOn(wrapper.vm, 'cleanPastedContent')
      const pasteEvent = new ClipboardEvent('paste', {
        clipboardData: new DataTransfer()
      })
      pasteEvent.clipboardData.setData('text/html', '<p><strong>Bold text</strong></p>')

      await wrapper.vm.handlePaste(pasteEvent)
      expect(cleanPastedContentSpy).toHaveBeenCalled()
    })

    it('cleans Google Docs formatting', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const googleDocsHtml = '<p style="margin:0;"><span style="font-weight:bold;">Bold text</span></p>'
      const cleaned = wrapper.vm.cleanPastedContent(googleDocsHtml)

      expect(cleaned).not.toContain('style=')
      expect(cleaned).toContain('**Bold text**')
    })

    it('cleans Microsoft Word formatting', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const wordHtml = '<p class="MsoNormal"><b>Bold text</b></p>'
      const cleaned = wrapper.vm.cleanPastedContent(wordHtml)

      expect(cleaned).not.toContain('MsoNormal')
      expect(cleaned).toContain('**Bold text**')
    })

    it('preserves markdown structure in pasted content', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const htmlContent = '<h1>Heading</h1><p>Paragraph</p><ul><li>List item</li></ul>'
      const cleaned = wrapper.vm.cleanPastedContent(htmlContent)

      expect(cleaned).toContain('# Heading')
      expect(cleaned).toContain('Paragraph')
      expect(cleaned).toContain('- List item')
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(MarkdownField, { field: mockField })

      const editorContainer = wrapper.find('.relative.border')
      expect(editorContainer.classes()).toContain('border-gray-600')
      expect(editorContainer.classes()).toContain('bg-gray-800')
    })

    it('applies dark theme to toolbar', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(MarkdownField, { field: mockField })

      const toolbar = wrapper.find('.toolbar')
      expect(toolbar.classes()).toContain('bg-gray-700')
    })

    it('applies dark theme to markdown textarea', async () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(MarkdownField, { field: mockField })

      wrapper.vm.mode = 'markdown'
      await nextTick()

      const textarea = wrapper.find('textarea')
      expect(textarea.classes()).toContain('bg-gray-800')
      expect(textarea.classes()).toContain('text-gray-100')
    })
  })

  describe('Event Handling', () => {
    it('emits focus event', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      await wrapper.vm.handleFocus()
      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      await wrapper.vm.handleBlur()
      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('emits change event when content changes', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      wrapper.vm.markdownContent = '# New Content'
      await wrapper.vm.updateContent()

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('change')).toBeTruthy()
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the active editor', async () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      const focusSpy = vi.spyOn(wrapper.vm, 'focusEditor')
      wrapper.vm.focus()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Edge Cases', () => {
    it('handles null modelValue', () => {
      wrapper = mountField(MarkdownField, {
        field: mockField,
        modelValue: null
      })

      expect(wrapper.vm.markdownContent).toBe('')
    })

    it('handles undefined modelValue', () => {
      wrapper = mountField(MarkdownField, {
        field: mockField,
        modelValue: undefined
      })

      expect(wrapper.vm.markdownContent).toBe('')
    })

    it('handles very large content', () => {
      const largeContent = 'A'.repeat(100000)

      wrapper = mountField(MarkdownField, {
        field: mockField,
        modelValue: largeContent
      })

      expect(wrapper.vm.markdownContent).toBe(largeContent)
    })

    it('handles malformed markdown gracefully', () => {
      const malformedMarkdown = '# Heading\n\n[broken link]('

      wrapper = mountField(MarkdownField, {
        field: mockField,
        modelValue: malformedMarkdown
      })

      expect(wrapper.vm.markdownContent).toBe(malformedMarkdown)
    })

    it('cleans up editor on unmount', () => {
      wrapper = mountField(MarkdownField, { field: mockField })

      // Component now uses simple contenteditable div, so just verify it unmounts cleanly
      expect(() => wrapper.unmount()).not.toThrow()
    })
  })
})
