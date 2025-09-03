<template>
  <div class="welcome-card">
    <!-- Welcome Header -->
    <div class="welcome-header">
      <div class="welcome-icon">
        <svg
          class="w-8 h-8 text-blue-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3.5M3 16.5h18"
          />
        </svg>
      </div>
      <div class="welcome-content">
        <h3 class="welcome-title">
          {{ title || 'Welcome to Admin Panel' }}
        </h3>
        <p class="welcome-subtitle">
          {{ subtitle || 'Manage your application with ease' }}
        </p>
      </div>
    </div>

    <!-- Welcome Body -->
    <div class="welcome-body">
      <div class="welcome-stats">
        <div class="stat-item">
          <div class="stat-value">{{ userCount || 0 }}</div>
          <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-item">
          <div class="stat-value">{{ dashboardCount || 1 }}</div>
          <div class="stat-label">Dashboards</div>
        </div>
        <div class="stat-item">
          <div class="stat-value">{{ uptime || '99.9%' }}</div>
          <div class="stat-label">Uptime</div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="welcome-actions">
        <h4 class="actions-title">Quick Actions</h4>
        <div class="actions-grid">
          <button
            v-for="action in quickActions"
            :key="action.name"
            @click="handleAction(action)"
            class="action-button"
          >
            <component
              v-if="action.icon"
              :is="action.icon"
              class="w-4 h-4"
            />
            <svg
              v-else
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 10V3L4 14h7v7l9-11h-7z"
              />
            </svg>
            <span>{{ action.label }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Welcome Footer -->
    <div class="welcome-footer">
      <div class="footer-info">
        <span class="info-item">
          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
              clip-rule="evenodd"
            />
          </svg>
          Last updated: {{ formattedLastUpdated }}
        </span>
        <span v-if="version" class="info-item">
          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm2 2a1 1 0 000 2h.01a1 1 0 100-2H5zm3 0a1 1 0 000 2h3a1 1 0 100-2H8z"
              clip-rule="evenodd"
            />
          </svg>
          Version: {{ version }}
        </span>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'WelcomeCard',
  props: {
    dashboard: {
      type: Object,
      required: true
    },
    title: {
      type: String,
      default: ''
    },
    subtitle: {
      type: String,
      default: ''
    },
    userCount: {
      type: Number,
      default: 0
    },
    dashboardCount: {
      type: Number,
      default: 1
    },
    uptime: {
      type: String,
      default: '99.9%'
    },
    quickActions: {
      type: Array,
      default: () => [
        { name: 'users', label: 'Manage Users', url: '/admin/users' },
        { name: 'settings', label: 'Settings', url: '/admin/settings' },
        { name: 'reports', label: 'View Reports', url: '/admin/reports' }
      ]
    },
    version: {
      type: String,
      default: ''
    },
    lastUpdated: {
      type: [String, Date],
      default: () => new Date()
    }
  },
  emits: ['action', 'error'],
  setup(props, { emit }) {
    // Computed properties
    const formattedLastUpdated = computed(() => {
      const date = typeof props.lastUpdated === 'string' 
        ? new Date(props.lastUpdated) 
        : props.lastUpdated
      
      return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
      })
    })

    // Methods
    const handleAction = (action) => {
      emit('action', action)
      
      // Handle built-in actions
      if (action.url) {
        if (action.external) {
          window.open(action.url, '_blank', 'noopener,noreferrer')
        } else {
          // Use Inertia navigation if available
          if (window.Inertia) {
            window.Inertia.visit(action.url)
          } else {
            window.location.href = action.url
          }
        }
      }
    }

    return {
      formattedLastUpdated,
      handleAction
    }
  }
}
</script>

<style scoped>
@import '../../../../css/admin.css' reference;

.welcome-card {
  @apply space-y-6;
}

.welcome-header {
  @apply flex items-start space-x-4;
}

.welcome-icon {
  @apply flex-shrink-0 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg;
}

.welcome-content {
  @apply flex-1 min-w-0;
}

.welcome-title {
  @apply text-xl font-semibold text-gray-900 dark:text-white;
}

.welcome-subtitle {
  @apply text-sm text-gray-600 dark:text-gray-400 mt-1;
}

.welcome-body {
  @apply space-y-6;
}

.welcome-stats {
  @apply grid grid-cols-3 gap-4;
}

.stat-item {
  @apply text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg;
}

.stat-value {
  @apply text-2xl font-bold text-gray-900 dark:text-white;
}

.stat-label {
  @apply text-xs font-medium text-gray-600 dark:text-gray-400 mt-1;
}

.welcome-actions {
  @apply space-y-3;
}

.actions-title {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.actions-grid {
  @apply grid grid-cols-1 sm:grid-cols-3 gap-2;
}

.action-button {
  @apply flex items-center justify-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200;
}

.welcome-footer {
  @apply pt-4 border-t border-gray-200 dark:border-gray-700;
}

.footer-info {
  @apply flex items-center justify-between text-xs text-gray-500 dark:text-gray-400;
}

.info-item {
  @apply flex items-center;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .welcome-stats {
    @apply grid-cols-1 gap-2;
  }
  
  .actions-grid {
    @apply grid-cols-1;
  }
  
  .footer-info {
    @apply flex-col space-y-1 items-start;
  }
}
</style>
