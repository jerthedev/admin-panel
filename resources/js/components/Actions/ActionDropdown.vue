<template>
  <div class="relative inline-block text-left">
    <!-- Dropdown trigger -->
    <button
      ref="triggerRef"
      type="button"
      class="inline-flex items-center justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      :class="{ 
        'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600 focus:ring-blue-400': isDarkTheme,
        'opacity-50 cursor-not-allowed': disabled || selectedResources.length === 0
      }"
      :disabled="disabled || selectedResources.length === 0"
      @click="toggleDropdown"
    >
      <span>{{ triggerText }}</span>
      <ChevronDownIcon class="ml-2 -mr-1 h-4 w-4" />
    </button>

    <!-- Dropdown menu -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="showDropdown"
        class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
        :class="{ 'bg-gray-800 ring-gray-700': isDarkTheme }"
      >
        <div class="py-1">
          <button
            v-for="action in availableActions"
            :key="action.uriKey"
            type="button"
            class="group flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
            :class="[
              { 
                'text-gray-300 hover:bg-gray-700 hover:text-white': isDarkTheme,
                'text-red-600 hover:bg-red-50 hover:text-red-700': action.destructive && !isDarkTheme,
                'text-red-400 hover:bg-red-900 hover:text-red-300': action.destructive && isDarkTheme
              }
            ]"
            @click="executeAction(action)"
          >
            <component
              v-if="action.icon"
              :is="getIcon(action.icon)"
              class="mr-3 h-4 w-4"
              :class="{ 'text-red-500': action.destructive }"
            />
            {{ action.name }}
          </button>

          <!-- No actions message -->
          <div
            v-if="availableActions.length === 0"
            class="px-4 py-2 text-sm text-gray-500"
            :class="{ 'text-gray-400': isDarkTheme }"
          >
            No actions available
          </div>
        </div>
      </div>
    </Transition>

    <!-- Action confirmation modals -->
    <ConfirmationModal
      v-if="showConfirmation && selectedAction"
      :title="`Execute ${selectedAction.name}`"
      :message="selectedAction.confirmationMessage"
      :confirm-text="selectedAction.destructive ? 'Delete' : 'Execute'"
      :confirm-color="selectedAction.destructive ? 'red' : 'blue'"
      :type="selectedAction.destructive ? 'warning' : 'info'"
      @confirm="confirmAction"
      @cancel="cancelAction"
    />
  </div>
</template>

<script setup>
/**
 * ActionDropdown Component
 * 
 * Dropdown component for displaying multiple actions with proper
 * authorization, confirmation, and execution handling.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import * as HeroIcons from '@heroicons/vue/24/outline'
import ConfirmationModal from '@/components/Common/ConfirmationModal.vue'

// Props
const props = defineProps({
  actions: {
    type: Array,
    default: () => []
  },
  selectedResources: {
    type: Array,
    default: () => []
  },
  resourceType: {
    type: String,
    required: true
  },
  triggerText: {
    type: String,
    default: 'Actions'
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

// Refs
const triggerRef = ref(null)
const showDropdown = ref(false)
const showConfirmation = ref(false)
const selectedAction = ref(null)
const processing = ref(false)

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const availableActions = computed(() => {
  return props.actions.filter(action => {
    // Add any authorization logic here if needed
    return true
  })
})

// Methods
const getIcon = (iconName) => {
  return HeroIcons[iconName] || HeroIcons.DocumentTextIcon
}

const toggleDropdown = () => {
  if (props.disabled || props.selectedResources.length === 0) {
    return
  }
  
  showDropdown.value = !showDropdown.value
}

const closeDropdown = () => {
  showDropdown.value = false
}

const executeAction = (action) => {
  closeDropdown()
  
  if (props.selectedResources.length === 0) {
    adminStore.notifyWarning('Please select at least one resource')
    return
  }

  selectedAction.value = action

  if (action.confirmationMessage) {
    showConfirmation.value = true
  } else {
    confirmAction()
  }
}

const confirmAction = async () => {
  showConfirmation.value = false
  
  if (!selectedAction.value) return

  processing.value = true

  try {
    const response = await axios.post(
      route('admin-panel.api.execute-action', [props.resourceType, selectedAction.value.uriKey]),
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
    selectedAction.value = null
  }
}

const cancelAction = () => {
  showConfirmation.value = false
  selectedAction.value = null
}

const handleClickOutside = (event) => {
  if (triggerRef.value && !triggerRef.value.contains(event.target)) {
    closeDropdown()
  }
}

const route = (name, params = {}) => {
  return window.adminPanel?.route(name, params) || '#'
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Ensure dropdown appears above other elements */
.z-50 {
  z-index: 50;
}

/* Smooth transitions */
.transition {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Dropdown item hover effects */
button:hover {
  transition: all 0.15s ease-in-out;
}

/* Focus styles */
button:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

/* Disabled state */
button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}
</style>
