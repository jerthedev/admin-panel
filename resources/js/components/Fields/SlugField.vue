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
      <input
        :id="fieldId"
        ref="inputRef"
        type="text"
        :value="modelValue"
        :placeholder="field.placeholder || 'Enter slug...'"
        :maxlength="field.maxLength"
        :disabled="disabled"
        :readonly="readonly"
        class="admin-input w-full pr-20"
        :class="[
          { 'admin-input-dark': isDarkTheme },
          { 'border-red-300': hasError },
          { 'border-green-300': isValid && modelValue }
        ]"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
      />

      <!-- Generate button -->
      <button
        v-if="field.fromAttribute && !readonly && !disabled"
        type="button"
        class="absolute inset-y-0 right-0 px-3 flex items-center text-sm font-medium text-blue-600 hover:text-blue-700 focus:outline-none focus:text-blue-700"
        :class="{ 'text-blue-400 hover:text-blue-300 focus:text-blue-300': isDarkTheme }"
        @click="generateSlug"
      >
        Generate
      </button>
    </div>

    <!-- Character count and validation info -->
    <div
      v-if="showMetaInfo"
      class="mt-1 flex items-center justify-between text-xs"
    >
      <!-- Character count -->
      <div
        v-if="field.maxLength"
        class="text-gray-500"
        :class="[
          { 'text-gray-400': isDarkTheme },
          { 'text-red-500': isOverLimit },
          { 'text-red-400': isOverLimit && isDarkTheme }
        ]"
      >
        {{ characterCount }}/{{ field.maxLength }} characters
      </div>

      <!-- Slug preview -->
      <div
        v-if="modelValue && isValid"
        class="text-gray-500 font-mono text-xs"
        :class="{ 'text-gray-400': isDarkTheme }"
      >
        Preview: {{ slugPreview }}
      </div>
    </div>

    <!-- Validation messages -->
    <div
      v-if="validationMessage"
      class="mt-1 text-xs"
      :class="validationMessageClass"
    >
      {{ validationMessage }}
    </div>
  </BaseField>
</template>

<script setup>
/**
 * SlugField Component
 * 
 * URL-friendly slug input field with auto-generation from other fields.
 * Supports customizable separators, length limits, and real-time validation.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, watch } from 'vue'
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
  },
  formData: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Refs
const inputRef = ref(null)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `slug-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const hasError = computed(() => {
  return props.errors && Object.keys(props.errors).length > 0
})

const characterCount = computed(() => {
  return String(props.modelValue || '').length
})

const isOverLimit = computed(() => {
  return props.field.maxLength && characterCount.value > props.field.maxLength
})

const isValid = computed(() => {
  if (isOverLimit.value) return false
  if (!props.modelValue) return false
  
  // Basic slug validation
  const slugPattern = /^[a-z0-9]+(?:[_-][a-z0-9]+)*$/i
  return slugPattern.test(props.modelValue)
})

const showMetaInfo = computed(() => {
  return props.field.maxLength || (props.modelValue && isValid.value)
})

const slugPreview = computed(() => {
  if (!props.modelValue) return ''
  
  // Show how the slug would appear in a URL
  return `/${props.modelValue}`
})

const validationMessage = computed(() => {
  if (!props.modelValue) return ''
  
  if (isOverLimit.value) {
    return `Slug is too long (maximum ${props.field.maxLength} characters)`
  }
  
  if (!isValid.value) {
    return 'Slug can only contain letters, numbers, hyphens, and underscores'
  }
  
  return ''
})

const validationMessageClass = computed(() => {
  const baseClasses = 'text-red-500'
  return isDarkTheme.value ? `${baseClasses} text-red-400` : baseClasses
})

// Methods
const generateSlug = () => {
  if (!props.field.fromAttribute || !props.formData) return
  
  const sourceValue = props.formData[props.field.fromAttribute]
  if (!sourceValue) return
  
  const slug = createSlug(sourceValue)
  emit('update:modelValue', slug)
  emit('change', slug)
}

const createSlug = (text) => {
  if (!text) return ''
  
  let slug = String(text)
    .toLowerCase()
    .trim()
    // Replace spaces and special characters with separator
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s_-]+/g, props.field.separator || '-')
    // Remove leading/trailing separators
    .replace(new RegExp(`^[${props.field.separator || '-'}]+|[${props.field.separator || '-'}]+$`, 'g'), '')
  
  // Apply max length if specified
  if (props.field.maxLength && slug.length > props.field.maxLength) {
    slug = slug.substring(0, props.field.maxLength)
    // Remove trailing separator if it exists
    slug = slug.replace(new RegExp(`[${props.field.separator || '-'}]+$`), '')
  }
  
  return slug
}

const handleInput = (event) => {
  let value = event.target.value
  
  // Auto-clean the slug as user types
  value = createSlug(value)
  
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
  // Allow common keyboard shortcuts
  if (event.ctrlKey || event.metaKey) {
    return
  }
  
  // Generate slug on Enter if fromAttribute is set
  if (event.key === 'Enter' && props.field.fromAttribute) {
    event.preventDefault()
    generateSlug()
  }
}

// Watch for changes in the source field
watch(
  () => props.formData?.[props.field.fromAttribute],
  (newValue) => {
    // Auto-generate slug if current slug is empty and we have a source value
    if (!props.modelValue && newValue && props.field.fromAttribute) {
      const slug = createSlug(newValue)
      emit('update:modelValue', slug)
      emit('change', slug)
    }
  },
  { immediate: false }
)

// Focus method for external use
const focus = () => {
  inputRef.value?.focus()
}

defineExpose({
  focus
})
</script>

<style scoped>
/* Ensure proper spacing */
.relative {
  position: relative;
}

/* Monospace font for slug preview */
.font-mono {
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
}
</style>
