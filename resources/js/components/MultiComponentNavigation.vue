<template>
  <div v-if="hasMultipleComponents" class="border-b border-gray-200 mb-6">
    <!-- Component Tabs -->
    <nav class="-mb-px flex space-x-8" aria-label="Component Navigation">
      <button
        v-for="component in availableComponents"
        :key="component"
        @click="navigateToComponent(component)"
        :class="[
          'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
          isCurrentComponent(component)
            ? 'border-blue-500 text-blue-600'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
        ]"
        :aria-current="isCurrentComponent(component) ? 'page' : undefined"
      >
        {{ formatComponentName(component) }}
      </button>
    </nav>

    <!-- Breadcrumb -->
    <div v-if="showBreadcrumb" class="mt-4 flex items-center text-sm text-gray-500">
      <span>{{ pageTitle }}</span>
      <svg class="flex-shrink-0 mx-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
      </svg>
      <span class="font-medium text-gray-900">{{ formatComponentName(currentComponent) }}</span>
    </div>

    <!-- Unsaved Changes Warning -->
    <div v-if="hasUnsavedChanges" class="mt-4 bg-yellow-50 border border-yellow-200 rounded-md p-3">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-yellow-800">
            You have unsaved changes. They will be preserved when switching components.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useMultiComponentPageStore } from '@jerthedev-admin-panel/stores/multiComponentPage'

const props = defineProps({
  pageTitle: {
    type: String,
    required: true
  },
  availableComponents: {
    type: Array,
    required: true
  },
  currentComponent: {
    type: String,
    required: true
  },
  componentUrls: {
    type: Object,
    required: true
  },
  showBreadcrumb: {
    type: Boolean,
    default: true
  }
})

const multiComponentStore = useMultiComponentPageStore()

// Computed properties
const hasMultipleComponents = computed(() => props.availableComponents.length > 1)
const hasUnsavedChanges = computed(() => multiComponentStore.hasUnsavedChanges)

// Methods
function isCurrentComponent(component) {
  return component === props.currentComponent
}

function formatComponentName(component) {
  // Convert component path to readable name
  // e.g., "Pages/SystemDashboard" -> "System Dashboard"
  const name = component.split('/').pop()
  return name.replace(/([A-Z])/g, ' $1').trim()
}

function navigateToComponent(component) {
  if (component === props.currentComponent) {
    return
  }

  const url = props.componentUrls[component]
  if (!url) {
    console.warn(`No URL found for component: ${component}`)
    return
  }

  // Update store state
  multiComponentStore.navigateToComponent(component)

  // Navigate using Inertia
  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
    only: ['page', 'multiComponent'], // Only reload necessary data
    onSuccess: () => {
      console.log(`🔄 Navigated to component: ${component}`)
    },
    onError: (errors) => {
      console.error('Navigation failed:', errors)
    }
  })
}
</script>

<!-- Styles removed to avoid Tailwind CSS issues -->
