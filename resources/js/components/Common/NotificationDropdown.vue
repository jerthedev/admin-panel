<template>
  <div class="py-1">
    <!-- Header -->
    <div class="px-4 py-2 border-b border-gray-200" :class="{ 'border-gray-700': isDarkTheme }">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
          Notifications
        </h3>
        <button
          v-if="notifications.length > 0"
          @click="clearAll"
          class="text-xs text-blue-600 hover:text-blue-500"
          :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
        >
          Clear all
        </button>
      </div>
    </div>

    <!-- Notifications list -->
    <div class="max-h-96 overflow-y-auto">
      <div v-if="notifications.length === 0" class="px-4 py-8 text-center">
        <BellIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
          No notifications
        </h3>
        <p class="mt-1 text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
          You're all caught up!
        </p>
      </div>

      <div v-else class="divide-y divide-gray-200" :class="{ 'divide-gray-700': isDarkTheme }">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="px-4 py-3 hover:bg-gray-50 cursor-pointer"
          :class="{ 
            'hover:bg-gray-700': isDarkTheme,
            'bg-blue-50': !notification.read && !isDarkTheme,
            'bg-blue-900': !notification.read && isDarkTheme
          }"
          @click="markAsRead(notification.id)"
        >
          <div class="flex items-start space-x-3">
            <!-- Icon -->
            <div class="flex-shrink-0">
              <component
                :is="getNotificationIcon(notification.type)"
                class="h-5 w-5"
                :class="getNotificationIconColor(notification.type)"
              />
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <p
                v-if="notification.title"
                class="text-sm font-medium text-gray-900"
                :class="{ 'text-white': isDarkTheme }"
              >
                {{ notification.title }}
              </p>
              <p
                class="text-sm text-gray-600"
                :class="{ 
                  'text-gray-300': isDarkTheme,
                  'mt-1': notification.title 
                }"
              >
                {{ notification.message }}
              </p>
              <p class="mt-1 text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                {{ formatTime(notification.timestamp) }}
              </p>
            </div>

            <!-- Unread indicator -->
            <div v-if="!notification.read" class="flex-shrink-0">
              <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div
      v-if="notifications.length > 0"
      class="px-4 py-2 border-t border-gray-200 bg-gray-50"
      :class="{ 'border-gray-700 bg-gray-700': isDarkTheme }"
    >
      <button
        @click="viewAll"
        class="w-full text-center text-sm text-blue-600 hover:text-blue-500"
        :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
      >
        View all notifications
      </button>
    </div>
  </div>
</template>

<script setup>
/**
 * NotificationDropdown Component
 * 
 * Dropdown menu showing recent notifications with actions
 * to mark as read, clear all, and view all notifications.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import {
  BellIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'

// Emits
const emit = defineEmits(['close'])

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)
const notifications = computed(() => adminStore.notifications.slice(0, 10)) // Show only recent 10

// Methods
const markAsRead = (id) => {
  adminStore.markNotificationAsRead(id)
}

const clearAll = () => {
  adminStore.clearNotifications()
  emit('close')
}

const viewAll = () => {
  // Navigate to notifications page
  // You can implement this based on your routing needs
  console.log('Navigate to all notifications')
  emit('close')
}

const getNotificationIcon = (type) => {
  switch (type) {
    case 'success':
      return CheckCircleIcon
    case 'warning':
      return ExclamationTriangleIcon
    case 'error':
      return XCircleIcon
    case 'info':
    default:
      return InformationCircleIcon
  }
}

const getNotificationIconColor = (type) => {
  switch (type) {
    case 'success':
      return 'text-green-400'
    case 'warning':
      return 'text-amber-400'
    case 'error':
      return 'text-red-400'
    case 'info':
    default:
      return 'text-blue-400'
  }
}

const formatTime = (timestamp) => {
  const now = new Date()
  const time = new Date(timestamp)
  const diffInMinutes = Math.floor((now - time) / (1000 * 60))

  if (diffInMinutes < 1) {
    return 'Just now'
  } else if (diffInMinutes < 60) {
    return `${diffInMinutes}m ago`
  } else if (diffInMinutes < 1440) {
    const hours = Math.floor(diffInMinutes / 60)
    return `${hours}h ago`
  } else {
    const days = Math.floor(diffInMinutes / 1440)
    return `${days}d ago`
  }
}
</script>

<style scoped>
/* Smooth transitions for hover effects */
.cursor-pointer {
  transition: background-color 0.15s ease-in-out;
}
</style>
