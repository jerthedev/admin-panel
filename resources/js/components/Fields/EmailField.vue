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
        type="email"
        :value="modelValue"
        :placeholder="field.placeholder || 'Enter email address'"
        :disabled="disabled"
        :readonly="readonly"
        class="admin-input w-full pl-10"
        :class="{ 'admin-input-dark': isDarkTheme }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
      />

      <!-- Email icon -->
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <AtSymbolIcon class="h-5 w-5 text-gray-400" />
      </div>

      <!-- Validation indicator -->
      <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
        <CheckCircleIcon
          v-if="isValidEmail && modelValue"
          class="h-5 w-5 text-green-500"
          data-testid="check-circle-icon"
        />
        <ExclamationCircleIcon
          v-else-if="modelValue && !isValidEmail"
          class="h-5 w-5 text-red-500"
          data-testid="exclamation-circle-icon"
        />
      </div>
    </div>

    <!-- Display as clickable link on detail view -->
    <div
      v-if="readonly && field.clickable && modelValue"
      class="mt-2"
    >
      <a
        :href="`mailto:${modelValue}`"
        class="inline-flex items-center text-blue-600 hover:text-blue-500"
        :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
      >
        <EnvelopeIcon class="h-4 w-4 mr-1" />
        Send Email
      </a>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * EmailField Component
 * 
 * Email input field with validation, formatting, and clickable
 * mailto links for detail views.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import {
  AtSymbolIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  EnvelopeIcon
} from '@heroicons/vue/24/outline'
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
const inputRef = ref(null)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `email-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const isValidEmail = computed(() => {
  if (!props.modelValue) return false
  
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(props.modelValue)
})

// Methods
const handleInput = (event) => {
  let value = event.target.value
  
  // Normalize email: trim and convert to lowercase
  value = value.trim().toLowerCase()
  
  emit('update:modelValue', value)
  emit('change', value)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
  
  // Final normalization on blur
  if (props.modelValue) {
    const normalizedValue = props.modelValue.trim().toLowerCase()
    if (normalizedValue !== props.modelValue) {
      emit('update:modelValue', normalizedValue)
      emit('change', normalizedValue)
    }
  }
}

const handleKeydown = (event) => {
  // Prevent spaces in email addresses
  if (event.key === ' ') {
    event.preventDefault()
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
@import '../../../css/admin.css' reference;

/* Ensure proper spacing for icon */
.pl-10 {
  padding-left: 2.5rem;
}

/* Smooth transitions for validation indicators */
.text-green-500,
.text-red-500 {
  transition: color 0.2s ease-in-out;
}
</style>
