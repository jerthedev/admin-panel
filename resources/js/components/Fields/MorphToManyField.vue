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
        <div class="flex items-center space-x-3">
          <h3 class="text-lg font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
            {{ field.name }}
          </h3>
          
          <!-- Item Count Badge -->
          <span
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            :class="{ 'bg-blue-900 text-blue-200': isDarkTheme }"
          >
            {{ itemCount }} {{ itemCount === 1 ? 'item' : 'items' }}
          </span>
        </div>

        <div class="flex items-center space-x-2">
          <!-- Attach button -->
          <button
            v-if="!readonly && !disabled"
            type="button"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            @click="showAttachModal"
          >
            <PlusIcon class="w-4 h-4 mr-1" />
            Attach {{ field.name.slice(0, -1) }}
          </button>

          <!-- Create Relation button -->
          <button
            v-if="field.showCreateRelationButton && !readonly && !disabled"
            type="button"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
            @click="showCreateModal"
          >
            <DocumentPlusIcon class="w-4 h-4 mr-1" />
            Create {{ field.name.slice(0, -1) }}
          </button>

          <!-- Collapse/Expand button -->
          <button
            v-if="field.collapsable"
            type="button"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
            @click="toggleCollapsed"
          >
            <ChevronDownIcon v-if="!isCollapsed" class="w-4 h-4 mr-1" />
            <ChevronRightIcon v-else class="w-4 h-4 mr-1" />
            {{ isCollapsed ? 'Expand' : 'Collapse' }}
          </button>
        </div>
      </div>

      <!-- Polymorphic Relationship Info -->
      <div 
        class="flex items-center space-x-2 text-sm text-gray-600"
        :class="{ 'text-gray-400': isDarkTheme }"
      >
        <InformationCircleIcon class="w-4 h-4" data-testid="info-icon" />
        <span>This is a polymorphic many-to-many relationship</span>
        <span v-if="field.morphType" class="text-xs bg-gray-100 px-2 py-1 rounded" :class="{ 'bg-gray-800 text-gray-300': isDarkTheme }">
          {{ field.morphType }}
        </span>
      </div>

      <!-- Search -->
      <div
        v-if="field.searchable && !isCollapsed"
        class="relative"
      >
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

      <!-- Collapsible content -->
      <div v-show="!isCollapsed">
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
            <p class="text-lg font-medium">No {{ field.name.toLowerCase() }} attached</p>
            <p class="text-sm">
              {{ field.showCreateRelationButton ? 'Attach or create items to get started.' : 'Attach items to get started.' }}
            </p>
            <p class="text-xs text-gray-400 mt-1" :class="{ 'text-gray-500': isDarkTheme }">
              Polymorphic many-to-many relationship
            </p>
          </div>
        </div>

        <!-- Items list -->
        <div
          v-else
          class="space-y-3"
        >
          <div
            v-for="item in items"
            :key="item.id"
            class="border rounded-lg p-4 hover:bg-gray-50"
            :class="{ 
              'border-gray-200': !isDarkTheme,
              'border-gray-700 hover:bg-gray-800': isDarkTheme 
            }"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <DocumentIcon class="w-5 h-5 text-gray-400" />
                <div>
                  <p class="font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
                    {{ item.title || item.name || `${field.name.slice(0, -1)} #${item.id}` }}
                  </p>
                  <p class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                    {{ field.resourceClass }}
                  </p>
                  <p class="text-xs text-gray-400" :class="{ 'text-gray-500': isDarkTheme }">
                    Polymorphic: {{ field.morphType }}
                  </p>
                  
                  <!-- Pivot Fields Display -->
                  <div v-if="item.pivot && hasPivotFields" class="mt-2 space-y-1">
                    <div
                      v-for="(value, key) in item.pivot"
                      :key="key"
                      class="text-xs text-gray-500"
                      :class="{ 'text-gray-400': isDarkTheme }"
                    >
                      <span class="font-medium">{{ formatPivotKey(key) }}:</span> {{ value }}
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Action Buttons -->
              <div class="flex items-center space-x-2">
                <button
                  v-if="!readonly && !disabled"
                  type="button"
                  class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
                  @click="viewItem(item)"
                >
                  <EyeIcon class="w-4 h-4 mr-1" />
                  View
                </button>
                
                <button
                  v-if="!readonly && !disabled && hasPivotFields"
                  type="button"
                  class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
                  @click="editPivot(item)"
                >
                  <CogIcon class="w-4 h-4 mr-1" />
                  Edit Pivot
                </button>
                
                <button
                  v-if="!readonly && !disabled"
                  type="button"
                  class="inline-flex items-center px-3 py-1.5 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                  :class="{ 'border-red-600 text-red-300 bg-gray-800 hover:bg-red-900': isDarkTheme }"
                  @click="detachItem(item)"
                >
                  <XMarkIcon class="w-4 h-4 mr-1" />
                  Detach
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination && pagination.last_page > 1 && !isCollapsed"
        class="flex items-center justify-between"
      >
        <div class="text-sm text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
          Showing {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} to 
          {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of 
          {{ pagination.total }} results
        </div>
        
        <div class="flex items-center space-x-2">
          <button
            :disabled="pagination.current_page === 1"
            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
            @click="goToPage(pagination.current_page - 1)"
          >
            Previous
          </button>
          
          <span class="text-sm text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
            Page {{ pagination.current_page }} of {{ pagination.last_page }}
          </span>
          
          <button
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="{ 'border-gray-600 text-gray-300 bg-gray-800 hover:bg-gray-700': isDarkTheme }"
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
 * MorphToManyField Component
 * 
 * Many-to-many polymorphic relationship field with display and management capabilities.
 * Displays multiple related models accessed through a polymorphic many-to-many relationship.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, onMounted } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { debounce } from 'lodash'
