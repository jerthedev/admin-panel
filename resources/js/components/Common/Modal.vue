<template>
  <Teleport to="body">
    <Transition
      enter-active-class="duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
      >
        <!-- Background overlay -->
        <div
          class="fixed inset-0 bg-gray-500/75 transition-opacity"
          @click="handleBackdropClick"
        ></div>

        <!-- Modal container -->
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <Transition
            enter-active-class="duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              v-if="show"
              :class="modalClasses"
              @click.stop
            >
              <!-- Header -->
              <div v-if="$slots.header || title || closable" class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center">
                  <component
                    v-if="icon"
                    :is="icon"
                    :class="iconClasses"
                  />
                  <div>
                    <h3 v-if="title" id="modal-title" class="text-lg font-medium text-gray-900">
                      {{ title }}
                    </h3>
                    <slot name="header" />
                  </div>
                </div>
                <button
                  v-if="closable"
                  type="button"
                  class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                  @click="close"
                >
                  <span class="sr-only">Close</span>
                  <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                </button>
              </div>

              <!-- Body -->
              <div class="p-6">
                <slot />
              </div>

              <!-- Footer -->
              <div v-if="$slots.footer" class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
                <slot name="footer" />
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: null
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', 'full'].includes(value)
  },
  closable: {
    type: Boolean,
    default: true
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  },
  icon: {
    type: [String, Object],
    default: null
  },
  iconColor: {
    type: String,
    default: 'blue',
    validator: (value) => ['blue', 'green', 'red', 'yellow', 'purple', 'gray'].includes(value)
  }
})

const emit = defineEmits(['close', 'opened', 'closed'])

const sizeClasses = {
  xs: 'sm:max-w-xs',
  sm: 'sm:max-w-sm',
  md: 'sm:max-w-md',
  lg: 'sm:max-w-lg',
  xl: 'sm:max-w-xl',
  '2xl': 'sm:max-w-2xl',
  full: 'sm:max-w-full sm:m-4'
}

const iconColorClasses = {
  blue: 'text-blue-600',
  green: 'text-green-600',
  red: 'text-red-600',
  yellow: 'text-yellow-600',
  purple: 'text-purple-600',
  gray: 'text-gray-600'
}

const modalClasses = computed(() => {
  const classes = [
    'relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all',
    'w-full',
    sizeClasses[props.size]
  ]

  return classes.join(' ')
})

const iconClasses = computed(() => {
  const classes = [
    'h-6 w-6 mr-3',
    iconColorClasses[props.iconColor]
  ]

  return classes.join(' ')
})

const close = () => {
  emit('close')
}

const handleBackdropClick = () => {
  if (props.closeOnBackdrop) {
    close()
  }
}

const handleEscapeKey = (event) => {
  if (event.key === 'Escape' && props.show && props.closable) {
    close()
  }
}

// Watch for show prop changes to emit events
watch(() => props.show, (newValue, oldValue) => {
  if (newValue && !oldValue) {
    emit('opened')
  } else if (!newValue && oldValue) {
    emit('closed')
  }
})

// Handle escape key
onMounted(() => {
  document.addEventListener('keydown', handleEscapeKey)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscapeKey)
})

// Prevent body scroll when modal is open
watch(() => props.show, (show) => {
  if (show) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

// Cleanup on unmount
onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<style scoped>
/* Additional modal styles if needed */
</style>
