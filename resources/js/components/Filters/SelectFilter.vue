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
    <select
      :id="filterId"
      ref="selectRef"
      :value="modelValue"
      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
      :class="{ 'bg-gray-700 border-gray-600 text-white focus:ring-blue-400 focus:border-blue-400': isDarkTheme }"
      @change="handleChange"
      @focus="handleFocus"
      @blur="handleBlur"
    >
      <option value="">
        {{ placeholder }}
      </option>
      <option
        v-for="(label, value) in filter.options"
        :key="value"
        :value="value"
      >
        {{ label }}
      </option>
    </select>
  </BaseFilter>
</template>

<script setup>
/**
 * SelectFilter Component
 *
 * Dropdown filter component for selecting from predefined options
 * with support for enums, relationships, and custom options.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import BaseFilter from './BaseFilter.vue'

// Props
const props = defineProps({
  filter: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, Number],
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
  },
  placeholder: {
    type: String,
    default: null
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'change', 'focus', 'blur'])

// Refs
const selectRef = ref(null)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const filterId = computed(() => {
  return `select-filter-${props.filter.key}-${Math.random().toString(36).substr(2, 9)}`
})

const placeholder = computed(() => {
  return props.placeholder || `Select ${props.filter.name}`
})

// Methods
const handleChange = (event) => {
  const value = event.target.value || null
  updateValue(value)
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
  selectRef.value?.focus()
}

defineExpose({
  focus
})
</script>

<style scoped>
/* Custom select styling */
.admin-select {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
  padding-right: 2.5rem;
}

.admin-select:focus {
}

.admin-input-dark {
}

.admin-input-dark:focus {
}

/* Dark theme select arrow */
.dark .admin-select {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%9ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
}
</style>