import {
  MagnifyingGlassIcon,
  PlusIcon,
  DocumentIcon,
  DocumentPlusIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  EyeIcon,
  CogIcon,
  XMarkIcon,
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
      count: 0, 
      resource_id: null, 
      resource_class: null, 
      morph_type: null, 
      morph_id: null,
      pivot_fields: [],
      pivot_computed_fields: [],
      pivot_actions: []
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
const loading = ref(false)
const items = ref([])
const pagination = ref(null)
const searchQuery = ref('')
const currentPage = ref(1)
const isCollapsed = ref(false)

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const itemCount = computed(() => {
  return props.modelValue?.count || 0
})

const hasPivotFields = computed(() => {
  return props.field.pivot_fields && props.field.pivot_fields.length > 0
})

// Methods
const loadItems = async () => {
  loading.value = true
  try {
    // Simulate API call - in real implementation, this would call the backend
    // For now, we'll just show empty state or mock data
    items.value = []
    pagination.value = {
      current_page: 1,
      last_page: 1,
      per_page: props.field.perPage || 15,
      total: props.modelValue?.count || 0
    }
  } catch (error) {
    console.error('Error loading items:', error)
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(() => {
  currentPage.value = 1
  loadItems()
}, 300)

const goToPage = (page) => {
  currentPage.value = page
  loadItems()
}

const showAttachModal = () => {
  console.log('Show attach modal for', props.field.resourceClass)
}

const showCreateModal = () => {
  console.log('Show create modal for', props.field.resourceClass)
}

const toggleCollapsed = () => {
  isCollapsed.value = !isCollapsed.value
}

const viewItem = (item) => {
  console.log('View item:', item)
}

const editPivot = (item) => {
  console.log('Edit pivot for item:', item)
}

const detachItem = (item) => {
  console.log('Detach item:', item)
}

const formatPivotKey = (key) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

// Lifecycle
onMounted(() => {
  // Initialize collapsed state based on field configuration
  isCollapsed.value = props.field.collapsedByDefault || false
  
  loadItems()
})
</script>

<style scoped>
/* Component-specific styles */
</style>
