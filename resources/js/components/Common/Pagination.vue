<template>
  <nav class="flex items-center justify-between" aria-label="Pagination">
    <!-- Mobile pagination -->
    <div class="flex flex-1 justify-between sm:hidden">
      <Link
        v-if="links.prev"
        :href="links.prev"
        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        :class="{ 'text-gray-300 bg-gray-800 border-gray-600 hover:bg-gray-700': isDarkTheme }"
      >
        Previous
      </Link>
      <span v-else class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-md cursor-not-allowed">
        Previous
      </span>

      <Link
        v-if="links.next"
        :href="links.next"
        class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        :class="{ 'text-gray-300 bg-gray-800 border-gray-600 hover:bg-gray-700': isDarkTheme }"
      >
        Next
      </Link>
      <span v-else class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-md cursor-not-allowed">
        Next
      </span>
    </div>

    <!-- Desktop pagination -->
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <!-- Results info -->
      <div>
        <p class="text-sm text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
          Showing
          <span class="font-medium">{{ meta.from || 0 }}</span>
          to
          <span class="font-medium">{{ meta.to || 0 }}</span>
          of
          <span class="font-medium">{{ meta.total || 0 }}</span>
          results
        </p>
      </div>

      <!-- Page links -->
      <div>
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
          <!-- Previous button -->
          <Link
            v-if="links.prev"
            :href="links.prev"
            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
            :class="{ 'bg-gray-800 border-gray-600 text-gray-300 hover:bg-gray-700': isDarkTheme }"
          >
            <ChevronLeftIcon class="h-5 w-5" />
            <span class="sr-only">Previous</span>
          </Link>
          <span
            v-else
            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-300 cursor-not-allowed"
            :class="{ 'bg-gray-800 border-gray-600': isDarkTheme }"
          >
            <ChevronLeftIcon class="h-5 w-5" />
            <span class="sr-only">Previous</span>
          </span>

          <!-- Page numbers -->
          <template v-for="(link, index) in pageLinks" :key="index">
            <Link
              v-if="link.url && !link.active"
              :href="link.url"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
              :class="{ 'bg-gray-800 border-gray-600 text-gray-300 hover:bg-gray-700': isDarkTheme }"
            >
              {{ link.label }}
            </Link>
            <span
              v-else-if="link.active"
              class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600"
              :class="{ 'bg-blue-900 border-blue-400 text-blue-200': isDarkTheme }"
            >
              {{ link.label }}
            </span>
            <span
              v-else
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-300"
              :class="{ 'bg-gray-800 border-gray-600': isDarkTheme }"
            >
              {{ link.label }}
            </span>
          </template>

          <!-- Next button -->
          <Link
            v-if="links.next"
            :href="links.next"
            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
            :class="{ 'bg-gray-800 border-gray-600 text-gray-300 hover:bg-gray-700': isDarkTheme }"
          >
            <ChevronRightIcon class="h-5 w-5" />
            <span class="sr-only">Next</span>
          </Link>
          <span
            v-else
            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-300 cursor-not-allowed"
            :class="{ 'bg-gray-800 border-gray-600': isDarkTheme }"
          >
            <ChevronRightIcon class="h-5 w-5" />
            <span class="sr-only">Next</span>
          </span>
        </nav>
      </div>
    </div>
  </nav>
</template>

<script setup>
/**
 * Pagination Component
 * 
 * Responsive pagination component with page numbers, navigation arrows,
 * and results information display.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  links: {
    type: Object,
    required: true
  }
})

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const meta = computed(() => {
  return props.links.meta || {}
})

const pageLinks = computed(() => {
  if (!props.links.links) return []
  
  // Remove first and last links (prev/next)
  const links = props.links.links.slice(1, -1)
  
  // Process ellipsis and page numbers
  return links.map(link => ({
    ...link,
    label: link.label === '...' ? '…' : link.label
  }))
})
</script>

<style scoped>
@import '../../../css/admin.css' reference;

/* Ensure proper spacing and alignment */
nav {
}

/* Hover effects */
a {
}

/* Active page styling */
.bg-blue-50 {
  background-color: rgba(59, 130, 246, 0.1);
}

.dark .bg-blue-900 {
  background-color: rgba(30, 58, 138, 0.3);
}

/* Disabled state */
.cursor-not-allowed {
}

/* Focus styles */
a:focus {
}

.dark a:focus {
}
</style>
