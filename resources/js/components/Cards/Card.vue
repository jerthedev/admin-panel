<template>
  <div :class="cardClasses" @click="handleClick">
    <!-- Header -->
    <div v-if="hasHeader" :class="headerClasses">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <component
            v-if="card.meta?.icon"
            :is="card.meta.icon"
            :class="iconClasses"
          />
          <div>
            <h3 v-if="displayTitle" :class="titleClasses">
              {{ displayTitle }}
            </h3>
            <p v-if="card.meta?.subtitle" :class="subtitleClasses">
              {{ card.meta.subtitle }}
            </p>
          </div>
        </div>
        <div v-if="$slots.actions" class="flex items-center space-x-2">
          <slot name="actions" />
        </div>
      </div>
      <slot name="header" />
    </div>

    <!-- Body -->
    <div :class="bodyClasses">
      <slot>
        <!-- Default content if no slot provided -->
        <div v-if="card.meta?.description" class="text-gray-600">
          {{ card.meta.description }}
        </div>
      </slot>
    </div>

    <!-- Footer -->
    <div v-if="$slots.footer" :class="footerClasses">
      <slot name="footer" />
    </div>

    <!-- Loading overlay -->
    <div v-if="loading" :class="loadingOverlayClasses">
      <div class="flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * Card Component
 * 
 * Base Vue component for admin panel cards providing Nova-compatible
 * functionality with support for meta data, theming, and interactive features.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, inject } from 'vue'
import { useAdminStore } from '@/stores/admin'

// Props
const props = defineProps({
  card: {
    type: Object,
    required: true,
    validator: (card) => {
      return !!(card && typeof card === 'object' &&
                card.name && card.component && card.uriKey)
    }
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'bordered', 'elevated', 'flat'].includes(value)
  },
  padding: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg', 'xl'].includes(value)
  },
  rounded: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg', 'xl', 'full'].includes(value)
  },
  hoverable: {
    type: Boolean,
    default: false
  },
  clickable: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  refreshable: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['click', 'refresh'])

// Store
const adminStore = useAdminStore()

// Injected context (for dashboard)
const dashboardContext = inject('dashboardContext', null)

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const displayTitle = computed(() => {
  return props.card.meta?.title || props.card.name
})

const hasHeader = computed(() => {
  return displayTitle.value || 
         props.card.meta?.subtitle || 
         props.card.meta?.icon || 
         props.$slots.header || 
         props.$slots.actions
})

const variantClasses = {
  default: 'bg-white shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700',
  bordered: 'bg-white border-2 border-gray-200 dark:bg-gray-800 dark:border-gray-600',
  elevated: 'bg-white shadow-lg border border-gray-100 dark:bg-gray-800 dark:border-gray-700',
  flat: 'bg-gray-50 dark:bg-gray-900'
}

const paddingClasses = {
  none: '',
  sm: 'p-3',
  md: 'p-4',
  lg: 'p-6',
  xl: 'p-8'
}

const roundedClasses = {
  none: '',
  sm: 'rounded-sm',
  md: 'rounded-md',
  lg: 'rounded-lg',
  xl: 'rounded-xl',
  full: 'rounded-full'
}

const cardClasses = computed(() => {
  const classes = [
    'relative overflow-hidden transition-all duration-200',
    variantClasses[props.variant],
    roundedClasses[props.rounded]
  ]

  if (props.hoverable) {
    classes.push('hover:shadow-md dark:hover:shadow-gray-900/20')
  }

  if (props.clickable) {
    classes.push('cursor-pointer hover:shadow-md active:scale-[0.98] dark:hover:shadow-gray-900/20')
  }

  if (props.loading) {
    classes.push('opacity-75')
  }

  return classes.join(' ')
})

const headerClasses = computed(() => {
  const classes = [
    'border-b border-gray-200 bg-gray-50',
    'dark:border-gray-700 dark:bg-gray-800'
  ]
  
  if (props.padding !== 'none') {
    classes.push(paddingClasses[props.padding])
  }

  return classes.join(' ')
})

const bodyClasses = computed(() => {
  const classes = []
  
  if (props.padding !== 'none') {
    classes.push(paddingClasses[props.padding])
  }

  return classes.join(' ')
})

const footerClasses = computed(() => {
  const classes = [
    'border-t border-gray-200 bg-gray-50',
    'dark:border-gray-700 dark:bg-gray-800'
  ]
  
  if (props.padding !== 'none') {
    classes.push(paddingClasses[props.padding])
  }

  return classes.join(' ')
})

const titleClasses = computed(() => {
  return 'text-lg font-semibold text-gray-900 dark:text-gray-100'
})

const subtitleClasses = computed(() => {
  return 'text-sm text-gray-600 dark:text-gray-400 mt-1'
})

const iconClasses = computed(() => {
  return 'h-5 w-5 text-gray-600 dark:text-gray-400 mr-3'
})

const loadingOverlayClasses = computed(() => {
  return [
    'absolute inset-0 bg-white bg-opacity-75 dark:bg-gray-800 dark:bg-opacity-75',
    'flex items-center justify-center z-10'
  ].join(' ')
})

// Methods
const handleClick = (event) => {
  if (props.clickable && !props.loading) {
    emit('click', event, props.card)
  }
}

const handleRefresh = () => {
  if (props.refreshable && !props.loading) {
    emit('refresh', props.card)
  }
}

// Provide methods to parent components
defineExpose({
  card: props.card,
  refresh: handleRefresh
})
</script>

<style scoped>
/* Card-specific styles */
.card-enter-active,
.card-leave-active {
  transition: all 0.3s ease;
}

.card-enter-from,
.card-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

/* Loading animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
