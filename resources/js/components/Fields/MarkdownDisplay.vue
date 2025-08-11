<template>
  <div
    class="markdown-content prose max-w-none"
    v-html="renderedContent"
  ></div>
</template>

<script setup>
/**
 * MarkdownDisplay Component
 *
 * Renders markdown content as HTML for display purposes.
 * Used in Show pages and anywhere markdown needs to be displayed.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { marked } from 'marked'

// Props
const props = defineProps({
  content: {
    type: String,
    default: ''
  }
})

// Configure marked for better rendering
marked.setOptions({
  breaks: true,
  gfm: true,
  headerIds: false,
  mangle: false
})

// Computed
const renderedContent = computed(() => {
  if (!props.content) return ''

  try {
    return marked.parse(props.content)
  } catch (error) {
    console.warn('Error parsing markdown:', error)
    return `<p>${props.content}</p>`
  }
})
</script>

<style>
.markdown-content h1 {
    font-size: 2rem;
    font-weight: bold;
    margin: 0.5rem 0;
    line-height: 1.2;
}

.markdown-content h2 {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0.5rem 0;
    line-height: 1.3;
}

.markdown-content h3 {
    font-size: 1.25rem;
    font-weight: bold;
    margin: 0.5rem 0;
    line-height: 1.4;
}

.markdown-content h4 {
    font-size: 1.125rem;
    font-weight: bold;
    margin: 0.5rem 0;
    line-height: 1.4;
}

.markdown-content h5 {
    font-size: 1rem;
    font-weight: bold;
    margin: 0.5rem 0;
    line-height: 1.4;
}

.markdown-content h6 {
    font-size: 0.875rem;
    font-weight: bold;
    margin: 0.5rem 0;
    line-height: 1.4;
}

.markdown-content {
  color: #111827;
}

.markdown-content h1 {
  font-size: 1.875rem;
  font-weight: bold;
  margin-bottom: 1rem;
  line-height: 1.2;
}

.markdown-content h2 {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0.75rem;
  line-height: 1.3;
}

.markdown-content h3 {
  font-size: 1.25rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

.markdown-content h4 {
  font-size: 1.125rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.markdown-content h5 {
  font-size: 1rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.markdown-content h6 {
  font-size: 0.875rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.markdown-content p {
  margin-bottom: 1rem;
  line-height: 1.6;
}

.markdown-content ul, .markdown-content ol {
  margin-bottom: 1rem;
  padding-left: 1.5rem;
}

.markdown-content li {
  margin-bottom: 0.25rem;
  line-height: 1.6;
}

.markdown-content blockquote {
  border-left: 4px solid #e5e7eb;
  padding-left: 1rem;
  margin: 1rem 0;
  font-style: italic;
  color: #6b7280;
}

.markdown-content code {
  background-color: #f3f4f6;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
}

.markdown-content pre {
  background-color: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1rem;
  margin: 1rem 0;
  overflow-x: auto;
  font-size: 0.875rem;
}

.markdown-content pre code {
  background-color: transparent;
  padding: 0;
  border-radius: 0;
}

.markdown-content a {
  color: #3b82f6;
  text-decoration: underline;
}

.markdown-content a:hover {
  color: #1d4ed8;
}

.markdown-content hr {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 2rem 0;
}

.markdown-content table {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
}

.markdown-content th,
.markdown-content td {
  border: 1px solid #e5e7eb;
  padding: 0.5rem;
  text-align: left;
}

.markdown-content th {
  background-color: #f9fafb;
  font-weight: bold;
}

.markdown-content strong {
  font-weight: bold;
}

.markdown-content em {
  font-style: italic;
}
</style>
