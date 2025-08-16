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
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
          {{ field.name }}
        </h3>
        
        <!-- Status Badge -->
        <span
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          :class="statusBadgeClasses"
        >
          {{ statusText }}
        </span>
      </div>

      <!-- Polymorphic Relationship Info -->
      <div 
        class="flex items-center space-x-2 text-sm text-gray-600"
        :class="{ 'text-gray-400': isDarkTheme }"
      >
        <InformationCircleIcon class="w-4 h-4" data-testid="info-icon" />
        <span>This is a polymorphic inverse relationship</span>
        <span v-if="field.types.length > 0" class="text-xs bg-gray-100 px-2 py-1 rounded" :class="{ 'bg-gray-800 text-gray-300': isDarkTheme }">
          {{ field.types.length }} type{{ field.types.length === 1 ? '' : 's' }}
        </span>
      </div>

      <!-- Type Selector -->
      <div v-if="!readonly && !disabled && field.types.length > 1" class="space-y-2">
        <label class="block text-sm font-medium text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
          Select Type
        </label>
        <select
          v-model="selectedType"
          class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          :class="{ 'border-gray-600 bg-gray-800 text-gray-100': isDarkTheme }"
          @change="onTypeChange"
        >
          <option value="">Select a type...</option>
          <option
            v-for="type in field.types"
            :key="type"
            :value="type"
          >
            {{ getTypeLabel(type) }}
          </option>
        </select>
      </div>

      <!-- Related Model Display -->
      <div v-if="hasRelatedModel" class="border rounded-lg p-4" :class="relatedModelClasses">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <DocumentIcon class="w-8 h-8 text-blue-500" />
            <div>
              <h4 class="font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
                {{ relatedModelTitle }}
              </h4>
              <p class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                {{ modelValue.resource_class || modelValue.morph_type }}
              </p>
              <p v-if="field.morphType" class="text-xs text-gray-400" :class="{ 'text-gray-500': isDarkTheme }">
                Polymorphic type: {{ field.morphType }}
              </p>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div v-if="!readonly" class="flex items-center space-x-2">
            <button
              v-if="field.peekable"
              type="button"
              class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
              @click="peekRelatedModel"
            >
              <EyeIcon class="w-4 h-4 mr-1" />
              Peek
            </button>
            
            <button
              type="button"
              class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
              @click="viewRelatedModel"
            >
              <ArrowTopRightOnSquareIcon class="w-4 h-4 mr-1" />
              View
            </button>
            
            <button
              v-if="field.nullable"
              type="button"
              class="inline-flex items-center px-3 py-1.5 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
              :class="{ 'border-red-600 text-red-300 bg-gray-800 hover:bg-red-900': isDarkTheme }"
              @click="clearRelation"
            >
              <XMarkIcon class="w-4 h-4 mr-1" />
              Clear
            </button>
          </div>
        </div>
      </div>

      <!-- No Related Model -->
      <div v-else class="border-2 border-dashed rounded-lg p-6 text-center" :class="emptyStateClasses">
        <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" :class="{ 'text-gray-500': isDarkTheme }" />
        <h3 class="mt-2 text-sm font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
          No {{ field.name }}
        </h3>
        <p class="mt-1 text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
          This resource doesn't have a related {{ field.name.toLowerCase() }}.
        </p>
        <p class="mt-1 text-xs text-gray-400" :class="{ 'text-gray-500': isDarkTheme }">
          Polymorphic inverse relationship
        </p>
        
        <!-- Select/Create Buttons -->
        <div v-if="!readonly && !disabled" class="mt-6 space-x-3">
          <button
            v-if="selectedType || field.types.length === 1"
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            @click="selectRelatedModel"
          >
            <MagnifyingGlassIcon class="h-4 w-4 mr-2" />
            Select {{ getTypeLabel(selectedType || field.types[0]) }}
          </button>
          
          <button
            v-if="field.showCreateRelationButton && (selectedType || field.types.length === 1)"
            type="button"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
            @click="createRelatedModel"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            Create {{ getTypeLabel(selectedType || field.types[0]) }}
          </button>
        </div>
      </div>

      <!-- Search Results (when selecting) -->
      <div v-if="showSearchResults" class="border rounded-lg p-4" :class="{ 'border-gray-700 bg-gray-800': isDarkTheme, 'border-gray-200 bg-white': !isDarkTheme }">
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <h4 class="font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
              Select {{ getTypeLabel(selectedType) }}
            </h4>
            <button
              type="button"
              class="text-gray-400 hover:text-gray-600"
              @click="hideSearchResults"
            >
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          
          <!-- Search Input -->
          <div v-if="field.searchable" class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
              v-model="searchQuery"
              type="text"
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="{ 'border-gray-600 bg-gray-800 text-gray-100 placeholder-gray-400': isDarkTheme }"
              placeholder="Search..."
              @input="debouncedSearch"
            />
          </div>
          
          <!-- Search Results List -->
          <div class="max-h-60 overflow-y-auto space-y-2">
            <div
              v-for="item in searchResults"
              :key="item.id"
              class="flex items-center justify-between p-3 border rounded-md cursor-pointer hover:bg-gray-50"
              :class="{ 'border-gray-600 hover:bg-gray-700': isDarkTheme, 'border-gray-200': !isDarkTheme }"
              @click="selectItem(item)"
            >
              <div class="flex items-center space-x-3">
                <DocumentIcon class="w-5 h-5 text-gray-400" />
                <div>
                  <p class="font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
                    {{ item.title }}
                  </p>
                  <p v-if="field.withSubtitles" class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                    {{ item.subtitle }}
                  </p>
                </div>
              </div>
              <ChevronRightIcon class="w-5 h-5 text-gray-400" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * MorphToField Component
 * 
 * Polymorphic inverse relationship field with selection and management capabilities.
 * Displays and manages the parent model in a polymorphic relationship.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, watch } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { debounce } from 'lodash'
