<template>
  <TransitionRoot as="template" :show="true">
    <Dialog as="div" class="relative z-50" @close="$emit('cancel')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6" :class="{ 'bg-gray-800': isDarkTheme }">
              <div class="sm:flex sm:items-start">
                <!-- Icon -->
                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10" :class="iconClasses">
                  <component :is="iconComponent" class="h-6 w-6" :class="iconColorClasses" />
                </div>

                <!-- Content -->
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                  <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900" :class="{ 'text-white': isDarkTheme }">
                    {{ title }}
                  </DialogTitle>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                      {{ message }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  class="inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm sm:ml-3 sm:w-auto"
                  :class="confirmButtonClasses"
                  @click="$emit('confirm')"
                >
                  {{ confirmText }}
                </button>
                <button
                  type="button"
                  class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                  :class="{ 'bg-gray-700 text-white ring-gray-600 hover:bg-gray-600': isDarkTheme }"
                  @click="$emit('cancel')"
                >
                  {{ cancelText }}
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
/**
 * ConfirmationModal Component
 * 
 * Reusable confirmation modal with customizable title, message,
 * and button colors for different action types.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  ExclamationTriangleIcon,
  InformationCircleIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  title: {
    type: String,
    default: 'Confirm Action'
  },
  message: {
    type: String,
    default: 'Are you sure you want to perform this action?'
  },
  confirmText: {
    type: String,
    default: 'Confirm'
  },
  cancelText: {
    type: String,
    default: 'Cancel'
  },
  confirmColor: {
    type: String,
    default: 'red',
    validator: (value) => ['red', 'blue', 'green', 'amber'].includes(value)
  },
  type: {
    type: String,
    default: 'warning',
    validator: (value) => ['warning', 'info', 'success', 'error'].includes(value)
  }
})

// Emits
defineEmits(['confirm', 'cancel'])

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const iconComponent = computed(() => {
  switch (props.type) {
    case 'info':
      return InformationCircleIcon
    case 'success':
      return CheckCircleIcon
    case 'error':
      return XCircleIcon
    case 'warning':
    default:
      return ExclamationTriangleIcon
  }
})

const iconClasses = computed(() => {
  const baseClasses = 'mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10'
  
  switch (props.type) {
    case 'info':
      return `${baseClasses} bg-blue-100`
    case 'success':
      return `${baseClasses} bg-green-100`
    case 'error':
      return `${baseClasses} bg-red-100`
    case 'warning':
    default:
      return `${baseClasses} bg-red-100`
  }
})

const iconColorClasses = computed(() => {
  switch (props.type) {
    case 'info':
      return 'text-blue-600'
    case 'success':
      return 'text-green-600'
    case 'error':
      return 'text-red-600'
    case 'warning':
    default:
      return 'text-red-600'
  }
})

const confirmButtonClasses = computed(() => {
  const baseClasses = 'inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto'
  
  switch (props.confirmColor) {
    case 'blue':
      return `${baseClasses} bg-blue-600 hover:bg-blue-500 focus:ring-blue-500`
    case 'green':
      return `${baseClasses} bg-green-600 hover:bg-green-500 focus:ring-green-500`
    case 'amber':
      return `${baseClasses} bg-amber-600 hover:bg-amber-500 focus:ring-amber-500`
    case 'red':
    default:
      return `${baseClasses} bg-red-600 hover:bg-red-500 focus:ring-red-500`
  }
})
</script>

<style scoped>
/* Ensure modal appears above everything */
.relative.z-50 {
  z-index: 50;
}

/* Smooth transitions */
.transition-opacity {
  transition-property: opacity;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Focus styles */
button:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

/* Button hover effects */
button {
  transition: all 0.15s ease-in-out;
}
</style>
