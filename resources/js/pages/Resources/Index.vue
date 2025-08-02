<template>
  <AdminLayout :title="`${resource.label} - Admin Panel`">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900" :class="{ 'text-white': isDarkTheme }">
            {{ resource.label }}
          </h1>
          <p class="mt-1 text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
            Manage your {{ resource.label.toLowerCase() }}
          </p>
        </div>

        <!-- Create button -->
        <Link
          v-if="resource.authorizedToCreate"
          :href="route('admin-panel.resources.create', resource.uriKey)"
          class="admin-btn-primary"
        >
          <PlusIcon class="h-4 w-4 mr-2" />
          Create {{ resource.singularLabel }}
        </Link>
      </div>

      <!-- Filters and Search -->
      <div class="admin-card">
        <div class="flex flex-col sm:flex-row gap-4">
          <!-- Search -->
          <div class="flex-1">
            <div class="relative">
              <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search resources..."
                class="admin-input pl-10 w-full"
                :class="{ 'admin-input-dark': isDarkTheme }"
                @input="debouncedSearch"
              />
            </div>
          </div>

          <!-- Filters -->
          <div v-if="filters.length > 0" class="flex gap-2">
            <select
              v-for="filter in filters"
              :key="filter.key"
              v-model="appliedFilters[filter.key]"
              class="admin-select"
              :class="{ 'admin-input-dark': isDarkTheme }"
              @change="applyFilters"
            >
              <option value="">{{ filter.name }}</option>
              <option
                v-for="option in filter.options"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <!-- Per page selector -->
          <select
            v-model="perPage"
            class="admin-select w-20"
            :class="{ 'admin-input-dark': isDarkTheme }"
            @change="changePerPage"
          >
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>

      <!-- Data table -->
      <div class="admin-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="admin-table">
            <thead class="admin-table-header" :class="{ 'admin-table-header-dark': isDarkTheme }">
              <tr>
                <!-- Bulk select -->
                <th class="px-6 py-3 w-12">
                  <input
                    v-model="selectAll"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    @change="toggleSelectAll"
                  />
                </th>

                <!-- Field headers -->
                <th
                  v-for="field in fields"
                  :key="field.attribute"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                  :class="{
                    'text-gray-400 hover:bg-gray-700': isDarkTheme,
                    'bg-gray-200': sort.field === field.attribute && !isDarkTheme,
                    'bg-gray-600': sort.field === field.attribute && isDarkTheme
                  }"
                  @click="field.sortable && toggleSort(field.attribute)"
                >
                  <div class="flex items-center space-x-1">
                    <span>{{ field.name }}</span>
                    <div v-if="field.sortable" class="flex flex-col">
                      <ChevronUpIcon
                        class="h-3 w-3"
                        :class="sort.field === field.attribute && sort.direction === 'asc' ? 'text-blue-600' : 'text-gray-400'"
                      />
                      <ChevronDownIcon
                        class="h-3 w-3 -mt-1"
                        :class="sort.field === field.attribute && sort.direction === 'desc' ? 'text-blue-600' : 'text-gray-400'"
                      />
                    </div>
                  </div>
                </th>

                <!-- Actions -->
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" :class="{ 'bg-gray-800 divide-gray-700': isDarkTheme }">
              <tr
                v-for="resourceItem in data.data"
                :key="resourceItem.id"
                class="hover:bg-gray-50"
                :class="{ 'hover:bg-gray-700': isDarkTheme }"
              >
                <!-- Bulk select -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <input
                    v-model="selectedResources"
                    :value="resourceItem.id"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                </td>

                <!-- Field data -->
                <td
                  v-for="field in fields"
                  :key="field.attribute"
                  class="admin-table-cell"
                  :class="{ 'admin-table-cell-dark': isDarkTheme }"
                >
                  <FieldDisplay
                    :field="field"
                    :value="resourceItem[field.attribute]"
                    context="index"
                  />
                </td>

                <!-- Actions -->
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end space-x-2">
                    <Link
                      :href="route('admin-panel.resources.show', [resource.uriKey, resourceItem.id])"
                      class="text-blue-600 hover:text-blue-900"
                      :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
                    >
                      <EyeIcon class="h-4 w-4" />
                    </Link>
                    <Link
                      :href="route('admin-panel.resources.edit', [resource.uriKey, resourceItem.id])"
                      class="text-amber-600 hover:text-amber-900"
                      :class="{ 'text-amber-400 hover:text-amber-300': isDarkTheme }"
                    >
                      <PencilIcon class="h-4 w-4" />
                    </Link>
                    <button
                      class="text-red-600 hover:text-red-900"
                      :class="{ 'text-red-400 hover:text-red-300': isDarkTheme }"
                      @click="confirmDelete(resourceItem)"
                    >
                      <TrashIcon class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Empty state -->
              <tr v-if="data.data.length === 0">
                <td :colspan="fields.length + 2" class="px-6 py-12 text-center">
                  <div class="text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                    <DocumentTextIcon class="mx-auto h-12 w-12 mb-4" />
                    <h3 class="text-lg font-medium mb-2">No {{ resource.label.toLowerCase() }} found</h3>
                    <p class="text-sm">
                      {{ searchQuery ? 'Try adjusting your search or filters.' : `Get started by creating a new ${resource.singularLabel.toLowerCase()}.` }}
                    </p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="data.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200" :class="{ 'bg-gray-800 border-gray-700': isDarkTheme }">
          <Pagination :links="data.links" />
        </div>
      </div>

      <!-- Bulk actions -->
      <div v-if="selectedResources.length > 0 && actions.length > 0" class="admin-card">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-600" :class="{ 'text-gray-400': isDarkTheme }">
            {{ selectedResources.length }} {{ selectedResources.length === 1 ? 'item' : 'items' }} selected
          </span>
          <div class="flex space-x-2">
            <button
              v-for="action in actions"
              :key="action.uriKey"
              class="admin-btn-secondary text-sm"
              @click="executeAction(action)"
            >
              {{ action.name }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <ConfirmationModal
      v-if="showDeleteModal"
      title="Delete Resource"
      :message="`Are you sure you want to delete this ${resource.singularLabel.toLowerCase()}? This action cannot be undone.`"
      confirm-text="Delete"
      confirm-color="red"
      @confirm="deleteResource"
      @cancel="showDeleteModal = false"
    />
  </AdminLayout>
</template>

<script setup>
/**
 * Resources Index Page
 *
 * Displays a paginated, searchable, and filterable list of resources
 * with bulk actions and CRUD operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'
import {
  PlusIcon,
  MagnifyingGlassIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
  DocumentTextIcon,
  ChevronUpIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'
import AdminLayout from '@/components/Layout/AdminLayout.vue'
import FieldDisplay from '@/components/Fields/FieldDisplay.vue'
import Pagination from '@/components/Common/Pagination.vue'
import ConfirmationModal from '@/components/Common/ConfirmationModal.vue'

// Props from Inertia
const props = defineProps({
  resource: Object,
  data: Object, // Changed from 'resources' to 'data' for table data
  fields: Array,
  filters: Array,
  actions: Array,
  search: String,
  appliedFilters: Object,
  sort: Object,
  perPage: Number,
})

// Store
const adminStore = useAdminStore()

// Local debounce utility
const debounce = (func, wait = 300) => {
  let timeout
  return (...args) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => func(...args), wait)
  }
}

// Reactive data
const searchQuery = ref(props.search || '')
const appliedFilters = ref({ ...props.appliedFilters })
const sort = ref({ ...props.sort })
const perPage = ref(props.perPage || 25)
const selectedResources = ref([])
const selectAll = ref(false)
const showDeleteModal = ref(false)
const resourceToDelete = ref(null)

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

// Methods
const debouncedSearch = debounce(() => {
  updateUrl()
}, 300)

const applyFilters = () => {
  updateUrl()
}

const changePerPage = () => {
  updateUrl()
}

const toggleSort = (field) => {
  if (sort.value.field === field) {
    sort.value.direction = sort.value.direction === 'asc' ? 'desc' : 'asc'
  } else {
    sort.value.field = field
    sort.value.direction = 'asc'
  }
  updateUrl()
}

const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedResources.value = props.data.data.map(r => r.id)
  } else {
    selectedResources.value = []
  }
}

const updateUrl = () => {
  const params = {
    search: searchQuery.value || undefined,
    filters: Object.keys(appliedFilters.value).length > 0 ? appliedFilters.value : undefined,
    sort_field: sort.value.field || undefined,
    sort_direction: sort.value.direction || undefined,
    per_page: perPage.value !== 25 ? perPage.value : undefined,
  }

  router.get(route('admin-panel.resources.index', props.resource.uriKey), params, {
    preserveState: true,
    preserveScroll: true,
  })
}

const confirmDelete = (resource) => {
  resourceToDelete.value = resource
  showDeleteModal.value = true
}

const deleteResource = () => {
  if (resourceToDelete.value) {
    router.delete(
      route('admin-panel.resources.destroy', [props.resource.uriKey, resourceToDelete.value.id]),
      {
        onSuccess: () => {
          adminStore.notifySuccess('Resource deleted successfully')
          showDeleteModal.value = false
          resourceToDelete.value = null
        },
        onError: () => {
          adminStore.notifyError('Failed to delete resource')
        }
      }
    )
  }
}

const executeAction = (action) => {
  if (selectedResources.value.length === 0) {
    adminStore.notifyWarning('Please select at least one resource')
    return
  }

  // Execute action via API
  axios.post(route('admin-panel.api.execute-action', [props.resource.uriKey, action.uriKey]), {
    resources: selectedResources.value
  })
  .then(response => {
    adminStore.notify(response.data.message, response.data.type)
    if (response.data.redirect) {
      router.visit(response.data.redirect)
    } else {
      // Refresh the page
      router.reload()
    }
  })
  .catch(error => {
    adminStore.notifyError(error.response?.data?.message || 'Action failed')
  })
}

// Use global route() function provided by Ziggy via @routes directive

// Watch for changes in selected resources
watch(selectedResources, (newVal) => {
  selectAll.value = newVal.length === props.data.data.length && newVal.length > 0
}, { deep: true })
</script>

<style scoped>
/* Additional styles for the index page */
.admin-table th {
  user-select: none;
}

.admin-table th.cursor-pointer:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.dark .admin-table th.cursor-pointer:hover {
  background-color: rgba(255, 255, 255, 0.05);
}
</style>
