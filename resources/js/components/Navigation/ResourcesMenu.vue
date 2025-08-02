<template>
  <div v-if="resources && resources.length > 0">
    <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2"
        :class="{ 'text-gray-400': isDarkTheme }">
      Resources
    </h3>
    <div class="space-y-1">
      <SidebarItem
        v-for="resource in resources"
        :key="resource.uriKey"
        :href="route('admin-panel.resources.index', resource.uriKey)"
        :active="currentResource === resource.uriKey"
        :icon="resource.icon || 'DocumentTextIcon'"
      >
        {{ resource.label }}
      </SidebarItem>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'
import SidebarItem from '../Layout/SidebarItem.vue'

// Store
const adminStore = useAdminStore()

// Page data
const page = usePage()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const resources = computed(() => {
  return page.props.resources || []
})

const currentResource = computed(() => {
  const url = page.url
  const match = url.match(/\/admin\/resources\/([^\/]+)/)
  return match ? match[1] : null
})

// Use global route() function provided by Ziggy via @routes directive
</script>
