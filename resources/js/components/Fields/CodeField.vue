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
    <!-- Readonly display -->
    <div v-if="readonly" class="space-y-2">
      <div class="bg-gray-50 border border-gray-200 rounded-md p-4" :class="{ 'bg-gray-800 border-gray-600': isDarkTheme }">
        <pre class="text-sm text-gray-900 whitespace-pre-wrap font-mono" :class="{ 'text-gray-100': isDarkTheme }">{{ displayValue }}</pre>
      </div>
    </div>

    <!-- Editable code editor -->
    <div v-else class="space-y-2">
      <div class="relative">
        <textarea
          :id="fieldId"
          ref="textareaRef"
          :value="currentValue"
          :disabled="disabled"
          :placeholder="placeholder"
          class="w-full min-h-[200px] p-3 border border-gray-300 rounded-md font-mono text-sm resize-y focus:ring-blue-500 focus:border-blue-500"
          :class="[
            {
              'bg-gray-50 cursor-not-allowed': disabled,
              'border-gray-600 bg-gray-700 text-white placeholder-gray-400': isDarkTheme,
              'border-red-300 focus:border-red-500 focus:ring-red-500': hasErrors
            }
          ]"
          @input="handleInput"
          @focus="handleFocus"
          @blur="handleBlur"
        />

        <!-- Language indicator -->
        <div
          v-if="language && language !== 'htmlmixed'"
          class="absolute top-2 right-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded"
          :class="{ 'bg-gray-600 text-gray-300': isDarkTheme }"
        >
          {{ languageLabel }}
        </div>

        <!-- JSON indicator -->
        <div
          v-if="isJson"
          class="absolute top-2 left-2 px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded"
          :class="{ 'bg-blue-900 text-blue-300': isDarkTheme }"
        >
          JSON
        </div>
      </div>

      <!-- Required indicator -->
      <div v-if="isRequired" class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
        <span class="text-red-500">*</span> Required
      </div>

      <!-- JSON validation hint -->
      <div v-if="isJson" class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
        <span class="text-blue-500">💡</span> Enter valid JSON format
      </div>
    </div>
  </BaseField>
</template>

<script>
import BaseField from './BaseField.vue'
import { useAdminStore } from '@/stores/admin'
import { computed, ref, nextTick } from 'vue'

export default {
  name: 'CodeField',

  components: {
    BaseField,
  },

  props: {
    field: {
      type: Object,
      required: true,
    },
    modelValue: {
      type: [String, Object, Array, null],
      default: '',
    },
    errors: {
      type: Array,
      default: () => [],
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    readonly: {
      type: Boolean,
      default: false,
    },
    size: {
      type: String,
      default: 'default',
    },
  },

  emits: ['update:modelValue', 'change', 'focus', 'blur'],

  setup(props, { emit }) {
    const adminStore = useAdminStore()
    const textareaRef = ref(null)

    // Generate unique field ID
    const fieldId = computed(() => {
      return `code-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
    })

    // Check if field is required
    const isRequired = computed(() => {
      return props.field.rules && props.field.rules.includes('required')
    })

    // Check if field has errors
    const hasErrors = computed(() => {
      return props.errors && props.errors.length > 0
    })

    // Dark theme detection
    const isDarkTheme = computed(() => {
      return adminStore.isDarkTheme
    })

    // Get JSON flag from field configuration
    const isJson = computed(() => {
      return props.field.isJson || false
    })

    // Get language from field configuration (JSON overrides to javascript)
    const language = computed(() => {
      if (isJson.value) {
        return 'javascript' // JSON always uses JavaScript highlighting
      }
      return props.field.language || 'htmlmixed'
    })

    // Get language label for display
    const languageLabel = computed(() => {
      const lang = language.value
      const labels = {
        'dockerfile': 'Dockerfile',
        'htmlmixed': 'HTML',
        'javascript': 'JavaScript',
        'markdown': 'Markdown',
        'nginx': 'Nginx',
        'php': 'PHP',
        'ruby': 'Ruby',
        'sass': 'Sass',
        'shell': 'Shell',
        'sql': 'SQL',
        'twig': 'Twig',
        'vim': 'Vim',
        'vue': 'Vue',
        'xml': 'XML',
        'yaml-frontmatter': 'YAML',
        'yaml': 'YAML',
      }
      return labels[lang] || lang.toUpperCase()
    })

    // Get placeholder text
    const placeholder = computed(() => {
      if (isJson.value) {
        return 'Enter valid JSON...'
      }
      return `Enter ${languageLabel.value} code...`
    })

    // Get current value as string
    const currentValue = computed(() => {
      if (props.modelValue === null || props.modelValue === undefined) {
        return ''
      }

      if (isJson.value && typeof props.modelValue === 'object') {
        try {
          return JSON.stringify(props.modelValue, null, 2)
        } catch (e) {
          return String(props.modelValue)
        }
      }

      return String(props.modelValue)
    })

    // Get display value for readonly mode
    const displayValue = computed(() => {
      if (!currentValue.value) {
        return 'No content'
      }

      if (isJson.value) {
        try {
          // Try to format JSON nicely
          const parsed = JSON.parse(currentValue.value)
          return JSON.stringify(parsed, null, 2)
        } catch (e) {
          return currentValue.value
        }
      }

      return currentValue.value
    })

    // Handle input changes
    const handleInput = (event) => {
      if (props.disabled || props.readonly) return

      let value = event.target.value

      // For JSON fields, try to parse and emit as object if valid
      if (isJson.value && value.trim()) {
        try {
          const parsed = JSON.parse(value)
          emit('update:modelValue', parsed)
          emit('change', parsed)
          return
        } catch (e) {
          // If invalid JSON, emit as string
        }
      }

      emit('update:modelValue', value)
      emit('change', value)
    }

    // Handle focus
    const handleFocus = (event) => {
      emit('focus', event)
    }

    // Handle blur
    const handleBlur = (event) => {
      emit('blur', event)
    }

    // Focus method for external access
    const focus = async () => {
      await nextTick()
      if (textareaRef.value) {
        textareaRef.value.focus()
      }
    }

    // Blur method for external access
    const blur = () => {
      if (textareaRef.value) {
        textareaRef.value.blur()
      }
    }

    return {
      textareaRef,
      fieldId,
      isRequired,
      hasErrors,
      isDarkTheme,
      language,
      isJson,
      languageLabel,
      placeholder,
      currentValue,
      displayValue,
      handleInput,
      handleFocus,
      handleBlur,
      focus,
      blur,
    }
  },
}
</script>

<style scoped>
/* Additional code field specific styles if needed */
.code-field {
  /* Custom styles */
}

/* Ensure proper textarea styling */
textarea {
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  line-height: 1.5;
}

/* Focus styles */
textarea:focus {
  outline: none;
}

/* Scrollbar styling for dark mode */
.dark textarea::-webkit-scrollbar {
  width: 8px;
}

.dark textarea::-webkit-scrollbar-track {
  background: #374151;
}

.dark textarea::-webkit-scrollbar-thumb {
  background: #6b7280;
  border-radius: 4px;
}

.dark textarea::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}
</style>
