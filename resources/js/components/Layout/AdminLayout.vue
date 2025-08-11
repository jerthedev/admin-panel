<template>
  <div class="min-h-screen bg-gray-50" :class="{ 'dark': isDarkTheme }">
    <!-- Mobile sidebar overlay -->
    <div
      v-if="!sidebarOpen && isMobile"
      class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 transition-opacity duration-300 ease-linear"
      @click="closeSidebar"
    ></div>

    <!-- Sidebar -->
    <Sidebar />

    <!-- Main content area -->
    <div
      class="relative z-10"
      :class="{
        'bg-gray-900': isDarkTheme,
        'ml-0 md:ml-64': !fullscreenMode,
        'ml-0': fullscreenMode
      }"
    >
      <!-- Header -->
      <Header v-show="!fullscreenMode" />

      <!-- Breadcrumbs -->
      <Breadcrumbs v-if="showBreadcrumbs && !fullscreenMode" />

      <!-- Page content -->
      <main
        class="flex-1"
        :class="{
          'p-6': !fullscreenMode,
          'p-0': fullscreenMode
        }"
      >
        <slot />
      </main>

      <!-- Footer -->
      <Footer v-show="!fullscreenMode" />
    </div>

    <!-- Notifications -->
    <NotificationContainer />
  </div>
</template>

<script setup>
/**
 * AdminLayout Component
 *
 * Main layout wrapper for the admin panel providing the overall
 * structure with sidebar, header, breadcrumbs, and content areas.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, onMounted, onUnmounted } from 'vue'
import { useAdminStore } from '@/stores/admin'
import Sidebar from './Sidebar.vue'
import Header from './Header.vue'
import Breadcrumbs from './Breadcrumbs.vue'
import Footer from './Footer.vue'
import NotificationContainer from '@/components/Common/NotificationContainer.vue'

// Props
const props = defineProps({
  showBreadcrumbs: {
    type: Boolean,
    default: true
  },
  title: {
    type: String,
    default: ''
  }
})

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)
const sidebarOpen = computed(() => adminStore.sidebarOpen)
const fullscreenMode = computed(() => adminStore.fullscreenMode)
const isMobile = computed(() => window.innerWidth < 768)

// Methods
const closeSidebar = () => {
  if (isMobile.value) {
    adminStore.setSidebarOpen(false)
  }
}

const handleResize = () => {
  // Auto-close sidebar on mobile, auto-open on desktop
  if (window.innerWidth < 768) {
    adminStore.setSidebarOpen(false)
  } else {
    adminStore.setSidebarOpen(true)
  }
}

// Lifecycle
onMounted(() => {
  // Initialize the admin store
  adminStore.initialize()

  // Set page title if provided
  if (props.title) {
    document.title = `${props.title} - ${window.adminPanelConfig?.appName || 'Admin Panel'}`
  }

  // Add resize listener
  window.addEventListener('resize', handleResize)

  // Initial resize check
  handleResize()
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>


