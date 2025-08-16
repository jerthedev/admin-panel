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

      <!-- Related Model Display -->
      <div v-if="hasRelatedModel" class="border rounded-lg p-4" :class="borderClasses">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <!-- Icon -->
            <div class="flex-shrink-0">
              <DocumentIcon class="h-5 w-5 text-gray-400" :class="{ 'text-gray-500': isDarkTheme }" />
            </div>
            
            <!-- Content -->
            <div>
              <h4 class="text-sm font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
                {{ relatedModelTitle }}
              </h4>
              <p class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                {{ field.resourceClass }}
              </p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center space-x-2">
            <!-- View Button -->
            <button
              type="button"
              class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              :class="{ 
                'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme 
              }"
              @click="viewRelatedModel"
            >
              <EyeIcon class="h-4 w-4 mr-1" />
              View
            </button>

            <!-- Edit Button -->
            <button
              v-if="!readonly"
              type="button"
              class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              :class="{ 
                'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme 
              }"
              @click="editRelatedModel"
            >
              <PencilIcon class="h-4 w-4 mr-1" />
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
          This is a "{{ field.name }}" relationship
        </span>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * HasOneField Component
 * 
 * One-to-one relationship field with display and navigation capabilities.
 * Supports both regular HasOne and "has one of many" relationships.
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
    default: () => ({ id: null, title: null, resource_class: null, exists: false })
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
    return isDarkTheme.value 
      ? `${baseClasses} bg-green-900 text-green-200`
      : `${baseClasses} bg-green-100 text-green-800`
  } else {
    return isDarkTheme.value 
      ? `${baseClasses} bg-gray-700 text-gray-300`
      : `${baseClasses} bg-gray-100 text-gray-800`
  }
})

const borderClasses = computed(() => {
  return isDarkTheme.value 
    ? 'border-gray-600 bg-gray-800'
    : 'border-gray-200 bg-white'
})

const emptyStateClasses = computed(() => {
  return isDarkTheme.value 
    ? 'border-gray-600 bg-gray-800'
    : 'border-gray-300 bg-gray-50'
})

// Methods
const viewRelatedModel = () => {
  if (hasRelatedModel.value) {
    // Emit event to parent component to handle navigation
    emit('view-related', {
      id: props.modelValue.id,
      resourceClass: props.modelValue.resource_class,
      title: props.modelValue.title
    })
  }
}

const editRelatedModel = () => {
  if (hasRelatedModel.value) {
    // Emit event to parent component to handle navigation
    emit('edit-related', {
      id: props.modelValue.id,
      resourceClass: props.modelValue.resource_class,
      title: props.modelValue.title
    })
  }
}

const createRelatedModel = () => {
  // Emit event to parent component to handle creation
  emit('create-related', {
    resourceClass: props.field.resourceClass,
    relationshipName: props.field.relationshipName,
    onCreated: (newResource) => {
      // Update the field value with the newly created resource
      const newValue = {
        id: newResource.id,
        title: newResource.title || newResource.name,
        resource_class: props.field.resourceClass,
        exists: true
      }
      emit('update:modelValue', newValue)
    }
  })
}
</script>

<style scoped>
/* Ensure proper spacing */
.space-y-4 > * + * {
  margin-top: 1rem;
}
</style>
