<template>
  <Link
    :href="href"
    class="admin-nav-item group"
    :class="[
      active ? activeClasses : inactiveClasses,
      { 'cursor-not-allowed opacity-50': disabled }
    ]"
    :disabled="disabled"
  >
    <!-- Icon -->
    <component
      :is="iconComponent"
      class="mr-3 flex-shrink-0 h-5 w-5"
      :class="active ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500'"
      aria-hidden="true"
    />
    
    <!-- Label -->
    <span class="truncate">
      <slot />
    </span>
    
    <!-- Badge -->
    <span
      v-if="badge"
      class="ml-auto inline-block py-0.5 px-2 text-xs rounded-full"
      :class="badgeClasses"
    >
      {{ badge }}
    </span>
  </Link>
</template>

<script setup>
/**
 * SidebarItem Component
 * 
 * Individual navigation item for the sidebar with icon, label,
 * active state, and optional badge support.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'
import * as HeroIcons from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  href: {
    type: String,
    required: true
  },
  active: {
    type: Boolean,
    default: false
  },
  icon: {
    type: String,
    default: 'DocumentTextIcon'
  },
  badge: {
    type: [String, Number],
    default: null
  },
  badgeType: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'success', 'warning', 'error'].includes(value)
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const iconComponent = computed(() => {
  return HeroIcons[props.icon] || HeroIcons.DocumentTextIcon
})

const activeClasses = computed(() => {
  return isDarkTheme.value
    ? 'admin-nav-item-active-dark'
    : 'admin-nav-item-active'
})

const inactiveClasses = computed(() => {
  return isDarkTheme.value
    ? 'admin-nav-item-inactive-dark'
    : 'admin-nav-item-inactive'
})

const badgeClasses = computed(() => {
  const baseClasses = 'font-medium'
  
  switch (props.badgeType) {
    case 'primary':
      return `${baseClasses} bg-blue-100 text-blue-800`
    case 'secondary':
      return `${baseClasses} bg-gray-100 text-gray-800`
    case 'success':
      return `${baseClasses} bg-green-100 text-green-800`
    case 'warning':
      return `${baseClasses} bg-amber-100 text-amber-800`
    case 'error':
      return `${baseClasses} bg-red-100 text-red-800`
    default:
      return `${baseClasses} bg-blue-100 text-blue-800`
  }
})
</script>

<style scoped>
/* Additional hover effects */
.admin-nav-item:hover .truncate {
}

.dark .admin-nav-item:hover .truncate {
}
</style>
