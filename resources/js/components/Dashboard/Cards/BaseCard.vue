<template>
  <div class="base-card">
    <!-- Card Content Slot -->
    <slot>
      <div class="default-content">
        <div class="text-center py-6">
          <svg
            class="mx-auto h-8 w-8 text-gray-400 mb-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Base Card Component
          </p>
        </div>
      </div>
    </slot>
  </div>
</template>

<script>
export default {
  name: 'BaseCard',
  props: {
    dashboard: {
      type: Object,
      required: true
    }
  },
  emits: ['action', 'error'],
  setup(props, { emit }) {
    const handleAction = (action) => {
      emit('action', action)
    }

    const handleError = (error) => {
      emit('error', error)
    }

    return {
      handleAction,
      handleError
    }
  }
}
</script>

<style scoped>
.base-card {
  @apply h-full;
}

.default-content {
  @apply min-h-[120px] flex items-center justify-center;
}
</style>
