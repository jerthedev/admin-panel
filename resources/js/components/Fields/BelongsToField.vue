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
      <!-- Relationship select dropdown -->
      <div
        ref="dropdownRef"
        class="relative"
      >
        <!-- Selected item display / Search input -->
        <div
          class="admin-input min-h-[2.5rem] p-2 cursor-pointer flex items-center justify-between"
          :class="[
            { 'admin-input-dark': isDarkTheme },
            { 'border-red-300': hasError },
            { 'opacity-50 cursor-not-allowed': disabled || readonly }
          ]"
          @click="toggleDropdown"
        >
          <!-- Selected item or search input -->
          <div class="flex-1">
            <input
              v-if="field.searchable && isOpen"
              ref="searchInputRef"
              v-model="searchQuery"
              type="text"
              class="w-full border-none outline-none bg-transparent text-sm"
              :placeholder="selectedLabel || 'Search...'"
              @click.stop
              @keydown="handleKeydown"
            />
            <span
              v-else
              class="text-sm"
              :class="{ 'text-gray-500': !selectedLabel, 'text-gray-400': !selectedLabel && isDarkTheme }"
            >
              {{ selectedLabel || field.placeholder || 'Select an option...' }}
            </span>
          </div>

          <!-- Clear button -->
          <button
            v-if="selectedLabel && !disabled && !readonly"
            type="button"
            class="mr-2 text-gray-400 hover:text-gray-600"
            :class="{ 'text-gray-500 hover:text-gray-300': isDarkTheme }"
            @click.stop="clearSelection"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>

          <!-- Dropdown arrow -->
          <ChevronDownIcon
            class="w-5 h-5 text-gray-400 transition-transform duration-200"
            :class="{ 'transform rotate-180': isOpen }"
          />
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
            <!-- Loading state -->
            <div
              v-if="loading"
              class="px-3 py-2 text-sm text-gray-500"
              :class="{ 'text-gray-400': isDarkTheme }"
            >
              Loading...
            </div>

            <!-- No results message -->
            <div
              v-else-if="filteredOptions.length === 0"
              class="px-3 py-2 text-sm text-gray-500"
              :class="{ 'text-gray-400': isDarkTheme }"
            >
              No options found
            </div>

            <!-- Options list -->
            <div
              v-else
              v-for="option in filteredOptions"
              :key="option.value"
              class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 flex items-center justify-between"
              :class="[
                { 'hover:bg-gray-700': isDarkTheme },
                { 'bg-blue-50 text-blue-700': isSelected(option.value) && !isDarkTheme },
                { 'bg-blue-900 text-blue-200': isSelected(option.value) && isDarkTheme }
              ]"
              @click="selectOption(option)"
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

      <!-- Create button -->
      <div
        v-if="field.showCreateButton && !readonly && !disabled"
        class="mt-2"
      >
        <button
          type="button"
          class="text-sm text-blue-600 hover:text-blue-700 font-medium"
          :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
          @click="showCreateModal"
        >
          + Create New {{ field.name }}
        </button>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * BelongsToField Component
 *
 * Many-to-one relationship field with dropdown selection and search capabilities.
 * Supports creating new related models and custom display options.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
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
    type: [String, Number],
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
const dropdownRef = ref(null)
const searchInputRef = ref(null)
const isOpen = ref(false)
const searchQuery = ref('')
const loading = ref(false)
const options = ref([])

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const hasError = computed(() => {
  return props.errors && Object.keys(props.errors).length > 0
})

const selectedLabel = computed(() => {
  if (!props.modelValue) return null

  const option = options.value.find(opt => opt.value == props.modelValue)
  return option ? option.label : null
})

const filteredOptions = computed(() => {
  if (!props.field.searchable || !searchQuery.value) {
    return options.value
  }

  const query = searchQuery.value.toLowerCase()
  return options.value.filter(option =>
    option.label.toLowerCase().includes(query)
  )
})

// Methods
const isSelected = (value) => {
  return props.modelValue == value
}

const toggleDropdown = async () => {
  if (props.disabled || props.readonly) return

  isOpen.value = !isOpen.value

  if (isOpen.value) {
    await loadOptions()

    if (props.field.searchable) {
      nextTick(() => {
        searchInputRef.value?.focus()
      })
    }
  }
}

const selectOption = (option) => {
  if (props.disabled || props.readonly) return

  emit('update:modelValue', option.value)
  emit('change', option.value)

  isOpen.value = false
  searchQuery.value = ''
}

const clearSelection = () => {
  if (props.disabled || props.readonly) return

  emit('update:modelValue', null)
  emit('change', null)
}

const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    isOpen.value = false
    searchQuery.value = ''
  }
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false
    searchQuery.value = ''
  }
}

const loadOptions = async () => {
  if (options.value.length > 0) return // Already loaded

  loading.value = true

  try {
    // Make real API call to get options
    const response = await fetch(`/admin-panel/api/fields/belongs-to/options`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        field: props.field,
        search: searchQuery.value,
      }),
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()
    options.value = data.options || []
  } catch (error) {
    console.error('Failed to load options:', error)
    options.value = []
  } finally {
    loading.value = false
  }
}

const showCreateModal = () => {
  // Emit event to parent component to handle modal creation
  // This allows the parent to manage the modal state and creation process
  emit('show-create-modal', {
    resourceClass: props.field.resourceClass,
    modalSize: props.field.modalSize || 'md',
    onCreated: (newResource) => {
      // Add the newly created resource to options and select it
      const newOption = {
        value: newResource.id,
        label: newResource.title || newResource.name,
      }
      options.value.push(newOption)
      selectedValue.value = newResource.id
      emit('update:modelValue', newResource.id)
    }
  })
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Watch for search changes
watch(searchQuery, (newQuery) => {
  // In a real implementation, this could trigger server-side search
  // For now, we just filter client-side
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
