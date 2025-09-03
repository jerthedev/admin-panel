<template>
  <div class="dashboard-tags" :class="containerClasses">
    <!-- Visible Tags -->
    <span
      v-for="tag in visibleTags"
      :key="tag"
      class="tag"
      :class="tagClasses"
      @click="handleTagClick(tag)"
    >
      {{ tag }}
    </span>

    <!-- More Tags Indicator -->
    <span
      v-if="hiddenTagsCount > 0"
      class="tag more-tags"
      :class="tagClasses"
      :title="hiddenTagsTooltip"
      @click="toggleShowAll"
    >
      +{{ hiddenTagsCount }}
    </span>

    <!-- Show All Toggle -->
    <button
      v-if="showToggle && tags.length > maxVisible"
      @click="toggleShowAll"
      class="toggle-button"
      :aria-label="showAll ? 'Show fewer tags' : 'Show all tags'"
      type="button"
    >
      {{ showAll ? 'Less' : 'More' }}
    </button>
  </div>
</template>

<script>
import { computed, ref } from 'vue'

export default {
  name: 'DashboardTags',
  props: {
    tags: {
      type: Array,
      default: () => []
    },
    maxVisible: {
      type: Number,
      default: 3
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
      default: 'gray'
    },
    clickable: {
      type: Boolean,
      default: false
    },
    showToggle: {
      type: Boolean,
      default: false
    }
  },
  emits: ['tag-click'],
  setup(props, { emit }) {
    const showAll = ref(false)

    // Computed properties
    const visibleTags = computed(() => {
      if (showAll.value || props.tags.length <= props.maxVisible) {
        return props.tags
      }
      return props.tags.slice(0, props.maxVisible)
    })

    const hiddenTags = computed(() => {
      if (showAll.value || props.tags.length <= props.maxVisible) {
        return []
      }
      return props.tags.slice(props.maxVisible)
    })

    const hiddenTagsCount = computed(() => hiddenTags.value.length)

    const hiddenTagsTooltip = computed(() => {
      return hiddenTags.value.join(', ')
    })

    const containerClasses = computed(() => [
      'dashboard-tags-base',
      `tags-${props.size}`
    ])

    const tagClasses = computed(() => [
      'tag-base',
      `tag-${props.variant}`,
      `tag-${props.size}`,
      `tag-${props.color}`,
      {
        'tag-clickable': props.clickable
      }
    ])

    // Methods
    const handleTagClick = (tag) => {
      if (props.clickable) {
        emit('tag-click', tag)
      }
    }

    const toggleShowAll = () => {
      showAll.value = !showAll.value
    }

    return {
      showAll,
      visibleTags,
      hiddenTags,
      hiddenTagsCount,
      hiddenTagsTooltip,
      containerClasses,
      tagClasses,
      handleTagClick,
      toggleShowAll
    }
  }
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.dashboard-tags {
  @apply flex flex-wrap items-center gap-1;
}

.dashboard-tags-base {
  @apply transition-all duration-200;
}

/* Size variants */
.tags-xs {
  @apply gap-0.5;
}

.tags-sm {
  @apply gap-1;
}

.tags-md {
  @apply gap-1.5;
}

.tags-lg {
  @apply gap-2;
}

/* Tag base */
.tag {
  @apply inline-flex items-center font-medium transition-all duration-200;
}

.tag-base {
  @apply rounded;
}

/* Tag size variants */
.tag-xs {
  @apply px-1.5 py-0.5 text-xs;
}

.tag-sm {
  @apply px-2 py-0.5 text-xs;
}

.tag-md {
  @apply px-2.5 py-1 text-sm;
}

.tag-lg {
  @apply px-3 py-1.5 text-base;
}

/* Clickable tags */
.tag-clickable {
  @apply cursor-pointer hover:scale-105;
}

.tag-clickable:focus {
  @apply outline-none ring-2 ring-offset-1;
}

/* More tags indicator */
.more-tags {
  @apply cursor-help;
}

/* Toggle button */
.toggle-button {
  @apply text-xs text-gray-500 hover:text-gray-700 underline ml-1;
}

.toggle-button:focus {
  @apply outline-none ring-2 ring-gray-500 ring-offset-1 rounded;
}

/* Color variants - Solid */
.tag-solid.tag-gray {
  @apply bg-gray-500 text-white;
}

.tag-solid.tag-blue {
  @apply bg-blue-500 text-white;
}

.tag-solid.tag-green {
  @apply bg-green-500 text-white;
}

.tag-solid.tag-yellow {
  @apply bg-yellow-500 text-white;
}

.tag-solid.tag-red {
  @apply bg-red-500 text-white;
}

.tag-solid.tag-purple {
  @apply bg-purple-500 text-white;
}

.tag-solid.tag-pink {
  @apply bg-pink-500 text-white;
}

.tag-solid.tag-indigo {
  @apply bg-indigo-500 text-white;
}

/* Color variants - Soft */
.tag-soft.tag-gray {
  @apply bg-gray-100 text-gray-800;
}

.tag-soft.tag-blue {
  @apply bg-blue-100 text-blue-800;
}

.tag-soft.tag-green {
  @apply bg-green-100 text-green-800;
}

.tag-soft.tag-yellow {
  @apply bg-yellow-100 text-yellow-800;
}

.tag-soft.tag-red {
  @apply bg-red-100 text-red-800;
}

.tag-soft.tag-purple {
  @apply bg-purple-100 text-purple-800;
}

.tag-soft.tag-pink {
  @apply bg-pink-100 text-pink-800;
}

.tag-soft.tag-indigo {
  @apply bg-indigo-100 text-indigo-800;
}

/* Color variants - Outline */
.tag-outline.tag-gray {
  @apply border border-gray-500 text-gray-500 bg-transparent;
}

.tag-outline.tag-blue {
  @apply border border-blue-500 text-blue-500 bg-transparent;
}

.tag-outline.tag-green {
  @apply border border-green-500 text-green-500 bg-transparent;
}

.tag-outline.tag-yellow {
  @apply border border-yellow-500 text-yellow-500 bg-transparent;
}

.tag-outline.tag-red {
  @apply border border-red-500 text-red-500 bg-transparent;
}

.tag-outline.tag-purple {
  @apply border border-purple-500 text-purple-500 bg-transparent;
}

.tag-outline.tag-pink {
  @apply border border-pink-500 text-pink-500 bg-transparent;
}

.tag-outline.tag-indigo {
  @apply border border-indigo-500 text-indigo-500 bg-transparent;
}

/* Color variants - Minimal */
.tag-minimal {
  @apply bg-transparent border-none;
}

.tag-minimal.tag-gray {
  @apply text-gray-600;
}

.tag-minimal.tag-blue {
  @apply text-blue-600;
}

.tag-minimal.tag-green {
  @apply text-green-600;
}

.tag-minimal.tag-yellow {
  @apply text-yellow-600;
}

.tag-minimal.tag-red {
  @apply text-red-600;
}

.tag-minimal.tag-purple {
  @apply text-purple-600;
}

.tag-minimal.tag-pink {
  @apply text-pink-600;
}

.tag-minimal.tag-indigo {
  @apply text-indigo-600;
}

/* Focus ring colors */
.tag-clickable.tag-gray:focus {
  @apply ring-gray-500;
}

.tag-clickable.tag-blue:focus {
  @apply ring-blue-500;
}

.tag-clickable.tag-green:focus {
  @apply ring-green-500;
}

.tag-clickable.tag-yellow:focus {
  @apply ring-yellow-500;
}

.tag-clickable.tag-red:focus {
  @apply ring-red-500;
}

.tag-clickable.tag-purple:focus {
  @apply ring-purple-500;
}

.tag-clickable.tag-pink:focus {
  @apply ring-pink-500;
}

.tag-clickable.tag-indigo:focus {
  @apply ring-indigo-500;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .tag-soft.tag-gray {
    @apply bg-gray-800 text-gray-200;
  }

  .tag-soft.tag-blue {
    @apply bg-blue-900 text-blue-200;
  }

  .tag-soft.tag-green {
    @apply bg-green-900 text-green-200;
  }

  .tag-soft.tag-yellow {
    @apply bg-yellow-900 text-yellow-200;
  }

  .tag-soft.tag-red {
    @apply bg-red-900 text-red-200;
  }

  .tag-soft.tag-purple {
    @apply bg-purple-900 text-purple-200;
  }

  .tag-soft.tag-pink {
    @apply bg-pink-900 text-pink-200;
  }

  .tag-soft.tag-indigo {
    @apply bg-indigo-900 text-indigo-200;
  }

  .tag-minimal.tag-gray {
    @apply text-gray-400;
  }

  .tag-minimal.tag-blue {
    @apply text-blue-400;
  }

  .tag-minimal.tag-green {
    @apply text-green-400;
  }

  .tag-minimal.tag-yellow {
    @apply text-yellow-400;
  }

  .tag-minimal.tag-red {
    @apply text-red-400;
  }

  .tag-minimal.tag-purple {
    @apply text-purple-400;
  }

  .tag-minimal.tag-pink {
    @apply text-pink-400;
  }

  .tag-minimal.tag-indigo {
    @apply text-indigo-400;
  }

  .toggle-button {
    @apply text-gray-400 hover:text-gray-200;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .tag-clickable:hover {
    @apply transform-none;
  }
}
</style>
