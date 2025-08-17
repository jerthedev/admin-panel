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
        type="date"
        :value="formattedValue"
        :min="field.minDate"
        :max="field.maxDate"
        :disabled="disabled"
        :readonly="readonly"
        class="admin-input w-full pr-10"
        :class="{ 'admin-input-dark': isDarkTheme }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @change="handleChange"
      />

      <!-- Calendar icon -->
      <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
        <CalendarDaysIcon class="h-5 w-5 text-gray-400" />
      </div>

      <!-- Clear button -->
      <button
        v-if="modelValue && !disabled && !readonly"
        type="button"
        class="absolute inset-y-0 right-8 flex items-center pr-2"
        @click="clearDate"
      >
        <XMarkIcon class="h-4 w-4 text-gray-400 hover:text-gray-600" />
      </button>
    </div>

    <!-- Alternative date picker (if native picker is not shown) -->
    <div
      v-if="field.showPicker && showCustomPicker"
      class="absolute z-10 mt-1 bg-white shadow-lg rounded-md border border-gray-200 p-4"
      :class="{ 'bg-gray-800 border-gray-700': isDarkTheme }"
    >
      <div class="text-center">
        <div class="flex items-center justify-between mb-4">
          <button
            type="button"
            class="p-1 hover:bg-gray-100 rounded"
            :class="{ 'hover:bg-gray-700': isDarkTheme }"
            @click="previousMonth"
          >
            <ChevronLeftIcon class="h-5 w-5" />
          </button>
          <h3 class="text-lg font-medium" :class="{ 'text-white': isDarkTheme }">
            {{ currentMonthYear }}
          </h3>
          <button
            type="button"
            class="p-1 hover:bg-gray-100 rounded"
            :class="{ 'hover:bg-gray-700': isDarkTheme }"
            @click="nextMonth"
          >
            <ChevronRightIcon class="h-5 w-5" />
          </button>
        </div>

        <!-- Calendar grid would go here -->
        <div class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
          Custom date picker implementation would go here
        </div>
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
 * DateField Component
 * 
 * Date input field with formatting, timezone support, date picker,
 * and min/max date validation.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import {
  CalendarDaysIcon,
  XMarkIcon,
  ChevronLeftIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: String,
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
const showCustomPicker = ref(false)
const currentDate = ref(new Date())

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `date-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const formattedValue = computed(() => {
  if (!props.modelValue) return ''

  try {
    const date = new Date(props.modelValue)
    if (isNaN(date.getTime())) return props.modelValue

    // Use pickerFormat if available, otherwise format for HTML date input (YYYY-MM-DD)
    if (props.field.pickerFormat) {
      return formatDateWithCustomFormat(date, props.field.pickerFormat)
    }

    return date.toISOString().split('T')[0]
  } catch (error) {
    return props.modelValue
  }
})

const displayValue = computed(() => {
  if (!props.modelValue) return ''

  try {
    const date = new Date(props.modelValue)
    if (isNaN(date.getTime())) return props.modelValue

    // Use pickerDisplayFormat if available, otherwise use displayFormat
    const format = props.field.pickerDisplayFormat || props.field.displayFormat || 'Y-m-d'
    return formatDate(date, format)
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
    const diffInMs = now.getTime() - date.getTime()
    const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24))
    
    if (diffInDays === 0) {
      return 'Today'
    } else if (diffInDays === 1) {
      return 'Yesterday'
    } else if (diffInDays === -1) {
      return 'Tomorrow'
    } else if (diffInDays > 0) {
      return `${diffInDays} days ago`
    } else {
      return `In ${Math.abs(diffInDays)} days`
    }
  } catch (error) {
    return ''
  }
})

const currentMonthYear = computed(() => {
  const date = currentDate.value
  return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
})

// Methods
const handleInput = (event) => {
  const value = event.target.value
  emit('update:modelValue', value || null)
}

const handleChange = (event) => {
  const value = event.target.value
  emit('change', value || null)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
  // Hide custom picker after a delay
  setTimeout(() => {
    showCustomPicker.value = false
  }, 200)
}

const clearDate = () => {
  emit('update:modelValue', null)
  emit('change', null)
}

const previousMonth = () => {
  const date = new Date(currentDate.value)
  date.setMonth(date.getMonth() - 1)
  currentDate.value = date
}

const nextMonth = () => {
  const date = new Date(currentDate.value)
  date.setMonth(date.getMonth() + 1)
  currentDate.value = date
}

const formatDate = (date, format) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  switch (format) {
    case 'Y-m-d':
      return `${year}-${month}-${day}`
    case 'd/m/Y':
      return `${day}/${month}/${year}`
    case 'm/d/Y':
      return `${month}/${day}/${year}`
    case 'd-m-Y':
      return `${day}-${month}-${year}`
    case 'm-d-Y':
      return `${month}-${day}-${year}`
    case 'Y/m/d':
      return `${year}/${month}/${day}`
    case 'DD-MM-YYYY':
      return `${day}-${month}-${year}`
    case 'DD/MM/YYYY':
      return `${day}/${month}/${year}`
    case 'MM-DD-YYYY':
      return `${month}-${day}-${year}`
    case 'MM/DD/YYYY':
      return `${month}/${day}/${year}`
    case 'YYYY-MM-DD':
      return `${year}-${month}-${day}`
    case 'YYYY/MM/DD':
      return `${year}/${month}/${day}`
    case 'M d, Y':
      const months = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
      ]
      return `${months[date.getMonth()]} ${day}, ${year}`
    case 'F j, Y':
      const fullMonths = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ]
      return `${fullMonths[date.getMonth()]} ${parseInt(day)}, ${year}`
    default:
      return date.toLocaleDateString()
  }
}

const formatDateWithCustomFormat = (date, format) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  // Handle common Nova picker formats
  switch (format) {
    case 'd-m-Y':
      return `${day}-${month}-${year}`
    case 'd/m/Y':
      return `${day}/${month}/${year}`
    case 'm-d-Y':
      return `${month}-${day}-${year}`
    case 'm/d/Y':
      return `${month}/${day}/${year}`
    case 'Y-m-d':
      return `${year}-${month}-${day}`
    case 'Y/m/d':
      return `${year}/${month}/${day}`
    default:
      // Fallback to standard format
      return formatDate(date, format)
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

<style scoped>
/* Ensure proper spacing for icons and buttons */
.pr-10 {
  padding-right: 2.5rem;
}

.right-8 {
  right: 2rem;
}

/* Ensure custom picker appears above other elements */
.z-10 {
  z-index: 10;
}

/* Smooth transitions for hover effects */
button {
  transition: background-color 0.15s ease-in-out;
}
</style>
