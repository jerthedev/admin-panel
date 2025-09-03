<template>
  <BaseField
    :field="field"
    :model-value="modelValue"
    :errors="errors"
    :disabled="disabled"
    :readonly="readonly"
    :size="size"
    :show-label="showStackLabel"
    v-bind="$attrs"
  >
    <!-- Stack content -->
    <div class="stack-field" :class="stackClasses">
      <!-- Render each field in the stack -->
      <div
        v-for="(stackedField, index) in stackedFields"
        :key="`stacked-field-${index}`"
        class="stack-item"
        :class="stackItemClasses"
      >
        <!-- Dynamic field component rendering -->
        <component
          :is="getFieldComponent(stackedField)"
          :field="stackedField"
          :model-value="stackedField.value"
          :errors="[]"
          :disabled="disabled"
          :readonly="true"
          :size="size"
          @update:modelValue="() => {}"
        />
      </div>
      
      <!-- Empty state -->
      <div
        v-if="stackedFields.length === 0"
        class="stack-empty"
        :class="{ 'text-gray-400': isDarkTheme, 'text-gray-500': !isDarkTheme }"
      >
        No fields to display
      </div>
    </div>
  </BaseField>
</template>

<script>
/**
 * Stack Field Vue Component
 *
 * Displays multiple fields in a stacked/vertical layout.
 * Supports Text, BelongsTo, and Line fields with proper component rendering.
 * 100% compatible with Nova v5 Stack field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import BaseField from './BaseField.vue'
import LineField from './LineField.vue'
import TextField from './TextField.vue'
import BelongsToField from './BelongsToField.vue'
import { computed } from 'vue'
import { useAdminStore } from '@/stores/admin'

export default {
  name: 'StackField',
  components: {
    BaseField,
    LineField,
    TextField,
    BelongsToField
  },
  inheritAttrs: false
}
</script>

<script setup>
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
    type: Array,
    default: () => []
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: true
  },
  size: {
    type: String,
    default: 'default',
    validator: (value) => ['small', 'default', 'large'].includes(value)
  }
})

// Emits (though stack fields don't typically emit events)
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const stackedFields = computed(() => {
  return props.field.fields || []
})

const showStackLabel = computed(() => {
  // Show label if the field has a meaningful name and it's not just for grouping
  return props.field.name && props.field.name !== 'Stack'
})

const stackClasses = computed(() => {
  return [
    'space-y-2',
    {
      'opacity-75': props.disabled
    }
  ]
})

const stackItemClasses = computed(() => {
  return [
    'stack-item-wrapper',
    {
      'border-l-2 border-gray-200 pl-3': stackedFields.value.length > 1,
      'border-l-2 border-gray-600 pl-3': stackedFields.value.length > 1 && isDarkTheme.value
    }
  ]
})

// Methods
const getFieldComponent = (field) => {
  // Map field components based on their component property
  const componentMap = {
    'LineField': 'LineField',
    'TextField': 'TextField',
    'BelongsToField': 'BelongsToField',
    'TextareaField': 'TextField', // Fallback to TextField for now
    'EmailField': 'TextField', // Fallback to TextField
    'NumberField': 'TextField', // Fallback to TextField
    'PasswordField': 'TextField', // Fallback to TextField
    'URLField': 'TextField', // Fallback to TextField
  }
  
  return componentMap[field.component] || 'TextField'
}

const focus = () => {
  // Stack fields don't have focusable elements directly
  // Could potentially focus the first focusable field in the stack
}

defineExpose({
  focus
})
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.stack-field {
  @apply w-full;
}

.stack-item {
  @apply relative;
}

.stack-item:not(:last-child) {
  @apply mb-3;
}

.stack-item-wrapper {
  @apply transition-colors duration-200;
}

.stack-empty {
  @apply text-sm italic py-4 text-center;
}

/* Ensure nested fields don't have excessive margins */
.stack-item :deep(.field-wrapper) {
  @apply mb-0;
}

.stack-item :deep(.field-content) {
  @apply mb-0;
}

/* Style for line fields within stack */
.stack-item :deep(.line-field) {
  @apply mb-0;
}

/* Reduce spacing for stacked fields */
.stack-item :deep(.admin-label) {
  @apply mb-1;
}
</style>
