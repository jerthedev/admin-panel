<template>
  <div class="mobile-notifications-panel">
    <div class="panel-header">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h2>
      <button
        v-if="notifications.length > 0"
        @click="markAllAsRead"
        class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
      >
        Mark all as read
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-8">
      <svg class="animate-spin h-6 w-6 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <div v-else-if="notifications.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
      <BellSlashIcon class="mx-auto h-12 w-12 mb-3 text-gray-300 dark:text-gray-600" />
      <p>No notifications</p>
    </div>

    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
      <div
        v-for="notification in notifications"
        :key="notification.id"
        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
        :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.read }"
        @click="handleNotificationClick(notification)"
      >
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0">
            <div class="w-2 h-2 rounded-full mt-2" :class="notification.read ? 'bg-gray-300' : 'bg-blue-600'"></div>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
              {{ notification.title }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              {{ notification.message }}
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
              {{ formatTime(notification.created_at) }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { BellSlashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  onClose: {
    type: Function,
    default: null
  }
})

const emit = defineEmits(['close', 'notification-click'])

const notifications = ref([])
const loading = ref(true)

const formatTime = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now - date
  const seconds = Math.floor(diff / 1000)
  const minutes = Math.floor(seconds / 60)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)

  if (days > 0) return `${days}d ago`
  if (hours > 0) return `${hours}h ago`
  if (minutes > 0) return `${minutes}m ago`
  return 'Just now'
}

const markAllAsRead = () => {
  notifications.value.forEach(n => {
    n.read = true
  })
}

const handleNotificationClick = (notification) => {
  notification.read = true
  emit('notification-click', notification)
  if (notification.url) {
    window.location.href = notification.url
  }
}

onMounted(() => {
  // Simulate loading notifications
  setTimeout(() => {
    loading.value = false
    // Mock data - replace with actual API call
    notifications.value = []
  }, 500)
})
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.mobile-notifications-panel {
  @apply h-full flex flex-col;
}

.panel-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}
</style>