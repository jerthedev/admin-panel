<template>
  <footer class="bg-white border-t border-gray-200 px-6 py-4 mt-auto" :class="{ 'bg-gray-800 border-gray-700': isDarkTheme }">
    <div class="flex items-center justify-between">
      <!-- Left side: Copyright and version -->
      <div class="flex items-center space-x-4 text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
        <span>
          © {{ currentYear }} 
          <a 
            href="https://jerthedev.com" 
            target="_blank" 
            rel="noopener noreferrer"
            class="hover:text-gray-700 transition-colors duration-150"
            :class="{ 'hover:text-gray-200': isDarkTheme }"
          >
            JerTheDev
          </a>
        </span>
        <span class="text-gray-300" :class="{ 'text-gray-600': isDarkTheme }">•</span>
        <span>
          Admin Panel v{{ version }}
        </span>
      </div>

      <!-- Right side: Links and status -->
      <div class="flex items-center space-x-4">
        <!-- Status indicator -->
        <div class="flex items-center space-x-2">
          <div class="flex items-center space-x-1">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
              Online
            </span>
          </div>
        </div>

        <!-- Links -->
        <div class="flex items-center space-x-4 text-sm">
          <a
            href="https://jerthedev.com/docs/admin-panel"
            target="_blank"
            rel="noopener noreferrer"
            class="text-gray-500 hover:text-gray-700 transition-colors duration-150"
            :class="{ 'text-gray-400 hover:text-gray-200': isDarkTheme }"
          >
            Documentation
          </a>
          <a
            href="https://github.com/jerthedev/admin-panel"
            target="_blank"
            rel="noopener noreferrer"
            class="text-gray-500 hover:text-gray-700 transition-colors duration-150"
            :class="{ 'text-gray-400 hover:text-gray-200': isDarkTheme }"
          >
            GitHub
          </a>
          <a
            href="mailto:jerthedev@gmail.com"
            class="text-gray-500 hover:text-gray-700 transition-colors duration-150"
            :class="{ 'text-gray-400 hover:text-gray-200': isDarkTheme }"
          >
            Support
          </a>
        </div>
      </div>
    </div>

    <!-- Development mode indicator -->
    <div
      v-if="isDevelopment"
      class="mt-2 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded px-2 py-1 inline-block"
      :class="{ 'text-amber-400 bg-amber-900 border-amber-700': isDarkTheme }"
    >
      <span class="font-medium">Development Mode</span> - 
      Debug information is enabled
    </div>
  </footer>
</template>

<script setup>
/**
 * Footer Component
 * 
 * Admin panel footer with copyright, version info, links,
 * and system status indicators.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'

// Page data
const page = usePage()

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)
const currentYear = computed(() => new Date().getFullYear())
const version = computed(() => adminStore.version || '1.0.0')
const isDevelopment = computed(() => {
  return window.adminPanelConfig?.debug || 
         import.meta.env.DEV || 
         page.props.app?.debug
})
</script>

<style scoped>
footer {
  flex-shrink: 0;
}

/* Ensure links have proper hover states */
a {
  transition: color 0.15s ease-in-out;
}

/* Status indicator animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
