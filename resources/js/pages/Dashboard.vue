<template>
  <AdminLayout title="Dashboard">
    <div class="space-y-6">
      <!-- Welcome section -->
      <div class="bg-white shadow-sm border border-gray-200 rounded-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900" :class="{ 'text-white': isDarkTheme }">
              Welcome back, {{ user?.name || 'Admin' }}!
            </h1>
            <p class="mt-1 text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
              Here's what's happening with your application today.
            </p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
              {{ currentDate }}
            </p>
            <p class="text-xs text-gray-400">
              Last updated: {{ lastUpdated }}
            </p>
          </div>
        </div>
      </div>

      <!-- Metrics grid -->
      <div v-if="metrics.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="metric in metrics"
          :key="metric.name"
          class="bg-white shadow-sm border border-gray-200 rounded-md p-6"
        >
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div
                class="w-8 h-8 rounded-md flex items-center justify-center"
                :class="`bg-${metric.color}-100`"
              >
                <component
                  :is="getIcon(metric.icon)"
                  class="w-5 h-5"
                  :class="`text-${metric.color}-600`"
                />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate" :class="{ 'text-gray-400': isDarkTheme }">
                  {{ metric.name }}
                </dt>
                <dd class="flex items-baseline">
                  <div class="text-2xl font-semibold text-gray-900" :class="{ 'text-white': isDarkTheme }">
                    {{ formatMetricValue(metric.value, metric.format) }}
                  </div>
                  <div
                    v-if="metric.trend"
                    class="ml-2 flex items-baseline text-sm font-semibold"
                    :class="getTrendColor(metric.trend.direction)"
                  >
                    <ArrowUpIcon v-if="metric.trend.direction === 'up'" class="self-center flex-shrink-0 h-4 w-4" />
                    <ArrowDownIcon v-if="metric.trend.direction === 'down'" class="self-center flex-shrink-0 h-4 w-4" />
                    {{ metric.trend.percentage }}%
                  </div>
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- Main content grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick actions -->
        <div class="lg:col-span-1">
          <div class="bg-white shadow-sm border border-gray-200 rounded-md p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
                Quick Actions
              </h3>
            </div>
            <div class="space-y-3">
              <Link
                v-for="action in quickActions"
                :key="action.label"
                :href="action.href"
                class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors duration-150"
                :class="{
                  'border-gray-600 hover:bg-gray-700': isDarkTheme,
                  'cursor-pointer': !action.action
                }"
                @click="action.action && handleQuickAction(action.action)"
              >
                <div
                  class="flex-shrink-0 w-8 h-8 rounded-md flex items-center justify-center"
                  :class="`bg-${action.color}-100`"
                >
                  <component
                    :is="getIcon(action.icon)"
                    class="w-4 h-4"
                    :class="`text-${action.color}-600`"
                  />
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
                    {{ action.label }}
                  </p>
                </div>
              </Link>
            </div>
          </div>
        </div>

        <!-- Recent activity -->
        <div class="lg:col-span-2">
          <div class="bg-white shadow-sm border border-gray-200 rounded-md p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
                Recent Activity
              </h3>
              <button
                class="text-sm text-blue-600 hover:text-blue-500"
                :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
                @click="refreshActivity"
              >
                Refresh
              </button>
            </div>
            <div class="flow-root">
              <ul class="-mb-8">
                <li
                  v-for="(activity, index) in recentActivity"
                  :key="activity.id"
                  class="relative pb-8"
                >
                  <div v-if="index !== recentActivity.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" :class="{ 'bg-gray-600': isDarkTheme }"></div>
                  <div class="relative flex space-x-3">
                    <div>
                      <span
                        class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white"
                        :class="[
                          getActivityColor(activity.type),
                          { 'ring-gray-800': isDarkTheme }
                        ]"
                      >
                        <component
                          :is="getActivityIcon(activity.type)"
                          class="w-4 h-4 text-white"
                        />
                      </span>
                    </div>
                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                      <div>
                        <p class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                          {{ activity.description }}
                        </p>
                        <p class="text-xs text-gray-400">
                          by {{ activity.user }}
                        </p>
                      </div>
                      <div class="text-right text-sm whitespace-nowrap text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                        {{ formatRelativeTime(activity.created_at) }}
                      </div>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Widgets -->
      <div v-if="widgets.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div
          v-for="widget in widgets"
          :key="widget.title"
          class="bg-white shadow-sm border border-gray-200 rounded-md p-6"
          :class="widget.size === 'large' ? 'lg:col-span-2' : ''"
        >
          <div class="mb-4">
            <h3 class="text-lg font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
              {{ widget.title }}
            </h3>
          </div>
          <component
            :is="widget.component"
            :data="widget.data"
          />
        </div>
      </div>

      <!-- System info -->
      <div class="bg-white shadow-sm border border-gray-200 rounded-md p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
            System Information
          </h3>
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
            <span class="w-1.5 h-1.5 mr-1.5 bg-green-400 rounded-full"></span>
            Online
          </span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="(value, key) in systemInfo" :key="key" class="text-center">
            <dt class="text-sm font-medium text-gray-500 capitalize" :class="{ 'text-gray-400': isDarkTheme }">
              {{ key.replace(/_/g, ' ') }}
            </dt>
            <dd class="mt-1 text-sm text-gray-900" :class="{ 'text-white': isDarkTheme }">
              {{ value }}
            </dd>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
