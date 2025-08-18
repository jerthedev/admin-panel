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
        type="url"
        :value="modelValue || ''"
        :disabled="disabled"
        :readonly="readonly"
        :placeholder="field.placeholder || 'https://example.com'"
        class="admin-input w-full pr-10"
        :class="{ 'admin-input-dark': isDarkTheme }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @change="handleChange"
      />

      <!-- Link Icon -->
      <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
        <LinkIcon class="h-5 w-5 text-gray-400" />
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * URLField Component
 *
 * A URL input field compatible with Laravel Nova's URL field API.
 * Renders URLs as clickable links instead of plain text.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, ref } from 'vue'
import { LinkIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'
import { useAdminStore } from '@/stores/admin'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: String,
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
    default: 'default',
    validator: (value) => ['small', 'default', 'large'].includes(value)
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
  return `url-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

// Methods
const handleInput = (event) => {
  const value = event.target.value
  emit('update:modelValue', value || null)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const handleChange = (event) => {
  const value = event.target.value
  emit('update:modelValue', value || null)
  emit('change', value || null)
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
.admin-input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm;
}

.admin-input-dark {
  @apply bg-gray-800 border-gray-600 text-white placeholder-gray-400 focus:border-blue-400 focus:ring-blue-400;
}

.admin-input:disabled {
  @apply bg-gray-50 text-gray-500 cursor-not-allowed;
}

.admin-input-dark:disabled {
  @apply bg-gray-900 text-gray-500;
}

.url-preview {
  @apply transition-all duration-200;
}
</style>
