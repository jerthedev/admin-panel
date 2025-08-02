<template>
  <nav
    v-if="breadcrumbs.length > 0"
    class="bg-white border-b border-gray-200 px-6 py-3"
    :class="{ 'bg-gray-800 border-gray-700': isDarkTheme }"
    aria-label="Breadcrumb"
  >
    <ol class="flex items-center space-x-2">
      <li
        v-for="(breadcrumb, index) in breadcrumbs"
        :key="index"
        class="flex items-center"
      >
        <!-- Separator -->
        <ChevronRightIcon
          v-if="index > 0"
          class="flex-shrink-0 h-4 w-4 text-gray-400 mr-2"
        />

        <!-- Breadcrumb item -->
        <div class="flex items-center">
          <!-- Icon -->
          <component
            v-if="breadcrumb.icon"
            :is="getIcon(breadcrumb.icon)"
            class="flex-shrink-0 h-4 w-4 mr-1.5 text-gray-400"
          />

          <!-- Link or text -->
          <Link
            v-if="breadcrumb.href && index < breadcrumbs.length - 1"
            :href="breadcrumb.href"
            class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors duration-150"
            :class="{ 'text-gray-400 hover:text-gray-200': isDarkTheme }"
          >
            {{ breadcrumb.label }}
          </Link>
          <span
            v-else
            class="text-sm font-medium"
            :class="index === breadcrumbs.length - 1 
              ? (isDarkTheme ? 'text-gray-200' : 'text-gray-900')
              : (isDarkTheme ? 'text-gray-400' : 'text-gray-500')
            "
          >
            {{ breadcrumb.label }}
          </span>
        </div>
      </li>
    </ol>
  </nav>
</template>

<script setup>
/**
 * Breadcrumbs Component
 * 
 * Breadcrumb navigation showing the current page hierarchy
 * with support for icons and clickable navigation.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'
import { ChevronRightIcon } from '@heroicons/vue/24/outline'
import * as HeroIcons from '@heroicons/vue/24/outline'

// Page data
const page = usePage()

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const breadcrumbs = computed(() => {
  const crumbs = []
  const component = page.component
  const props = page.props

  // Always start with Dashboard
  crumbs.push({
    label: 'Dashboard',
    href: route('admin-panel.dashboard'),
    icon: 'HomeIcon'
  })

  // Add breadcrumbs based on current page
  switch (component) {
    case 'Resources/Index':
      if (props.resource) {
        crumbs.push({
          label: props.resource.label,
          href: null,
          icon: 'DocumentTextIcon'
        })
      }
      break

    case 'Resources/Show':
      if (props.resource) {
        crumbs.push({
          label: props.resource.label,
          href: route('admin-panel.resources.index', { resource: props.resource.uriKey }),
          icon: 'DocumentTextIcon'
        })
        crumbs.push({
          label: `${props.resource.singularLabel} #${props.resourceId}`,
          href: null,
          icon: 'EyeIcon'
        })
      }
      break

    case 'Resources/Create':
      if (props.resource) {
        crumbs.push({
          label: props.resource.label,
          href: route('admin-panel.resources.index', { resource: props.resource.uriKey }),
          icon: 'DocumentTextIcon'
        })
        crumbs.push({
          label: `Create ${props.resource.singularLabel}`,
          href: null,
          icon: 'PlusIcon'
        })
      }
      break

    case 'Resources/Edit':
      if (props.resource) {
        crumbs.push({
          label: props.resource.label,
          href: route('admin-panel.resources.index', { resource: props.resource.uriKey }),
          icon: 'DocumentTextIcon'
        })
        crumbs.push({
          label: `${props.resource.singularLabel} #${props.resourceId}`,
          href: route('admin-panel.resources.show', { 
            resource: props.resource.uriKey, 
            id: props.resourceId 
          }),
          icon: 'EyeIcon'
        })
        crumbs.push({
          label: 'Edit',
          href: null,
          icon: 'PencilIcon'
        })
      }
      break

    default:
      // For custom pages, check if breadcrumbs are provided in props
      if (props.breadcrumbs && Array.isArray(props.breadcrumbs)) {
        crumbs.push(...props.breadcrumbs)
      } else if (props.title) {
        crumbs.push({
          label: props.title,
          href: null
        })
      }
  }

  return crumbs
})

// Methods
const getIcon = (iconName) => {
  return HeroIcons[iconName] || HeroIcons.DocumentTextIcon
}

const route = (name, params = {}) => {
  return window.adminPanel?.route(name, params) || '#'
}
</script>

<style scoped>
/* Smooth transitions for hover effects */
a {
  transition: color 0.15s ease-in-out;
}
</style>
