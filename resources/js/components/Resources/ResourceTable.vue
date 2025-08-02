<template>
  <div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <!-- Table Header with Actions -->
    <div v-if="$slots.header || selectedItems.length > 0" class="px-6 py-4 border-b border-gray-200 bg-gray-50">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <!-- Bulk selection info -->
          <div v-if="selectedItems.length > 0" class="flex items-center space-x-2">
            <span class="text-sm text-gray-700">
              {{ selectedItems.length }} {{ selectedItems.length === 1 ? 'item' : 'items' }} selected
            </span>
            <Button
              variant="ghost"
              size="sm"
              @click="clearSelection"
            >
              Clear
            </Button>
          </div>
          <slot name="header" />
        </div>
        
        <!-- Bulk actions -->
        <div v-if="selectedItems.length > 0 && bulkActions.length > 0" class="flex items-center space-x-2">
          <Button
            v-for="action in bulkActions"
            :key="action.key"
            :variant="action.variant || 'secondary'"
            size="sm"
            @click="handleBulkAction(action)"
          >
            {{ action.name }}
          </Button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <LoadingSpinner size="lg" text="Loading..." />
    </div>

    <!-- Empty State -->
    <div v-else-if="!items.length" class="text-center py-12">
      <div class="text-gray-500">
        <slot name="empty">
          <p class="text-lg font-medium">No items found</p>
          <p class="text-sm mt-1">Try adjusting your search or filter criteria.</p>
        </slot>
      </div>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <!-- Table Header -->
        <thead class="bg-gray-50">
          <tr>
            <!-- Select All Checkbox -->
            <th v-if="selectable" class="px-6 py-3 text-left">
              <input
                type="checkbox"
                :checked="allSelected"
                :indeterminate="someSelected"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                @change="toggleSelectAll"
              />
            </th>
            
            <!-- Column Headers -->
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              :class="{ 'cursor-pointer hover:bg-gray-100': column.sortable }"
              @click="column.sortable ? handleSort(column.key) : null"
            >
              <div class="flex items-center space-x-1">
                <span>{{ column.label }}</span>
                <div v-if="column.sortable" class="flex flex-col">
                  <ChevronUpIcon
                    class="h-3 w-3"
                    :class="getSortIconClass(column.key, 'asc')"
                  />
                  <ChevronDownIcon
                    class="h-3 w-3 -mt-1"
                    :class="getSortIconClass(column.key, 'desc')"
                  />
                </div>
              </div>
            </th>
            
            <!-- Actions Column -->
            <th v-if="actions.length > 0" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>

        <!-- Table Body -->
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="(item, index) in items"
            :key="getItemKey(item, index)"
            class="hover:bg-gray-50"
            :class="{ 'bg-blue-50': selectedItems.includes(getItemKey(item, index)) }"
          >
            <!-- Select Checkbox -->
            <td v-if="selectable" class="px-6 py-4">
              <input
                type="checkbox"
                :checked="selectedItems.includes(getItemKey(item, index))"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                @change="toggleItemSelection(getItemKey(item, index))"
              />
            </td>
            
            <!-- Data Columns -->
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 whitespace-nowrap"
            >
              <slot
                :name="`column.${column.key}`"
                :item="item"
                :value="getColumnValue(item, column.key)"
                :column="column"
              >
                <div class="text-sm text-gray-900">
                  {{ formatColumnValue(getColumnValue(item, column.key), column) }}
                </div>
              </slot>
            </td>
            
            <!-- Actions Column -->
            <td v-if="actions.length > 0" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex items-center justify-end space-x-2">
                <Button
                  v-for="action in actions"
                  :key="action.key"
                  :variant="action.variant || 'ghost'"
                  size="sm"
                  @click="handleAction(action, item)"
                >
                  {{ action.name }}
                </Button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && !loading" class="px-6 py-4 border-t border-gray-200">
      <Pagination
        :links="pagination.links"
        :meta="pagination.meta"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/20/solid'
import Button from '../Common/Button.vue'
import LoadingSpinner from '../Common/LoadingSpinner.vue'
import Pagination from '../Common/Pagination.vue'

const props = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  columns: {
    type: Array,
    required: true
  },
  actions: {
    type: Array,
    default: () => []
  },
  bulkActions: {
    type: Array,
    default: () => []
  },
  selectable: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  pagination: {
    type: Object,
    default: null
  },
  sortBy: {
    type: String,
    default: null
  },
  sortDirection: {
    type: String,
    default: 'asc'
  },
  itemKey: {
    type: String,
    default: 'id'
  }
})

const emit = defineEmits(['sort', 'action', 'bulk-action', 'selection-change'])

const selectedItems = ref([])

const allSelected = computed(() => {
  return props.items.length > 0 && selectedItems.value.length === props.items.length
})

const someSelected = computed(() => {
  return selectedItems.value.length > 0 && selectedItems.value.length < props.items.length
})

const getItemKey = (item, index) => {
  return item[props.itemKey] || index
}

const getColumnValue = (item, key) => {
  return key.split('.').reduce((obj, k) => obj?.[k], item)
}

const formatColumnValue = (value, column) => {
  if (column.formatter && typeof column.formatter === 'function') {
    return column.formatter(value)
  }
  
  if (value === null || value === undefined) {
    return '-'
  }
  
  return value
}

const handleSort = (column) => {
  const direction = props.sortBy === column && props.sortDirection === 'asc' ? 'desc' : 'asc'
  emit('sort', { column, direction })
}

const getSortIconClass = (column, direction) => {
  const isActive = props.sortBy === column && props.sortDirection === direction
  return isActive ? 'text-blue-600' : 'text-gray-400'
}

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedItems.value = []
  } else {
    selectedItems.value = props.items.map((item, index) => getItemKey(item, index))
  }
  emit('selection-change', selectedItems.value)
}

const toggleItemSelection = (itemKey) => {
  const index = selectedItems.value.indexOf(itemKey)
  if (index > -1) {
    selectedItems.value.splice(index, 1)
  } else {
    selectedItems.value.push(itemKey)
  }
  emit('selection-change', selectedItems.value)
}

const clearSelection = () => {
  selectedItems.value = []
  emit('selection-change', selectedItems.value)
}

const handleAction = (action, item) => {
  emit('action', { action, item })
}

const handleBulkAction = (action) => {
  const selectedItemsData = props.items.filter((item, index) => 
    selectedItems.value.includes(getItemKey(item, index))
  )
  emit('bulk-action', { action, items: selectedItemsData })
}
</script>

<style scoped>
/* Additional table styles if needed */
</style>
