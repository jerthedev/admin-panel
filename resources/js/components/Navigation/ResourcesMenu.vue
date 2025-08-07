<template>
  <div v-if="resources && resources.length > 0">
    <!-- Grouped Resources -->
    <div v-for="(groupResources, groupName) in groupedResources" :key="groupName" class="mb-6">
      <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2"
          :class="{ 'text-gray-400': isDarkTheme }">
        {{ groupName }}
      </h3>
      <div class="space-y-1">
        <SidebarItem
          v-for="resource in groupResources"
          :key="resource.uriKey"
          :href="route('admin-panel.resources.index', resource.uriKey)"
          :active="currentResource === resource.uriKey"
          :icon="resource.icon || 'DocumentTextIcon'"
          :badge="resource.badge"
          :badge-type="resource.badgeType || 'primary'"
        >
          {{ resource.label }}
        </SidebarItem>
      </div>
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

const groupedResources = computed(() => {
  const groups = {}

  // Group resources by their group property
  resources.value.forEach(resource => {
    const groupName = resource.group || 'Default'
    if (!groups[groupName]) {
      groups[groupName] = []
    }
    groups[groupName].push(resource)
  })

  // Sort resources alphabetically within each group
  Object.keys(groups).forEach(groupName => {
    groups[groupName].sort((a, b) => a.label.localeCompare(b.label))
  })

  // Sort groups alphabetically, but keep 'Default' last
  const sortedGroups = {}
  const groupNames = Object.keys(groups).sort((a, b) => {
    if (a === 'Default') return 1
    if (b === 'Default') return -1
    return a.localeCompare(b)
  })

  groupNames.forEach(groupName => {
    sortedGroups[groupName] = groups[groupName]
  })

  return sortedGroups
})

const currentResource = computed(() => {
  const url = page.url
  const match = url.match(/\/admin\/resources\/([^\/]+)/)
  return match ? match[1] : null
})

// Use global route() function provided by Ziggy via @routes directive
</script>
