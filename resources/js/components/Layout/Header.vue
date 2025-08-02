<template>
  <header class="bg-white border-b border-gray-200 px-6 py-4 shadow-sm" :class="{ 'bg-gray-800 border-gray-700': isDarkTheme }">
    <div class="flex items-center justify-between">
      <!-- Left side: Menu toggle and search -->
      <div class="flex items-center space-x-4">
        <!-- Mobile menu toggle -->
        <button
          @click="toggleSidebar"
          class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 md:hidden"
          :class="{ 'hover:bg-gray-700 hover:text-gray-300': isDarkTheme }"
        >
          <Bars3Icon class="h-6 w-6" />
        </button>

        <!-- Page title -->
        <div class="hidden md:block">
          <h1 class="text-2xl font-semibold text-gray-900" :class="{ 'text-white': isDarkTheme }">
            {{ pageTitle }}
          </h1>
        </div>

        <!-- Global search -->
        <div class="hidden md:block">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search resources..."
              class="pl-10 w-64 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
              :class="{ 'bg-gray-700 border-gray-600 text-white focus:ring-blue-400 focus:border-blue-400': isDarkTheme }"
              @keydown.enter="performSearch"
              @input="handleSearchInput"
            />
          </div>
        </div>
      </div>

      <!-- Right side: Actions and user menu -->
      <div class="flex items-center space-x-4">
        <!-- Theme toggle -->
        <button
          @click="toggleTheme"
          class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
          :class="{ 'hover:bg-gray-700 hover:text-gray-300': isDarkTheme }"
          :title="isDarkTheme ? 'Switch to light theme' : 'Switch to dark theme'"
        >
          <SunIcon v-if="isDarkTheme" class="h-5 w-5" />
          <MoonIcon v-else class="h-5 w-5" />
        </button>

        <!-- Notifications -->
        <div class="relative">
          <button
            @click="toggleNotifications"
            class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
            :class="{ 'hover:bg-gray-700 hover:text-gray-300': isDarkTheme }"
          >
            <BellIcon class="h-5 w-5" />
            <span
              v-if="unreadNotifications > 0"
              class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
            >
              {{ unreadNotifications > 9 ? '9+' : unreadNotifications }}
            </span>
          </button>

          <!-- Notifications dropdown -->
          <div
            v-if="showNotifications"
            class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
            :class="{ 'bg-gray-800 ring-gray-700': isDarkTheme }"
          >
            <NotificationDropdown @close="showNotifications = false" />
          </div>
        </div>

        <!-- User menu -->
        <div class="relative">
          <button
            @click="toggleUserMenu"
            class="flex items-center space-x-3 p-2 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
            :class="{ 'hover:bg-gray-700': isDarkTheme }"
          >
            <img
              class="h-8 w-8 rounded-full"
              :src="user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&background=3b82f6&color=fff`"
              :alt="user?.name"
            />
            <span class="hidden md:block text-sm font-medium text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
              {{ user?.name }}
            </span>
            <ChevronDownIcon class="hidden md:block h-4 w-4 text-gray-400" />
          </button>

          <!-- User dropdown -->
          <div
            v-if="showUserMenu"
            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
            :class="{ 'bg-gray-800 ring-gray-700': isDarkTheme }"
          >
            <UserDropdown @close="showUserMenu = false" />
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
/**
 * Header Component
 *
 * Top header bar with navigation toggle, search, theme toggle,
 * notifications, and user menu.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'
import {
  Bars3Icon,
  MagnifyingGlassIcon,
  BellIcon,
  SunIcon,
  MoonIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'
import NotificationDropdown from '@/components/Common/NotificationDropdown.vue'
import UserDropdown from '@/components/Common/UserDropdown.vue'

// Page data
const page = usePage()

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
const searchQuery = ref('')
const showNotifications = ref(false)
const showUserMenu = ref(false)

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)
const user = computed(() => page.props.auth?.user)
const unreadNotifications = computed(() => adminStore.unreadNotifications)
const pageTitle = computed(() => {
  // Extract page title from component name or props
  const component = page.component
  const title = page.props.title

  if (title) return title

  // Generate title from component name
  switch (component) {
    case 'Dashboard':
      return 'Dashboard'
    case 'Resources/Index':
      return page.props.resource?.label || 'Resources'
    case 'Resources/Show':
      return `${page.props.resource?.singularLabel || 'Resource'} Details`
    case 'Resources/Create':
      return `Create ${page.props.resource?.singularLabel || 'Resource'}`
    case 'Resources/Edit':
      return `Edit ${page.props.resource?.singularLabel || 'Resource'}`
    default:
      return 'Admin Panel'
  }
})

// Methods
const toggleSidebar = () => {
  adminStore.toggleSidebar()
}

const toggleTheme = () => {
  const newTheme = isDarkTheme.value ? 'default' : 'dark'
  adminStore.setTheme(newTheme)
}

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value
  showUserMenu.value = false
}

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
  showNotifications.value = false
}

const performSearch = () => {
  if (searchQuery.value.trim()) {
    // Implement global search functionality
    console.log('Searching for:', searchQuery.value)
    // You can navigate to a search results page or show a search modal
  }
}

const handleSearchInput = debounce(() => {
  // Implement live search suggestions
  if (searchQuery.value.length > 2) {
    console.log('Live search:', searchQuery.value)
  }
}, 300)

// Close dropdowns when clicking outside
const closeDropdowns = () => {
  showNotifications.value = false
  showUserMenu.value = false
}

// Add click outside listener
document.addEventListener('click', (event) => {
  if (!event.target.closest('.relative')) {
    closeDropdowns()
  }
})
</script>

<style scoped>
/* Ensure dropdowns appear above other content */
.z-50 {
  z-index: 50;
}
</style>
