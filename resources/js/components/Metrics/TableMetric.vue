<template>
  <div 
    class="table-metric bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700"
    :class="{ 'dark': darkMode }"
  >
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ title }}
      </h3>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      data-testid="loading-spinner"
      class="flex items-center justify-center py-12"
    >
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      <span class="ml-2 text-gray-600 dark:text-gray-400">Loading...</span>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      data-testid="error-message"
      class="flex items-center justify-center py-12 text-red-600 dark:text-red-400"
    >
      <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      {{ error }}
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!hasData"
      data-testid="empty-state"
      class="flex items-center justify-center py-12 text-gray-500 dark:text-gray-400"
    >
      <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
      </svg>
      {{ emptyText }}
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table 
        data-testid="data-table"
        class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
        :aria-label="`${title} data table`"
      >
        <!-- Table Header -->
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <!-- Select All Checkbox -->
            <th v-if="selectable" class="px-6 py-3 text-left">
              <input
                data-testid="select-all"
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
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              :class="{ 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600': column.sortable }"
              :data-testid="column.sortable ? 'sortable-header' : 'header'"
              @click="column.sortable ? handleSort(column.key) : null"
            >
              <div class="flex items-center space-x-1">
                <span>{{ column.label }}</span>
                <div v-if="column.sortable" class="flex flex-col">
                  <svg
                    :data-testid="sortBy === column.key && sortDirection === 'asc' ? 'sort-asc' : 'sort-icon'"
                    class="h-3 w-3"
                    :class="getSortIconClass(column.key, 'asc')"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                  </svg>
                  <svg
                    :data-testid="sortBy === column.key && sortDirection === 'desc' ? 'sort-desc' : 'sort-icon'"
                    class="h-3 w-3 -mt-1"
                    :class="getSortIconClass(column.key, 'desc')"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </div>
              </div>
            </th>

            <!-- Actions Header -->
            <th
              v-if="showActions && actions.length > 0"
              data-testid="actions-header"
              class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
            >
              Actions
            </th>
          </tr>
        </thead>

        <!-- Table Body -->
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <tr
            v-for="(item, index) in data"
            :key="getItemKey(item, index)"
            data-testid="table-row"
            class="hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            <!-- Row Selection Checkbox -->
            <td v-if="selectable" class="px-6 py-4">
              <input
                data-testid="row-checkbox"
                type="checkbox"
                :checked="isSelected(item)"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                @change="toggleRowSelection(item)"
              />
            </td>

            <!-- Data Cells -->
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"
            >
              <div class="flex items-center">
                <!-- Icon -->
                <svg
                  v-if="column.icon"
                  class="w-4 h-4 mr-2 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconPath(column.icon)"></path>
                </svg>
                
                <!-- Cell Content -->
                <span>{{ formatColumnValue(getColumnValue(item, column.key), column) }}</span>
              </div>
            </td>

            <!-- Actions Cell -->
            <td
              v-if="showActions && actions.length > 0"
              class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
            >
              <div class="flex items-center justify-end space-x-2">
                <button
                  v-for="action in actions"
                  :key="action.key"
                  data-testid="action-button"
                  :class="getActionButtonClass(action)"
                  @click="handleAction(action.key, item, index)"
                >
                  <svg
                    v-if="action.icon"
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconPath(action.icon)"></path>
                  </svg>
                  <span v-if="!action.icon" class="text-xs">{{ action.label }}</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div
      v-if="pagination && !loading && !error"
      data-testid="pagination"
      class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"
    >
      <Pagination :links="pagination" />
    </div>

    <!-- Screen Reader Content -->
    <div data-testid="sr-only" class="sr-only">
      {{ title }} table with {{ data.length }} rows and {{ columns.length }} columns.
      <span v-if="selectedItems.length > 0">{{ selectedItems.length }} rows selected.</span>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import Pagination from '../Common/Pagination.vue'

