<template>
  <div class="py-1">
    <!-- User info -->
    <div class="px-4 py-3 border-b border-gray-200" :class="{ 'border-gray-700': isDarkTheme }">
      <div class="flex items-center space-x-3">
        <img
          class="h-10 w-10 rounded-full"
          :src="user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&background=3b82f6&color=fff`"
          :alt="user?.name"
        />
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 truncate" :class="{ 'text-white': isDarkTheme }">
            {{ user?.name || 'User' }}
          </p>
          <p class="text-sm text-gray-500 truncate" :class="{ 'text-gray-400': isDarkTheme }">
            {{ user?.email }}
          </p>
        </div>
      </div>
    </div>

    <!-- Menu items -->
    <div class="py-1">
      <!-- Profile -->
      <Link
        :href="route('profile.show')"
        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
        :class="{ 'text-gray-300 hover:bg-gray-700 hover:text-white': isDarkTheme }"
        @click="emit('close')"
      >
        <UserIcon class="mr-3 h-5 w-5 text-gray-400" />
        Your Profile
      </Link>

      <!-- Settings -->
      <Link
        :href="route('profile.edit')"
        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
        :class="{ 'text-gray-300 hover:bg-gray-700 hover:text-white': isDarkTheme }"
        @click="emit('close')"
      >
        <CogIcon class="mr-3 h-5 w-5 text-gray-400" />
        Settings
      </Link>

      <!-- Theme toggle -->
      <button
        @click="toggleTheme"
        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
        :class="{ 'text-gray-300 hover:bg-gray-700 hover:text-white': isDarkTheme }"
      >
        <SunIcon v-if="isDarkTheme" class="mr-3 h-5 w-5 text-gray-400" />
        <MoonIcon v-else class="mr-3 h-5 w-5 text-gray-400" />
        {{ isDarkTheme ? 'Light Theme' : 'Dark Theme' }}
      </button>

      <!-- Divider -->
      <div class="border-t border-gray-200 my-1" :class="{ 'border-gray-700': isDarkTheme }"></div>

      <!-- Help & Support -->
      <a
        href="https://jerthedev.com/docs/admin-panel"
        target="_blank"
        rel="noopener noreferrer"
        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
        :class="{ 'text-gray-300 hover:bg-gray-700 hover:text-white': isDarkTheme }"
        @click="emit('close')"
      >
        <QuestionMarkCircleIcon class="mr-3 h-5 w-5 text-gray-400" />
        Help & Support
      </a>

      <!-- Keyboard shortcuts -->
      <button
        @click="showKeyboardShortcuts"
        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
        :class="{ 'text-gray-300 hover:bg-gray-700 hover:text-white': isDarkTheme }"
      >
        <CommandLineIcon class="mr-3 h-5 w-5 text-gray-400" />
        Keyboard Shortcuts
      </button>

      <!-- Divider -->
      <div class="border-t border-gray-200 my-1" :class="{ 'border-gray-700': isDarkTheme }"></div>

      <!-- Sign out -->
      <Link
        :href="route('logout')"
        method="post"
        as="button"
        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
        :class="{ 'text-gray-300 hover:bg-gray-700 hover:text-white': isDarkTheme }"
        @click="emit('close')"
      >
        <ArrowRightOnRectangleIcon class="mr-3 h-5 w-5 text-gray-400" />
        Sign out
      </Link>
    </div>
  </div>
</template>

<script setup>
/**
 * UserDropdown Component
 * 
 * User menu dropdown with profile actions, settings, theme toggle,
 * help links, and sign out functionality.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'
import {
  UserIcon,
  CogIcon,
  SunIcon,
  MoonIcon,
  QuestionMarkCircleIcon,
  CommandLineIcon,
  ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline'

// Emits
const emit = defineEmits(['close'])

// Page data
const page = usePage()

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)
const user = computed(() => page.props.auth?.user)

// Methods
const toggleTheme = () => {
  const newTheme = isDarkTheme.value ? 'default' : 'dark'
  adminStore.setTheme(newTheme)
  emit('close')
}

const showKeyboardShortcuts = () => {
  // Show keyboard shortcuts modal
  adminStore.notifyInfo('Keyboard shortcuts: Ctrl+K for search, Ctrl+/ for help', 'Shortcuts')
  emit('close')
}

const route = (name, params = {}) => {
  // Generate routes - you might want to use a proper route helper
  switch (name) {
    case 'profile.show':
      return '/profile'
    case 'profile.edit':
      return '/profile/edit'
    case 'logout':
      return '/logout'
    default:
      return '#'
  }
}
</script>

<style scoped>
/* Smooth transitions for hover effects */
a, button {
  transition: all 0.15s ease-in-out;
}

/* Ensure proper alignment for icons */
.flex.items-center svg {
  flex-shrink: 0;
}
</style>
