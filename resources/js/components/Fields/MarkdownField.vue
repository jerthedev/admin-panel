<template>
  <BaseField
    :field="field"
    :model-value="modelValue"
    :errors="errors"
    :disabled="disabled"
    :readonly="readonly"
    :size="size"
    v-bind="$attrs"
  >
    <!-- BlockNote Editor Container -->
    <div
      class="relative border border-gray-200 rounded-lg shadow-sm bg-white"
      :class="{ 'markdown-editor-fullscreen': adminStore.fullscreenMode }"
    >

      <!-- WYSIWYG Toolbar (only show in rich text mode) - AT THE TOP -->
      <div v-if="mode === 'rich'" class="flex items-center justify-between p-2 bg-gray-50 border-b border-gray-200">
        <!-- Left side: Formatting buttons -->
        <div class="flex flex-wrap items-center gap-1">
          <!-- Bold -->
          <button
            type="button"
            @click="execCommand('bold')"
            :class="toolbarButtonClass"
            :disabled="disabled || readonly"
            title="Bold (Ctrl+B)"
          >
            <component :is="BoldIcon" class="w-4 h-4" />
          </button>

          <!-- Italic -->
          <button
            type="button"
            @click="execCommand('italic')"
            :class="toolbarButtonClass"
            :disabled="disabled || readonly"
            title="Italic (Ctrl+I)"
          >
            <component :is="ItalicIcon" class="w-4 h-4" />
          </button>

          <!-- Underline -->
          <button
            type="button"
            @click="execCommand('underline')"
            :class="toolbarButtonClass"
            :disabled="disabled || readonly"
            title="Underline (Ctrl+U)"
          >
            <component :is="UnderlineIcon" class="w-4 h-4" />
          </button>

          <!-- Separator -->
          <div class="w-px h-6 bg-gray-300 mx-1"></div>

          <!-- Heading 1 -->
          <button
            type="button"
            @click="execCommand('formatBlock', '<h1>')"
            :class="toolbarButtonClass"
            :disabled="disabled || readonly"
            title="Heading 1"
          >
            <span class="text-xs font-bold">H1</span>
          </button>

          <!-- Heading 2 -->
          <button
            type="button"
            @click="execCommand('formatBlock', '<h2>')"
            :class="toolbarButtonClass"
            :disabled="disabled || readonly"
            title="Heading 2"
          >
            <span class="text-xs font-bold">H2</span>
          </button>

          <!-- Heading 3 -->
          <button
            type="button"
            @click="execCommand('formatBlock', '<h3>')"
            :class="toolbarButtonClass"
            :disabled="disabled || readonly"
            title="Heading 3"
          >
            <span class="text-xs font-bold">H3</span>
          </button>

          <!-- Separator -->
          <div class="w-px h-6 bg-gray-300 mx-1"></div>

          <!-- Bullet List -->
          <button
            type="button"
            @click="execCommand('insertUnorderedList')"
            :class="toolbarButtonClass"
            :disabled="disabled || readonly"
            title="Bullet List"
          >
            <component :is="ListBulletIcon" class="w-4 h-4" />
          </button>

          <!-- Numbered List -->
          <button
            type="button"
            @click="execCommand('insertOrderedList')"
            :class="toolbarButtonClass"
            :disabled="disabled || readonly"
            title="Numbered List"
          >
            <component :is="NumberedListIcon" class="w-4 h-4" />
          </button>

          <!-- Separator -->
          <div class="w-px h-6 bg-gray-300 mx-1"></div>

          <!-- Link -->
          <button
            type="button"
            @click="insertLink"
            :class="toolbarButtonClass"
            :disabled="disabled || readonly"
            title="Insert Link"
          >
            <component :is="LinkIcon" class="w-4 h-4" />
          </button>

          <!-- Strikethrough -->
          <button
            type="button"
            @click="execCommand('strikeThrough')"
            :class="toolbarButtonClass"
            :disabled="disabled || readonly"
            title="Strikethrough"
          >
            <component :is="MinusIcon" class="w-4 h-4" />
          </button>
        </div>

        <!-- Right side: Fullscreen toggle -->
        <div class="flex items-center">
          <button
            type="button"
            @click="toggleFullscreen"
            :class="toolbarButtonClass"
            :title="adminStore.fullscreenMode ? 'Exit Fullscreen (Esc)' : 'Enter Fullscreen'"
          >
            <!-- Fullscreen Icon -->
            <svg
              class="w-4 h-4"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <template v-if="!adminStore.fullscreenMode">
                <!-- Expand Icon -->
                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
              </template>
              <template v-else>
                <!-- Collapse Icon -->
                <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/>
              </template>
            </svg>
          </button>
        </div>
      </div>

      <!-- Step 3: BlockNote Rich Text Editor -->
      <div v-show="mode === 'rich'" class="relative">
        <div
          ref="editorContainer"
          class="min-h-[200px] prose max-w-none focus:outline-none"
          :style="{ height: field.height ? `${field.height}px` : 'auto' }"
        ></div>

        <!-- STEP 5: Slash Commands Menu -->
        <div
          v-if="showSlashMenu"
          class="absolute z-50 bg-white border border-gray-200 rounded-lg shadow-lg py-1 min-w-[250px]"
          :style="{ top: slashMenuPosition.top + 'px', left: slashMenuPosition.left + 'px' }"
        >
          <div
            v-for="(command, index) in filteredSlashCommands"
            :key="command.command"
            @click="executeSlashCommand(command)"
            :class="[
              'px-3 py-2 cursor-pointer flex items-center space-x-3 hover:bg-gray-50',
              index === selectedSlashIndex ? 'bg-blue-50 border-l-2 border-blue-500' : ''
            ]"
          >
            <div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center text-xs font-bold">
              {{ command.icon }}
            </div>
            <div class="flex-1">
              <div class="font-medium text-sm">{{ command.name }}</div>
              <div class="text-xs text-gray-500">{{ command.description }}</div>
            </div>
          </div>
          <div v-if="filteredSlashCommands.length === 0" class="px-3 py-2 text-sm text-gray-500">
            No commands found
          </div>
        </div>
      </div>

      <!-- Step 3: Markdown Code Editor -->
      <div v-show="mode === 'markdown'" class="relative">
        <textarea
          ref="markdownTextarea"
          :value="markdownContent"
          @input="handleMarkdownInput"
          @focus="emit('focus')"
          @blur="emit('blur')"
          :placeholder="field.placeholder || 'Enter markdown content...'"
          :disabled="disabled"
          :readonly="readonly"
          class="w-full font-mono text-sm border-0 rounded-lg p-4 resize-none focus:outline-none"
          :style="{ height: field.height ? `${field.height}px` : '300px' }"
        ></textarea>
      </div>

      <!-- Step 3: Mode Toggle -->
      <div class="mode-toggle flex justify-between items-center mt-2">
        <div class="flex border border-gray-200 rounded-lg overflow-hidden bg-white shadow-sm">
          <button
            type="button"
            @click="setMode('rich')"
            :disabled="disabled"
            :class="[
              'px-3 py-1.5 text-xs font-medium transition-colors border-r border-gray-200 last:border-r-0',
              disabled ? 'opacity-50 cursor-not-allowed' : '',
              mode === 'rich'
                ? 'bg-blue-100 text-blue-900'
                : 'bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'
            ]"
          >
            Rich Text
          </button>
          <button
            type="button"
            @click="setMode('markdown')"
            :disabled="disabled"
            :class="[
              'px-3 py-1.5 text-xs font-medium transition-colors',
              disabled ? 'opacity-50 cursor-not-allowed' : '',
              mode === 'markdown'
                ? 'bg-blue-100 text-blue-900'
                : 'bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'
            ]"
          >
            Markdown
          </button>
        </div>
      </div>



    </div>
  </BaseField>
