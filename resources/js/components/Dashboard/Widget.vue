<template>
  <Card
    :title="title"
    :subtitle="subtitle"
    :icon="icon"
    :variant="variant"
    :padding="padding"
    :hoverable="hoverable"
    :clickable="clickable"
    @click="handleClick"
  >
    <template v-if="$slots.actions" #actions>
      <slot name="actions" />
    </template>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <LoadingSpinner size="lg" :text="loadingText" />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-8">
      <div class="text-red-500 mb-2">
        <ExclamationTriangleIcon class="w-8 h-8 mx-auto" />
      </div>
      <p class="text-sm text-gray-600">{{ error }}</p>
      <Button
        v-if="retryable"
        variant="ghost"
        size="sm"
        class="mt-2"
        @click="handleRetry"
      >
        Try Again
      </Button>
    </div>

    <!-- Empty State -->
    <div v-else-if="isEmpty" class="text-center py-8">
      <div class="text-gray-400 mb-2">
        <component
          :is="emptyIcon"
          class="w-8 h-8 mx-auto"
        />
      </div>
      <p class="text-sm text-gray-600">{{ emptyMessage }}</p>
    </div>

    <!-- Content -->
    <div v-else>
      <slot />
    </div>

    <!-- Footer -->
    <template v-if="$slots.footer" #footer>
      <slot name="footer" />
    </template>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import { ExclamationTriangleIcon, InboxIcon } from '@heroicons/vue/24/outline'
import Card from '../Common/Card.vue'
import Button from '../Common/Button.vue'
import LoadingSpinner from '../Common/LoadingSpinner.vue'

const props = defineProps({
  title: {
    type: String,
    default: null
  },
  subtitle: {
    type: String,
    default: null
  },
  icon: {
    type: [String, Object],
    default: null
  },
  variant: {
    type: String,
    default: 'default'
  },
  padding: {
    type: String,
    default: 'md'
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
  loadingText: {
    type: String,
    default: 'Loading...'
  },
  error: {
    type: String,
    default: null
  },
  retryable: {
    type: Boolean,
    default: false
  },
  isEmpty: {
    type: Boolean,
    default: false
  },
  emptyMessage: {
    type: String,
    default: 'No data available'
  },
  emptyIcon: {
    type: [String, Object],
    default: InboxIcon
  },
  refreshable: {
    type: Boolean,
    default: false
  },
  lastUpdated: {
    type: [String, Date],
    default: null
  }
})

const emit = defineEmits(['click', 'retry', 'refresh'])

const handleClick = (event) => {
  if (props.clickable) {
    emit('click', event)
  }
}

const handleRetry = () => {
  emit('retry')
}

const handleRefresh = () => {
  emit('refresh')
}

const formattedLastUpdated = computed(() => {
  if (!props.lastUpdated) return null
  
  const date = props.lastUpdated instanceof Date ? props.lastUpdated : new Date(props.lastUpdated)
  
  return new Intl.RelativeTimeFormat('en', { numeric: 'auto' }).format(
    Math.round((date.getTime() - Date.now()) / (1000 * 60)),
    'minute'
  )
})
</script>

<style scoped>
/* Additional widget styles if needed */
</style>
