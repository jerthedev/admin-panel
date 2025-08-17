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
        type="color"
        :value="normalizedValue"
        :disabled="disabled"
        :readonly="readonly"
        class="h-10 w-16 p-1 rounded border border-gray-300 bg-white"
        :class="{
          'border-gray-600 bg-gray-700': isDarkTheme,
          'opacity-50 cursor-not-allowed': disabled
        }"
        @input="handleInput"
        @change="handleChange"
        @focus="handleFocus"
        @blur="handleBlur"
      />
    </div>
  </BaseField>
</template>

<script setup>
/**
 * ColorField Component (Nova-compatible)
 *
 * Simple HTML5 color input. Nova does not expose additional options for this field.
 */
import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: { type: Object, required: true },
  modelValue: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  size: { type: String, default: 'default' },
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
  return `color-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

// Normalize value to valid hex color for input[type=color]; fallback to #000000
const normalizedValue = computed(() => {
  const v = props.modelValue || ''
  return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(v) ? v : '#000000'
})

// Methods
const handleInput = (event) => {
  const value = event.target.value
  emit('update:modelValue', value)
}

const handleChange = (event) => {
  const value = event.target.value
  emit('change', value)
}

const handleFocus = (event) => emit('focus', event)
const handleBlur = (event) => emit('blur', event)

// Expose focus for parent calls
const focus = () => inputRef.value?.focus()

defineExpose({ focus })
</script>

