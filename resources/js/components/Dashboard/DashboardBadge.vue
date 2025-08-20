<template>
  <span
    v-if="shouldShow"
    class="dashboard-badge"
    :class="badgeClasses"
    :style="badgeStyles"
    :title="tooltip"
    :aria-label="ariaLabel"
  >
    <!-- Icon -->
    <component
      v-if="iconComponent"
      :is="iconComponent"
      class="badge-icon"
    />
    
    <!-- Content -->
    <span class="badge-content">
      {{ displayValue }}
    </span>
    
    <!-- Pulse animation for live badges -->
    <span
      v-if="isLive"
      class="badge-pulse"
    ></span>
  </span>
</template>

<script>
import { computed } from 'vue'
import {
  ExclamationTriangleIcon,
  CheckCircleIcon,
  InformationCircleIcon,
  XCircleIcon,
  BoltIcon,
  FireIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'DashboardBadge',
  components: {
    ExclamationTriangleIcon,
    CheckCircleIcon,
    InformationCircleIcon,
    XCircleIcon,
    BoltIcon,
    FireIcon
  },
  props: {
    badge: {
      type: [String, Number, Object],
      required: true
    },
    type: {
      type: String,
      default: 'primary',
      validator: (value) => [
        'primary', 'secondary', 'success', 'warning', 'danger', 'info',
        'light', 'dark', 'live', 'hot', 'new', 'beta'
      ].includes(value)
    },
    size: {
      type: String,
      default: 'sm',
      validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
    },
    variant: {
      type: String,
      default: 'solid',
      validator: (value) => ['solid', 'outline', 'soft', 'minimal'].includes(value)
    },
    showIcon: {
      type: Boolean,
      default: false
    },
    isLive: {
      type: Boolean,
      default: false
    },
    pulse: {
      type: Boolean,
      default: false
    },
    rounded: {
      type: Boolean,
      default: true
    },
    uppercase: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    // Computed properties
    const badgeData = computed(() => {
      if (typeof props.badge === 'object') {
        return props.badge
      }
      return { value: props.badge }
    })

    const displayValue = computed(() => {
      const value = badgeData.value.value || props.badge
      
      // Format numbers
      if (typeof value === 'number') {
        if (value >= 1000000) {
          return `${(value / 1000000).toFixed(1)}M`
        }
        if (value >= 1000) {
          return `${(value / 1000).toFixed(1)}K`
        }
        return value.toString()
      }
      
      return value
    })

    const badgeType = computed(() => {
      return badgeData.value.type || props.type
    })

    const shouldShow = computed(() => {
      const value = badgeData.value.value || props.badge
      return value !== null && value !== undefined && value !== ''
    })

    const badgeClasses = computed(() => [
      'dashboard-badge-base',
      `badge-${badgeType.value}`,
      `badge-${props.variant}`,
      `badge-${props.size}`,
      {
        'badge-rounded': props.rounded,
        'badge-uppercase': props.uppercase,
        'badge-pulse': props.pulse || props.isLive,
        'badge-with-icon': props.showIcon && iconComponent.value
      }
    ])

    const badgeStyles = computed(() => {
      const styles = {}
      
      if (badgeData.value.color) {
        if (props.variant === 'solid') {
          styles.backgroundColor = badgeData.value.color
          styles.color = getContrastColor(badgeData.value.color)
        } else {
          styles.color = badgeData.value.color
          styles.borderColor = badgeData.value.color
        }
      }
      
      return styles
    })

    const iconComponent = computed(() => {
      if (!props.showIcon) return null
      
      const iconMap = {
        success: CheckCircleIcon,
        warning: ExclamationTriangleIcon,
        danger: XCircleIcon,
        info: InformationCircleIcon,
        live: BoltIcon,
        hot: FireIcon,
        new: BoltIcon,
        beta: InformationCircleIcon
      }
      
      return iconMap[badgeType.value] || null
    })

    const tooltip = computed(() => {
      return badgeData.value.tooltip || badgeData.value.title || null
    })

    const ariaLabel = computed(() => {
      const value = displayValue.value
      const type = badgeType.value
      
      if (tooltip.value) {
        return tooltip.value
      }
      
      return `${type} badge: ${value}`
    })

    // Helper function to get contrast color
    const getContrastColor = (hexColor) => {
      // Remove # if present
      const hex = hexColor.replace('#', '')
      
      // Convert to RGB
      const r = parseInt(hex.substr(0, 2), 16)
      const g = parseInt(hex.substr(2, 2), 16)
      const b = parseInt(hex.substr(4, 2), 16)
      
      // Calculate luminance
      const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
      
      // Return black or white based on luminance
      return luminance > 0.5 ? '#000000' : '#FFFFFF'
    }

    return {
      badgeData,
      displayValue,
      badgeType,
      shouldShow,
      badgeClasses,
      badgeStyles,
      iconComponent,
      tooltip,
      ariaLabel
    }
  }
}
</script>

<style scoped>
.dashboard-badge {
  @apply inline-flex items-center justify-center font-medium relative;
}

.dashboard-badge-base {
  @apply transition-all duration-200;
}

/* Size variants */
.badge-xs {
  @apply px-1.5 py-0.5 text-xs;
}

.badge-sm {
  @apply px-2 py-1 text-xs;
}

.badge-md {
  @apply px-2.5 py-1.5 text-sm;
}

.badge-lg {
  @apply px-3 py-2 text-base;
}

/* Rounded variants */
.badge-rounded.badge-xs,
.badge-rounded.badge-sm {
  @apply rounded-full;
}

