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
          <!-- Search input -->
          <div
            v-if="field.searchable"
            class="relative"
          >
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search..."
              class="admin-input w-48 text-sm pl-8"
              :class="{ 'admin-input-dark': isDarkTheme }"
              @input="handleSearch"
            />
            <MagnifyingGlassIcon class="absolute left-2 top-2 w-4 h-4 text-gray-400" />
          </div>

          <!-- Create button -->
          <button
            v-if="field.showCreateButton && !readonly && !disabled"
            type="button"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            @click="showCreateModal"
          >
            <PlusIcon class="w-4 h-4 mr-1" />
            Create
          </button>

          <!-- Attach button -->
          <button
            v-if="field.showAttachButton && !readonly && !disabled"
            type="button"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
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
        v-else-if="items.length === 0"
        class="text-center py-8"
      >
        <div class="text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
          <DocumentIcon class="mx-auto h-12 w-12 mb-4" />
          <p class="text-lg font-medium">No {{ field.name.toLowerCase() }} found</p>
          <p class="text-sm">
            {{ field.showCreateButton ? 'Create your first item to get started.' : 'No items to display.' }}
          </p>
        </div>
      </div>

      <!-- Items table -->
      <div
        v-else
        class="bg-white shadow overflow-hidden sm:rounded-md"
        :class="{ 'bg-gray-800': isDarkTheme }"
      >
        <ul class="divide-y divide-gray-200" :class="{ 'divide-gray-700': isDarkTheme }">
          <li
            v-for="item in items"
            :key="item.id"
            class="px-6 py-4 hover:bg-gray-50"
            :class="{ 'hover:bg-gray-700': isDarkTheme }"
          >
            <div class="flex items-center justify-between">
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

                <!-- Edit button -->
                <button
                  v-if="!readonly && !disabled"
                  type="button"
                  class="text-gray-600 hover:text-gray-700 text-sm font-medium"
                  :class="{ 'text-gray-400 hover:text-gray-300': isDarkTheme }"
                  @click="editItem(item)"
                >
                  Edit
                </button>

                <!-- Delete button -->
                <button
                  v-if="!readonly && !disabled"
                  type="button"
                  class="text-red-600 hover:text-red-700 text-sm font-medium"
                  :class="{ 'text-red-400 hover:text-red-300': isDarkTheme }"
                  @click="deleteItem(item)"
                >
                  Delete
                </button>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination && pagination.last_page > 1"
        class="flex items-center justify-between"
      >
        <div class="text-sm text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
        </div>

        <div class="flex items-center space-x-2">
          <button
            :disabled="pagination.current_page === 1"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="{ 'bg-gray-800 border-gray-600 text-gray-300 hover:bg-gray-700': isDarkTheme }"
            @click="goToPage(pagination.current_page - 1)"
          >
            Previous
          </button>

          <span class="text-sm text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
            Page {{ pagination.current_page }} of {{ pagination.last_page }}
          </span>

          <button
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="{ 'bg-gray-800 border-gray-600 text-gray-300 hover:bg-gray-700': isDarkTheme }"
            @click="goToPage(pagination.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * HasManyField Component
 * 
 * One-to-many relationship field with table display and management.
 * Supports pagination, search, and CRUD operations on related models.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, onMounted, watch } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { 
  MagnifyingGlassIcon, 
  PlusIcon, 
  LinkIcon, 
  DocumentIcon 
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
const items = ref([])
const pagination = ref(null)
const searchQuery = ref('')
const currentPage = ref(1)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const totalCount = computed(() => {
  return props.modelValue?.count || 0
})

// Methods
const loadItems = async (page = 1) => {
  if (!props.modelValue?.resource_id) return
  
  loading.value = true
  
  try {
    // In a real implementation, this would make an API call
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // Mock data - in real implementation, this would come from the API
    items.value = [
      { id: 1, title: 'Item 1', subtitle: 'Description 1' },
      { id: 2, title: 'Item 2', subtitle: 'Description 2' },
      { id: 3, title: 'Item 3', subtitle: 'Description 3' },
    ]
    
    pagination.value = {
      current_page: page,
      last_page: 3,
      per_page: 10,
      total: 25,
      from: 1,
      to: 10
    }
  } catch (error) {
    console.error('Failed to load items:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  loadItems(1)
}

const goToPage = (page) => {
  currentPage.value = page
  loadItems(page)
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

const editItem = (item) => {
  console.log('Edit item:', item)
}

const deleteItem = (item) => {
  console.log('Delete item:', item)
}

const showCreateModal = () => {
  console.log('Show create modal for', props.field.resourceClass)
}

const showAttachModal = () => {
  console.log('Show attach modal for', props.field.resourceClass)
}

// Lifecycle
onMounted(() => {
  loadItems()
})

// Watch for changes
watch(() => props.modelValue?.resource_id, (newId) => {
  if (newId) {
    loadItems()
  }
})
</script>

<style scoped>
/* Ensure proper spacing */
.space-y-4 > * + * {
  margin-top: 1rem;
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