</template>

<style>
/* Styling for headings and content inside the contenteditable editor */
.markdown-editor-content h1 {
  font-size: 2rem;
  font-weight: bold;
  margin: 0.5rem 0;
  line-height: 1.2;
}

.markdown-editor-content h2 {
  font-size: 1.5rem;
  font-weight: bold;
  margin: 0.5rem 0;
  line-height: 1.3;
}

.markdown-editor-content h3 {
  font-size: 1.25rem;
  font-weight: bold;
  margin: 0.5rem 0;
  line-height: 1.4;
}

.markdown-editor-content h4 {
  font-size: 1.125rem;
  font-weight: bold;
  margin: 0.5rem 0;
  line-height: 1.4;
}

.markdown-editor-content h5 {
  font-size: 1rem;
  font-weight: bold;
  margin: 0.5rem 0;
  line-height: 1.4;
}

.markdown-editor-content h6 {
  font-size: 0.875rem;
  font-weight: bold;
  margin: 0.5rem 0;
  line-height: 1.4;
}

.markdown-editor-content p {
  margin: 0.5rem 0;
  line-height: 1.6;
}

.markdown-editor-content ul, .markdown-editor-content ol {
  margin: 0.5rem 0;
  padding-left: 1.5rem;
}

.markdown-editor-content li {
  margin: 0.25rem 0;
  line-height: 1.6;
}

