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
      <!-- Header with count and actions -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <h3 class="text-lg font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
            {{ field.name }}
          </h3>
          <span
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
            :class="{ 'bg-gray-700 text-gray-200': isDarkTheme }"
          >
            {{ totalCount }} {{ totalCount === 1 ? 'item' : 'items' }}
          </span>
        </div>

        <div class="flex items-center space-x-2">
          <!-- Attach button -->
          <button
            v-if="field.showAttachButton && !readonly && !disabled"
            type="button"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            @click="showAttachModal"
          >
            <LinkIcon class="w-4 h-4 mr-1" />
            Attach
          </button>
        </div>
      </div>

      <!-- Loading state -->
      <div
        v-if="loading"
        class="flex items-center justify-center py-8"
      >
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="attachedItems.length === 0"
        class="text-center py-8"
      >
        <div class="text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
          <TagIcon class="mx-auto h-12 w-12 mb-4" />
          <p class="text-lg font-medium">No {{ field.name.toLowerCase() }} attached</p>
          <p class="text-sm">
            {{ field.showAttachButton ? 'Attach items to get started.' : 'No items to display.' }}
          </p>
        </div>
      </div>

      <!-- Attached items -->
      <div
        v-else
        class="space-y-2"
      >
        <div
          v-for="item in attachedItems"
          :key="item.id"
          class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg"
          :class="{ 'bg-gray-800 border-gray-700': isDarkTheme }"
        >
          <div class="flex-1">
            <div class="flex items-center">
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
                  {{ getItemTitle(item) }}
                </p>
                <p
                  v-if="getItemSubtitle(item)"
                  class="text-sm text-gray-500"
                  :class="{ 'text-gray-400': isDarkTheme }"
                >
                  {{ getItemSubtitle(item) }}
                </p>
                
                <!-- Pivot data -->
                <div
                  v-if="item.pivot && field.pivotFields.length > 0"
                  class="mt-2 flex flex-wrap gap-2"
                >
                  <span
                    v-for="pivotField in field.pivotFields"
                    :key="pivotField"
                    class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800"
                    :class="{ 'bg-gray-700 text-gray-200': isDarkTheme }"
                  >
                    {{ pivotField }}: {{ item.pivot[pivotField] || 'N/A' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center space-x-2">
            <!-- View button -->
            <button
              type="button"
              class="text-blue-600 hover:text-blue-700 text-sm font-medium"
              :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
              @click="viewItem(item)"
            >
              View
            </button>

            <!-- Edit pivot button -->
            <button
              v-if="field.pivotFields.length > 0 && !readonly && !disabled"
              type="button"
              class="text-gray-600 hover:text-gray-700 text-sm font-medium"
              :class="{ 'text-gray-400 hover:text-gray-300': isDarkTheme }"
              @click="editPivot(item)"
            >
              Edit
            </button>

            <!-- Detach button -->
            <button
              v-if="field.showDetachButton && !readonly && !disabled"
              type="button"
              class="text-red-600 hover:text-red-700 text-sm font-medium"
              :class="{ 'text-red-400 hover:text-red-300': isDarkTheme }"
              @click="detachItem(item)"
            >
              Detach
            </button>
          </div>
        </div>
      </div>

      <!-- Attach Modal -->
      <Teleport to="body">
        <div
          v-if="showAttachModalState"
          class="fixed inset-0 z-50 overflow-y-auto"
          @click="closeAttachModal"
        >
          <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div
              class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
              :class="{ 'bg-gray-800': isDarkTheme }"
              @click.stop
            >
              <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" :class="{ 'text-gray-100': isDarkTheme }">
                  Attach {{ field.name }}
                </h3>

                <!-- Search input -->
                <div
                  v-if="field.searchable"
                  class="mb-4"
                >
                  <input
                    v-model="attachSearchQuery"
                    type="text"
                    placeholder="Search..."
                    class="admin-input w-full"
                    :class="{ 'admin-input-dark': isDarkTheme }"
                    @input="searchAttachableItems"
                  />
                </div>

                <!-- Attachable items -->
                <div class="max-h-60 overflow-y-auto">
                  <div
                    v-if="loadingAttachable"
                    class="text-center py-4"
                  >
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                  </div>

                  <div
                    v-else-if="attachableItems.length === 0"
                    class="text-center py-4 text-gray-500"
                    :class="{ 'text-gray-400': isDarkTheme }"
                  >
                    No items available to attach
                  </div>

                  <div
                    v-else
                    class="space-y-2"
                  >
                    <label
                      v-for="item in attachableItems"
                      :key="item.value"
                      class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer"
                      :class="{ 'hover:bg-gray-700': isDarkTheme }"
                    >
                      <input
                        v-model="selectedAttachItems"
                        type="checkbox"
                        :value="item.value"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                      />
                      <span class="ml-2 text-sm text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
                        {{ item.label }}
                      </span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse" :class="{ 'bg-gray-700': isDarkTheme }">
                <button
                  type="button"
                  class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                  @click="attachSelectedItems"
                >
                  Attach Selected
                </button>
                <button
                  type="button"
                  class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                  :class="{ 'border-gray-600 bg-gray-800 text-gray-300 hover:bg-gray-700': isDarkTheme }"
                  @click="closeAttachModal"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * ManyToManyField Component
 * 
 * Many-to-many relationship field with multi-select interface and pivot data support.
 * Supports attach/detach operations and pivot field management.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, onMounted, watch } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { LinkIcon, TagIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: Object,
    default: () => ({ count: 0, resource_id: null })
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
const loading = ref(false)
const attachedItems = ref([])
const showAttachModalState = ref(false)
const loadingAttachable = ref(false)
const attachableItems = ref([])
const selectedAttachItems = ref([])
const attachSearchQuery = ref('')

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const totalCount = computed(() => {
  return props.modelValue?.count || 0
})

// Methods
const loadAttachedItems = async () => {
  if (!props.modelValue?.resource_id) return
  
  loading.value = true
  
  try {
    // In a real implementation, this would make an API call
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // Mock data - in real implementation, this would come from the API
    attachedItems.value = [
      { 
        id: 1, 
        title: 'Attached Item 1', 
        subtitle: 'Description 1',
        pivot: { created_at: '2024-01-01', role: 'admin' }
      },
      { 
        id: 2, 
        title: 'Attached Item 2', 
        subtitle: 'Description 2',
        pivot: { created_at: '2024-01-02', role: 'user' }
      },
    ]
  } catch (error) {
    console.error('Failed to load attached items:', error)
  } finally {
    loading.value = false
  }
}

const loadAttachableItems = async () => {
  loadingAttachable.value = true
  
  try {
    // In a real implementation, this would make an API call
    await new Promise(resolve => setTimeout(resolve, 300))
    
    // Mock data - in real implementation, this would come from the API
    attachableItems.value = [
      { value: 3, label: 'Available Item 1' },
      { value: 4, label: 'Available Item 2' },
      { value: 5, label: 'Available Item 3' },
    ]
  } catch (error) {
    console.error('Failed to load attachable items:', error)
  } finally {
    loadingAttachable.value = false
  }
}

const getItemTitle = (item) => {
  return item.title || item.name || `Item ${item.id}`
}

const getItemSubtitle = (item) => {
  return item.subtitle || item.description || null
}

const viewItem = (item) => {
  console.log('View item:', item)
}

const editPivot = (item) => {
  console.log('Edit pivot for item:', item)
}

const detachItem = (item) => {
  console.log('Detach item:', item)
  // In a real implementation, this would make an API call to detach
  attachedItems.value = attachedItems.value.filter(i => i.id !== item.id)
}

const showAttachModal = () => {
  showAttachModalState.value = true
  selectedAttachItems.value = []
  loadAttachableItems()
}

const closeAttachModal = () => {
  showAttachModalState.value = false
  selectedAttachItems.value = []
  attachSearchQuery.value = ''
}

const searchAttachableItems = () => {
  // In a real implementation, this would trigger server-side search
  console.log('Search attachable items:', attachSearchQuery.value)
}

const attachSelectedItems = () => {
  console.log('Attach selected items:', selectedAttachItems.value)
  // In a real implementation, this would make an API call to attach
  closeAttachModal()
  loadAttachedItems() // Reload to show newly attached items
}

// Lifecycle
onMounted(() => {
  loadAttachedItems()
})

// Watch for changes
watch(() => props.modelValue?.resource_id, (newId) => {
  if (newId) {
    loadAttachedItems()
  }
})
</script>

<style scoped>
/* Ensure proper spacing */
.space-y-4 > * + * {
  margin-top: 1rem;
}

.space-y-2 > * + * {
  margin-top: 0.5rem;
}

/* Loading animation */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
