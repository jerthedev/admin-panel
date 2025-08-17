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
        :value="modelValue !== null && modelValue !== undefined ? modelValue : ''"
        :placeholder="field.placeholder || field.name"
        :min="field.min"
        :max="field.max"
        :step="field.step || 1"
        :disabled="disabled"
        :readonly="readonly"
        class="admin-input w-full"
        :class="{ 'admin-input-dark': isDarkTheme }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
      />
    </div>
  </BaseField>
</template>

<script setup>
/**
 * NumberField Component
 *
 * Numeric input field with min/max validation and step controls.
 * 100% compatible with Laravel Nova Number field.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
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

  if (!isNaN(numValue)) {
    emit('update:modelValue', numValue)
    emit('change', numValue)
  }
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
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
</style>