.badge-rounded.badge-md,
.badge-rounded.badge-lg {
  @apply rounded-lg;
}

.dashboard-badge:not(.badge-rounded) {
  @apply rounded;
}

/* Type variants - Solid */
.badge-primary.badge-solid {
  @apply bg-blue-500 text-white;
}

.badge-secondary.badge-solid {
  @apply bg-gray-500 text-white;
}

.badge-success.badge-solid {
  @apply bg-green-500 text-white;
}

.badge-warning.badge-solid {
  @apply bg-yellow-500 text-white;
}

.badge-danger.badge-solid {
  @apply bg-red-500 text-white;
}

.badge-info.badge-solid {
  @apply bg-blue-400 text-white;
}

.badge-light.badge-solid {
  @apply bg-gray-100 text-gray-800;
}

.badge-dark.badge-solid {
  @apply bg-gray-800 text-white;
}

.badge-live.badge-solid {
  @apply bg-green-500 text-white;
}

.badge-hot.badge-solid {
  @apply bg-red-500 text-white;
}

.badge-new.badge-solid {
  @apply bg-blue-500 text-white;
}

.badge-beta.badge-solid {
  @apply bg-purple-500 text-white;
}

/* Type variants - Outline */
.badge-primary.badge-outline {
  @apply border border-blue-500 text-blue-500 bg-transparent;
}

.badge-secondary.badge-outline {
  @apply border border-gray-500 text-gray-500 bg-transparent;
}

.badge-success.badge-outline {
  @apply border border-green-500 text-green-500 bg-transparent;
}

.badge-warning.badge-outline {
  @apply border border-yellow-500 text-yellow-500 bg-transparent;
}

.badge-danger.badge-outline {
  @apply border border-red-500 text-red-500 bg-transparent;
}

.badge-info.badge-outline {
  @apply border border-blue-400 text-blue-400 bg-transparent;
}

.badge-live.badge-outline {
  @apply border border-green-500 text-green-500 bg-transparent;
}

.badge-hot.badge-outline {
  @apply border border-red-500 text-red-500 bg-transparent;
}

.badge-new.badge-outline {
  @apply border border-blue-500 text-blue-500 bg-transparent;
}

.badge-beta.badge-outline {
  @apply border border-purple-500 text-purple-500 bg-transparent;
}

/* Type variants - Soft */
.badge-primary.badge-soft {
  @apply bg-blue-100 text-blue-800;
}

.badge-secondary.badge-soft {
  @apply bg-gray-100 text-gray-800;
}

.badge-success.badge-soft {
  @apply bg-green-100 text-green-800;
}

.badge-warning.badge-soft {
  @apply bg-yellow-100 text-yellow-800;
}

.badge-danger.badge-soft {
  @apply bg-red-100 text-red-800;
}

.badge-info.badge-soft {
  @apply bg-blue-50 text-blue-700;
}

.badge-live.badge-soft {
  @apply bg-green-100 text-green-800;
}

.badge-hot.badge-soft {
  @apply bg-red-100 text-red-800;
}

.badge-new.badge-soft {
  @apply bg-blue-100 text-blue-800;
}

.badge-beta.badge-soft {
  @apply bg-purple-100 text-purple-800;
}

/* Type variants - Minimal */
.badge-minimal {
  @apply bg-transparent text-gray-600 border-none;
}

/* Uppercase */
.badge-uppercase {
  @apply uppercase tracking-wide;
}

/* Icon */
.badge-with-icon {
  @apply pl-1;
}

.badge-icon {
  @apply h-3 w-3 mr-1;
}

.badge-xs .badge-icon {
  @apply h-2.5 w-2.5;
}

.badge-lg .badge-icon {
  @apply h-4 w-4;
}

/* Pulse animation */
.badge-pulse {
  @apply animate-pulse;
}

.badge-live .badge-pulse {
  @apply absolute -top-1 -right-1 h-2 w-2 bg-green-400 rounded-full animate-ping;
}

.badge-hot .badge-pulse {
  @apply absolute -top-1 -right-1 h-2 w-2 bg-red-400 rounded-full animate-ping;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .badge-light.badge-solid {
    @apply bg-gray-700 text-gray-200;
  }

  .badge-minimal {
    @apply text-gray-400;
  }

  .badge-primary.badge-soft {
    @apply bg-blue-900 text-blue-200;
  }

  .badge-secondary.badge-soft {
    @apply bg-gray-800 text-gray-200;
  }

  .badge-success.badge-soft {
    @apply bg-green-900 text-green-200;
  }

  .badge-warning.badge-soft {
    @apply bg-yellow-900 text-yellow-200;
  }

  .badge-danger.badge-soft {
    @apply bg-red-900 text-red-200;
  }

  .badge-info.badge-soft {
    @apply bg-blue-900 text-blue-200;
  }

  .badge-live.badge-soft {
    @apply bg-green-900 text-green-200;
  }

  .badge-hot.badge-soft {
    @apply bg-red-900 text-red-200;
  }

  .badge-new.badge-soft {
    @apply bg-blue-900 text-blue-200;
  }

  .badge-beta.badge-soft {
    @apply bg-purple-900 text-purple-200;
  }
}

/* Hover effects */
.dashboard-badge:hover {
  @apply transform scale-105;
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .badge-pulse,
  .badge-pulse .badge-pulse {
    @apply animate-none;
  }

  .dashboard-badge:hover {
    @apply transform-none;
  }
}
</style>
