<template>
  <button
    type="button"
    class="action-button"
    :class="buttonClasses"
    :disabled="disabled || processing"
    @click="handleClick"
  >
    <!-- Loading spinner -->
    <svg
      v-if="processing"
      class="animate-spin -ml-1 mr-2 h-4 w-4"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>

    <!-- Action icon -->
    <component
      v-else-if="action.icon"
      :is="getIcon(action.icon)"
      class="h-4 w-4"
      :class="{ 'mr-2': !iconOnly }"
    />

    <!-- Action text -->
    <span v-if="!iconOnly">
      {{ processing ? processingText : action.name }}
    </span>
  </button>

  <!-- Confirmation modal -->
  <ConfirmationModal
    v-if="showConfirmation"
    :title="confirmationTitle"
    :message="action.confirmationMessage"
    :confirm-text="confirmText"
    :confirm-color="action.destructive ? 'red' : 'blue'"
    :type="action.destructive ? 'warning' : 'info'"
    @confirm="executeAction"
    @cancel="showConfirmation = false"
  />
</template>

<script setup>
/**
 * ActionButton Component
 * 
 * Button component for executing actions with confirmation dialogs,
 * loading states, and proper error handling.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import * as HeroIcons from '@heroicons/vue/24/outline'
import ConfirmationModal from '@/components/Common/ConfirmationModal.vue'

// Props
const props = defineProps({
  action: {
    type: Object,
    required: true
  },
  selectedResources: {
    type: Array,
    default: () => []
  },
  resourceType: {
    type: String,
    required: true
  },
  variant: {
    type: String,
    default: 'secondary',
    validator: (value) => ['primary', 'secondary', 'danger', 'outline'].includes(value)
  },
  size: {
    type: String,
    default: 'default',
    validator: (value) => ['small', 'default', 'large'].includes(value)
  },
  iconOnly: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['executed', 'error'])

// Store
const adminStore = useAdminStore()

// Reactive data
const processing = ref(false)
const showConfirmation = ref(false)

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const buttonClasses = computed(() => {
  const baseClasses = 'inline-flex items-center justify-center font-medium rounded-md transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2'
  
  // Size classes
  const sizeClasses = {
    small: 'px-2.5 py-1.5 text-xs',
    default: 'px-3 py-2 text-sm',
    large: 'px-4 py-2 text-base'
  }
  
  // Variant classes
  let variantClasses = ''
  const variant = props.action.destructive ? 'danger' : props.variant
  
  switch (variant) {
    case 'primary':
      variantClasses = 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500'
      if (isDarkTheme.value) {
        variantClasses = 'bg-blue-500 text-white hover:bg-blue-600 focus:ring-blue-400'
      }
      break
    case 'danger':
      variantClasses = 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500'
      if (isDarkTheme.value) {
        variantClasses = 'bg-red-500 text-white hover:bg-red-600 focus:ring-red-400'
      }
      break
    case 'outline':
      variantClasses = 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-blue-500'
      if (isDarkTheme.value) {
        variantClasses = 'bg-gray-700 border border-gray-600 text-gray-300 hover:bg-gray-600 focus:ring-blue-400'
      }
      break
    case 'secondary':
    default:
      variantClasses = 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-500'
      if (isDarkTheme.value) {
        variantClasses = 'bg-gray-600 text-gray-300 hover:bg-gray-500 focus:ring-gray-400'
      }
      break
  }
  
  // Disabled state
  const disabledClasses = (props.disabled || processing.value) 
    ? 'opacity-50 cursor-not-allowed pointer-events-none' 
    : ''
  
  return [
    baseClasses,
    sizeClasses[props.size],
    variantClasses,
    disabledClasses
  ].filter(Boolean).join(' ')
})

const confirmationTitle = computed(() => {
  return `Execute ${props.action.name}`
})

const confirmText = computed(() => {
  return props.action.destructive ? 'Delete' : 'Execute'
})

const processingText = computed(() => {
  return `${props.action.name}...`
})

// Methods
const getIcon = (iconName) => {
  return HeroIcons[iconName] || HeroIcons.DocumentTextIcon
}

const handleClick = () => {
  if (props.selectedResources.length === 0) {
    adminStore.notifyWarning('Please select at least one resource')
    return
  }

  if (props.action.confirmationMessage) {
    showConfirmation.value = true
  } else {
    executeAction()
  }
}

const executeAction = async () => {
  showConfirmation.value = false
  processing.value = true

  try {
    const response = await axios.post(
      route('admin-panel.api.execute-action', [props.resourceType, props.action.uriKey]),
      {
        resources: props.selectedResources
      }
    )

    const { message, type, redirect, download, filename } = response.data

    // Handle different response types
    switch (type) {
      case 'success':
        adminStore.notifySuccess(message)
        break
      case 'warning':
        adminStore.notifyWarning(message)
        break
      case 'info':
        adminStore.notifyInfo(message)
        break
      case 'error':
      default:
        adminStore.notifyError(message)
        break
    }

    // Handle download
    if (download && filename) {
      const link = document.createElement('a')
      link.href = download
      link.download = filename
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    }

    // Handle redirect
    if (redirect) {
      window.location.href = redirect
    } else {
      // Emit executed event for parent to handle
      emit('executed', response.data)
    }
  } catch (error) {
    const message = error.response?.data?.message || 'Action failed'
    adminStore.notifyError(message)
    emit('error', error)
  } finally {
    processing.value = false
  }
}

const route = (name, params = {}) => {
  return window.adminPanel?.route(name, params) || '#'
}
</script>

<style scoped>
/* Loading spinner animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Button transitions */
.action-button {
  transition: all 0.15s ease-in-out;
}

/* Focus ring offset for dark theme */
.dark .action-button:focus {
  --tw-ring-offset-color: rgb(31 41 55);
}

/* Icon-only button styling */
.action-button:has(span:empty) {
}
</style>
