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
            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 p-6 shadow-xl transition-all">
              <DialogTitle class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Keyboard Shortcuts
              </DialogTitle>

              <div class="space-y-6">
                <!-- Navigation -->
                <div>
                  <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Navigation</h3>
                  <div class="space-y-2">
                    <ShortcutItem keys="cmd+k" description="Open quick switcher" />
                    <ShortcutItem keys="cmd+/" description="Search dashboards" />
                    <ShortcutItem keys="cmd+," description="Open settings" />
                    <ShortcutItem keys="esc" description="Close modals/dialogs" />
                  </div>
                </div>

                <!-- Dashboard Actions -->
                <div>
                  <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Dashboard Actions</h3>
                  <div class="space-y-2">
                    <ShortcutItem keys="r" description="Refresh current dashboard" />
                    <ShortcutItem keys="f" description="Toggle fullscreen" />
                    <ShortcutItem keys="e" description="Edit dashboard" />
                    <ShortcutItem keys="d" description="Duplicate dashboard" />
                  </div>
                </div>

                <!-- Card Navigation -->
                <div>
                  <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Card Navigation</h3>
                  <div class="space-y-2">
                    <ShortcutItem keys="↑ ↓ ← →" description="Navigate between cards" />
                    <ShortcutItem keys="enter" description="Open selected card" />
                    <ShortcutItem keys="space" description="Toggle card selection" />
                    <ShortcutItem keys="cmd+a" description="Select all cards" />
                  </div>
                </div>

                <!-- View Controls -->
                <div>
                  <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">View Controls</h3>
                  <div class="space-y-2">
                    <ShortcutItem keys="1-9" description="Switch to dashboard 1-9" />
                    <ShortcutItem keys="g" description="Toggle grid view" />
                    <ShortcutItem keys="l" description="Toggle list view" />
                    <ShortcutItem keys="c" description="Toggle compact mode" />
                  </div>
                </div>
              </div>

              <div class="mt-6 flex justify-end">
                <button
                  @click="close"
                  class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                >
                  Got it
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
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const close = () => {
  emit('close')
}

// Shortcut Item Component (inline)
const ShortcutItem = {
  props: ['keys', 'description'],
  template: `
    <div class="flex items-center justify-between text-sm">
      <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600">
        {{ keys }}
      </kbd>
      <span class="text-gray-600 dark:text-gray-400">{{ description }}</span>
    </div>
  `
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;
</style>