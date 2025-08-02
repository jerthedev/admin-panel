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
        type="text"
        :value="modelValue"
        :placeholder="placeholder"
        class="w-full pl-10 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        :class="{ 'bg-gray-700 border-gray-600 text-white focus:ring-blue-400 focus:border-blue-400': isDarkTheme }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
      />

      <!-- Search icon -->
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
      </div>
    </div>

    <!-- Search info -->
    <div
      v-if="showSearchInfo"
      class="mt-1 text-xs text-gray-500"
      :class="{ 'text-gray-400': isDarkTheme }"
    >
      {{ searchInfoText }}
    </div>
  </BaseFilter>
</template>

<script setup>
/**
 * TextFilter Component
 *
 * Text input filter component with support for different search
 * operators, wildcards, and multi-column searching.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import BaseFilter from './BaseFilter.vue'

// Props
const props = defineProps({
  filter: {
    type: Object,
    required: true
  },
  modelValue: {
    type: String,
    default: ''
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
  },
  placeholder: {
    type: String,
    default: null
  },
  debounce: {
    type: Number,
    default: 300
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'change', 'focus', 'blur'])

// Refs
const inputRef = ref(null)

// Store
const adminStore = useAdminStore()

// Local debounce utility
const debounce = (func, wait = 300) => {
  let timeout
  return (...args) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => func(...args), wait)
  }
}

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const filterId = computed(() => {
  return `text-filter-${props.filter.key}-${Math.random().toString(36).substr(2, 9)}`
})

const placeholder = computed(() => {
  return props.placeholder || props.filter.options?.placeholder || `Search ${props.filter.name}...`
})

const showSearchInfo = computed(() => {
  return props.filter.operator && props.filter.operator !== 'LIKE'
})

const searchInfoText = computed(() => {
  const operator = props.filter.operator || 'LIKE'
  const wildcards = props.filter.wildcards !== false

  switch (operator) {
    case '=':
      return 'Exact match'
    case 'LIKE':
      return wildcards ? 'Contains text' : 'Exact match'
    default:
      return `Search using ${operator}`
  }
})

// Debounced input handler
const debouncedInput = debounce((value) => {
  emit('change', value)
}, props.debounce)

// Methods
const handleInput = (event) => {
  const value = event.target.value
  updateValue(value)
  debouncedInput(value)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const handleKeydown = (event) => {
  // Handle Enter key
  if (event.key === 'Enter') {
    event.preventDefault()
    emit('change', props.modelValue)
  }

  // Handle Escape key
  if (event.key === 'Escape') {
    clearFilter()
    inputRef.value?.blur()
  }
}

const updateValue = (value) => {
  emit('update:modelValue', value)
}

const clearFilter = () => {
  emit('update:modelValue', '')
  emit('change', '')
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
.pl-10 {
  padding-left: 2.5rem;
}
</style>
