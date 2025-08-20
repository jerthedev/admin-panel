<template>
  <div
    class="dashboard-selection-card"
    :class="cardClasses"
    @click="handleClick"
    @keydown.enter="handleClick"
    @keydown.space.prevent="handleClick"
    :tabindex="interactive ? 0 : -1"
    :role="interactive ? 'button' : 'article'"
    :aria-label="ariaLabel"
    data-testid="dashboard-selection-card"
  >
    <!-- Loading Overlay -->
    <DashboardLoading
      v-if="isLoading"
      :is-visible="true"
      variant="spinner"
      :overlay="false"
      :show-cancel="false"
      class="card-loading-overlay"
    />

    <!-- Card Header -->
    <div class="card-header">
      <!-- Icon -->
      <div class="icon-container" :style="iconStyles">
        <DashboardIcon
          :icon="dashboard.icon || dashboard.metadata?.icon"
          :size="iconSize"
          :color="dashboard.metadata?.color"
          class="dashboard-icon"
        />
      </div>

      <!-- Badge -->
      <div v-if="badge" class="badge-container">
        <DashboardBadge
          :badge="badge"
          :type="badge.type || 'primary'"
        />
      </div>

      <!-- Favorite Button -->
      <button
        v-if="showFavorite && canBeFavorited"
        @click.stop="toggleFavorite"
        class="favorite-button"
        :class="{ 'is-favorite': isFavorite }"
        :aria-label="isFavorite ? 'Remove from favorites' : 'Add to favorites'"
        type="button"
      >
        <StarIcon v-if="isFavorite" class="star-icon filled" />
        <StarIcon v-else class="star-icon outline" />
      </button>
    </div>

    <!-- Card Content -->
    <div class="card-content">
      <!-- Title -->
      <h3 class="dashboard-title" :style="titleStyles">
        {{ dashboard.name }}
      </h3>

      <!-- Description -->
      <p
        v-if="showDescription && dashboard.description"
        class="dashboard-description"
        :title="dashboard.description"
      >
        {{ truncatedDescription }}
      </p>

      <!-- Metadata -->
      <div v-if="showMetadata" class="dashboard-metadata">
        <!-- Category -->
        <DashboardCategory
          v-if="dashboard.category"
          :category="dashboard.category"
          :show-icon="true"
          size="small"
        />

        <!-- Tags -->
        <DashboardTags
          v-if="dashboard.metadata?.tags?.length"
          :tags="dashboard.metadata.tags"
          :max-visible="maxTags"
          size="small"
        />

        <!-- Last Accessed -->
        <div
          v-if="showLastAccessed && lastAccessed"
          class="metadata-item last-accessed"
        >
          <ClockIcon class="metadata-icon" />
          <span>{{ formatLastAccessed(lastAccessed) }}</span>
        </div>
      </div>

      <!-- Status Indicators -->
      <div v-if="showStatus && hasStatusIndicators" class="status-indicators">
        <DashboardStatus
          :dashboard="dashboard"
          :show-enabled="true"
          :show-dependencies="true"
          :show-permissions="true"
          size="small"
        />
      </div>
    </div>

    <!-- Card Footer -->
    <div v-if="showFooter && hasFooterContent" class="card-footer">
      <!-- Author & Version -->
      <div class="footer-info">
        <span v-if="dashboard.metadata?.author" class="author">
          by {{ dashboard.metadata.author }}
        </span>
        <span v-if="dashboard.metadata?.version" class="version">
          v{{ dashboard.metadata.version }}
        </span>
      </div>

      <!-- Actions -->
      <div v-if="showActions && hasActions" class="card-actions">
        <button
          v-if="canEdit"
          @click.stop="$emit('edit', dashboard)"
          class="action-button edit"
          :aria-label="`Edit ${dashboard.name}`"
          type="button"
        >
          <PencilIcon class="action-icon" />
        </button>
        <button
          v-if="canDelete"
          @click.stop="$emit('delete', dashboard)"
          class="action-button delete"
          :aria-label="`Delete ${dashboard.name}`"
          type="button"
        >
          <TrashIcon class="action-icon" />
        </button>
        <button
          v-if="canConfigure"
          @click.stop="$emit('configure', dashboard)"
          class="action-button configure"
          :aria-label="`Configure ${dashboard.name}`"
          type="button"
        >
          <CogIcon class="action-icon" />
        </button>
      </div>
    </div>

    <!-- Transition Indicator -->
    <div
      v-if="isTransitioning"
      class="transition-indicator"
      :class="transitionType"
    >
      <div class="transition-progress" :style="{ width: `${transitionProgress}%` }"></div>
    </div>
  </div>
