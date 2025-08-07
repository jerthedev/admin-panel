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
        type="datetime-local"
        :value="formattedValue"
        :min="field.minDateTime"
        :max="field.maxDateTime"
        :step="stepInSeconds"
        :disabled="disabled"
        :readonly="readonly"
        class="admin-input w-full pr-10"
        :class="{ 'admin-input-dark': isDarkTheme }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @change="handleChange"
      />

      <!-- Calendar/Clock icon -->
      <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
        <ClockIcon class="h-5 w-5 text-gray-400" />
      </div>
    </div>

    <!-- Formatted display for readonly -->
    <div
      v-if="readonly && modelValue"
      class="mt-2 text-sm text-gray-600"
      :class="{ 'text-gray-400': isDarkTheme }"
    >
      Formatted: {{ displayValue }}
    </div>

    <!-- Timezone display -->
    <div
      v-if="field.timezone && field.timezone !== 'UTC'"
      class="mt-1 text-xs text-gray-500"
      :class="{ 'text-gray-400': isDarkTheme }"
    >
      Timezone: {{ field.timezone }}
    </div>

    <!-- Relative time display -->
    <div
      v-if="modelValue && showRelativeTime"
      class="mt-1 text-xs text-gray-500"
      :class="{ 'text-gray-400': isDarkTheme }"
    >
      {{ relativeTime }}
    </div>
  </BaseField>
</template>

<script setup>
/**
 * DateTimeField Component
 * 
 * A datetime input field with timezone support, time intervals,
 * and formatting capabilities.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, ref } from 'vue'
import { ClockIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'
import { useAdminStore } from '@/stores/admin'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, Date],
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
    default: 'default',
    validator: (value) => ['small', 'default', 'large'].includes(value)
  },
  showRelativeTime: {
    type: Boolean,
    default: false
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
  return `datetime-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const stepInSeconds = computed(() => {
  // Convert minutes to seconds for HTML input step attribute
  return (props.field.step || 1) * 60
})

const formattedValue = computed(() => {
  if (!props.modelValue) return ''
  
  try {
    const date = new Date(props.modelValue)
    if (isNaN(date.getTime())) return props.modelValue
    
    // Format for HTML datetime-local input (YYYY-MM-DDTHH:MM:SS)
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hours = String(date.getHours()).padStart(2, '0')
    const minutes = String(date.getMinutes()).padStart(2, '0')
    const seconds = String(date.getSeconds()).padStart(2, '0')
    
    return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`
  } catch (error) {
    return props.modelValue
  }
})

const displayValue = computed(() => {
  if (!props.modelValue) return ''
  
  try {
    const date = new Date(props.modelValue)
    if (isNaN(date.getTime())) return props.modelValue
    
    const format = props.field.displayFormat || 'Y-m-d H:i:s'
    return formatDateTime(date, format)
  } catch (error) {
    return props.modelValue
  }
})

const relativeTime = computed(() => {
  if (!props.modelValue) return ''
  
  try {
    const date = new Date(props.modelValue)
    if (isNaN(date.getTime())) return ''
    
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMinutes = Math.floor(diffMs / (1000 * 60))
    const diffHours = Math.floor(diffMinutes / 60)
    const diffDays = Math.floor(diffHours / 24)
    
    if (diffMinutes < 1) return 'Just now'
    if (diffMinutes < 60) return `${diffMinutes} minute${diffMinutes !== 1 ? 's' : ''} ago`
    if (diffHours < 24) return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`
    if (diffDays < 30) return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`
    
    return date.toLocaleDateString()
  } catch (error) {
    return ''
  }
})

// Methods
const handleInput = (event) => {
  const value = event.target.value
  emit('update:modelValue', value)
  emit('change', value)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const handleChange = (event) => {
  const value = event.target.value
  emit('update:modelValue', value)
  emit('change', value)
}

// Format datetime according to PHP-style format strings
const formatDateTime = (date, format) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const seconds = String(date.getSeconds()).padStart(2, '0')
  const hours12 = date.getHours() % 12 || 12
  const ampm = date.getHours() >= 12 ? 'PM' : 'AM'
  
  switch (format) {
    case 'Y-m-d H:i:s':
      return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
    case 'Y-m-d H:i':
      return `${year}-${month}-${day} ${hours}:${minutes}`
    case 'F j, Y g:i A':
      const fullMonths = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ]
      return `${fullMonths[date.getMonth()]} ${parseInt(day)}, ${year} ${hours12}:${minutes} ${ampm}`
    case 'M j, Y g:i A':
      const shortMonths = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
      ]
      return `${shortMonths[date.getMonth()]} ${parseInt(day)}, ${year} ${hours12}:${minutes} ${ampm}`
    default:
      return date.toLocaleString()
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