/**
 * Dashboard Page
 *
 * Main dashboard page displaying metrics, quick actions, recent activity,
 * widgets, and system information.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'
import {
  ArrowUpIcon,
  ArrowDownIcon,
  PlusIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
  DocumentTextIcon,
  UserIcon,
  ChatBubbleLeftIcon
} from '@heroicons/vue/24/outline'
import * as HeroIcons from '@heroicons/vue/24/outline'
import AdminLayout from '@/components/Layout/AdminLayout.vue'

// Props from Inertia
const props = defineProps({
  metrics: Array,
  widgets: Array,
  recentActivity: Array,
  quickActions: Array,
  systemInfo: Object,
})

// Page data
const page = usePage()

// Store
const adminStore = useAdminStore()

// Reactive data
const lastUpdated = ref(new Date().toLocaleTimeString())

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)
const user = computed(() => page.props.auth?.user)
const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

// Methods
const getIcon = (iconName) => {
  return HeroIcons[iconName] || HeroIcons.DocumentTextIcon
}

const formatMetricValue = (value, format) => {
  switch (format) {
    case 'number':
      return new Intl.NumberFormat().format(value)
    case 'currency':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(value)
    case 'percentage':
      return `${value}%`
    default:
      return value
  }
}

const getTrendColor = (direction) => {
  return direction === 'up' ? 'text-green-600' : 'text-red-600'
}

const getActivityColor = (type) => {
  switch (type) {
    case 'created':
      return 'bg-green-500'
    case 'updated':
      return 'bg-blue-500'
    case 'deleted':
      return 'bg-red-500'
    default:
      return 'bg-gray-500'
  }
}

const getActivityIcon = (type) => {
  switch (type) {
    case 'created':
      return PlusIcon
    case 'updated':
      return PencilIcon
    case 'deleted':
      return TrashIcon
    default:
      return DocumentTextIcon
  }
}

const formatRelativeTime = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffInMinutes = Math.floor((now - date) / (1000 * 60))

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

const handleQuickAction = (action) => {
  switch (action) {
    case 'clear-cache':
      clearCache()
      break
    case 'view-logs':
      adminStore.notifyInfo('Log viewer not implemented yet')
      break
    default:
      console.log('Unknown action:', action)
  }
}

const clearCache = async () => {
  try {
    const response = await axios.post('/admin/api/system/clear-cache')
    adminStore.notify(response.data.message, response.data.type)
  } catch (error) {
    adminStore.notifyError(error.response?.data?.message || 'Failed to clear cache')
  }
}

const refreshActivity = () => {
  // Refresh activity data
  window.location.reload()
}

// Update last updated time every minute
onMounted(() => {
  setInterval(() => {
    lastUpdated.value = new Date().toLocaleTimeString()
  }, 60000)
})
</script>

<style scoped>
/* Custom styles for dashboard */
.flow-root {
  overflow: hidden;
}

/* Activity timeline */
.relative::before {
  content: '';
  position: absolute;
  top: 0;
  left: 1rem;
  width: 2px;
  height: 100%;
  background: currentColor;
  opacity: 0.1;
}
</style>