.markdown-editor-content strong {
  font-weight: bold;
}

.markdown-editor-content em {
  font-style: italic;
}

.markdown-editor-content blockquote {
  border-left: 4px solid #e5e7eb;
  padding-left: 1rem;
  margin: 0.5rem 0;
  font-style: italic;
  color: #6b7280;
}

.markdown-editor-content code {
  background-color: #f3f4f6;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-family: monospace;
  font-size: 0.875rem;
}

.markdown-editor-content pre {
  background-color: #f3f4f6;
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin: 0.5rem 0;
}

.markdown-editor-content pre code {
  background-color: transparent;
  padding: 0;
}
</style>

<script setup>
/**
 * MarkdownField Component - BlockNote Implementation
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useAdminStore } from '@/stores/admin'
import BaseField from './BaseField.vue'

// Step 2: Add markdown conversion libraries
import TurndownService from 'turndown'
import { marked } from 'marked'

// Step 3: Simple contenteditable approach - CLEAN IMPLEMENTATION
// STEP 4: WYSIWYG Toolbar imports
import {
  BoldIcon,
  ItalicIcon,
  UnderlineIcon,
  ListBulletIcon,
  NumberedListIcon,
  LinkIcon,
  MinusIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: String,
    default: ''
  },
  errors: {
    type: Object,
    default: () => ({})
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  size: {
    type: String,
    default: 'default'
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Store
const adminStore = useAdminStore()

// Refs
const editorContainer = ref(null)
const markdownTextarea = ref(null)

// State
const isEditorReady = ref(false)
const htmlContent = ref('')
const markdownContent = ref('')
const mode = ref('rich') // 'rich' or 'markdown'

// STEP 5: Slash Commands State
const showSlashMenu = ref(false)
const slashMenuPosition = ref({ top: 0, left: 0 })
const slashQuery = ref('')
const selectedSlashIndex = ref(0)
const blockFullscreenExit = ref(false) // Block fullscreen exit when slash menu operations are active

// STEP 5: Slash Commands Data
const slashCommands = [
  { name: 'Heading 1', command: 'h1', description: 'Large heading', icon: 'H1' },
  { name: 'Heading 2', command: 'h2', description: 'Medium heading', icon: 'H2' },
  { name: 'Heading 3', command: 'h3', description: 'Small heading', icon: 'H3' },
  { name: 'Bullet List', command: 'ul', description: 'Create a bulleted list', icon: '•' },
  { name: 'Numbered List', command: 'ol', description: 'Create a numbered list', icon: '1.' },
  { name: 'Bold Text', command: 'bold', description: 'Make text bold', icon: 'B' },
  { name: 'Italic Text', command: 'italic', description: 'Make text italic', icon: 'I' },
]

// STEP 5: Filtered slash commands based on query
const filteredSlashCommands = computed(() => {
  if (!slashQuery.value) return slashCommands
  return slashCommands.filter(cmd =>
    cmd.name.toLowerCase().includes(slashQuery.value.toLowerCase()) ||
    cmd.command.toLowerCase().includes(slashQuery.value.toLowerCase())
  )
})

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

// Step 2: Markdown conversion service
const turndownService = new TurndownService({
  headingStyle: 'atx',
  codeBlockStyle: 'fenced',
  bulletListMarker: '-',
  linkStyle: 'inlined'
})

// Step 2: Markdown conversion functions
const htmlToMarkdown = (html) => {
  try {
    return turndownService.turndown(html)
  } catch (error) {
    console.warn('STEP 2: HTML to Markdown conversion failed:', error)
    return html // Fallback to original content
  }
}

const markdownToHtml = (markdown) => {
  try {
    return marked(markdown)
  } catch (error) {
    console.warn('STEP 2: Markdown to HTML conversion failed:', error)
    return `<p>${markdown}</p>` // Fallback to paragraph
  }
}

// STEP 3: Simple contenteditable editor initialization
const initializeEditor = () => {
  console.log('STEP 3: Initializing simple contenteditable editor')

  if (!editorContainer.value) {
    console.warn('Editor container not found')
    return
  }

  // Initialize markdown content
  if (props.modelValue) {
    console.log('STEP 3: Setting initial markdown content:', props.modelValue.length, 'chars')
    markdownContent.value = props.modelValue
  }

  // Create simple contenteditable editor
  let initialHtml = ''
  if (props.modelValue && props.modelValue.trim()) {
    console.log('STEP 3: Converting initial markdown to HTML')
    initialHtml = markdownToHtml(props.modelValue)
    markdownContent.value = props.modelValue
    htmlContent.value = initialHtml
  } else {
    // Empty content - let placeholder show
    initialHtml = ''
    markdownContent.value = ''
    htmlContent.value = ''
  }

  // Check if we're in rich mode to style the editor appropriately
  const isRichMode = mode.value === 'rich'
  const borderRadius = isRichMode ? '0 0 8px 8px' : '8px'
  const borderTop = isRichMode ? 'none' : '1px solid #e5e7eb'

  editorContainer.value.innerHTML = `
    <style>
      .markdown-editor-content ol, .markdown-editor-content ul {
        margin: 1em 0;
        padding-left: 2em;
        list-style-position: outside;
      }
      .markdown-editor-content ol {
        list-style-type: decimal;
      }
      .markdown-editor-content ul {
        list-style-type: disc;
      }
      .markdown-editor-content ol ol {
        list-style-type: lower-alpha;
        margin: 0.5em 0;
        padding-left: 1.5em;
      }
      .markdown-editor-content ol ol ol {
        list-style-type: lower-roman;
      }
      .markdown-editor-content ul ul {
        list-style-type: circle;
        margin: 0.5em 0;
        padding-left: 1.5em;
      }
      .markdown-editor-content ul ul ul {
        list-style-type: square;
      }
      .markdown-editor-content li {
        margin: 0.25em 0;
        display: list-item;
      }
      .markdown-editor-content li p {
        margin: 0;
        display: inline;
      }
    </style>
    <div
      contenteditable="${!props.disabled && !props.readonly ? 'true' : 'false'}"
      class="markdown-editor-content"
      style="min-height: 200px; padding: 16px; outline: none; border: 1px solid #e5e7eb; border-top: ${borderTop}; border-radius: ${borderRadius}; ${props.disabled || props.readonly ? 'background-color: #f9fafb; color: #6b7280; cursor: not-allowed;' : ''}"
      placeholder="${props.field.placeholder || 'Start typing...'}"
    >
      ${initialHtml}
    </div>
  `

  const editableDiv = editorContainer.value.querySelector('[contenteditable]')
  if (editableDiv) {
    editableDiv.addEventListener('input', handleContentChange)
    editableDiv.addEventListener('focus', () => emit('focus'))
    editableDiv.addEventListener('blur', () => emit('blur'))
    editableDiv.addEventListener('keydown', handleKeyDown)
    editableDiv.addEventListener('paste', handlePaste)

    // Store initial HTML content
    htmlContent.value = editableDiv.innerHTML
  }

  isEditorReady.value = true
  console.log('STEP 3: Simple contenteditable editor ready')
}

const handleContentChange = (event) => {
  const currentHtml = event.target.innerHTML || ''
  const textContent = event.target.textContent || ''

  console.log('STEP 3: Content changed, converting HTML to markdown')

  // Update our state
  htmlContent.value = currentHtml

  // STEP 5: Check for slash command query updates
  if (showSlashMenu.value) {
    updateSlashQuery()
  }

  // Convert HTML to markdown
  const markdown = htmlToMarkdown(currentHtml)
  markdownContent.value = markdown

  console.log('STEP 3: Conversion complete:', {
    htmlLength: currentHtml.length,
    markdownLength: markdown.length,
    textLength: textContent.length
  })

  // Only emit if we're in rich mode to avoid loops
  if (mode.value === 'rich') {
    emit('update:modelValue', markdown)
    emit('change', markdown)
  }
}

// STEP 5: Update slash query based on current cursor position
const updateSlashQuery = () => {
  const selection = window.getSelection()
  if (selection.rangeCount === 0) return

  const range = selection.getRangeAt(0)
  const textNode = range.startContainer

  if (textNode.nodeType === Node.TEXT_NODE) {
    const text = textNode.textContent
    const slashIndex = text.lastIndexOf('/', range.startOffset)

    if (slashIndex !== -1) {
      const query = text.slice(slashIndex + 1, range.startOffset)

      // Smart hiding conditions - hide menu when it's clear user isn't using a command
      const shouldHideMenu = (
        query.includes(' ') ||                    // Contains space (commands don't have spaces)
        query.length > 20 ||                      // Too long to be a reasonable command
        /[.!?,:;]/.test(query) ||                // Contains punctuation
        (query.length > 5 && filteredSlashCommands.value.length === 0) // Long query with no matches
      )

      if (shouldHideMenu) {
        console.log('STEP 5: Auto-hiding slash menu - user is typing regular text')
        // Use setTimeout to avoid interfering with current event processing
        setTimeout(() => {
          hideSlashMenu()
        }, 0)
      } else {
        slashQuery.value = query
        selectedSlashIndex.value = 0
      }
    } else {
      hideSlashMenu()
    }
  } else {
    hideSlashMenu()
  }
}

// STEP 3: Markdown textarea input handler
const handleMarkdownInput = (event) => {
  const markdown = event.target.value
  markdownContent.value = markdown

  console.log('STEP 3: Markdown input changed:', markdown.length, 'chars')

  // Convert markdown to HTML for BlockNote
  const html = markdownToHtml(markdown)
  htmlContent.value = html

  // Don't update BlockNote from markdown input - it will be updated on mode switch
  console.log('STEP 3: Markdown input - will update BlockNote on mode switch')

  // Emit markdown as the model value
  emit('update:modelValue', markdown)
  emit('change', markdown)
}

// STEP 4: WYSIWYG Toolbar functions
const toolbarButtonClass = computed(() => {
  const baseClasses = 'p-1.5 rounded transition-colors focus:outline-none'
  const disabledClasses = 'opacity-50 cursor-not-allowed'
  const enabledClasses = 'hover:bg-gray-200 focus:ring-2 focus:ring-blue-500'

  return props.disabled || props.readonly
    ? `${baseClasses} ${disabledClasses}`
    : `${baseClasses} ${enabledClasses}`
})

const execCommand = (command, value = null) => {
  const editableDiv = editorContainer.value?.querySelector('[contenteditable]')
  if (!editableDiv) return

  // Focus the editor first
  editableDiv.focus()

  // Execute the command
  document.execCommand(command, false, value)

  // Trigger content change event manually
  const event = new Event('input', { bubbles: true })
  editableDiv.dispatchEvent(event)
}

const insertLink = () => {
  const url = prompt('Enter the URL:')
  if (url) {
    execCommand('createLink', url)
  }
}

// STEP 7: Fullscreen toggle function
const toggleFullscreen = () => {
  adminStore.toggleFullscreenMode()
  console.log('STEP 7: Toggled fullscreen mode:', adminStore.fullscreenMode)
}

// STEP 7: Global keydown handler for fullscreen mode
const handleGlobalKeydown = (event) => {
  if (event.key === 'Escape' && adminStore.fullscreenMode && !blockFullscreenExit.value) {
    event.preventDefault()
    toggleFullscreen()
  }
}

// STEP 5: Slash Commands Functions
const handleKeyDown = (event) => {
  const editableDiv = editorContainer.value?.querySelector('[contenteditable]')
  if (!editableDiv) return

  if (showSlashMenu.value) {
    // Handle slash menu navigation
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      selectedSlashIndex.value = Math.min(selectedSlashIndex.value + 1, filteredSlashCommands.value.length - 1)
    } else if (event.key === 'ArrowUp') {
      event.preventDefault()
      selectedSlashIndex.value = Math.max(selectedSlashIndex.value - 1, 0)
    } else if (event.key === 'Enter') {
      event.preventDefault()
      const selectedCommand = filteredSlashCommands.value[selectedSlashIndex.value]
      if (selectedCommand) {
        executeSlashCommand(selectedCommand)
      }
    } else if (event.key === 'Escape') {
      event.preventDefault()
      // Priority: Close slash menu first, then fullscreen
      if (showSlashMenu.value) {
        event.stopPropagation() // Prevent global handler from firing
        hideSlashMenu()
      } else if (adminStore.fullscreenMode) {
        event.stopPropagation() // Prevent global handler from firing
        toggleFullscreen()
      }
    }
    return
  }

  // Check for slash command trigger
  if (event.key === '/') {
    const selection = window.getSelection()
    if (selection.rangeCount > 0) {
      const range = selection.getRangeAt(0)
      const textBefore = range.startContainer.textContent?.slice(0, range.startOffset) || ''

      // Show slash menu if at start of line, after whitespace, or after any character (more flexible)
      // This allows slash commands anywhere in the text
      setTimeout(() => showSlashMenuAtCursor(), 10)
      console.log('STEP 5: Slash detected, showing menu')
    }
  }
}

const showSlashMenuAtCursor = () => {
  const selection = window.getSelection()
  if (selection.rangeCount === 0) return

  const range = selection.getRangeAt(0)
  const rect = range.getBoundingClientRect()
  const editorRect = editorContainer.value.getBoundingClientRect()

  slashMenuPosition.value = {
    top: rect.bottom - editorRect.top + 5,
    left: rect.left - editorRect.left
  }

  showSlashMenu.value = true
  slashQuery.value = ''
  selectedSlashIndex.value = 0
  blockFullscreenExit.value = true // Block fullscreen exit while slash menu is open
}

const hideSlashMenu = () => {
  showSlashMenu.value = false
  slashQuery.value = ''
  selectedSlashIndex.value = 0
  blockFullscreenExit.value = false // Allow fullscreen exit when slash menu is closed
}

const executeSlashCommand = (command) => {
  const editableDiv = editorContainer.value?.querySelector('[contenteditable]')
  if (!editableDiv) return

  // Remove the slash and any query text
  const selection = window.getSelection()
  if (selection.rangeCount > 0) {
    const range = selection.getRangeAt(0)
    const textNode = range.startContainer

    if (textNode.nodeType === Node.TEXT_NODE) {
      const text = textNode.textContent
      const slashIndex = text.lastIndexOf('/', range.startOffset)

      if (slashIndex !== -1) {
        // Remove the slash and query
        const newRange = document.createRange()
        newRange.setStart(textNode, slashIndex)
        newRange.setEnd(textNode, range.startOffset)
        newRange.deleteContents()

        // Execute the command
        editableDiv.focus()

        switch (command.command) {
          case 'h1':
            execCommand('formatBlock', '<h1>')
            break
          case 'h2':
            execCommand('formatBlock', '<h2>')
            break
          case 'h3':
            execCommand('formatBlock', '<h3>')
            break
          case 'ul':
            execCommand('insertUnorderedList')
            break
          case 'ol':
            execCommand('insertOrderedList')
            break
          case 'bold':
            execCommand('bold')
            break
          case 'italic':
            execCommand('italic')
            break
        }
      }
    }
  }

  hideSlashMenu()
}

// STEP 6: Copy-Paste Enhancement Functions
const handlePaste = (event) => {
  event.preventDefault()
  console.log('STEP 6: Paste event detected')

  const clipboardData = event.clipboardData || window.clipboardData
  if (!clipboardData) return

  // Get HTML content if available, otherwise fall back to plain text
  let htmlContent = clipboardData.getData('text/html')
  const plainText = clipboardData.getData('text/plain')

  console.log('STEP 6: Clipboard data:', {
    hasHtml: !!htmlContent,
    hasText: !!plainText,
    htmlLength: htmlContent?.length || 0,
    textLength: plainText?.length || 0
  })

  if (htmlContent) {
    // Sanitize and clean the HTML content
    const cleanedHtml = sanitizeHtmlContent(htmlContent)
    insertHtmlAtCursor(cleanedHtml)
  } else if (plainText) {
    // Insert plain text
    insertTextAtCursor(plainText)
  }
}

const sanitizeHtmlContent = (html) => {
  console.log('STEP 6: Sanitizing HTML content')

  // Create a temporary div to parse the HTML
  const tempDiv = document.createElement('div')
  tempDiv.innerHTML = html

  // Define allowed tags and attributes
  const allowedTags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'a', 'ul', 'ol', 'li', 'blockquote', 'code', 'pre']
  const allowedAttributes = {
    'a': ['href', 'title'],
    'img': ['src', 'alt', 'title'],
    '*': ['class'] // Allow class on any element for basic styling
  }

  // Recursively clean the HTML
  const cleanElement = (element) => {
    if (element.nodeType === Node.TEXT_NODE) {
      return document.createTextNode(element.textContent)
    }

    if (element.nodeType !== Node.ELEMENT_NODE) {
      return null
    }

    const tagName = element.tagName.toLowerCase()

    // If tag is not allowed, return its text content as a text node
    if (!allowedTags.includes(tagName)) {
      return document.createTextNode(element.textContent)
    }

    // Clean attributes
    const allowedAttrs = allowedAttributes[tagName] || allowedAttributes['*'] || []
    const cleanedElement = document.createElement(tagName)

    // Copy allowed attributes
    for (const attr of element.attributes) {
      if (allowedAttrs.includes(attr.name)) {
        cleanedElement.setAttribute(attr.name, attr.value)
      }
    }

    // Recursively clean children
    for (const child of element.childNodes) {
      const cleanedChild = cleanElement(child)
      if (cleanedChild) {
        cleanedElement.appendChild(cleanedChild)
      }
    }

    return cleanedElement
  }

  // Clean all child nodes
  const cleanedDiv = document.createElement('div')
  for (const child of tempDiv.childNodes) {
    const cleanedChild = cleanElement(child)
    if (cleanedChild) {
      cleanedDiv.appendChild(cleanedChild)
    }
  }

  const result = cleanedDiv.innerHTML
  console.log('STEP 6: HTML sanitized:', {
    originalLength: html.length,
    cleanedLength: result.length
  })

  return result
}

const insertHtmlAtCursor = (html) => {
  const selection = window.getSelection()
  if (selection.rangeCount === 0) return

  const range = selection.getRangeAt(0)
  range.deleteContents()

  // Create a temporary div to hold the HTML
  const tempDiv = document.createElement('div')
  tempDiv.innerHTML = html

  // Insert each child node
  const fragment = document.createDocumentFragment()
  while (tempDiv.firstChild) {
    fragment.appendChild(tempDiv.firstChild)
  }

  range.insertNode(fragment)

  // Move cursor to end of inserted content
  range.collapse(false)
  selection.removeAllRanges()
  selection.addRange(range)

  // Trigger content change
  const editableDiv = editorContainer.value?.querySelector('[contenteditable]')
  if (editableDiv) {
    const event = new Event('input', { bubbles: true })
    editableDiv.dispatchEvent(event)
  }
}

const insertTextAtCursor = (text) => {
  const selection = window.getSelection()
  if (selection.rangeCount === 0) return

  const range = selection.getRangeAt(0)
  range.deleteContents()

  const textNode = document.createTextNode(text)
  range.insertNode(textNode)

  // Move cursor to end of inserted text
  range.setStartAfter(textNode)
  range.collapse(true)
  selection.removeAllRanges()
  selection.addRange(range)

  // Trigger content change
  const editableDiv = editorContainer.value?.querySelector('[contenteditable]')
  if (editableDiv) {
    const event = new Event('input', { bubbles: true })
    editableDiv.dispatchEvent(event)
  }
}

// STEP 3: Mode toggle functions - SIMPLE APPROACH
const setMode = (newMode) => {
  console.log('STEP 3: Switching mode from', mode.value, 'to', newMode)
  const oldMode = mode.value
  mode.value = newMode

  if (newMode === 'markdown') {
    // Switching to markdown mode - just focus the textarea
    nextTick(() => {
      if (markdownTextarea.value) {
        markdownTextarea.value.focus()
      }
    })
  } else if (newMode === 'rich' && oldMode === 'markdown') {
    // Switching from markdown to rich mode - update contenteditable with new content
    nextTick(() => {
      updateRichEditor()
    })
  }
}

// STEP 3: Update rich editor with current markdown content
const updateRichEditor = () => {
  if (!editorContainer.value) return

  try {
    console.log('STEP 3: Updating rich editor with current markdown content')

    const editableDiv = editorContainer.value.querySelector('[contenteditable]')
    if (editableDiv && markdownContent.value) {
      // Convert markdown to HTML and update the contenteditable
      const html = markdownToHtml(markdownContent.value)
      editableDiv.innerHTML = html
      htmlContent.value = html

      console.log('STEP 3: Rich editor updated successfully')
    }

  } catch (error) {
    console.error('STEP 3: Error updating rich editor:', error)
  }
}



// Lifecycle hooks
onMounted(() => {
  console.log('STEP 3: Component mounted, initializing simple editor')
  nextTick(() => {
    initializeEditor()
  })

  // Add global keydown listener for fullscreen mode
  document.addEventListener('keydown', handleGlobalKeydown)
})

onBeforeUnmount(() => {
  console.log('STEP 3: Component unmounting, cleaning up')

  // Clean up global event listener
  document.removeEventListener('keydown', handleGlobalKeydown)

  // Clean up event listeners
  if (editorContainer.value) {
    const editableDiv = editorContainer.value.querySelector('[contenteditable]')
    if (editableDiv) {
      editableDiv.removeEventListener('input', handleContentChange)
      editableDiv.removeEventListener('focus', () => emit('focus'))
      editableDiv.removeEventListener('blur', () => emit('blur'))
      editableDiv.removeEventListener('keydown', handleKeyDown)
      editableDiv.removeEventListener('paste', handlePaste)
    }
  }
})

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  console.log('STEP 3: ModelValue changed externally:', newValue?.length || 0, 'characters')

  // Always update markdownContent to match modelValue
  markdownContent.value = newValue || ''

  if (isEditorReady.value) {
    // Update markdown textarea if in markdown mode
    if (mode.value === 'markdown' && markdownTextarea.value) {
      markdownTextarea.value.value = newValue || ''
    }

    // Update rich text editor if in rich mode
    if (mode.value === 'rich') {
      updateRichEditor()
    }

    console.log('STEP 3: Updated editor with external changes')
  }
}, { immediate: true })

// Expose methods for testing
const focus = () => {
  if (mode.value === 'markdown' && markdownTextarea.value) {
    markdownTextarea.value.focus()
  } else if (mode.value === 'rich' && editorContainer.value) {
    const editableDiv = editorContainer.value.querySelector('[contenteditable]')
    if (editableDiv) {
      editableDiv.focus()
    }
  }
}

const destroyEditor = () => {
  // This method exists for test compatibility
  // The actual cleanup is handled in onBeforeUnmount
  console.log('STEP 3: destroyEditor called (test compatibility)')
}

defineExpose({
  focus,
  destroyEditor,
  markdownContent,
  htmlContent,
  mode
})
</script>

<style scoped>
@import '../../../css/admin.css' reference;

/* STEP 1: Basic styling for the placeholder editor */
[contenteditable] {
  outline: none;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
  min-height: 200px;
  font-family: inherit;
  line-height: 1.6;
}

