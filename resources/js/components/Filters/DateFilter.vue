<template>
  <BaseFilter
    :filter="filter"
    :model-value="modelValue"
    :show-label="showLabel"
    :show-clear-button="showClearButton"
    :size="size"
    v-bind="$attrs"
    @update:model-value="updateValue"
    @clear="clearFilter"
  >
    <div class="relative">
      <input
        :id="filterId"
        ref="inputRef"
        type="date"
        :value="formattedValue"
        class="w-full pr-10 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        :class="{ 'bg-gray-700 border-gray-600 text-white focus:ring-blue-400 focus:border-blue-400': isDarkTheme }"
        @input="handleInput"
        @change="handleChange"
        @focus="handleFocus"
        @blur="handleBlur"
      />

      <!-- Calendar icon -->
      <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
        <CalendarDaysIcon class="h-5 w-5 text-gray-400" />
      </div>
    </div>

    <!-- Operator display -->
    <div
      v-if="filter.operator && filter.operator !== '='"
      class="mt-1 text-xs text-gray-500"
      :class="{ 'text-gray-400': isDarkTheme }"
    >
      {{ operatorText }}
    </div>
  </BaseFilter>
</template>

<script setup>
/**
 * DateFilter Component
 *
 * Date input filter component with support for different comparison
 * operators and date-only or datetime filtering.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { CalendarDaysIcon } from '@heroicons/vue/24/outline'
import BaseFilter from './BaseFilter.vue'

// Props
const props = defineProps({
  filter: {
    type: Object,
    required: true
  },
  modelValue: {
    type: String,
    default: null
  },
  showLabel: {
    type: Boolean,
    default: true
  },
  showClearButton: {
    type: Boolean,
    default: true
  },
  size: {
    type: String,
    default: 'default'
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'change', 'focus', 'blur'])

// Refs
const inputRef = ref(null)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const filterId = computed(() => {
  return `date-filter-${props.filter.key}-${Math.random().toString(36).substr(2, 9)}`
})

const formattedValue = computed(() => {
  if (!props.modelValue) return ''

  try {
    const date = new Date(props.modelValue)
    if (isNaN(date.getTime())) return props.modelValue

    // Format for HTML date input (YYYY-MM-DD)
    return date.toISOString().split('T')[0]
  } catch (error) {
    return props.modelValue
  }
})

const operatorText = computed(() => {
  const operator = props.filter.operator || '='

  switch (operator) {
    case '>':
      return 'After this date'
    case '>=':
      return 'On or after this date'
    case '<':
      return 'Before this date'
    case '<=':
      return 'On or before this date'
    case '=':
    default:
      return 'On this date'
  }
})

// Methods
const handleInput = (event) => {
  const value = event.target.value || null
  updateValue(value)
}

const handleChange = (event) => {
  const value = event.target.value || null
  emit('change', value)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const updateValue = (value) => {
  emit('update:modelValue', value)
}

const clearFilter = () => {
  emit('update:modelValue', null)
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
/* Ensure proper spacing for icon */
.pr-10 {
  padding-right: 2.5rem;
}

/* Date input calendar icon styling */
input[type="date"]::-webkit-calendar-picker-indicator {
  opacity: 0;
  position: absolute;
  right: 0;
  width: 2.5rem;
  height: 100%;
  cursor: pointer;
}

/* Firefox date input styling */
input[type="date"]::-moz-focus-inner {
  border: 0;
}
</style>
