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
        :type="inputType"
        :value="modelValue"
        :placeholder="field.placeholder || field.name"
        :maxlength="field.maxLength"
        :disabled="disabled"
        :readonly="readonly"
        class="admin-input w-full"
        :class="[
          { 'admin-input-dark': isDarkTheme },
          { 'pr-10': showSuggestions && suggestions.length > 0 }
        ]"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
      />

      <!-- Suggestions dropdown button -->
      <button
        v-if="showSuggestions && suggestions.length > 0"
        type="button"
        class="absolute inset-y-0 right-0 pr-3 flex items-center"
        @click="toggleSuggestions"
      >
        <ChevronDownIcon class="h-5 w-5 text-gray-400" />
      </button>

      <!-- Character count -->
      <div
        v-if="field.maxLength && showCharacterCount"
        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
        :class="{ 'pr-10': showSuggestions && suggestions.length > 0 }"
      >
        <span
          class="text-xs"
          :class="characterCountClasses"
        >
          {{ characterCount }}/{{ field.maxLength }}
        </span>
      </div>
    </div>

    <!-- Suggestions dropdown -->
    <div
      v-if="showSuggestionsDropdown"
      class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
      :class="{ 'bg-gray-800 ring-gray-700': isDarkTheme }"
    >
      <div
        v-for="(suggestion, index) in filteredSuggestions"
        :key="index"
        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100"
        :class="{ 'hover:bg-gray-700': isDarkTheme }"
        @click="selectSuggestion(suggestion)"
      >
        <span class="block truncate" :class="{ 'text-white': isDarkTheme }">
          {{ suggestion }}
        </span>
      </div>
      <div
        v-if="filteredSuggestions.length === 0"
        class="cursor-default select-none relative py-2 pl-3 pr-9 text-gray-500"
        :class="{ 'text-gray-400': isDarkTheme }"
      >
        No suggestions found
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * TextField Component
 * 
 * Text input field with support for suggestions, character limits,
 * and password mode functionality.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, nextTick } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, Number],
    default: ''
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
const inputRef = ref(null)
const showSuggestionsDropdown = ref(false)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `text-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const inputType = computed(() => {
  return props.field.asPassword ? 'password' : 'text'
})

const suggestions = computed(() => {
  return props.field.suggestions || []
})

const showSuggestions = computed(() => {
  return suggestions.value.length > 0 && !props.field.asPassword
})

const showCharacterCount = computed(() => {
  return props.field.maxLength && !props.field.asPassword
})

const characterCount = computed(() => {
  return String(props.modelValue || '').length
})

const characterCountClasses = computed(() => {
  const count = characterCount.value
  const max = props.field.maxLength
  
  if (count > max * 0.9) {
    return 'text-red-500'
  } else if (count > max * 0.7) {
    return 'text-amber-500'
  }
  return 'text-gray-500'
})

const filteredSuggestions = computed(() => {
  if (!props.modelValue) return suggestions.value
  
  const query = String(props.modelValue).toLowerCase()
  return suggestions.value.filter(suggestion =>
    suggestion.toLowerCase().includes(query)
  )
})

// Methods
const handleInput = (event) => {
  let value = event.target.value
  
  // Apply maxLength if specified
  if (props.field.maxLength && value.length > props.field.maxLength) {
    value = value.substring(0, props.field.maxLength)
    event.target.value = value
  }
  
  emit('update:modelValue', value)
  emit('change', value)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
  // Hide suggestions after a delay to allow for clicks
  setTimeout(() => {
    showSuggestionsDropdown.value = false
  }, 200)
}

const handleKeydown = (event) => {
  if (event.key === 'ArrowDown' && showSuggestions.value) {
    event.preventDefault()
    showSuggestionsDropdown.value = true
  } else if (event.key === 'Escape') {
    showSuggestionsDropdown.value = false
    inputRef.value?.blur()
  }
}

const toggleSuggestions = () => {
  showSuggestionsDropdown.value = !showSuggestionsDropdown.value
  if (showSuggestionsDropdown.value) {
    nextTick(() => {
      inputRef.value?.focus()
    })
  }
}

const selectSuggestion = (suggestion) => {
  emit('update:modelValue', suggestion)
  emit('change', suggestion)
  showSuggestionsDropdown.value = false
  nextTick(() => {
    inputRef.value?.focus()
  })
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
.relative {
  position: relative;
}

/* Ensure suggestions dropdown appears above other elements */
.z-10 {
  z-index: 10;
}
</style>