[contenteditable]:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

[contenteditable]:empty::before {
  content: attr(placeholder);
  color: #9ca3af;
  pointer-events: none;
}

/* STEP 7: Fullscreen mode styles */
.markdown-editor-fullscreen {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  z-index: 99999 !important;
  background: white !important;
  border: none !important;
  border-radius: 0 !important;
  box-shadow: none !important;
  display: flex !important;
  flex-direction: column !important;
  max-width: none !important;
  margin: 0 !important;
  padding: 1rem !important;
}

/* Toolbar stays at top */
.markdown-editor-fullscreen .bg-gray-50 {
  flex-shrink: 0 !important;
}

/* Editor content area expands to fill available space */
.markdown-editor-fullscreen .markdown-editor-content {
  flex: 1 !important;
  overflow: hidden !important;
  font-size: 1.1rem !important;
  line-height: 1.6 !important;
  min-height: 0 !important;
  height: 100% !important;
  display: flex !important;
  flex-direction: column !important;
  padding: 0 !important;
  margin: 0 !important;
}

/* Make the rich text editor container fill the content area when in rich mode */
.markdown-editor-fullscreen .markdown-editor-content .relative {
  flex: 1 !important;
  display: flex !important;
  flex-direction: column !important;
}

/* Override the editorContainer height constraints for rich text editor */
.markdown-editor-fullscreen .markdown-editor-content [ref="editorContainer"] {
  flex: 1 !important;
  min-height: 100% !important;
  height: 100% !important;
  max-height: none !important;
  display: flex !important;
  flex-direction: column !important;
}

/* Make the contenteditable div fill the editor container */
.markdown-editor-fullscreen .markdown-editor-content [contenteditable] {
  flex: 1 !important;
  min-height: 100% !important;
  height: 100% !important;
  max-height: none !important;
  padding: 1rem !important;
  outline: none !important;
  border: 1px solid #d1d5db !important;
  border-radius: 0.375rem !important;
  resize: none !important;
}

/* Make the markdown textarea fill the content area when in markdown mode */
.markdown-editor-fullscreen [ref="markdownTextarea"] {
  flex: 1 !important;
  min-height: 100% !important;
  height: 100% !important;
  max-height: none !important;
  resize: none !important;
}

/* Mode toggle stays at bottom */
.markdown-editor-fullscreen .mode-toggle {
  flex-shrink: 0 !important;
  margin-top: auto !important;
}


</style>
