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
      <!-- Simple select (non-searchable) -->
      <select
        v-if="!field.searchable"
        :id="fieldId"
        ref="selectRef"
        :value="modelValue"
        :disabled="disabled"
        class="admin-select w-full"
        :class="{ 'admin-input-dark': isDarkTheme }"
        @change="handleChange"
        @focus="handleFocus"
        @blur="handleBlur"
      >
        <option value="" disabled>
          {{ field.placeholder || `Select ${field.name}` }}
        </option>
        <option
          v-for="(label, value) in field.options"
          :key="value"
          :value="value"
        >
          {{ label }}
        </option>
      </select>

      <!-- Searchable select -->
      <div v-else class="relative">
        <button
          :id="fieldId"
          ref="buttonRef"
          type="button"
          class="admin-input w-full text-left flex items-center justify-between"
          :class="{ 'admin-input-dark': isDarkTheme }"
          :disabled="disabled"
          @click="toggleDropdown"
          @focus="handleFocus"
          @blur="handleBlur"
        >
          <span :class="{ 'text-gray-500': !selectedLabel }">
            {{ selectedLabel || field.placeholder || `Select ${field.name}` }}
          </span>
          <ChevronDownIcon class="h-5 w-5 text-gray-400" />
        </button>

        <!-- Searchable dropdown -->
        <div
          v-if="showDropdown"
          class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black/5 overflow-hidden focus:outline-none sm:text-sm"
          :class="{ 'bg-gray-800 ring-gray-700': isDarkTheme }"
        >
          <!-- Search input -->
          <div class="sticky top-0 bg-white px-2 py-2" :class="{ 'bg-gray-800': isDarkTheme }">
            <input
              ref="searchRef"
              v-model="searchQuery"
              type="text"
              placeholder="Search options..."
              class="admin-input w-full text-sm"
              :class="{ 'admin-input-dark': isDarkTheme }"
              @keydown="handleSearchKeydown"
            />
          </div>

          <!-- Options list -->
          <div class="max-h-48 overflow-auto">
            <div
              v-for="(option, index) in filteredOptions"
              :key="option.value"
              class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100"
              :class="[
                { 'hover:bg-gray-700': isDarkTheme },
                { 'bg-blue-100 text-blue-900': option.value === modelValue && !isDarkTheme },
                { 'bg-blue-900 text-blue-200': option.value === modelValue && isDarkTheme },
                { 'bg-gray-100': index === highlightedIndex && !isDarkTheme },
                { 'bg-gray-700': index === highlightedIndex && isDarkTheme }
              ]"
              @click="selectOption(option.value)"
              @mouseenter="highlightedIndex = index"
            >
              <span class="block truncate" :class="{ 'text-white': isDarkTheme }">
                {{ option.label }}
              </span>
              <CheckIcon
                v-if="option.value === modelValue"
                class="absolute inset-y-0 right-0 flex items-center pr-4 h-5 w-5 text-blue-600"
                :class="{ 'text-blue-400': isDarkTheme }"
              />
            </div>
            <div
              v-if="filteredOptions.length === 0"
              class="cursor-default select-none relative py-2 pl-3 pr-9 text-gray-500"
              :class="{ 'text-gray-400': isDarkTheme }"
            >
              No options found
            </div>
          </div>
        </div>
      </div>

      <!-- Clear button -->
      <button
        v-if="modelValue && !disabled && !readonly"
        type="button"
        class="absolute inset-y-0 right-8 flex items-center pr-2"
        @click="clearSelection"
      >
        <XMarkIcon class="h-4 w-4 text-gray-400 hover:text-gray-600" />
      </button>
    </div>

    <!-- Display selected value for readonly -->
    <div
      v-if="readonly && selectedLabel"
      class="mt-2 text-sm text-gray-600"
      :class="{ 'text-gray-400': isDarkTheme }"
    >
      Selected: {{ selectedLabel }}
    </div>
  </BaseField>
</template>

<script setup>
/**
 * SelectField Component
 * 
 * Select dropdown field with support for searchable options,
 * Enum integration, and custom option formatting.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { ChevronDownIcon, CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'

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
    default: false
  },
  size: {
    type: String,
    default: 'default'
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Refs
const selectRef = ref(null)
const buttonRef = ref(null)
const searchRef = ref(null)
const showDropdown = ref(false)
const searchQuery = ref('')
const highlightedIndex = ref(-1)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `select-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const options = computed(() => {
  const opts = []
  for (const [value, label] of Object.entries(props.field.options || {})) {
    opts.push({ value, label })
  }
  return opts
})

const filteredOptions = computed(() => {
  if (!searchQuery.value) return options.value
  
  const query = searchQuery.value.toLowerCase()
  return options.value.filter(option =>
    option.label.toLowerCase().includes(query) ||
    String(option.value).toLowerCase().includes(query)
  )
})

const selectedLabel = computed(() => {
  if (!props.modelValue) return null
  
  const option = options.value.find(opt => opt.value == props.modelValue)
  return option?.label || props.modelValue
})

// Methods
const handleChange = (event) => {
  const value = event.target.value
  emit('update:modelValue', value || null)
  emit('change', value || null)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
  // Close dropdown after a delay to allow for clicks
  setTimeout(() => {
    showDropdown.value = false
  }, 200)
}

const toggleDropdown = () => {
  if (props.disabled || props.readonly) return
  
  showDropdown.value = !showDropdown.value
  
  if (showDropdown.value) {
    nextTick(() => {
      searchRef.value?.focus()
      highlightedIndex.value = -1
    })
  }
}

const selectOption = (value) => {
  emit('update:modelValue', value)
  emit('change', value)
  showDropdown.value = false
  searchQuery.value = ''
  
  nextTick(() => {
    buttonRef.value?.focus()
  })
}

const clearSelection = () => {
  emit('update:modelValue', null)
  emit('change', null)
}

const handleSearchKeydown = (event) => {
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1)
      break
    case 'Enter':
      event.preventDefault()
      if (highlightedIndex.value >= 0 && filteredOptions.value[highlightedIndex.value]) {
        selectOption(filteredOptions.value[highlightedIndex.value].value)
      }
      break
    case 'Escape':
      showDropdown.value = false
      buttonRef.value?.focus()
      break
  }
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!buttonRef.value?.contains(event.target) && !searchRef.value?.contains(event.target)) {
    showDropdown.value = false
  }
}

// Focus method for external use
const focus = () => {
  if (props.field.searchable) {
    buttonRef.value?.focus()
  } else {
    selectRef.value?.focus()
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

defineExpose({
  focus
})
</script>

<style scoped>
/* Ensure dropdown appears above other elements */
.z-10 {
  z-index: 10;
}

/* Smooth transitions for dropdown */
.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Ensure proper spacing for clear button */
.right-8 {
  right: 2rem;
}
</style>
