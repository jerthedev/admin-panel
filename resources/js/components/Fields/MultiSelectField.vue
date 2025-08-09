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
      <!-- Multi-select dropdown -->
      <div
        ref="dropdownRef"
        class="relative"
      >
        <!-- Selected items display / Input -->
        <div
          class="admin-input min-h-[2.5rem] p-2 cursor-pointer"
          :class="[
            { 'admin-input-dark': isDarkTheme },
            { 'border-red-300': hasError },
            { 'opacity-50 cursor-not-allowed': disabled || readonly }
          ]"
          @click="toggleDropdown"
        >
          <!-- Selected tags -->
          <div
            v-if="selectedItems.length > 0"
            class="flex flex-wrap gap-1 mb-1"
          >
            <span
              v-for="(item, index) in selectedItems"
              :key="index"
              class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800"
              :class="{ 'bg-blue-800 text-blue-100': isDarkTheme }"
            >
              {{ getItemLabel(item) }}
              <button
                v-if="!disabled && !readonly"
                type="button"
                class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-blue-200"
                :class="{ 'hover:bg-blue-700': isDarkTheme }"
                @click.stop="removeItem(item)"
              >
                <XMarkIcon class="w-3 h-3" />
              </button>
            </span>
          </div>

          <!-- Search input -->
          <input
            v-if="field.searchable && isOpen"
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            class="w-full border-none outline-none bg-transparent text-sm"
            :placeholder="selectedItems.length === 0 ? (field.placeholder || 'Select options...') : 'Search...'"
            @keydown="handleKeydown"
            @click.stop
          />

          <!-- Placeholder when no items selected and not searchable -->
          <div
            v-else-if="selectedItems.length === 0"
            class="text-gray-500 text-sm"
            :class="{ 'text-gray-400': isDarkTheme }"
          >
            {{ field.placeholder || 'Select options...' }}
          </div>

          <!-- Dropdown arrow -->
          <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
            <ChevronDownIcon
              class="w-5 h-5 text-gray-400 transition-transform duration-200"
              :class="{ 'transform rotate-180': isOpen }"
            />
          </div>
        </div>

        <!-- Dropdown menu -->
        <Transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="transform opacity-0 scale-95"
          enter-to-class="transform opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="transform opacity-100 scale-100"
          leave-to-class="transform opacity-0 scale-95"
        >
          <div
            v-if="isOpen"
            class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
            :class="{ 'bg-gray-800 border-gray-600': isDarkTheme }"
          >
            <!-- No options message -->
            <div
              v-if="filteredOptions.length === 0 && !field.taggable"
              class="px-3 py-2 text-sm text-gray-500"
              :class="{ 'text-gray-400': isDarkTheme }"
            >
              No options available
            </div>

            <!-- Create new tag option -->
            <div
              v-else-if="field.taggable && searchQuery && !optionExists(searchQuery)"
              class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100"
              :class="{ 'hover:bg-gray-700': isDarkTheme }"
              @click="createTag(searchQuery)"
            >
              <span class="text-blue-600" :class="{ 'text-blue-400': isDarkTheme }">
                Create "{{ searchQuery }}"
              </span>
            </div>

            <!-- Options list -->
            <div
              v-for="option in filteredOptions"
              :key="option.value"
              class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 flex items-center justify-between"
              :class="[
                { 'hover:bg-gray-700': isDarkTheme },
                { 'bg-blue-50 text-blue-700': isSelected(option.value) && !isDarkTheme },
                { 'bg-blue-900 text-blue-200': isSelected(option.value) && isDarkTheme }
              ]"
              @click="toggleOption(option.value)"
            >
              <span>{{ option.label }}</span>
              <CheckIcon
                v-if="isSelected(option.value)"
                class="w-4 h-4 text-blue-600"
                :class="{ 'text-blue-400': isDarkTheme }"
              />
            </div>
          </div>
        </Transition>
      </div>

      <!-- Max selections warning -->
      <div
        v-if="field.maxSelections && selectedItems.length >= field.maxSelections"
        class="mt-1 text-xs text-amber-600"
        :class="{ 'text-amber-400': isDarkTheme }"
      >
        Maximum {{ field.maxSelections }} selections allowed
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * MultiSelectField Component
 * 
 * Multi-select dropdown field with tagging interface and searchable options.
 * Supports creating new tags and enforcing maximum selection limits.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { ChevronDownIcon, XMarkIcon, CheckIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: Array,
    default: () => []
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
const dropdownRef = ref(null)
const searchInputRef = ref(null)
const isOpen = ref(false)
const searchQuery = ref('')

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const hasError = computed(() => {
  return props.errors && Object.keys(props.errors).length > 0
})

const selectedItems = computed(() => {
  return Array.isArray(props.modelValue) ? props.modelValue : []
})

const options = computed(() => {
  const opts = props.field.options || {}
  return Object.entries(opts).map(([value, label]) => ({ value, label }))
})

const filteredOptions = computed(() => {
  if (!props.field.searchable || !searchQuery.value) {
    return options.value
  }
  
  const query = searchQuery.value.toLowerCase()
  return options.value.filter(option => 
    option.label.toLowerCase().includes(query) ||
    option.value.toLowerCase().includes(query)
  )
})

// Methods
const getItemLabel = (value) => {
  const option = options.value.find(opt => opt.value === value)
  return option ? option.label : value
}

const isSelected = (value) => {
  return selectedItems.value.includes(value)
}

const optionExists = (value) => {
  return options.value.some(opt => 
    opt.value.toLowerCase() === value.toLowerCase() ||
    opt.label.toLowerCase() === value.toLowerCase()
  )
}

const toggleDropdown = () => {
  if (props.disabled || props.readonly) return
  
  isOpen.value = !isOpen.value
  
  if (isOpen.value && props.field.searchable) {
    nextTick(() => {
      searchInputRef.value?.focus()
    })
  }
}

const toggleOption = (value) => {
  if (props.disabled || props.readonly) return
  
  const newValue = [...selectedItems.value]
  const index = newValue.indexOf(value)
  
  if (index > -1) {
    newValue.splice(index, 1)
  } else {
    // Check max selections limit
    if (props.field.maxSelections && newValue.length >= props.field.maxSelections) {
      return
    }
    newValue.push(value)
  }
  
  emit('update:modelValue', newValue)
  emit('change', newValue)
  
  // Clear search after selection
  searchQuery.value = ''
}

const removeItem = (value) => {
  if (props.disabled || props.readonly) return
  
  const newValue = selectedItems.value.filter(item => item !== value)
  emit('update:modelValue', newValue)
  emit('change', newValue)
}

const createTag = (value) => {
  if (props.disabled || props.readonly || !props.field.taggable) return
  
  const trimmedValue = value.trim()
  if (!trimmedValue || isSelected(trimmedValue)) return
  
  // Check max selections limit
  if (props.field.maxSelections && selectedItems.value.length >= props.field.maxSelections) {
    return
  }
  
  const newValue = [...selectedItems.value, trimmedValue]
  emit('update:modelValue', newValue)
  emit('change', newValue)
  
  searchQuery.value = ''
}

const handleKeydown = (event) => {
  if (event.key === 'Enter' && props.field.taggable && searchQuery.value) {
    event.preventDefault()
    createTag(searchQuery.value)
  } else if (event.key === 'Escape') {
    isOpen.value = false
  }
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false
    searchQuery.value = ''
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Ensure proper z-index for dropdown */
.z-10 {
  z-index: 10;
}

/* Smooth transitions */
.transition {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
