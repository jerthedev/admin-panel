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
        type="number"
        :value="modelValue"
        :placeholder="field.placeholder || field.name"
        :min="field.min"
        :max="field.max"
        :step="field.step || 1"
        :disabled="disabled"
        :readonly="readonly"
        class="admin-input w-full"
        :class="[
          { 'admin-input-dark': isDarkTheme },
          { 'pr-16': field.showButtons }
        ]"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
        @wheel="handleWheel"
      />

      <!-- Increment/Decrement buttons -->
      <div
        v-if="field.showButtons"
        class="absolute inset-y-0 right-0 flex flex-col"
      >
        <button
          type="button"
          class="flex-1 px-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 border-l border-gray-300"
          :class="{ 'border-gray-600 hover:text-gray-300 focus:text-gray-300': isDarkTheme }"
          :disabled="disabled || (field.max !== null && Number(modelValue) >= field.max)"
          @click="increment"
        >
          <ChevronUpIcon class="h-3 w-3" />
        </button>
        <button
          type="button"
          class="flex-1 px-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 border-l border-t border-gray-300"
          :class="{ 'border-gray-600 hover:text-gray-300 focus:text-gray-300': isDarkTheme }"
          :disabled="disabled || (field.min !== null && Number(modelValue) <= field.min)"
          @click="decrement"
        >
          <ChevronDownIcon class="h-3 w-3" />
        </button>
      </div>
    </div>

    <!-- Formatted display for readonly -->
    <div
      v-if="readonly && modelValue !== null && modelValue !== ''"
      class="mt-2 text-sm text-gray-600"
      :class="{ 'text-gray-400': isDarkTheme }"
    >
      Formatted: {{ formattedValue }}
    </div>
  </BaseField>
</template>

<script setup>
/**
 * NumberField Component
 * 
 * Numeric input field with increment/decrement buttons, min/max validation,
 * step controls, and decimal formatting.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [Number, String],
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
  return `number-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const stepValue = computed(() => {
  return props.field.step || 1
})

const formattedValue = computed(() => {
  if (props.modelValue === null || props.modelValue === '') return ''
  
  const num = Number(props.modelValue)
  if (isNaN(num)) return props.modelValue
  
  if (props.field.decimals !== null && props.field.decimals !== undefined) {
    return num.toFixed(props.field.decimals)
  }
  
  return num.toLocaleString()
})

// Methods
const handleInput = (event) => {
  let value = event.target.value
  
  // Allow empty value
  if (value === '') {
    emit('update:modelValue', null)
    emit('change', null)
    return
  }
  
  // Convert to number
  const numValue = Number(value)
  
  if (isNaN(numValue)) {
    // Reset to previous valid value
    event.target.value = props.modelValue || ''
    return
  }
  
  // Apply min/max constraints
  let constrainedValue = numValue
  if (props.field.min !== null && constrainedValue < props.field.min) {
    constrainedValue = props.field.min
    event.target.value = constrainedValue
  }
  if (props.field.max !== null && constrainedValue > props.field.max) {
    constrainedValue = props.field.max
    event.target.value = constrainedValue
  }
  
  emit('update:modelValue', constrainedValue)
  emit('change', constrainedValue)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
  
  // Format the value on blur if decimals are specified
  if (props.field.decimals !== null && props.field.decimals !== undefined && props.modelValue !== null) {
    const formatted = Number(props.modelValue).toFixed(props.field.decimals)
    event.target.value = formatted
    emit('update:modelValue', Number(formatted))
  }
}

const handleKeydown = (event) => {
  // Allow: backspace, delete, tab, escape, enter, home, end, left, right, up, down
  const allowedKeys = [
    'Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'Home', 'End',
    'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'
  ]
  
  if (allowedKeys.includes(event.key)) {
    return
  }
  
  // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
  if (event.ctrlKey && ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase())) {
    return
  }
  
  // Allow decimal point if step allows decimals
  if (event.key === '.' && stepValue.value % 1 !== 0) {
    // Only allow one decimal point
    if (event.target.value.includes('.')) {
      event.preventDefault()
    }
    return
  }
  
  // Allow minus sign for negative numbers (if min allows it)
  if (event.key === '-' && (props.field.min === null || props.field.min < 0)) {
    // Only allow at the beginning
    if (event.target.selectionStart !== 0) {
      event.preventDefault()
    }
    return
  }
  
  // Only allow numbers
  if (!/\d/.test(event.key)) {
    event.preventDefault()
  }
}

const handleWheel = (event) => {
  // Prevent scrolling from changing the number when focused
  if (document.activeElement === event.target) {
    event.preventDefault()
  }
}

const increment = () => {
  const currentValue = Number(props.modelValue) || 0
  const newValue = currentValue + stepValue.value
  
  if (props.field.max === null || newValue <= props.field.max) {
    emit('update:modelValue', newValue)
    emit('change', newValue)
  }
}

const decrement = () => {
  const currentValue = Number(props.modelValue) || 0
  const newValue = currentValue - stepValue.value
  
  if (props.field.min === null || newValue >= props.field.min) {
    emit('update:modelValue', newValue)
    emit('change', newValue)
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
/* Hide default number input spinners */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  -moz-appearance: textfield;
}

/* Custom button styling */
button:disabled {
}

/* Ensure proper spacing for buttons */
.pr-16 {
  padding-right: 4rem;
}
</style>
