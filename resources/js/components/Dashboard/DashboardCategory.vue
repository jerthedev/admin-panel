<template>
  <div
    class="dashboard-category"
    :class="categoryClasses"
    :title="tooltip"
  >
    <!-- Icon -->
    <component
      v-if="showIcon && categoryIcon"
      :is="categoryIcon"
      class="category-icon"
    />
    
    <!-- Category Name -->
    <span class="category-name">
      {{ category }}
    </span>
  </div>
</template>

<script>
import { computed } from 'vue'
import {
  ChartBarIcon,
  DocumentTextIcon,
  HomeIcon,
  ViewGridIcon,
  BriefcaseIcon,
  CurrencyDollarIcon,
  UsersIcon,
  DocumentDuplicateIcon,
  CogIcon,
  EyeIcon,
  ShieldCheckIcon,
  MegaphoneIcon,
  TrendingUpIcon,
  SupportIcon,
  UserCircleIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'DashboardCategory',
  components: {
    ChartBarIcon,
    DocumentTextIcon,
    HomeIcon,
    ViewGridIcon,
    BriefcaseIcon,
    CurrencyDollarIcon,
    UsersIcon,
    DocumentDuplicateIcon,
    CogIcon,
    EyeIcon,
    ShieldCheckIcon,
    MegaphoneIcon,
    TrendingUpIcon,
    SupportIcon,
    UserCircleIcon
  },
  props: {
    category: {
      type: String,
      required: true
    },
    showIcon: {
      type: Boolean,
      default: true
    },
    size: {
      type: String,
      default: 'sm',
      validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
    },
    variant: {
      type: String,
      default: 'soft',
      validator: (value) => ['solid', 'soft', 'outline', 'minimal'].includes(value)
    },
    color: {
      type: String,
      default: null
    },
    clickable: {
      type: Boolean,
      default: false
    }
  },
  emits: ['click'],
  setup(props, { emit }) {
    // Category icon mapping
    const categoryIcons = {
      'Analytics': ChartBarIcon,
      'Reports': DocumentTextIcon,
      'Overview': HomeIcon,
      'General': ViewGridIcon,
      'Business': BriefcaseIcon,
      'Financial': CurrencyDollarIcon,
      'Users': UsersIcon,
      'Content': DocumentDuplicateIcon,
      'System': CogIcon,
      'Monitoring': EyeIcon,
      'Security': ShieldCheckIcon,
      'Marketing': MegaphoneIcon,
      'Sales': TrendingUpIcon,
      'Support': SupportIcon,
      'Admin': UserCircleIcon
    }

    // Category color mapping
    const categoryColors = {
      'Analytics': 'blue',
      'Reports': 'purple',
      'Overview': 'green',
      'General': 'gray',
      'Business': 'indigo',
      'Financial': 'yellow',
      'Users': 'pink',
      'Content': 'cyan',
      'System': 'gray',
      'Monitoring': 'orange',
      'Security': 'red',
      'Marketing': 'purple',
      'Sales': 'green',
      'Support': 'blue',
      'Admin': 'red'
    }

    // Computed properties
    const categoryIcon = computed(() => {
      return categoryIcons[props.category] || ViewGridIcon
    })

    const categoryColor = computed(() => {
      return props.color || categoryColors[props.category] || 'gray'
    })

    const categoryClasses = computed(() => [
      'dashboard-category-base',
      `category-${props.variant}`,
      `category-${props.size}`,
      `category-${categoryColor.value}`,
      {
        'category-clickable': props.clickable,
        'category-with-icon': props.showIcon
      }
    ])

    const tooltip = computed(() => {
      return `Category: ${props.category}`
    })

    // Methods
    const handleClick = () => {
      if (props.clickable) {
        emit('click', props.category)
      }
    }

    return {
      categoryIcon,
      categoryColor,
      categoryClasses,
      tooltip,
      handleClick
    }
  }
}
</script>

<style scoped>
.dashboard-category {
  @apply inline-flex items-center font-medium transition-all duration-200;
}

.dashboard-category-base {
  @apply rounded;
}

/* Size variants */
.category-xs {
  @apply px-1.5 py-0.5 text-xs;
}

.category-sm {
  @apply px-2 py-1 text-xs;
}

.category-md {
  @apply px-2.5 py-1.5 text-sm;
}

.category-lg {
  @apply px-3 py-2 text-base;
}

/* Clickable */
.category-clickable {
  @apply cursor-pointer hover:scale-105;
}

.category-clickable:focus {
  @apply outline-none ring-2 ring-offset-2;
}

/* Icon */
.category-with-icon {
  @apply pl-1.5;
}

.category-icon {
  @apply mr-1.5;
}

.category-xs .category-icon {
  @apply h-3 w-3 mr-1;
}

.category-sm .category-icon {
  @apply h-3 w-3 mr-1;
}

.category-md .category-icon {
  @apply h-4 w-4 mr-1.5;
}

.category-lg .category-icon {
  @apply h-5 w-5 mr-2;
}

/* Color variants - Solid */
.category-solid.category-blue {
  @apply bg-blue-500 text-white;
}

.category-solid.category-purple {
  @apply bg-purple-500 text-white;
}

.category-solid.category-green {
  @apply bg-green-500 text-white;
}

.category-solid.category-gray {
  @apply bg-gray-500 text-white;
}

.category-solid.category-indigo {
  @apply bg-indigo-500 text-white;
}

.category-solid.category-yellow {
  @apply bg-yellow-500 text-white;
}

