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
    <!-- Line content -->
    <div class="line-field" :class="lineClasses">
      <!-- HTML content -->
      <div
        v-if="field.asHtml"
        v-html="displayValue"
        class="line-content"
      />
      
      <!-- Plain text content -->
      <div
        v-else
        class="line-content"
        :class="textClasses"
      >
        {{ displayValue }}
      </div>
    </div>
  </BaseField>
</template>

<script>
/**
 * Line Field Vue Component
 *
 * Displays formatted text lines within Stack fields with various formatting options.
 * Supports asSmall, asHeading, and asSubText formatting modes.
 * 100% compatible with Nova v5 Line field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import BaseField from './BaseField.vue'
import { computed } from 'vue'
import { useAdminStore } from '@/stores/admin'

export default {
  name: 'LineField',
  components: {
    BaseField
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

// Emits (though line fields don't typically emit events)
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const displayValue = computed(() => {
  return props.field.value || props.field.name || ''
})

const lineClasses = computed(() => {
  return [
    'py-1',
    {
      'opacity-75': props.disabled
    }
  ]
})

const textClasses = computed(() => {
  const classes = ['text-gray-900']
  
  // Dark theme
  if (isDarkTheme.value) {
    classes.push('text-gray-100')
  }
  
  // Formatting classes
  if (props.field.asSmall) {
    classes.push('text-xs', 'text-gray-600')
    if (isDarkTheme.value) {
      classes.push('text-gray-400')
    }
  } else if (props.field.asHeading) {
    classes.push('text-lg', 'font-semibold')
  } else if (props.field.asSubText) {
    classes.push('text-sm', 'text-gray-700')
    if (isDarkTheme.value) {
      classes.push('text-gray-300')
    }
  } else {
    // Default text styling
    classes.push('text-sm')
  }
  
  return classes
})

// Methods (for consistency with other fields, though not typically used)
const focus = () => {
  // Line fields don't have focusable elements
}

defineExpose({
  focus
})
</script>

<style scoped>
.line-field {
  @apply mb-1;
}

.line-content {
  @apply leading-relaxed;
}

/* Ensure HTML content is properly styled */
.line-content :deep(h1),
.line-content :deep(h2),
.line-content :deep(h3),
.line-content :deep(h4),
.line-content :deep(h5),
.line-content :deep(h6) {
  @apply font-semibold mb-2;
}

.line-content :deep(p) {
  @apply mb-2;
}

.line-content :deep(a) {
  @apply text-blue-600 hover:text-blue-500;
}

.line-content :deep(strong) {
  @apply font-semibold;
}

.line-content :deep(em) {
  @apply italic;
}

/* Dark theme styles for HTML content */
.dark .line-content :deep(a) {
  @apply text-blue-400 hover:text-blue-300;
}
</style>
