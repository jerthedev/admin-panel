<template>
  <TransitionRoot :show="isOpen" as="template">
    <Dialog as="div" class="relative z-50" @close="close">
      <TransitionChild
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25 dark:bg-black/50" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            enter="ease-out duration-300"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 p-6 shadow-xl transition-all">
              <DialogTitle class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Dashboard Settings
              </DialogTitle>

              <div class="space-y-4">
                <!-- Default Dashboard -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Default Dashboard
                  </label>
                  <select
                    v-model="settings.defaultDashboard"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  >
                    <option v-for="dashboard in availableDashboards" :key="dashboard.id" :value="dashboard.id">
                      {{ dashboard.name }}
                    </option>
                  </select>
                </div>

                <!-- Auto-refresh -->
                <div>
                  <label class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Auto-refresh Dashboard
                    </span>
                    <input
                      v-model="settings.autoRefresh"
                      type="checkbox"
                      class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                  </label>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Automatically refresh dashboard data every 30 seconds
                  </p>
                </div>

                <!-- Compact Mode -->
                <div>
                  <label class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Compact Mode
                    </span>
                    <input
                      v-model="settings.compactMode"
                      type="checkbox"
                      class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                  </label>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Reduce spacing between dashboard cards
                  </p>
                </div>

                <!-- Show Empty Cards -->
                <div>
                  <label class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Show Empty Cards
                    </span>
                    <input
                      v-model="settings.showEmptyCards"
                      type="checkbox"
                      class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                  </label>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Display cards even when they have no data
                  </p>
                </div>
              </div>

              <div class="mt-6 flex justify-end gap-3">
                <button
                  @click="close"
                  class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
                >
                  Cancel
                </button>
                <button
                  @click="save"
                  class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                >
                  Save Settings
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
import { ref, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { useDashboardNavigationStore } from '@/stores/dashboardNavigation'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const dashboardStore = useDashboardNavigationStore()

const settings = ref({
  defaultDashboard: '',
  autoRefresh: false,
  compactMode: false,
  showEmptyCards: true
})

const availableDashboards = ref([
  { id: 'main', name: 'Main Dashboard' },
  { id: 'analytics', name: 'Analytics Dashboard' },
  { id: 'sales', name: 'Sales Dashboard' }
])

const close = () => {
  emit('close')
}

const save = () => {
  // Save settings to store/localStorage
  localStorage.setItem('dashboardSettings', JSON.stringify(settings.value))
  dashboardStore.updateSettings(settings.value)
  close()
}

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    // Load settings when opening
    const saved = localStorage.getItem('dashboardSettings')
    if (saved) {
      try {
        settings.value = JSON.parse(saved)
      } catch (e) {
        // Use defaults
      }
    }
  }
})
</script>

<style scoped>
@import '../../../css/admin.css' reference;
</style>