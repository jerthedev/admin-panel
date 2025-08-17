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
    <!-- Heading content -->
    <div class="heading-field" :class="headingClasses">
      <!-- HTML content -->
      <div
        v-if="field.asHtml"
        v-html="field.name"
        class="heading-content"
      />
      
      <!-- Plain text content -->
      <div
        v-else
        class="heading-content heading-text"
      >
        {{ field.name }}
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * HeadingField Component
 * 
 * A field that displays a banner across forms and can function as a separator
 * for long lists of fields. Does not correspond to any database column.
 * 100% compatible with Nova v5 Heading field API.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import BaseField from './BaseField.vue'
import { useAdminStore } from '@/stores/admin'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, Number, Boolean],
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
    default: true // Heading fields are typically readonly
  },
  size: {
    type: String,
    default: 'default'
  }
})

// Emits (though heading fields don't typically emit events)
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const headingClasses = computed(() => {
  return [
    'py-4',
    'border-b',
    'border-gray-200',
    {
      'border-gray-700': isDarkTheme.value
    }
  ]
})

// Methods (for consistency with other fields, though not typically used)
const focus = () => {
  // Heading fields don't have focusable elements
}

defineExpose({
  focus
})
</script>

<style scoped>
.heading-field {
  @apply mb-6;
}

.heading-content {
  @apply text-gray-900;
}

.heading-text {
  @apply text-lg font-semibold;
}

/* Dark theme support */
.admin-dark .heading-content {
  @apply text-gray-100;
}

/* HTML content styling */
.heading-content :deep(h1) {
  @apply text-2xl font-bold mb-2;
}

.heading-content :deep(h2) {
  @apply text-xl font-semibold mb-2;
}

.heading-content :deep(h3) {
  @apply text-lg font-medium mb-2;
}

.heading-content :deep(h4) {
  @apply text-base font-medium mb-1;
}

.heading-content :deep(h5) {
  @apply text-sm font-medium mb-1;
}

.heading-content :deep(h6) {
  @apply text-xs font-medium mb-1;
}

.heading-content :deep(p) {
  @apply mb-2;
}

.heading-content :deep(strong) {
  @apply font-semibold;
}

.heading-content :deep(em) {
  @apply italic;
}

.heading-content :deep(ul) {
  @apply list-disc list-inside mb-2;
}

.heading-content :deep(ol) {
  @apply list-decimal list-inside mb-2;
}

.heading-content :deep(li) {
  @apply mb-1;
}
</style>
