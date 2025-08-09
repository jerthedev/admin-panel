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
      <!-- Display mode for index/detail views -->
      <div
        v-if="isDisplayMode"
        class="flex items-center space-x-2"
      >
        <span
          class="text-sm font-mono text-gray-600"
          :class="{ 'text-gray-400': isDarkTheme }"
        >
          {{ displayValue }}
        </span>
        
        <!-- Copy button -->
        <button
          v-if="field.copyable && modelValue"
          type="button"
          class="inline-flex items-center p-1 text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition-colors duration-200"
          :class="{ 'hover:text-gray-300 focus:text-gray-300': isDarkTheme }"
          :title="copyButtonTitle"
          @click="copyToClipboard"
        >
          <ClipboardDocumentIcon
            v-if="!copied"
            class="h-4 w-4"
          />
          <CheckIcon
            v-else
            class="h-4 w-4 text-green-500"
          />
        </button>
      </div>

      <!-- Input mode for forms (when explicitly shown on creation/update) -->
      <input
        v-else
        :id="fieldId"
        ref="inputRef"
        type="text"
        :value="modelValue"
        :placeholder="field.placeholder || field.name"
        :disabled="disabled"
        :readonly="readonly || isReadonlyByDefault"
        class="admin-input w-full font-mono text-sm"
        :class="[
          { 'admin-input-dark': isDarkTheme },
          { 'bg-gray-50 cursor-not-allowed': isReadonlyByDefault },
          { 'bg-gray-800 cursor-not-allowed': isDarkTheme && isReadonlyByDefault }
        ]"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
      />
    </div>
  </BaseField>
</template>

<script setup>
/**
 * IDField Component
 * 
 * ID field for displaying primary keys and other ID values.
 * Supports copyable functionality and is typically readonly on creation forms.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { ClipboardDocumentIcon, CheckIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, Number],
    default: null
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
  },
  mode: {
    type: String,
    default: 'form', // 'form', 'index', 'detail'
    validator: (value) => ['form', 'index', 'detail'].includes(value)
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Refs
const inputRef = ref(null)
const copied = ref(false)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `id-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const isDisplayMode = computed(() => {
  return props.mode === 'index' || props.mode === 'detail' || props.readonly
})

const isReadonlyByDefault = computed(() => {
  // ID fields are typically readonly on creation forms
  return props.mode === 'form' && !props.field.showOnCreation
})

const displayValue = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined) {
    return '—'
  }
  return String(props.modelValue)
})

const copyButtonTitle = computed(() => {
  return copied.value ? 'Copied!' : 'Copy to clipboard'
})

// Methods
const handleInput = (event) => {
  const value = event.target.value
  emit('update:modelValue', value)
  emit('change', value)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const copyToClipboard = async () => {
  if (!props.modelValue) return
  
  try {
    await navigator.clipboard.writeText(String(props.modelValue))
    copied.value = true
    
    // Reset copied state after 2 seconds
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy to clipboard:', err)
    
    // Fallback for older browsers
    try {
      const textArea = document.createElement('textarea')
      textArea.value = String(props.modelValue)
      document.body.appendChild(textArea)
      textArea.select()
      document.execCommand('copy')
      document.body.removeChild(textArea)
      
      copied.value = true
      setTimeout(() => {
        copied.value = false
      }, 2000)
    } catch (fallbackErr) {
      console.error('Fallback copy failed:', fallbackErr)
    }
  }
}

// Focus method for external use
const focus = () => {
  inputRef.value?.focus()
}

defineExpose({
  focus
})
</script>

<style scoped>
/* Ensure proper spacing and alignment */
.font-mono {
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
}

/* Transition for copy button */
.transition-colors {
  transition-property: color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Readonly styling */
.cursor-not-allowed {
  cursor: not-allowed;
}
</style>
