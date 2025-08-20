<template>
  <div
    class="dashboard-icon-container"
    :class="containerClasses"
    :style="containerStyles"
  >
    <!-- Heroicon -->
    <component
      v-if="iconType === 'heroicon' && heroiconComponent"
      :is="heroiconComponent"
      :class="iconClasses"
      :style="iconStyles"
    />
    
    <!-- FontAwesome -->
    <i
      v-else-if="iconType === 'fontawesome'"
      :class="[fontAwesomeClasses, iconClasses]"
      :style="iconStyles"
    ></i>
    
    <!-- Image -->
    <img
      v-else-if="iconType === 'image'"
      :src="iconData.url || iconData"
      :alt="alt"
      :class="['dashboard-icon-image', iconClasses]"
      :style="iconStyles"
      @error="handleImageError"
    />
    
    <!-- SVG -->
    <div
      v-else-if="iconType === 'svg'"
      :class="['dashboard-icon-svg', iconClasses]"
      :style="iconStyles"
      v-html="iconData.content || iconData"
    ></div>
    
    <!-- Emoji -->
    <span
      v-else-if="iconType === 'emoji'"
      :class="['dashboard-icon-emoji', iconClasses]"
      :style="iconStyles"
    >
      {{ iconData.emoji || iconData }}
    </span>
    
    <!-- Custom/Fallback -->
    <div
      v-else
      :class="['dashboard-icon-custom', iconClasses]"
      :style="iconStyles"
    >
      <slot name="fallback">
        <ChartBarIcon />
      </slot>
    </div>
  </div>
</template>

<script>
import { computed, ref, onMounted } from 'vue'
import { ChartBarIcon } from '@heroicons/vue/24/outline'

export default {
  name: 'DashboardIcon',
  components: {
    ChartBarIcon
  },
  props: {
    icon: {
      type: [String, Object],
      default: null
    },
    size: {
      type: String,
      default: 'md',
      validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl'].includes(value)
    },
    color: {
      type: String,
      default: null
    },
    backgroundColor: {
      type: String,
      default: null
    },
    rounded: {
      type: Boolean,
      default: true
    },
    shadow: {
      type: Boolean,
      default: false
    },
    alt: {
      type: String,
      default: 'Dashboard icon'
    }
  },
  setup(props) {
    const imageError = ref(false)
    const heroiconComponent = ref(null)

    // Computed properties
    const iconData = computed(() => {
      if (typeof props.icon === 'string') {
        return { type: 'heroicon', name: props.icon }
      }
      return props.icon || {}
    })

    const iconType = computed(() => {
      if (imageError.value) return 'fallback'
      
      if (iconData.value.type) {
        return iconData.value.type
      }

      // Auto-detect icon type
      const icon = props.icon
      if (typeof icon === 'string') {
        if (icon.startsWith('heroicon:')) return 'heroicon'
        if (icon.startsWith('fa:') || icon.startsWith('fas:') || icon.startsWith('far:')) return 'fontawesome'
        if (icon.startsWith('http') || icon.startsWith('data:image/')) return 'image'
        if (icon.startsWith('<svg')) return 'svg'
        if (/[\u{1F600}-\u{1F64F}]|[\u{1F300}-\u{1F5FF}]|[\u{1F680}-\u{1F6FF}]|[\u{1F1E0}-\u{1F1FF}]/u.test(icon)) return 'emoji'
        return 'heroicon' // Default for plain strings
      }

      return 'custom'
    })

    const containerClasses = computed(() => [
      'dashboard-icon-base',
      `size-${props.size}`,
      {
        'rounded-full': props.rounded,
        'rounded-lg': !props.rounded,
        'shadow-sm': props.shadow
      }
    ])

    const containerStyles = computed(() => {
      const styles = {}
      if (props.backgroundColor) {
        styles.backgroundColor = props.backgroundColor
      }
      return styles
    })

    const iconClasses = computed(() => {
      const sizeClasses = {
        xs: 'h-3 w-3',
        sm: 'h-4 w-4',
        md: 'h-6 w-6',
        lg: 'h-8 w-8',
        xl: 'h-10 w-10',
        '2xl': 'h-12 w-12'
      }

      return [
        sizeClasses[props.size] || sizeClasses.md,
        'dashboard-icon'
      ]
    })

    const iconStyles = computed(() => {
      const styles = {}
      if (props.color) {
        styles.color = props.color
      }
      return styles
    })

    const fontAwesomeClasses = computed(() => {
      const iconName = iconData.value.name || props.icon
      if (typeof iconName === 'string') {
        return iconName.replace(':', ' ')
      }
      return 'fas fa-chart-bar' // Fallback
    })

    // Methods
    const handleImageError = () => {
      imageError.value = true
    }

    const loadHeroicon = async () => {
      if (iconType.value !== 'heroicon') return

      const iconName = iconData.value.name || props.icon
      if (!iconName) return

      try {
        // Try to dynamically import the heroicon
        const iconModule = await import(`@heroicons/vue/24/outline/${iconName}.js`)
        heroiconComponent.value = iconModule.default
      } catch (error) {
        // Try solid icons
        try {
          const iconModule = await import(`@heroicons/vue/24/solid/${iconName}.js`)
          heroiconComponent.value = iconModule.default
        } catch (solidError) {
          console.warn(`Could not load heroicon: ${iconName}`)
          heroiconComponent.value = ChartBarIcon
        }
      }
    }

    // Lifecycle
    onMounted(() => {
      if (iconType.value === 'heroicon') {
        loadHeroicon()
      }
    })

    return {
      imageError,
      heroiconComponent,
      iconData,
      iconType,
      containerClasses,
      containerStyles,
      iconClasses,
      iconStyles,
      fontAwesomeClasses,
      handleImageError
    }
  }
}
</script>

<style scoped>
.dashboard-icon-container {
  @apply inline-flex items-center justify-center flex-shrink-0;
}

.dashboard-icon-base {
  @apply relative overflow-hidden;
}

/* Size variants */
.dashboard-icon-base.size-xs {
  @apply p-1;
}

.dashboard-icon-base.size-sm {
  @apply p-1.5;
}

.dashboard-icon-base.size-md {
  @apply p-2;
}

.dashboard-icon-base.size-lg {
  @apply p-3;
}

.dashboard-icon-base.size-xl {
  @apply p-4;
}

.dashboard-icon-base.size-2xl {
  @apply p-5;
}

/* Icon styles */
.dashboard-icon {
  @apply text-current;
}

.dashboard-icon-image {
  @apply object-cover rounded;
}

.dashboard-icon-svg {
  @apply flex items-center justify-center;
}

.dashboard-icon-svg :deep(svg) {
  @apply h-full w-full;
}

.dashboard-icon-emoji {
  @apply text-center leading-none;
  font-family: 'Apple Color Emoji', 'Segoe UI Emoji', 'Noto Color Emoji', sans-serif;
}

.dashboard-icon-custom {
  @apply flex items-center justify-center;
}

/* Default background colors */
.dashboard-icon-base:not([style*="background-color"]) {
  @apply bg-gray-100;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .dashboard-icon-base:not([style*="background-color"]) {
    @apply bg-gray-700;
  }
}

/* Hover effects */
.dashboard-icon-container:hover .dashboard-icon-base {
  @apply transform scale-105 transition-transform duration-200;
}

/* Focus styles */
.dashboard-icon-container:focus-within .dashboard-icon-base {
  @apply ring-2 ring-blue-500 ring-offset-2;
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .dashboard-icon-container:hover .dashboard-icon-base {
    @apply transform-none transition-none;
  }
}
</style>
