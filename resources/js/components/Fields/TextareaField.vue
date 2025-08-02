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
    <div class="relative">
      <textarea
        :id="fieldId"
        ref="textareaRef"
        :value="modelValue"
        :placeholder="field.placeholder || field.name"
        :rows="field.rows || 4"
        :maxlength="field.maxLength"
        :disabled="disabled"
        :readonly="readonly"
        class="admin-textarea w-full"
        :class="{ 'admin-input-dark': isDarkTheme }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
      ></textarea>

      <!-- Character count -->
      <div
        v-if="showCharacterCount"
        class="absolute bottom-2 right-2 text-xs pointer-events-none"
        :class="characterCountClasses"
      >
        {{ characterCount }}{{ field.maxLength ? `/${field.maxLength}` : '' }}
      </div>
    </div>

    <!-- Character count (external) -->
    <div
      v-if="showCharacterCount && !field.maxLength"
      class="mt-1 text-right text-xs text-gray-500"
      :class="{ 'text-gray-400': isDarkTheme }"
    >
      {{ characterCount }} characters
    </div>
  </BaseField>
</template>

<script setup>
/**
 * TextareaField Component
 * 
 * Textarea input field with support for character limits, auto-resize,
 * and character count display.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, nextTick, onMounted, watch } from 'vue'
import { useAdminStore } from '@/stores/admin'
import BaseField from './BaseField.vue'

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

// Refs
const textareaRef = ref(null)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `textarea-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const showCharacterCount = computed(() => {
  return props.field.showCharacterCount || props.field.maxLength
})

const characterCount = computed(() => {
  return String(props.modelValue || '').length
})

const characterCountClasses = computed(() => {
  const count = characterCount.value
  const max = props.field.maxLength
  
  if (!max) {
    return isDarkTheme.value ? 'text-gray-400' : 'text-gray-500'
  }
  
  if (count > max * 0.9) {
    return isDarkTheme.value ? 'text-red-400' : 'text-red-500'
  } else if (count > max * 0.7) {
    return isDarkTheme.value ? 'text-amber-400' : 'text-amber-500'
  }
  return isDarkTheme.value ? 'text-gray-400' : 'text-gray-500'
})

// Methods
const handleInput = (event) => {
  let value = event.target.value
  
  // Apply maxLength if specified
  if (props.field.maxLength && value.length > props.field.maxLength) {
    value = value.substring(0, props.field.maxLength)
    event.target.value = value
  }
  
  // Auto-resize if enabled
  if (props.field.autoResize) {
    autoResize()
  }
  
  emit('update:modelValue', value)
  emit('change', value)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const handleKeydown = (event) => {
  // Handle tab key for indentation
  if (event.key === 'Tab' && !event.shiftKey) {
    event.preventDefault()
    const start = event.target.selectionStart
    const end = event.target.selectionEnd
    const value = event.target.value
    
    const newValue = value.substring(0, start) + '  ' + value.substring(end)
    event.target.value = newValue
    event.target.selectionStart = event.target.selectionEnd = start + 2
    
    emit('update:modelValue', newValue)
    emit('change', newValue)
  }
}

const autoResize = () => {
  if (!textareaRef.value || !props.field.autoResize) return
  
  nextTick(() => {
    const textarea = textareaRef.value
    textarea.style.height = 'auto'
    textarea.style.height = textarea.scrollHeight + 'px'
  })
}

// Focus method for external use
const focus = () => {
  textareaRef.value?.focus()
}

// Watch for value changes to trigger auto-resize
watch(() => props.modelValue, () => {
  if (props.field.autoResize) {
    autoResize()
  }
})

// Initialize auto-resize on mount
onMounted(() => {
  if (props.field.autoResize) {
    autoResize()
  }
})

defineExpose({
  focus
})
</script>

<style scoped>
/* Auto-resize textarea */
textarea.auto-resize {
  resize: none;
  overflow: hidden;
}

/* Character count positioning */
.relative textarea {
  padding-bottom: 2rem;
}

.relative textarea + .absolute {
  background: rgba(255, 255, 255, 0.9);
  border-radius: 0.25rem;
  padding: 0.125rem 0.25rem;
}

.dark .relative textarea + .absolute {
  background: rgba(31, 41, 55, 0.9);
}
</style>
