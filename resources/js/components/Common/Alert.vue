<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 transform scale-95"
    enter-to-class="opacity-100 transform scale-100"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100 transform scale-100"
    leave-to-class="opacity-0 transform scale-95"
  >
    <div v-if="show" :class="alertClasses" role="alert">
      <div class="flex">
        <!-- Icon -->
        <div class="flex-shrink-0">
          <component
            :is="alertIcon"
            :class="iconClasses"
            aria-hidden="true"
          />
        </div>

        <!-- Content -->
        <div class="ml-3 flex-1">
          <h3 v-if="title" :class="titleClasses">
            {{ title }}
          </h3>
          <div :class="messageClasses">
            <slot>
              {{ message }}
            </slot>
          </div>
          
          <!-- Actions -->
          <div v-if="$slots.actions" class="mt-4">
            <div class="flex space-x-3">
              <slot name="actions" />
            </div>
          </div>
        </div>

        <!-- Close button -->
        <div v-if="dismissible" class="ml-auto pl-3">
          <div class="-mx-1.5 -my-1.5">
            <button
              type="button"
              :class="closeButtonClasses"
              @click="dismiss"
            >
              <span class="sr-only">Dismiss</span>
              <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import {
  CheckCircleIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  XMarkIcon
} from '@heroicons/vue/20/solid'

const props = defineProps({
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'info', 'warning', 'error'].includes(value)
  },
  title: {
    type: String,
    default: null
  },
  message: {
    type: String,
    default: null
  },
  dismissible: {
    type: Boolean,
    default: false
  },
  autoDismiss: {
    type: Number,
    default: null
  },
  variant: {
    type: String,
    default: 'filled',
    validator: (value) => ['filled', 'outlined', 'minimal'].includes(value)
  }
})

const emit = defineEmits(['dismiss'])

const show = ref(true)

const typeConfig = {
  success: {
    icon: CheckCircleIcon,
    colors: {
      filled: {
        bg: 'bg-green-50',
        border: 'border-green-200',
        icon: 'text-green-400',
        title: 'text-green-800',
        message: 'text-green-700',
        close: 'text-green-500 hover:bg-green-100 focus:ring-green-600'
      },
      outlined: {
        bg: 'bg-white',
        border: 'border-green-300',
        icon: 'text-green-500',
        title: 'text-green-800',
        message: 'text-green-700',
        close: 'text-green-500 hover:bg-green-50 focus:ring-green-600'
      },
      minimal: {
        bg: 'bg-transparent',
        border: 'border-l-4 border-green-400',
        icon: 'text-green-400',
        title: 'text-green-800',
        message: 'text-green-700',
        close: 'text-green-500 hover:bg-green-50 focus:ring-green-600'
      }
    }
  },
  info: {
    icon: InformationCircleIcon,
    colors: {
      filled: {
        bg: 'bg-blue-50',
        border: 'border-blue-200',
        icon: 'text-blue-400',
        title: 'text-blue-800',
        message: 'text-blue-700',
        close: 'text-blue-500 hover:bg-blue-100 focus:ring-blue-600'
      },
      outlined: {
        bg: 'bg-white',
        border: 'border-blue-300',
        icon: 'text-blue-500',
        title: 'text-blue-800',
        message: 'text-blue-700',
        close: 'text-blue-500 hover:bg-blue-50 focus:ring-blue-600'
      },
      minimal: {
        bg: 'bg-transparent',
        border: 'border-l-4 border-blue-400',
        icon: 'text-blue-400',
        title: 'text-blue-800',
        message: 'text-blue-700',
        close: 'text-blue-500 hover:bg-blue-50 focus:ring-blue-600'
      }
    }
  },
  warning: {
    icon: ExclamationTriangleIcon,
    colors: {
      filled: {
        bg: 'bg-yellow-50',
        border: 'border-yellow-200',
        icon: 'text-yellow-400',
        title: 'text-yellow-800',
        message: 'text-yellow-700',
        close: 'text-yellow-500 hover:bg-yellow-100 focus:ring-yellow-600'
      },
      outlined: {
        bg: 'bg-white',
        border: 'border-yellow-300',
        icon: 'text-yellow-500',
        title: 'text-yellow-800',
        message: 'text-yellow-700',
        close: 'text-yellow-500 hover:bg-yellow-50 focus:ring-yellow-600'
      },
      minimal: {
        bg: 'bg-transparent',
        border: 'border-l-4 border-yellow-400',
        icon: 'text-yellow-400',
        title: 'text-yellow-800',
        message: 'text-yellow-700',
        close: 'text-yellow-500 hover:bg-yellow-50 focus:ring-yellow-600'
      }
    }
  },
  error: {
    icon: XCircleIcon,
    colors: {
      filled: {
        bg: 'bg-red-50',
        border: 'border-red-200',
        icon: 'text-red-400',
        title: 'text-red-800',
        message: 'text-red-700',
        close: 'text-red-500 hover:bg-red-100 focus:ring-red-600'
      },
      outlined: {
        bg: 'bg-white',
        border: 'border-red-300',
        icon: 'text-red-500',
        title: 'text-red-800',
        message: 'text-red-700',
        close: 'text-red-500 hover:bg-red-50 focus:ring-red-600'
      },
      minimal: {
        bg: 'bg-transparent',
        border: 'border-l-4 border-red-400',
        icon: 'text-red-400',
        title: 'text-red-800',
        message: 'text-red-700',
        close: 'text-red-500 hover:bg-red-50 focus:ring-red-600'
      }
    }
  }
}

const currentColors = computed(() => typeConfig[props.type].colors[props.variant])

const alertClasses = computed(() => {
  const classes = [
    'rounded-md p-4 border',
    currentColors.value.bg,
    currentColors.value.border
  ]

  return classes.join(' ')
})

const alertIcon = computed(() => typeConfig[props.type].icon)

const iconClasses = computed(() => {
  return `h-5 w-5 ${currentColors.value.icon}`
})

const titleClasses = computed(() => {
  return `text-sm font-medium ${currentColors.value.title}`
})

const messageClasses = computed(() => {
  const classes = [`text-sm ${currentColors.value.message}`]
  
  if (props.title) {
    classes.push('mt-1')
  }

  return classes.join(' ')
})

const closeButtonClasses = computed(() => {
  return `inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 ${currentColors.value.close}`
})

const dismiss = () => {
  show.value = false
  emit('dismiss')
}

// Auto dismiss functionality
onMounted(() => {
  if (props.autoDismiss && props.autoDismiss > 0) {
    setTimeout(() => {
      dismiss()
    }, props.autoDismiss)
  }
})
</script>

<style scoped>
/* Additional alert styles if needed */
</style>
