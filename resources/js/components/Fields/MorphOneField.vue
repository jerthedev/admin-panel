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
        <span>This is a polymorphic relationship</span>
        <span v-if="field.morphType" class="text-xs bg-gray-100 px-2 py-1 rounded" :class="{ 'bg-gray-800 text-gray-300': isDarkTheme }">
          {{ field.morphType }}
        </span>
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
                {{ field.resourceClass }}
              </p>
              <p v-if="field.morphType" class="text-xs text-gray-400" :class="{ 'text-gray-500': isDarkTheme }">
                Polymorphic type: {{ field.morphType }}
              </p>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div v-if="!readonly" class="flex items-center space-x-2">
            <button
              type="button"
              class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
              @click="viewRelatedModel"
            >
              <EyeIcon class="w-4 h-4 mr-1" />
              View
            </button>
            
            <button
              type="button"
              class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
              @click="editRelatedModel"
            >
              <PencilIcon class="w-4 h-4 mr-1" />
              Edit
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
          Polymorphic relationship
        </p>
        
        <!-- Create Button -->
        <div v-if="!readonly" class="mt-6">
          <button
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            @click="createRelatedModel"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            Create {{ field.name }}
          </button>
        </div>
      </div>

      <!-- Of Many Information -->
      <div v-if="field.isOfMany" class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
        <span class="inline-flex items-center">
          <InformationCircleIcon class="h-4 w-4 mr-1" />
          This is a "{{ field.name }}" of many polymorphic relationship
        </span>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * MorphOneField Component
 * 
 * One-to-one polymorphic relationship field with display and management capabilities.
 * Displays a single related model accessed through a polymorphic relationship.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { 
  DocumentIcon,
  EyeIcon,
  PencilIcon,
  PlusIcon,
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
      exists: false,
      morph_type: null,
      morph_id: null,
      is_of_many: false,
      of_many_relationship: null
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

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const hasRelatedModel = computed(() => {
  return props.modelValue?.exists && props.modelValue?.id
})

const relatedModelTitle = computed(() => {
  return props.modelValue?.title || `${props.field.name} #${props.modelValue?.id}`
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
const viewRelatedModel = () => {
  console.log('View related model:', props.modelValue)
}

const editRelatedModel = () => {
  console.log('Edit related model:', props.modelValue)
}

const createRelatedModel = () => {
  console.log('Create related model for field:', props.field.name)
}
</script>

<style scoped>
/* Component-specific styles */
</style>
