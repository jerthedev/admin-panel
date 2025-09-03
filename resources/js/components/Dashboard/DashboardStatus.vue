<template>
  <div class="dashboard-status" :class="containerClasses">
    <!-- Enabled/Disabled Status -->
    <div
      v-if="showEnabled && !dashboard.metadata?.enabled"
      class="status-item disabled"
      :class="statusClasses"
    >
      <XCircleIcon class="status-icon" />
      <span>Disabled</span>
    </div>

    <!-- Dependencies -->
    <div
      v-if="showDependencies && hasDependencies"
      class="status-item dependencies"
      :class="statusClasses"
      :title="dependenciesTooltip"
    >
      <LinkIcon class="status-icon" />
      <span>{{ dependenciesText }}</span>
    </div>

    <!-- Permissions -->
    <div
      v-if="showPermissions && hasPermissions"
      class="status-item permissions"
      :class="statusClasses"
      :title="permissionsTooltip"
    >
      <ShieldCheckIcon class="status-icon" />
      <span>{{ permissionsText }}</span>
    </div>

    <!-- Loading Status -->
    <div
      v-if="showLoading && isLoading"
      class="status-item loading"
      :class="statusClasses"
    >
      <div class="loading-spinner">
        <div class="spinner"></div>
      </div>
      <span>Loading</span>
    </div>

    <!-- Error Status -->
    <div
      v-if="showError && hasError"
      class="status-item error"
      :class="statusClasses"
      :title="errorMessage"
    >
      <ExclamationTriangleIcon class="status-icon" />
      <span>Error</span>
    </div>

    <!-- Custom Status -->
    <div
      v-if="customStatus"
      class="status-item custom"
      :class="[statusClasses, `status-${customStatus.type || 'info'}`]"
      :title="customStatus.tooltip"
    >
      <component
        v-if="customStatus.icon"
        :is="customStatus.icon"
        class="status-icon"
      />
      <span>{{ customStatus.text }}</span>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import {
  XCircleIcon,
  LinkIcon,
  ShieldCheckIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'DashboardStatus',
  components: {
    XCircleIcon,
    LinkIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon
  },
  props: {
    dashboard: {
      type: Object,
      required: true
    },
    size: {
      type: String,
      default: 'sm',
      validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
    },
    variant: {
      type: String,
      default: 'soft',
      validator: (value) => ['solid', 'soft', 'outline', 'minimal'].includes(value)
    },
    showEnabled: {
      type: Boolean,
      default: true
    },
    showDependencies: {
      type: Boolean,
      default: true
    },
    showPermissions: {
      type: Boolean,
      default: true
    },
    showLoading: {
      type: Boolean,
      default: true
    },
    showError: {
      type: Boolean,
      default: true
    },
    isLoading: {
      type: Boolean,
      default: false
    },
    error: {
      type: [String, Object],
      default: null
    },
    customStatus: {
      type: Object,
      default: null
    }
  },
  setup(props) {
    // Computed properties
    const hasDependencies = computed(() => {
      return props.dashboard.metadata?.dependencies?.length > 0
    })

    const hasPermissions = computed(() => {
      return props.dashboard.metadata?.permissions?.length > 0
    })

    const hasError = computed(() => {
      return !!props.error
    })

    const errorMessage = computed(() => {
      if (typeof props.error === 'string') {
        return props.error
      }
      return props.error?.message || 'An error occurred'
    })

    const dependenciesText = computed(() => {
      const count = props.dashboard.metadata?.dependencies?.length || 0
      return count === 1 ? '1 dependency' : `${count} dependencies`
    })

    const dependenciesTooltip = computed(() => {
      const deps = props.dashboard.metadata?.dependencies || []
      return `Dependencies: ${deps.join(', ')}`
    })

    const permissionsText = computed(() => {
      const count = props.dashboard.metadata?.permissions?.length || 0
      return count === 1 ? '1 permission' : `${count} permissions`
    })

    const permissionsTooltip = computed(() => {
      const perms = props.dashboard.metadata?.permissions || []
      return `Required permissions: ${perms.join(', ')}`
    })

    const containerClasses = computed(() => [
      'dashboard-status-base',
      `status-container-${props.size}`
    ])

    const statusClasses = computed(() => [
      'status-item-base',
      `status-${props.variant}`,
      `status-${props.size}`
    ])

    return {
      hasDependencies,
      hasPermissions,
      hasError,
      errorMessage,
      dependenciesText,
      dependenciesTooltip,
      permissionsText,
      permissionsTooltip,
      containerClasses,
      statusClasses
    }
  }
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.dashboard-status {
  @apply flex flex-wrap items-center gap-2;
}

.dashboard-status-base {
  @apply transition-all duration-200;
}

/* Container size variants */
.status-container-xs {
  @apply gap-1;
}

.status-container-sm {
  @apply gap-1.5;
}

.status-container-md {
  @apply gap-2;
}

.status-container-lg {
  @apply gap-3;
}

/* Status item base */
.status-item {
  @apply inline-flex items-center font-medium rounded transition-all duration-200;
}

.status-item-base {
  @apply border;
}

/* Status item size variants */
.status-xs {
  @apply px-1.5 py-0.5 text-xs;
}

.status-sm {
  @apply px-2 py-1 text-xs;
}

.status-md {
  @apply px-2.5 py-1.5 text-sm;
}

.status-lg {
  @apply px-3 py-2 text-base;
}

/* Status icons */
.status-icon {
  @apply mr-1;
}

.status-xs .status-icon {
  @apply h-3 w-3;
}

.status-sm .status-icon {
  @apply h-3 w-3;
}

.status-md .status-icon {
  @apply h-4 w-4;
}

.status-lg .status-icon {
  @apply h-5 w-5;
}

/* Loading spinner */
.loading-spinner {
  @apply mr-1;
}

.spinner {
  @apply border-2 border-current border-t-transparent rounded-full animate-spin;
}

.status-xs .spinner {
  @apply h-3 w-3;
}

.status-sm .spinner {
  @apply h-3 w-3;
}

.status-md .spinner {
  @apply h-4 w-4;
}

.status-lg .spinner {
  @apply h-5 w-5;
}

/* Status types - Solid variant */
.status-solid.disabled {
  @apply bg-red-500 text-white border-red-500;
}

.status-solid.dependencies {
  @apply bg-blue-500 text-white border-blue-500;
}

.status-solid.permissions {
  @apply bg-yellow-500 text-white border-yellow-500;
}

.status-solid.loading {
  @apply bg-gray-500 text-white border-gray-500;
}

.status-solid.error {
  @apply bg-red-500 text-white border-red-500;
}

.status-solid.custom.status-info {
  @apply bg-blue-500 text-white border-blue-500;
}

.status-solid.custom.status-success {
  @apply bg-green-500 text-white border-green-500;
}

.status-solid.custom.status-warning {
  @apply bg-yellow-500 text-white border-yellow-500;
}

.status-solid.custom.status-danger {
  @apply bg-red-500 text-white border-red-500;
}

/* Status types - Soft variant */
.status-soft.disabled {
  @apply bg-red-100 text-red-800 border-red-200;
}

.status-soft.dependencies {
  @apply bg-blue-100 text-blue-800 border-blue-200;
}

.status-soft.permissions {
  @apply bg-yellow-100 text-yellow-800 border-yellow-200;
}

.status-soft.loading {
  @apply bg-gray-100 text-gray-800 border-gray-200;
}

.status-soft.error {
  @apply bg-red-100 text-red-800 border-red-200;
}

.status-soft.custom.status-info {
  @apply bg-blue-100 text-blue-800 border-blue-200;
}

.status-soft.custom.status-success {
  @apply bg-green-100 text-green-800 border-green-200;
}

.status-soft.custom.status-warning {
  @apply bg-yellow-100 text-yellow-800 border-yellow-200;
}

.status-soft.custom.status-danger {
  @apply bg-red-100 text-red-800 border-red-200;
}

/* Status types - Outline variant */
.status-outline.disabled {
  @apply bg-transparent text-red-600 border-red-500;
}

.status-outline.dependencies {
  @apply bg-transparent text-blue-600 border-blue-500;
}

.status-outline.permissions {
  @apply bg-transparent text-yellow-600 border-yellow-500;
}

.status-outline.loading {
  @apply bg-transparent text-gray-600 border-gray-500;
}

.status-outline.error {
  @apply bg-transparent text-red-600 border-red-500;
}

.status-outline.custom.status-info {
  @apply bg-transparent text-blue-600 border-blue-500;
}

.status-outline.custom.status-success {
  @apply bg-transparent text-green-600 border-green-500;
}

.status-outline.custom.status-warning {
  @apply bg-transparent text-yellow-600 border-yellow-500;
}

.status-outline.custom.status-danger {
  @apply bg-transparent text-red-600 border-red-500;
}

/* Status types - Minimal variant */
.status-minimal {
  @apply bg-transparent border-transparent;
}

.status-minimal.disabled {
  @apply text-red-600;
}

.status-minimal.dependencies {
  @apply text-blue-600;
}

.status-minimal.permissions {
  @apply text-yellow-600;
}

.status-minimal.loading {
  @apply text-gray-600;
}

.status-minimal.error {
  @apply text-red-600;
}

.status-minimal.custom.status-info {
  @apply text-blue-600;
}

.status-minimal.custom.status-success {
  @apply text-green-600;
}

.status-minimal.custom.status-warning {
  @apply text-yellow-600;
}

.status-minimal.custom.status-danger {
  @apply text-red-600;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .status-soft.disabled {
    @apply bg-red-900 text-red-200 border-red-800;
  }

  .status-soft.dependencies {
    @apply bg-blue-900 text-blue-200 border-blue-800;
  }

  .status-soft.permissions {
    @apply bg-yellow-900 text-yellow-200 border-yellow-800;
  }

  .status-soft.loading {
    @apply bg-gray-800 text-gray-200 border-gray-700;
  }

  .status-soft.error {
    @apply bg-red-900 text-red-200 border-red-800;
  }

  .status-soft.custom.status-info {
    @apply bg-blue-900 text-blue-200 border-blue-800;
  }

  .status-soft.custom.status-success {
    @apply bg-green-900 text-green-200 border-green-800;
  }

  .status-soft.custom.status-warning {
    @apply bg-yellow-900 text-yellow-200 border-yellow-800;
  }

  .status-soft.custom.status-danger {
    @apply bg-red-900 text-red-200 border-red-800;
  }

  .status-minimal.disabled {
    @apply text-red-400;
  }

  .status-minimal.dependencies {
    @apply text-blue-400;
  }

  .status-minimal.permissions {
    @apply text-yellow-400;
  }

  .status-minimal.loading {
    @apply text-gray-400;
  }

  .status-minimal.error {
    @apply text-red-400;
  }

  .status-minimal.custom.status-info {
    @apply text-blue-400;
  }

  .status-minimal.custom.status-success {
    @apply text-green-400;
  }

  .status-minimal.custom.status-warning {
    @apply text-yellow-400;
  }

  .status-minimal.custom.status-danger {
    @apply text-red-400;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .spinner {
    @apply animate-none;
  }
}
</style>
