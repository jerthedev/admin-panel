<template>
  <AdminLayout :title="`${resource.singularLabel} Details - Admin Panel`">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900" :class="{ 'text-white': isDarkTheme }">
            {{ resource.singularLabel }} Details
          </h1>
          <p class="mt-1 text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
            View and manage this {{ resource.singularLabel.toLowerCase() }}
          </p>
        </div>

        <!-- Action buttons -->
        <div class="flex space-x-3">
          <Link
            :href="route('admin-panel.resources.index', resource.uriKey)"
            class="admin-btn-outline"
          >
            <ArrowLeftIcon class="h-4 w-4 mr-2" />
            Back to {{ resource.label }}
          </Link>

          <Link
            :href="route('admin-panel.resources.edit', [resource.uriKey, resourceData.id])"
            class="admin-btn-secondary"
          >
            <PencilIcon class="h-4 w-4 mr-2" />
            Edit
          </Link>

          <button
            class="admin-btn-danger"
            @click="confirmDelete"
          >
            <TrashIcon class="h-4 w-4 mr-2" />
            Delete
          </button>
        </div>
      </div>

      <!-- Resource details -->
      <div class="admin-card">
        <div class="px-4 py-5 sm:p-0">
          <dl class="sm:divide-y sm:divide-gray-200" :class="{ 'sm:divide-gray-700': isDarkTheme }">
            <div
              v-for="field in fields"
              :key="field.attribute"
              class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
            >
              <dt class="text-sm font-medium text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                {{ field.name }}
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2" :class="{ 'text-white': isDarkTheme }">
                <FieldDisplay
                  :field="field"
                  :value="resourceData[field.attribute]"
                  context="detail"
                />
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Actions -->
      <div v-if="actions.length > 0" class="admin-card">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" :class="{ 'text-white': isDarkTheme }">
            Actions
          </h3>
          <div class="flex flex-wrap gap-3">
            <button
              v-for="action in actions"
              :key="action.uriKey"
              class="admin-btn-secondary"
              @click="executeAction(action)"
            >
              <component
                v-if="action.icon"
                :is="getIcon(action.icon)"
                class="h-4 w-4 mr-2"
              />
              {{ action.name }}
            </button>
          </div>
        </div>
      </div>

      <!-- Metadata -->
      <div class="admin-card">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" :class="{ 'text-white': isDarkTheme }">
            Metadata
          </h3>
          <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div>
              <dt class="text-sm font-medium text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                Resource ID
              </dt>
              <dd class="mt-1 text-sm text-gray-900" :class="{ 'text-white': isDarkTheme }">
                {{ resourceData.id }}
              </dd>
            </div>
            <div v-if="resourceData.created_at">
              <dt class="text-sm font-medium text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                Created
              </dt>
              <dd class="mt-1 text-sm text-gray-900" :class="{ 'text-white': isDarkTheme }">
                {{ formatDate(resourceData.created_at) }}
              </dd>
            </div>
            <div v-if="resourceData.updated_at">
              <dt class="text-sm font-medium text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                Last Updated
              </dt>
              <dd class="mt-1 text-sm text-gray-900" :class="{ 'text-white': isDarkTheme }">
                {{ formatDate(resourceData.updated_at) }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                Resource Type
              </dt>
              <dd class="mt-1 text-sm text-gray-900" :class="{ 'text-white': isDarkTheme }">
                {{ resource.singularLabel }}
              </dd>
            </div>
          </dl>
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
 * Resources Show Page
 *
 * Displays detailed view of a resource with all fields,
 * actions, and metadata information.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'
import {
  ArrowLeftIcon,
  PencilIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'
import * as HeroIcons from '@heroicons/vue/24/outline'
import AdminLayout from '@/components/Layout/AdminLayout.vue'
import FieldDisplay from '@/components/Fields/FieldDisplay.vue'
import ConfirmationModal from '@/components/Common/ConfirmationModal.vue'

// Props from Inertia
const props = defineProps({
  resource: Object,
  resourceData: Object,
  fields: Array,
  actions: Array,
})

// Store
const adminStore = useAdminStore()

// Reactive data
const showDeleteModal = ref(false)

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

// Methods
const getIcon = (iconName) => {
  return HeroIcons[iconName] || HeroIcons.DocumentTextIcon
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'

  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    return dateString
  }
}

const confirmDelete = () => {
  showDeleteModal.value = true
}

const deleteResource = () => {
  router.delete(
    route('admin-panel.resources.destroy', [props.resource.uriKey, props.resourceData.id]),
    {
      onSuccess: () => {
        adminStore.notifySuccess(`${props.resource.singularLabel} deleted successfully`)
        showDeleteModal.value = false
      },
      onError: () => {
        adminStore.notifyError('Failed to delete resource')
        showDeleteModal.value = false
      }
    }
  )
}

const executeAction = (action) => {
  // Execute action via API
  axios.post(route('admin-panel.api.execute-action', [props.resource.uriKey, action.uriKey]), {
    resources: [props.resourceData.id]
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
</script>

<style scoped>
/* Custom styles for the show page */
.admin-card dl {
}

.dark .admin-card dl {
}

/* Metadata grid styling */
.grid dt {
}

.grid dd {
}

/* Action buttons spacing */
.flex.flex-wrap.gap-3 button {
}
</style>
