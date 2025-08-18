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
      <textarea
        :id="fieldId"
        ref="textareaRef"
        :value="modelValue"
        :placeholder="field.placeholder || field.name"
        :rows="field.rows || 4"
        :maxlength="field.enforceMaxlength ? field.maxlength : null"
        :disabled="disabled"
        :readonly="readonly"
        class="admin-textarea w-full"
        :class="{ 'admin-input-dark': isDarkTheme }"
        v-bind="extraAttributes"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
      ></textarea>

      <!-- Character count -->
      <div
        v-if="showCharacterCount"
        class="absolute bottom-2 right-2 text-xs pointer-events-none"
        :class="characterCountClasses"
      >
        {{ characterCount }}{{ (field.maxlength !== null && field.maxlength !== undefined) ? `/${field.maxlength}` : '' }}
      </div>
    </div>

    <!-- Character count (external) -->
    <div
      v-if="showCharacterCount && (field.maxlength === null || field.maxlength === undefined)"
      class="mt-1 text-right text-xs text-gray-500"
      :class="{ 'text-gray-400': isDarkTheme }"
    >
      {{ characterCount }} characters
    </div>
  </BaseField>
</template>

<script setup>
/**
 * TextareaField Component
 *
 * Textarea input field compatible with Nova's Textarea field API.
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
const textareaRef = ref(null)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `textarea-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const extraAttributes = computed(() => {
  return props.field.extraAttributes || {}
})

const showCharacterCount = computed(() => {
  return props.field.maxlength !== null && props.field.maxlength !== undefined
})

const characterCount = computed(() => {
  return String(props.modelValue || '').length
})

const characterCountClasses = computed(() => {
  const count = characterCount.value
  const max = props.field.maxlength

  if (!max) {
    return isDarkTheme.value ? 'text-gray-400' : 'text-gray-500'
  }

  if (count > max * 0.9) {
    return isDarkTheme.value ? 'text-red-400' : 'text-red-500'
  } else if (count > max * 0.7) {
    return isDarkTheme.value ? 'text-amber-400' : 'text-amber-500'
  }
  return isDarkTheme.value ? 'text-gray-400' : 'text-gray-500'
})

// Methods
const handleInput = (event) => {
  let value = event.target.value

  // Apply maxlength if enforceMaxlength is enabled and not using HTML maxlength
  if (props.field.enforceMaxlength && props.field.maxlength && !event.target.hasAttribute('maxlength')) {
    if (value.length > props.field.maxlength) {
      value = value.substring(0, props.field.maxlength)
      event.target.value = value
    }
  }

  emit('update:modelValue', value)
  emit('change', value)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

// Focus method for external use
const focus = () => {
  textareaRef.value?.focus()
}

defineExpose({
  focus
})
</script>

<style scoped>
/* Character count positioning */
.relative textarea {
  padding-bottom: 2rem;
}

.relative textarea + .absolute {
  background: rgba(255, 255, 255, 0.9);
  border-radius: 0.25rem;
  padding: 0.125rem 0.25rem;
}

.dark .relative textarea + .absolute {
  background: rgba(31, 41, 55, 0.9);
}
</style>