</template>

<script>
import { computed, ref, inject } from 'vue'
import {
  StarIcon,
  ClockIcon,
  PencilIcon,
  TrashIcon,
  CogIcon
} from '@heroicons/vue/24/outline'
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'
import DashboardLoading from './DashboardLoading.vue'
import DashboardIcon from './DashboardIcon.vue'
import DashboardBadge from './DashboardBadge.vue'
import DashboardCategory from './DashboardCategory.vue'
import DashboardTags from './DashboardTags.vue'
import DashboardStatus from './DashboardStatus.vue'
import { useDashboardTransitions } from '@/composables/useDashboardTransitions'

export default {
  name: 'DashboardSelectionCard',
  components: {
    StarIcon,
    StarIconSolid,
    ClockIcon,
    PencilIcon,
    TrashIcon,
    CogIcon,
    DashboardLoading,
    DashboardIcon,
    DashboardBadge,
    DashboardCategory,
    DashboardTags,
    DashboardStatus
  },
  props: {
    dashboard: {
      type: Object,
      required: true
    },
    size: {
      type: String,
      default: 'medium',
      validator: (value) => ['small', 'medium', 'large'].includes(value)
    },
    variant: {
      type: String,
      default: 'default',
      validator: (value) => ['default', 'compact', 'detailed', 'minimal'].includes(value)
    },
    interactive: {
      type: Boolean,
      default: true
    },
    showDescription: {
      type: Boolean,
      default: true
    },
    showMetadata: {
      type: Boolean,
      default: true
    },
    showStatus: {
      type: Boolean,
      default: true
    },
    showFooter: {
      type: Boolean,
      default: false
    },
    showActions: {
      type: Boolean,
      default: false
    },
    showFavorite: {
      type: Boolean,
      default: true
    },
    showLastAccessed: {
      type: Boolean,
      default: true
    },
    canEdit: {
      type: Boolean,
      default: false
    },
    canDelete: {
      type: Boolean,
      default: false
    },
    canConfigure: {
      type: Boolean,
      default: false
    },
    isLoading: {
      type: Boolean,
      default: false
    },
    badge: {
      type: [Object, String, Number],
      default: null
    },
    maxTags: {
      type: Number,
      default: 3
    },
    maxDescriptionLength: {
      type: Number,
      default: 120
    }
  },
  emits: ['click', 'favorite', 'edit', 'delete', 'configure'],
  setup(props, { emit }) {
    // Composables
    const transitions = useDashboardTransitions()

    // Reactive state
    const isFavorite = ref(props.dashboard.isFavorite || false)

    // Computed properties
    const cardClasses = computed(() => [
      'dashboard-selection-card-base',
      `size-${props.size}`,
      `variant-${props.variant}`,
      {
        'interactive': props.interactive,
        'is-loading': props.isLoading,
        'is-disabled': !props.dashboard.metadata?.enabled,
        'has-custom-colors': hasCustomColors.value,
        'is-transitioning': isTransitioning.value,
        'is-current': isCurrentDashboard.value
      }
    ])

    const hasCustomColors = computed(() => {
      return props.dashboard.metadata?.color ||
             props.dashboard.metadata?.background_color ||
             props.dashboard.metadata?.text_color
    })

    const iconSize = computed(() => {
      const sizeMap = {
        small: 'sm',
        medium: 'md',
        large: 'lg'
      }
      return sizeMap[props.size] || 'md'
    })

    const iconStyles = computed(() => {
      const styles = {}
      if (props.dashboard.metadata?.background_color) {
        styles.backgroundColor = props.dashboard.metadata.background_color
      }
      return styles
    })

    const titleStyles = computed(() => {
      const styles = {}
      if (props.dashboard.metadata?.text_color) {
        styles.color = props.dashboard.metadata.text_color
      }
      return styles
    })

    const truncatedDescription = computed(() => {
      const description = props.dashboard.description || ''
      if (description.length <= props.maxDescriptionLength) {
        return description
      }
      return description.substring(0, props.maxDescriptionLength) + '...'
    })

    const lastAccessed = computed(() => {
      return props.dashboard.lastAccessed || props.dashboard.metadata?.last_accessed
    })

    const canBeFavorited = computed(() => {
      return props.dashboard.metadata?.can_be_favorited !== false
    })

    const hasStatusIndicators = computed(() => {
      return !props.dashboard.metadata?.enabled ||
             props.dashboard.metadata?.dependencies?.length ||
             props.dashboard.metadata?.permissions?.length
    })

    const hasFooterContent = computed(() => {
      return props.dashboard.metadata?.author ||
             props.dashboard.metadata?.version ||
             hasActions.value
    })

    const hasActions = computed(() => {
      return props.canEdit || props.canDelete || props.canConfigure
    })

    const isCurrentDashboard = computed(() => {
      // This would be injected from parent or determined by route
      return false // Placeholder
    })

    const isTransitioning = computed(() => {
      return transitions.isTransitioning.value &&
             transitions.currentTransition.value?.dashboard?.uriKey === props.dashboard.uriKey
    })

    const transitionType = computed(() => {
      return transitions.currentTransition.value?.type || 'navigate'
    })

    const transitionProgress = computed(() => {
      return transitions.transitionProgress.value
    })

    const ariaLabel = computed(() => {
      let label = `Dashboard: ${props.dashboard.name}`
      if (props.dashboard.description) {
        label += `. ${props.dashboard.description}`
      }
      if (props.dashboard.category) {
        label += `. Category: ${props.dashboard.category}`
      }
      if (props.isLoading) {
        label += '. Loading'
      }
      if (!props.dashboard.metadata?.enabled) {
        label += '. Disabled'
      }
      return label
    })

    // Methods
    const handleClick = () => {
      if (props.interactive && !props.isLoading && props.dashboard.metadata?.enabled) {
        emit('click', props.dashboard)
      }
    }

    const toggleFavorite = () => {
      if (!canBeFavorited.value) return
      
      isFavorite.value = !isFavorite.value
      emit('favorite', {
        dashboard: props.dashboard,
        isFavorite: isFavorite.value
      })
    }

    const formatLastAccessed = (timestamp) => {
      if (!timestamp) return ''
      
      const date = new Date(timestamp)
      const now = new Date()
      const diffMs = now - date
      const diffMins = Math.floor(diffMs / 60000)
      const diffHours = Math.floor(diffMs / 3600000)
      const diffDays = Math.floor(diffMs / 86400000)

      if (diffMins < 1) return 'Just now'
      if (diffMins < 60) return `${diffMins}m ago`
      if (diffHours < 24) return `${diffHours}h ago`
      if (diffDays < 7) return `${diffDays}d ago`
      
      return date.toLocaleDateString()
    }

    return {
      isFavorite,
      cardClasses,
      hasCustomColors,
      iconSize,
      iconStyles,
      titleStyles,
      truncatedDescription,
      lastAccessed,
      canBeFavorited,
      hasStatusIndicators,
      hasFooterContent,
      hasActions,
      isCurrentDashboard,
      isTransitioning,
      transitionType,
      transitionProgress,
      ariaLabel,
      handleClick,
      toggleFavorite,
      formatLastAccessed
    }
  }
}
</script>