.category-solid.category-pink {
  @apply bg-pink-500 text-white;
}

.category-solid.category-cyan {
  @apply bg-cyan-500 text-white;
}

.category-solid.category-orange {
  @apply bg-orange-500 text-white;
}

.category-solid.category-red {
  @apply bg-red-500 text-white;
}

/* Color variants - Soft */
.category-soft.category-blue {
  @apply bg-blue-100 text-blue-800;
}

.category-soft.category-purple {
  @apply bg-purple-100 text-purple-800;
}

.category-soft.category-green {
  @apply bg-green-100 text-green-800;
}

.category-soft.category-gray {
  @apply bg-gray-100 text-gray-800;
}

.category-soft.category-indigo {
  @apply bg-indigo-100 text-indigo-800;
}

.category-soft.category-yellow {
  @apply bg-yellow-100 text-yellow-800;
}

.category-soft.category-pink {
  @apply bg-pink-100 text-pink-800;
}

.category-soft.category-cyan {
  @apply bg-cyan-100 text-cyan-800;
}

.category-soft.category-orange {
  @apply bg-orange-100 text-orange-800;
}

.category-soft.category-red {
  @apply bg-red-100 text-red-800;
}

/* Color variants - Outline */
.category-outline.category-blue {
  @apply border border-blue-500 text-blue-500 bg-transparent;
}

.category-outline.category-purple {
  @apply border border-purple-500 text-purple-500 bg-transparent;
}

.category-outline.category-green {
  @apply border border-green-500 text-green-500 bg-transparent;
}

.category-outline.category-gray {
  @apply border border-gray-500 text-gray-500 bg-transparent;
}

.category-outline.category-indigo {
  @apply border border-indigo-500 text-indigo-500 bg-transparent;
}

.category-outline.category-yellow {
  @apply border border-yellow-500 text-yellow-500 bg-transparent;
}

.category-outline.category-pink {
  @apply border border-pink-500 text-pink-500 bg-transparent;
}

.category-outline.category-cyan {
  @apply border border-cyan-500 text-cyan-500 bg-transparent;
}

.category-outline.category-orange {
  @apply border border-orange-500 text-orange-500 bg-transparent;
}

.category-outline.category-red {
  @apply border border-red-500 text-red-500 bg-transparent;
}

/* Color variants - Minimal */
.category-minimal {
  @apply bg-transparent border-none;
}

.category-minimal.category-blue {
  @apply text-blue-600;
}

.category-minimal.category-purple {
  @apply text-purple-600;
}

.category-minimal.category-green {
  @apply text-green-600;
}

.category-minimal.category-gray {
  @apply text-gray-600;
}

.category-minimal.category-indigo {
  @apply text-indigo-600;
}

.category-minimal.category-yellow {
  @apply text-yellow-600;
}

.category-minimal.category-pink {
  @apply text-pink-600;
}

.category-minimal.category-cyan {
  @apply text-cyan-600;
}

.category-minimal.category-orange {
  @apply text-orange-600;
}

.category-minimal.category-red {
  @apply text-red-600;
}

/* Focus ring colors */
.category-clickable.category-blue:focus {
  @apply ring-blue-500;
}

.category-clickable.category-purple:focus {
  @apply ring-purple-500;
}

.category-clickable.category-green:focus {
  @apply ring-green-500;
}

.category-clickable.category-gray:focus {
  @apply ring-gray-500;
}

.category-clickable.category-indigo:focus {
  @apply ring-indigo-500;
}

.category-clickable.category-yellow:focus {
  @apply ring-yellow-500;
}

.category-clickable.category-pink:focus {
  @apply ring-pink-500;
}

.category-clickable.category-cyan:focus {
  @apply ring-cyan-500;
}

.category-clickable.category-orange:focus {
  @apply ring-orange-500;
}

.category-clickable.category-red:focus {
  @apply ring-red-500;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .category-soft.category-blue {
    @apply bg-blue-900 text-blue-200;
  }

  .category-soft.category-purple {
    @apply bg-purple-900 text-purple-200;
  }

  .category-soft.category-green {
    @apply bg-green-900 text-green-200;
  }

  .category-soft.category-gray {
    @apply bg-gray-800 text-gray-200;
  }

  .category-soft.category-indigo {
    @apply bg-indigo-900 text-indigo-200;
  }

  .category-soft.category-yellow {
    @apply bg-yellow-900 text-yellow-200;
  }

  .category-soft.category-pink {
    @apply bg-pink-900 text-pink-200;
  }

  .category-soft.category-cyan {
    @apply bg-cyan-900 text-cyan-200;
  }

  .category-soft.category-orange {
    @apply bg-orange-900 text-orange-200;
  }

  .category-soft.category-red {
    @apply bg-red-900 text-red-200;
  }

  .category-minimal.category-blue {
    @apply text-blue-400;
  }

  .category-minimal.category-purple {
    @apply text-purple-400;
  }

  .category-minimal.category-green {
    @apply text-green-400;
  }

  .category-minimal.category-gray {
    @apply text-gray-400;
  }

  .category-minimal.category-indigo {
    @apply text-indigo-400;
  }

  .category-minimal.category-yellow {
    @apply text-yellow-400;
  }

  .category-minimal.category-pink {
    @apply text-pink-400;
  }

  .category-minimal.category-cyan {
    @apply text-cyan-400;
  }

  .category-minimal.category-orange {
    @apply text-orange-400;
  }

  .category-minimal.category-red {
    @apply text-red-400;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .category-clickable:hover {
    @apply transform-none;
  }
}
</style>
