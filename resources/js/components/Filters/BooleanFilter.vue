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
    <div class="flex items-center space-x-4">
      <!-- True option -->
      <label class="flex items-center cursor-pointer">
        <input
          :id="`${filterId}-true`"
          ref="trueRef"
          type="radio"
          :name="filterId"
          value="true"
          :checked="modelValue === 'true'"
          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
          :class="{ 'border-gray-600 bg-gray-700': isDarkTheme }"
          @change="handleChange"
          @focus="handleFocus"
          @blur="handleBlur"
        />
        <span class="ml-2 text-sm text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
          {{ trueLabel }}
        </span>
      </label>

      <!-- False option -->
      <label class="flex items-center cursor-pointer">
        <input
          :id="`${filterId}-false`"
          ref="falseRef"
          type="radio"
          :name="filterId"
          value="false"
          :checked="modelValue === 'false'"
          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
          :class="{ 'border-gray-600 bg-gray-700': isDarkTheme }"
          @change="handleChange"
          @focus="handleFocus"
          @blur="handleBlur"
        />
        <span class="ml-2 text-sm text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
          {{ falseLabel }}
        </span>
      </label>
    </div>
  </BaseFilter>
</template>

<script setup>
/**
 * BooleanFilter Component
 * 
 * Radio button filter component for true/false selections
 * with customizable labels for different boolean contexts.
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
const trueRef = ref(null)
const falseRef = ref(null)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const filterId = computed(() => {
  return `boolean-filter-${props.filter.key}-${Math.random().toString(36).substr(2, 9)}`
})

const trueLabel = computed(() => {
  return props.filter.options?.true || 'Yes'
})

const falseLabel = computed(() => {
  return props.filter.options?.false || 'No'
})

// Methods
const handleChange = (event) => {
  const value = event.target.value
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
  trueRef.value?.focus()
}

defineExpose({
  focus
})
</script>

<style scoped>
/* Radio button styling */
input[type="radio"] {
}

input[type="radio"]:focus {
}

.dark input[type="radio"]:focus {
}

/* Label hover effects */
label:hover input[type="radio"] {
}

label:hover span {
}

.dark label:hover span {
}

/* Cursor pointer for entire label */
label {
}
</style>