<style scoped>
.dashboard-selection-card {
  @apply relative bg-white border border-gray-200 rounded-lg shadow-sm transition-all duration-200 overflow-hidden;
}

.dashboard-selection-card.interactive {
  @apply cursor-pointer hover:shadow-md hover:border-gray-300;
}

.dashboard-selection-card.interactive:hover {
  @apply transform -translate-y-0.5;
}

.dashboard-selection-card.interactive:focus {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

.dashboard-selection-card.is-loading {
  @apply opacity-75;
}

.dashboard-selection-card.is-disabled {
  @apply opacity-60 bg-gray-50 cursor-not-allowed;
}

.dashboard-selection-card.is-disabled:hover {
  @apply transform-none shadow-sm;
}

.dashboard-selection-card.is-current {
  @apply ring-2 ring-blue-500 border-blue-500;
}

.dashboard-selection-card.is-transitioning {
  @apply ring-2 ring-green-500 border-green-500;
}

.dashboard-selection-card.has-custom-colors {
  background-color: var(--dashboard-bg-color, theme('colors.white'));
}

/* Size variants */
.dashboard-selection-card.size-small {
  @apply p-4;
}

.dashboard-selection-card.size-medium {
  @apply p-6;
}

.dashboard-selection-card.size-large {
  @apply p-8;
}

/* Variant styles */
.dashboard-selection-card.variant-compact .dashboard-description {
  @apply hidden;
}

.dashboard-selection-card.variant-compact .dashboard-metadata {
  @apply hidden;
}

.dashboard-selection-card.variant-minimal .dashboard-metadata,
.dashboard-selection-card.variant-minimal .status-indicators {
  @apply hidden;
}

.dashboard-selection-card.variant-detailed .card-footer {
  @apply block;
}

/* Card loading overlay */
.card-loading-overlay {
  @apply absolute inset-0 bg-white bg-opacity-90 z-10;
}

/* Card header */
.card-header {
  @apply flex items-start justify-between mb-4;
}

.icon-container {
  @apply flex-shrink-0 p-2 rounded-lg;
}

.badge-container {
  @apply ml-2;
}

.favorite-button {
  @apply p-1 text-gray-400 hover:text-yellow-500 transition-colors rounded;
}

.favorite-button:focus {
  @apply outline-none ring-2 ring-yellow-500 ring-offset-1;
}

.favorite-button.is-favorite {
  @apply text-yellow-500;
}

.star-icon {
  @apply h-5 w-5;
}

/* Card content */
.card-content {
  @apply flex-1;
}

.dashboard-title {
  @apply text-lg font-semibold text-gray-900 mb-2 line-clamp-1;
}

.dashboard-description {
  @apply text-sm text-gray-600 mb-3 line-clamp-2;
}

.dashboard-metadata {
  @apply space-y-2 mb-3;
}

.metadata-item {
  @apply inline-flex items-center text-xs text-gray-500;
}

.metadata-icon {
  @apply h-3 w-3 mr-1;
}

.status-indicators {
  @apply mt-3;
}

/* Card footer */
.card-footer {
  @apply hidden mt-4 pt-4 border-t border-gray-200 flex items-center justify-between;
}

.footer-info {
  @apply flex items-center space-x-2 text-xs text-gray-500;
}

.card-actions {
  @apply flex items-center space-x-1;
}

.action-button {
  @apply p-1.5 text-gray-400 hover:text-gray-600 transition-colors rounded;
}

.action-button:focus {
  @apply outline-none ring-2 ring-gray-500 ring-offset-1;
}

.action-button.delete:hover {
  @apply text-red-600;
}

.action-icon {
  @apply h-4 w-4;
}

/* Transition indicator */
.transition-indicator {
  @apply absolute bottom-0 left-0 right-0 h-1 bg-gray-200;
}

.transition-progress {
  @apply h-full bg-green-500 transition-all duration-300;
}

.transition-indicator.navigate .transition-progress {
  @apply bg-blue-500;
}

.transition-indicator.switch .transition-progress {
  @apply bg-purple-500;
}

.transition-indicator.refresh .transition-progress {
  @apply bg-green-500;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .dashboard-selection-card {
    @apply bg-gray-800 border-gray-700;
  }

  .dashboard-selection-card.is-disabled {
    @apply bg-gray-900;
  }

  .card-loading-overlay {
    @apply bg-gray-800 bg-opacity-90;
  }

  .dashboard-title {
    @apply text-gray-100;
  }

  .dashboard-description {
    @apply text-gray-300;
  }

  .metadata-item {
    @apply text-gray-400;
  }

  .card-footer {
    @apply border-gray-700;
  }

  .footer-info {
    @apply text-gray-400;
  }

  .transition-indicator {
    @apply bg-gray-700;
  }
}

/* Responsive */
@media (max-width: 640px) {
  .dashboard-selection-card.size-large {
    @apply p-6;
  }

  .dashboard-selection-card.size-medium {
    @apply p-4;
  }

  .dashboard-title {
    @apply text-base;
  }

  .card-actions {
    @apply flex-col space-x-0 space-y-1;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .dashboard-selection-card {
    @apply transition-none;
  }

  .dashboard-selection-card.interactive:hover {
    @apply transform-none;
  }

  .transition-progress {
    @apply transition-none;
  }
}
</style>
