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
    <select
      :value="modelValue"
      class="admin-input w-full"
      :class="[
        { 'admin-input-dark': isDarkTheme },
        { 'border-red-300': hasError },
        { 'opacity-50 cursor-not-allowed': disabled || readonly }
      ]"
      :disabled="disabled || readonly"
      @change="handleChange"
      @focus="$emit('focus')"
      @blur="$emit('blur')"
    >
      <option value="">
        {{ field.placeholder || 'Select timezone...' }}
      </option>
      <option
        v-for="(label, value) in options"
        :key="value"
        :value="value"
      >
        {{ label }}
      </option>
    </select>
  </BaseField>
</template>

<script setup>
/**
 * TimezoneField Component
 *
 * A simple timezone selection field. Generates a Select field containing
 * a list of the world's timezones.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, null],
    default: null
  },
  errors: {
    type: Array,
    default: () => []
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
    default: 'md'
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'focus', 'blur'])

// Store
const adminStore = useAdminStore()

// Computed properties
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const hasError = computed(() => props.errors.length > 0)

const options = computed(() => {
  return props.field.options || {}
})

// Methods
const handleChange = (event) => {
  emit('update:modelValue', event.target.value || null)
}
</script>


