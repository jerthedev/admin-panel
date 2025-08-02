<template>
  <div class="filter-wrapper" :class="filterWrapperClasses">
    <!-- Filter label -->
    <label
      v-if="showLabel"
      :for="filterId"
      class="block text-sm font-medium text-gray-700 mb-1"
      :class="{ 'text-gray-300': isDarkTheme }"
    >
      {{ filter.name }}
    </label>

    <!-- Filter content slot -->
    <div class="filter-content">
      <slot />
    </div>

    <!-- Clear button -->
    <button
      v-if="showClearButton && hasValue"
      type="button"
      class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
      :class="{ 'hover:text-gray-300': isDarkTheme }"
      @click="clearFilter"
    >
      <XMarkIcon class="h-4 w-4" />
    </button>
  </div>
</template>

<script setup>
/**
 * BaseFilter Component
 * 
 * Base component for all filter types providing common functionality
 * like labels, clear buttons, and consistent styling.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, inject } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { XMarkIcon } from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  filter: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, Number, Boolean, Array, Object],
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
    default: 'default',
    validator: (value) => ['small', 'default', 'large'].includes(value)
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'clear'])

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const filterId = computed(() => {
  return `filter-${props.filter.key}-${Math.random().toString(36).substr(2, 9)}`
})

const hasValue = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
    return false
  }
  
  if (Array.isArray(props.modelValue)) {
    return props.modelValue.length > 0
  }
  
  if (typeof props.modelValue === 'object') {
    return Object.keys(props.modelValue).length > 0
  }
  
  return true
})

const filterWrapperClasses = computed(() => {
  return [
    'filter-wrapper relative',
    {
      'filter-size-small': props.size === 'small',
      'filter-size-large': props.size === 'large'
    }
  ]
})

// Methods
const clearFilter = () => {
  emit('update:modelValue', null)
  emit('clear')
}

// Provide methods to child components
defineExpose({
  filterId,
  clearFilter,
  hasValue
})
</script>

<style scoped>
.filter-wrapper {
}

.filter-wrapper.filter-size-small {
}

.filter-wrapper.filter-size-large {
}

.filter-content {
}

/* Clear button positioning */
.filter-wrapper:has(.absolute) .filter-content input,
.filter-wrapper:has(.absolute) .filter-content select {
}
</style>