export default {
  name: 'TableMetric',
  
  components: {
    Pagination,
  },

  props: {
    title: {
      type: String,
      required: true,
    },
    data: {
      type: Array,
      default: () => [],
    },
    columns: {
      type: Array,
      default: () => [],
    },
    actions: {
      type: Array,
      default: () => [],
    },
    loading: {
      type: Boolean,
      default: false,
    },
    error: {
      type: String,
      default: null,
    },
    sortBy: {
      type: String,
      default: null,
    },
    sortDirection: {
      type: String,
      default: 'asc',
      validator: (value) => ['asc', 'desc'].includes(value),
    },
    pagination: {
      type: Object,
      default: null,
    },
    showActions: {
      type: Boolean,
      default: true,
    },
    selectable: {
      type: Boolean,
      default: false,
    },
    emptyText: {
      type: String,
      default: 'No data available',
    },
    itemKey: {
      type: String,
      default: 'id',
    },
    darkMode: {
      type: Boolean,
      default: false,
    },
  },

  emits: ['sort', 'action', 'selection-change'],

  setup(props, { emit }) {
    const selectedItems = ref([])

    const hasData = computed(() => {
      return props.data && props.data.length > 0
    })

    const allSelected = computed(() => {
      return props.data.length > 0 && selectedItems.value.length === props.data.length
    })

    const someSelected = computed(() => {
      return selectedItems.value.length > 0 && selectedItems.value.length < props.data.length
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

    const getSortIconClass = (columnKey, direction) => {
      const isActive = props.sortBy === columnKey && props.sortDirection === direction
      return isActive
        ? 'text-blue-600 dark:text-blue-400'
        : 'text-gray-400 dark:text-gray-500'
    }

    const handleSort = (columnKey) => {
      const newDirection = props.sortBy === columnKey && props.sortDirection === 'asc' ? 'desc' : 'asc'
      emit('sort', columnKey, newDirection)
    }

    const handleAction = (actionKey, item, index) => {
      emit('action', {
        action: actionKey,
        item,
        index,
      })
    }

    const isSelected = (item) => {
      const itemKey = getItemKey(item, 0)
      return selectedItems.value.some(selected => getItemKey(selected, 0) === itemKey)
    }

    const toggleRowSelection = (item) => {
      const itemKey = getItemKey(item, 0)
      const index = selectedItems.value.findIndex(selected => getItemKey(selected, 0) === itemKey)

      if (index > -1) {
        selectedItems.value.splice(index, 1)
      } else {
        selectedItems.value.push(item)
      }

      emit('selection-change', selectedItems.value)
    }

    const toggleSelectAll = () => {
      if (allSelected.value) {
        selectedItems.value = []
      } else {
        selectedItems.value = [...props.data]
      }

      emit('selection-change', selectedItems.value)
    }

    const getActionButtonClass = (action) => {
      const baseClasses = 'inline-flex items-center px-2 py-1 border text-xs font-medium rounded focus:outline-none focus:ring-2 focus:ring-offset-2'

      switch (action.variant) {
        case 'primary':
          return `${baseClasses} btn-primary border-blue-600 text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500`
        case 'secondary':
          return `${baseClasses} btn-secondary border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700`
        case 'danger':
          return `${baseClasses} btn-danger border-red-600 text-white bg-red-600 hover:bg-red-700 focus:ring-red-500`
        default:
          return `${baseClasses} btn-secondary border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700`
      }
    }

    const getIconPath = (iconName) => {
      const icons = {
        eye: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
        pencil: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        trash: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
        user: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        mail: 'M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        phone: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
        calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        check: 'M5 13l4 4L19 7',
        x: 'M6 18L18 6M6 6l12 12',
      }

      return icons[iconName] || icons.eye
    }

    return {
      selectedItems,
      hasData,
      allSelected,
      someSelected,
      getItemKey,
      getColumnValue,
      formatColumnValue,
      getSortIconClass,
      handleSort,
      handleAction,
      isSelected,
      toggleRowSelection,
      toggleSelectAll,
      getActionButtonClass,
      getIconPath,
    }
  },
}
</script>

<style scoped>
.table-metric {
  transition: all 0.2s ease-in-out;
}

.table-metric:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* Button hover effects */
.btn-primary:hover {
  transform: translateY(-1px);
}

.btn-secondary:hover {
  transform: translateY(-1px);
}

.btn-danger:hover {
  transform: translateY(-1px);
}

/* Table row hover effects */
tbody tr:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Sort icon transitions */
svg {
  transition: color 0.2s ease-in-out;
}

/* Responsive table */
@media (max-width: 640px) {
  .table-metric {
    font-size: 0.875rem;
  }

  th, td {
    padding: 0.5rem 0.75rem;
  }
}
</style>
