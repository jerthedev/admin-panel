<template>
  <BaseField
    :field="field"
    :model-value="modelValue"
    :errors="errors"
    :disabled="disabled"
    :readonly="readonly"
    :size="size"
    :show-label="false"
    v-bind="$attrs"
  >
    <!-- Toggle Switch -->
    <div v-if="field.asToggle" class="flex items-center space-x-3">
      <button
        :id="fieldId"
        ref="toggleRef"
        type="button"
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        :class="[
          isChecked ? 'bg-blue-600' : 'bg-gray-200',
          { 'bg-blue-500': isChecked && isDarkTheme },
          { 'bg-gray-600': !isChecked && isDarkTheme },
          { 'opacity-50 cursor-not-allowed': disabled }
        ]"
        :disabled="disabled"
        :aria-checked="isChecked"
        @click="toggle"
        @focus="handleFocus"
        @blur="handleBlur"
      >
        <span
          class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
          :class="{ 'translate-x-5': isChecked, 'translate-x-0': !isChecked }"
        ></span>
      </button>

      <label
        :for="fieldId"
        class="text-sm font-medium text-gray-900 cursor-pointer"
        :class="{ 'text-white': isDarkTheme, 'cursor-not-allowed opacity-50': disabled }"
        @click="!disabled && toggle()"
      >
        {{ field.name }}
        <span v-if="isRequired" class="text-red-500 ml-1">*</span>
      </label>

      <span class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
        ({{ displayValue }})
      </span>
    </div>

    <!-- Checkbox -->
    <div v-else class="flex items-center space-x-3">
      <input
        :id="fieldId"
        ref="checkboxRef"
        type="checkbox"
        :checked="isChecked"
        :disabled="disabled"
        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
        :class="{ 'border-gray-600 bg-gray-700': isDarkTheme }"
        @change="handleChange"
        @focus="handleFocus"
        @blur="handleBlur"
      />

      <label
        :for="fieldId"
        class="text-sm font-medium text-gray-900 cursor-pointer"
        :class="{ 'text-white': isDarkTheme, 'cursor-not-allowed opacity-50': disabled }"
      >
        {{ field.name }}
        <span v-if="isRequired" class="text-red-500 ml-1">*</span>
      </label>

      <span class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
        ({{ displayValue }})
      </span>
    </div>

    <!-- Readonly display -->
    <div
      v-if="readonly"
      class="mt-2 flex items-center space-x-2"
    >
      <div
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
        :class="[
          isChecked 
            ? 'bg-green-100 text-green-800' 
            : 'bg-gray-100 text-gray-800',
          {
            'bg-green-900 text-green-200': isChecked && isDarkTheme,
            'bg-gray-700 text-gray-300': !isChecked && isDarkTheme
          }
        ]"
      >
        <CheckIcon v-if="isChecked" class="h-3 w-3 mr-1" />
        <XMarkIcon v-else class="h-3 w-3 mr-1" />
        {{ displayValue }}
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * BooleanField Component
 * 
 * Boolean toggle/checkbox field with customizable true/false values,
 * labels, and display modes (toggle switch or checkbox).
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [Boolean, String, Number],
    default: false
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
const toggleRef = ref(null)
const checkboxRef = ref(null)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `boolean-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const isRequired = computed(() => {
  return props.field.rules?.includes('required') || 
         props.field.creationRules?.includes('required') ||
         props.field.updateRules?.includes('required')
})

const trueValue = computed(() => {
  return props.field.trueValue !== undefined ? props.field.trueValue : true
})

const falseValue = computed(() => {
  return props.field.falseValue !== undefined ? props.field.falseValue : false
})

const trueText = computed(() => {
  return props.field.trueText || 'Yes'
})

const falseText = computed(() => {
  return props.field.falseText || 'No'
})

const isChecked = computed(() => {
  return props.modelValue == trueValue.value
})

const displayValue = computed(() => {
  return isChecked.value ? trueText.value : falseText.value
})

// Methods
const toggle = () => {
  if (props.disabled || props.readonly) return
  
  const newValue = isChecked.value ? falseValue.value : trueValue.value
  emit('update:modelValue', newValue)
  emit('change', newValue)
}

const handleChange = (event) => {
  const newValue = event.target.checked ? trueValue.value : falseValue.value
  emit('update:modelValue', newValue)
  emit('change', newValue)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

// Focus method for external use
const focus = () => {
  if (props.field.asToggle) {
    toggleRef.value?.focus()
  } else {
    checkboxRef.value?.focus()
  }
}

defineExpose({
  focus
})
</script>

<style scoped>
/* Smooth transitions for toggle switch */
.transition-colors {
  transition-property: background-color, border-color, color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

.transition {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Focus ring for toggle */
.focus\:ring-2:focus {
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}

.focus\:ring-offset-2:focus {
  --tw-ring-offset-width: 2px;
}

.focus\:ring-blue-500:focus {
  --tw-ring-opacity: 1;
  --tw-ring-color: rgb(59 130 246 / var(--tw-ring-opacity));
}
</style>