import { 
  DocumentIcon,
  EyeIcon,
  ArrowTopRightOnSquareIcon,
  XMarkIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  ChevronRightIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: Object,
    default: () => ({ 
      id: null, 
      title: null, 
      resource_class: null, 
      morph_type: null,
      exists: false
    })
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

// Store
const adminStore = useAdminStore()

// Refs
const selectedType = ref('')
const showSearchResults = ref(false)
const searchQuery = ref('')
const searchResults = ref([])

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const hasRelatedModel = computed(() => {
  return props.modelValue?.exists && props.modelValue?.id
})

const relatedModelTitle = computed(() => {
  return props.modelValue?.title || `#${props.modelValue?.id}`
})

const statusText = computed(() => {
  return hasRelatedModel.value ? 'Related' : 'No Relation'
})

const statusBadgeClasses = computed(() => {
  const baseClasses = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'
  
  if (hasRelatedModel.value) {
    return `${baseClasses} bg-green-100 text-green-800 ${isDarkTheme.value ? 'bg-green-900 text-green-200' : ''}`
  } else {
    return `${baseClasses} bg-gray-100 text-gray-800 ${isDarkTheme.value ? 'bg-gray-900 text-gray-200' : ''}`
  }
})

const relatedModelClasses = computed(() => {
  const baseClasses = 'border rounded-lg p-4'
  
  if (isDarkTheme.value) {
    return `${baseClasses} border-gray-700 bg-gray-800`
  } else {
    return `${baseClasses} border-gray-200 bg-white`
  }
})

const emptyStateClasses = computed(() => {
  const baseClasses = 'border-2 border-dashed rounded-lg p-6 text-center'
  
  if (isDarkTheme.value) {
    return `${baseClasses} border-gray-700 bg-gray-800`
  } else {
    return `${baseClasses} border-gray-300 bg-gray-50`
  }
})

// Methods
const getTypeLabel = (resourceClass) => {
  if (!resourceClass) return ''
  
  // Extract class name from full namespace
  const parts = resourceClass.split('\\')
  const className = parts[parts.length - 1]
  
  // Remove "Resource" suffix if present
  return className.replace(/Resource$/, '')
}

const onTypeChange = () => {
  // Clear current selection when type changes
  if (hasRelatedModel.value) {
    clearRelation()
  }
}

const selectRelatedModel = () => {
  showSearchResults.value = true
  searchResults.value = [] // Mock empty results for now
}

const hideSearchResults = () => {
  showSearchResults.value = false
  searchQuery.value = ''
  searchResults.value = []
}

const selectItem = (item) => {
  emit('update:modelValue', {
    id: item.id,
    title: item.title,
    resource_class: selectedType.value,
    morph_type: item.morph_type,
    exists: true
  })
  hideSearchResults()
}

const createRelatedModel = () => {
  console.log('Create related model for type:', selectedType.value)
}

const peekRelatedModel = () => {
  console.log('Peek related model:', props.modelValue)
}

const viewRelatedModel = () => {
  console.log('View related model:', props.modelValue)
}

const clearRelation = () => {
  emit('update:modelValue', {
    id: null,
    title: null,
    resource_class: null,
    morph_type: null,
    exists: false
  })
}

const debouncedSearch = debounce(() => {
  // Mock search implementation
  console.log('Search for:', searchQuery.value)
}, 300)

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (newValue?.resource_class) {
    selectedType.value = newValue.resource_class
  }
}, { immediate: true })
</script>

<style scoped>
/* Component-specific styles */
</style>
