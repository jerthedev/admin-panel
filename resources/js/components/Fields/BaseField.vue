<template>
  <div class="field-wrapper" :class="fieldWrapperClasses">
    <!-- Label -->
    <label
      v-if="field.name && showLabel"
      :for="fieldId"
      class="admin-label"
      :class="{ 'admin-label-dark': isDarkTheme }"
    >
      {{ field.name }}
      <span v-if="isRequired" class="text-red-500 ml-1">*</span>
    </label>

    <!-- Field content slot -->
    <div class="field-content" :class="fieldContentClasses">
      <slot />
    </div>

    <!-- Help text -->
    <p
      v-if="field.helpText"
      class="mt-1 text-sm text-gray-500"
      :class="{ 'text-gray-400': isDarkTheme }"
    >
      {{ field.helpText }}
    </p>

    <!-- Error message -->
    <p
      v-if="errorMessage"
      class="mt-1 text-sm text-red-600"
      :class="{ 'text-red-400': isDarkTheme }"
    >
      {{ errorMessage }}
    </p>
  </div>
</template>

<script setup>
/**
 * BaseField Component
 * 
 * Base component for all admin panel fields providing common
 * functionality like labels, help text, error handling, and styling.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, inject } from 'vue'
import { useAdminStore } from '@/stores/admin'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, Number, Boolean, Array, Object],
    default: null
  },
  errors: {
    type: Object,
    default: () => ({})
  },
  showLabel: {
    type: Boolean,
    default: true
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

// Store
const adminStore = useAdminStore()

// Injected context (for forms)
const formContext = inject('formContext', null)

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const isRequired = computed(() => {
  return props.field.rules?.includes('required') || 
         props.field.creationRules?.includes('required') ||
         props.field.updateRules?.includes('required')
})

const errorMessage = computed(() => {
  return props.errors[props.field.attribute]?.[0] || null
})

const hasError = computed(() => {
  return !!errorMessage.value
})

const fieldWrapperClasses = computed(() => {
  return [
    'field-wrapper',
    {
      'field-error': hasError.value,
      'field-disabled': props.disabled,
      'field-readonly': props.readonly,
      [`field-size-${props.size}`]: props.size !== 'default'
    }
  ]
})

const fieldContentClasses = computed(() => {
  return [
    'field-content',
    {
      'field-content-error': hasError.value
    }
  ]
})

// Methods
const updateValue = (value) => {
  emit('update:modelValue', value)
  emit('change', value)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

// Provide methods to child components
defineExpose({
  fieldId,
  updateValue,
  handleFocus,
  handleBlur,
  isRequired,
  hasError,
  errorMessage
})
</script>

<style scoped>
.field-wrapper {
}

.field-wrapper.field-size-small {
}

.field-wrapper.field-size-large {
}

.field-wrapper.field-disabled {
}

.field-wrapper.field-readonly .field-content {
}

.field-wrapper.field-error .field-content input,
.field-wrapper.field-error .field-content textarea,
.field-wrapper.field-error .field-content select {
}

/* Dark theme overrides */
.dark .field-wrapper.field-readonly .field-content {
}
</style>
