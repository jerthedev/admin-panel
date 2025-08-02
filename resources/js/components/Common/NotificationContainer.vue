<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <TransitionGroup
      name="notification"
      tag="div"
      class="space-y-2"
    >
      <div
        v-for="notification in notifications"
        :key="notification.id"
        class="max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden"
        :class="{ 'bg-gray-800 ring-gray-700': isDarkTheme }"
      >
        <div class="p-4">
          <div class="flex items-start">
            <!-- Icon -->
            <div class="flex-shrink-0">
              <component
                :is="getNotificationIcon(notification.type)"
                class="h-6 w-6"
                :class="getNotificationIconColor(notification.type)"
              />
            </div>

            <!-- Content -->
            <div class="ml-3 w-0 flex-1 pt-0.5">
              <p
                v-if="notification.title"
                class="text-sm font-medium text-gray-900"
                :class="{ 'text-white': isDarkTheme }"
              >
                {{ notification.title }}
              </p>
              <p
                class="text-sm text-gray-500"
                :class="{ 
                  'text-gray-300': isDarkTheme,
                  'mt-1': notification.title 
                }"
              >
                {{ notification.message }}
              </p>
            </div>

            <!-- Close button -->
            <div class="ml-4 flex-shrink-0 flex">
              <button
                @click="removeNotification(notification.id)"
                class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                :class="{ 'bg-gray-800 hover:text-gray-300': isDarkTheme }"
              >
                <XMarkIcon class="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Progress bar for auto-dismiss -->
        <div
          v-if="['success', 'info'].includes(notification.type)"
          class="h-1 bg-gray-200"
          :class="{ 'bg-gray-700': isDarkTheme }"
        >
          <div
            class="h-full bg-blue-500 transition-all duration-5000 ease-linear"
            :class="{ 'w-0': notification.dismissing }"
            style="width: 100%"
          ></div>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
/**
 * NotificationContainer Component
 * 
 * Container for displaying toast notifications with different types,
 * auto-dismiss functionality, and smooth animations.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, onMounted, onUnmounted } from 'vue'
import { useAdminStore } from '@/stores/admin'
import {
  XMarkIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)
const notifications = computed(() => adminStore.notifications)

// Methods
const removeNotification = (id) => {
  adminStore.removeNotification(id)
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

// Auto-dismiss notifications
let dismissTimers = new Map()

const setupAutoDismiss = (notification) => {
  if (['success', 'info'].includes(notification.type)) {
    const timer = setTimeout(() => {
      removeNotification(notification.id)
    }, 5000)
    
    dismissTimers.set(notification.id, timer)
  }
}

const clearAutoDismiss = (notificationId) => {
  if (dismissTimers.has(notificationId)) {
    clearTimeout(dismissTimers.get(notificationId))
    dismissTimers.delete(notificationId)
  }
}

// Watch for new notifications
const unwatchNotifications = adminStore.$subscribe((mutation, state) => {
  if (mutation.type === 'direct' && mutation.events?.key === 'notifications') {
    // Set up auto-dismiss for new notifications
    state.notifications.forEach(notification => {
      if (!dismissTimers.has(notification.id)) {
        setupAutoDismiss(notification)
      }
    })
  }
})

// Lifecycle
onMounted(() => {
  // Set up auto-dismiss for existing notifications
  notifications.value.forEach(setupAutoDismiss)
})

onUnmounted(() => {
  // Clear all timers
  dismissTimers.forEach(timer => clearTimeout(timer))
  dismissTimers.clear()
  
  // Unwatch notifications
  unwatchNotifications()
})
</script>

<style scoped>
/* Notification animations */
.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.notification-move {
  transition: transform 0.3s ease;
}

/* Progress bar animation */
.transition-all {
  transition-property: width;
}

.duration-5000 {
  transition-duration: 5000ms;
}
</style>
