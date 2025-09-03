<template>
  <div class="multi-component-page">
    <!-- Page Header -->
    <div class="bg-white shadow">
      <div class="px-4 sm:px-6 lg:px-8">
        <div class="py-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div v-if="page.icon" class="flex-shrink-0">
                <component :is="getIconComponent(page.icon)" class="h-8 w-8 text-gray-400" />
              </div>
              <div class="ml-4">
                <h1 class="text-2xl font-bold text-gray-900">{{ page.title }}</h1>
                <p v-if="page.group" class="text-sm text-gray-500">{{ page.group }}</p>
              </div>
            </div>

            <!-- Page Actions -->
            <div v-if="actions.length > 0" class="flex space-x-3">
              <button
                v-for="action in actions"
                :key="action.name"
                @click="executeAction(action)"
                :class="[
                  'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm',
                  action.style === 'primary'
                    ? 'text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'
                    : 'text-gray-700 bg-white hover:bg-gray-50 border-gray-300 focus:ring-blue-500',
                  'focus:outline-none focus:ring-2 focus:ring-offset-2'
                ]"
                :disabled="isSaving"
              >
                <component
                  v-if="action.icon"
                  :is="getIconComponent(action.icon)"
                  class="h-4 w-4 mr-2"
                />
                {{ action.label }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Multi-Component Navigation -->
    <div class="bg-white border-b border-gray-200">
      <div class="px-4 sm:px-6 lg:px-8">
        <MultiComponentNavigation
          :page-title="page.title"
          :available-components="multiComponent?.availableComponents || []"
          :current-component="multiComponent?.currentComponent || ''"
          :component-urls="multiComponent?.componentUrls || {}"
          :show-breadcrumb="true"
        />
      </div>
    </div>

    <!-- Page Content -->
    <div class="flex-1 bg-gray-50">
      <div class="px-4 sm:px-6 lg:px-8 py-8">
        <!-- Metrics Row -->
        <div v-if="metrics.length > 0" class="mb-8">
          <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div
              v-for="metric in metrics"
              :key="metric.name"
              class="bg-white overflow-hidden shadow rounded-lg"
            >
              <div class="p-5">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <component
                      :is="getIconComponent(metric.icon)"
                      :class="['h-6 w-6', `text-${metric.color}-600`]"
                    />
                  </div>
                  <div class="ml-5 w-0 flex-1">
                    <dl>
                      <dt class="text-sm font-medium text-gray-500 truncate">
                        {{ metric.name }}
                      </dt>
                      <dd class="text-lg font-medium text-gray-900">
                        {{ formatMetricValue(metric.value, metric.format) }}
                      </dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Component Content -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-6">
            <slot
              :fields="fields"
              :data="data"
              :page="page"
              :multiComponent="multiComponent"
              :updateField="updateField"
              :getFieldValue="getFieldValue"
              :hasFieldChanged="hasFieldChanged"
              :saveChanges="saveChanges"
              :isSaving="isSaving"
            />
          </div>
        </div>

        <!-- Save Button (if has changes) -->
        <div v-if="hasUnsavedChanges" class="mt-6 flex justify-end">
          <button
            @click="saveAllChanges"
            :disabled="isSaving"
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="isSaving" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isSaving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { useMultiComponentPageStore } from '@jerthedev-admin-panel/stores/multiComponentPage'
import MultiComponentNavigation from '@jerthedev-admin-panel/components/MultiComponentNavigation.vue'

const props = defineProps({
  page: {
    type: Object,
    required: true
  },
  fields: {
    type: Array,
    default: () => []
  },
  actions: {
    type: Array,
    default: () => []
  },
  metrics: {
    type: Array,
    default: () => []
  },
  data: {
    type: Object,
    default: () => ({})
  },
  multiComponent: {
    type: Object,
    default: null
  }
})

const multiComponentStore = useMultiComponentPageStore()

// Computed properties
const hasUnsavedChanges = computed(() => multiComponentStore.hasUnsavedChanges)
const isSaving = computed(() => multiComponentStore.isSaving)

// Initialize multi-component page
onMounted(() => {
  if (props.multiComponent) {
    multiComponentStore.initializePage(
      props.page,
      props.multiComponent.availableComponents,
      props.data
    )
  }
})

// Cleanup on unmount
onUnmounted(() => {
  // Don't reset store here - let it persist for navigation
})

// Methods
function getIconComponent(iconName) {
  // This would be replaced with your icon system
  // For now, return a placeholder
  return 'div'
}

function formatMetricValue(value, format) {
  switch (format) {
    case 'currency':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(value)
    case 'percentage':
      return `${value}%`
    case 'number':
      return new Intl.NumberFormat('en-US').format(value)
    default:
      return value
  }
}

function updateField(fieldName, value) {
  multiComponentStore.updateField(fieldName, value)
}

function getFieldValue(fieldName, defaultValue = null) {
  return multiComponentStore.getFieldValue(fieldName, defaultValue)
}

function hasFieldChanged(fieldName) {
  return multiComponentStore.hasFieldChanged(fieldName)
}

function saveChanges(additionalData = {}) {
  return multiComponentStore.saveChanges('/admin/api/pages/save', additionalData)
}

async function saveAllChanges() {
  const result = await saveChanges({
    page_slug: props.page.slug,
    component: props.multiComponent?.currentComponent
  })

  if (result.success) {
    // Show success message
    console.log('✅ Changes saved successfully')
  } else {
    // Show error message
    console.error('❌ Failed to save changes:', result.error)
  }
}

function executeAction(action) {
  // Execute page action
  console.log('Executing action:', action.name)

  if (action.handler) {
    action.handler()
  }
}
</script>

<style scoped>
@import '../../css/admin.css' reference;

.multi-component-page {
  @apply min-h-screen flex flex-col;
}
</style>
