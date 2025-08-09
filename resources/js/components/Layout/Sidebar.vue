<template>
  <aside
    class="fixed left-0 top-0 h-full w-64 bg-white border-r border-gray-200 shadow-sm z-30 transition-transform duration-300 ease-in-out overflow-y-auto"
    :class="{
      'bg-gray-800 border-gray-700': isDarkTheme,
      'transform -translate-x-full': !sidebarOpen && isMobile,
      'fixed z-50': isMobile
    }"
  >
    <!-- Logo/Brand -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200" :class="{ 'border-gray-700': isDarkTheme }">
      <div class="flex items-center space-x-3">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
          <span class="text-white font-bold text-sm">AP</span>
        </div>
        <h1 class="text-lg font-semibold text-gray-900" :class="{ 'text-white': isDarkTheme }">
          {{ appName }}
        </h1>
      </div>

      <!-- Mobile close button -->
      <button
        v-if="isMobile"
        @click="closeSidebar"
        class="p-1 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
        :class="{ 'hover:bg-gray-800 hover:text-gray-300': isDarkTheme }"
      >
        <XMarkIcon class="w-5 h-5" />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
      <!-- Dashboard -->
      <SidebarItem
        :href="routeHelper('admin-panel.dashboard')"
        :active="$page.component === 'Dashboard'"
        icon="HomeIcon"
      >
        Dashboard
      </SidebarItem>



      <!-- Resources Menu Widget -->
      <div class="pt-4">
        <ResourcesMenu />
      </div>



      <!-- Custom Pages -->
      <div v-if="customPages.length > 0" class="pt-4">
        <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider" :class="{ 'text-gray-400': isDarkTheme }">
          Pages
        </h3>
        <div class="mt-2 space-y-1">
          <SidebarItem
            v-for="page in customPages"
            :key="page.routeName"
            :href="routeHelper(page.routeName)"
            :active="$page.component === page.component"
            :icon="page.icon"
          >
            {{ page.label }}
          </SidebarItem>
        </div>
      </div>
    </nav>

    <!-- User section -->
    <div class="flex-shrink-0 border-t border-gray-200 p-4" :class="{ 'border-gray-700': isDarkTheme }">
      <div class="flex items-center space-x-3">
        <img
          class="w-8 h-8 rounded-full"
          :src="user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&background=3b82f6&color=fff`"
          :alt="user?.name"
        />
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 truncate" :class="{ 'text-white': isDarkTheme }">
            {{ user?.name || 'User' }}
          </p>
          <p class="text-xs text-gray-500 truncate" :class="{ 'text-gray-400': isDarkTheme }">
            {{ user?.email }}
          </p>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
/**
 * Sidebar Component
 *
 * Navigation sidebar with menu items, resources, and user information.
 * Responsive design with mobile overlay support.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import SidebarItem from './SidebarItem.vue'
import ResourcesMenu from '../Navigation/ResourcesMenu.vue'
import { route } from 'ziggy-js'

// Page data
const page = usePage()

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)
const sidebarOpen = computed(() => adminStore.sidebarOpen)
const user = computed(() => page.props.auth?.user)
const customPages = computed(() => page.props.pages || [])
const appName = computed(() => page.props.config?.admin_panel?.name || 'Admin Panel')
const isMobile = computed(() => window.innerWidth < 768)

// Methods
const closeSidebar = () => {
  adminStore.setSidebarOpen(false)
}

// Route helper using Ziggy
const routeHelper = (name, params = {}) => {
  try {
    return route(name, params)
  } catch (error) {
    console.warn('Route not found:', name, params)
    // Fallback: construct admin panel URLs manually
    const adminPath = page.props.config?.admin_path || '/admin'

    switch (name) {
      case 'admin-panel.dashboard':
        return adminPath
      case 'admin-panel.resources.index':
        return `${adminPath}/resources/${params.resource}`
      case 'admin-panel.resources.create':
        return `${adminPath}/resources/${params.resource}/create`
      case 'admin-panel.resources.show':
        return `${adminPath}/resources/${params.resource}/${params.id}`
      case 'admin-panel.resources.edit':
        return `${adminPath}/resources/${params.resource}/${params.id}/edit`
      default:
        // Handle custom page routes
        if (name && name.startsWith('admin-panel.pages.')) {
          const pageName = name.replace('admin-panel.pages.', '')
          return `${adminPath}/pages/${pageName}`
        }
        return '#'
    }
  }
}
</script>


