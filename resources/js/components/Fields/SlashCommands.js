/**
 * Slash Commands Extension for Tiptap
 * 
 * Provides Notion-style slash commands for quick content insertion.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { Extension } from '@tiptap/core'
import Suggestion from '@tiptap/suggestion'

export const SlashCommands = Extension.create({
  name: 'slashCommands',

  addOptions() {
    return {
      suggestion: {
        char: '/',
        command: ({ editor, range, props }) => {
          props.command({ editor, range })
        },
      },
    }
  },

  addProseMirrorPlugins() {
    return [
      Suggestion({
        editor: this.editor,
        ...this.options.suggestion,
      }),
    ]
  },
})

// Slash command items
export const slashCommandItems = [
  {
    title: 'Heading 1',
    description: 'Large heading',
    searchTerms: ['h1', 'heading', 'title'],
    command: ({ editor, range }) => {
      editor
        .chain()
        .focus()
        .deleteRange(range)
        .setNode('heading', { level: 1 })
        .run()
    },
  },
  {
    title: 'Heading 2',
    description: 'Medium heading',
    searchTerms: ['h2', 'heading', 'subtitle'],
    command: ({ editor, range }) => {
      editor
        .chain()
        .focus()
        .deleteRange(range)
        .setNode('heading', { level: 2 })
        .run()
    },
  },
  {
    title: 'Heading 3',
    description: 'Small heading',
    searchTerms: ['h3', 'heading'],
    command: ({ editor, range }) => {
      editor
        .chain()
        .focus()
        .deleteRange(range)
        .setNode('heading', { level: 3 })
        .run()
    },
  },
  {
    title: 'Bullet List',
    description: 'Create a bullet list',
    searchTerms: ['ul', 'list', 'bullet'],
    command: ({ editor, range }) => {
      editor
        .chain()
        .focus()
        .deleteRange(range)
        .toggleBulletList()
        .run()
    },
  },
  {
    title: 'Numbered List',
    description: 'Create a numbered list',
    searchTerms: ['ol', 'list', 'numbered', 'ordered'],
    command: ({ editor, range }) => {
      editor
        .chain()
        .focus()
        .deleteRange(range)
        .toggleOrderedList()
        .run()
    },
  },
  {
    title: 'Task List',
    description: 'Create a task list with checkboxes',
    searchTerms: ['todo', 'task', 'checkbox', 'check'],
    command: ({ editor, range }) => {
      editor
        .chain()
        .focus()
        .deleteRange(range)
        .toggleTaskList()
        .run()
    },
  },
  {
    title: 'Quote',
    description: 'Create a blockquote',
    searchTerms: ['quote', 'blockquote', 'citation'],
    command: ({ editor, range }) => {
      editor
        .chain()
        .focus()
        .deleteRange(range)
        .toggleBlockquote()
        .run()
    },
  },
  {
    title: 'Code Block',
    description: 'Create a code block',
    searchTerms: ['code', 'codeblock', 'pre'],
    command: ({ editor, range }) => {
      editor
        .chain()
        .focus()
        .deleteRange(range)
        .toggleCodeBlock()
        .run()
    },
  },
  {
    title: 'Horizontal Rule',
    description: 'Insert a horizontal divider',
    searchTerms: ['hr', 'rule', 'divider', 'separator'],
    command: ({ editor, range }) => {
      editor
        .chain()
        .focus()
        .deleteRange(range)
        .setHorizontalRule()
        .run()
    },
  },
]

// Filter function for slash commands
export const filterSlashCommands = (query) => {
  return slashCommandItems.filter(item => {
    const searchText = query.toLowerCase()
    return (
      item.title.toLowerCase().includes(searchText) ||
      item.description.toLowerCase().includes(searchText) ||
      item.searchTerms.some(term => term.toLowerCase().includes(searchText))
    )
  })
}
