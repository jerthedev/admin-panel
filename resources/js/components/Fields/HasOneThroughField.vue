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

      <!-- Through Relationship Info -->
      <div 
        v-if="field.through"
        class="flex items-center space-x-2 text-sm text-gray-600"
        :class="{ 'text-gray-400': isDarkTheme }"
      >
        <InformationCircleIcon class="w-4 h-4" data-testid="info-icon" />
        <span>This relationship is accessed through {{ field.through }}</span>
      </div>

      <!-- Related Model Display -->
      <div
        v-if="hasRelatedModel"
        class="border rounded-lg p-4"
        :class="{ 
          'border-gray-200 bg-gray-50': !isDarkTheme,
          'border-gray-700 bg-gray-800': isDarkTheme 
        }"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <DocumentIcon class="w-5 h-5 text-gray-400" />
            <div>
              <p class="font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
                {{ relatedModelTitle }}
              </p>
              <p class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                {{ field.resourceClass }}
              </p>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="flex items-center space-x-2">
            <button
              v-if="!readonly && !disabled"
              type="button"
              class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
              @click="viewRelatedModel"
            >
              <EyeIcon class="w-4 h-4 mr-1" />
              View
            </button>
            
            <button
              v-if="!readonly && !disabled"
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

      <!-- Empty State -->
      <div
        v-else
        class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center"
        :class="{ 'border-gray-600': isDarkTheme }"
      >
        <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
          No {{ field.name.toLowerCase() }} found
        </h3>
        <p class="mt-1 text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
          This relationship is accessed through {{ field.through || 'an intermediate model' }}.
        </p>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * HasOneThroughField Component
 * 
 * One-to-one through relationship field with display and navigation capabilities.
 * Displays related models accessed through an intermediate model.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { 
  DocumentIcon,
  EyeIcon,
  PencilIcon,
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
    default: () => ({ id: null, title: null, resource_class: null, exists: false, through: null })
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
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change', 'view-related', 'edit-related'])

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
    return `${baseClasses} bg-green-100 text-green-800`
  }
  
  return `${baseClasses} bg-gray-100 text-gray-800`
})

// Methods
const viewRelatedModel = () => {
  if (hasRelatedModel.value) {
    // Emit event to parent component to handle navigation
    emit('view-related', {
      id: props.modelValue.id,
      resourceClass: props.modelValue.resource_class,
      title: props.modelValue.title,
      through: props.modelValue.through
    })
  }
}

const editRelatedModel = () => {
  if (hasRelatedModel.value) {
    // Emit event to parent component to handle navigation
    emit('edit-related', {
      id: props.modelValue.id,
      resourceClass: props.modelValue.resource_class,
      title: props.modelValue.title,
      through: props.modelValue.through
    })
  }
}
</script>

<style scoped>
/* Component-specific styles */
</style>
