<template>
  <div :class="containerClasses">
    <svg
      :class="spinnerClasses"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      ></circle>
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      ></path>
    </svg>
    <span v-if="text" :class="textClasses">{{ text }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
  },
  color: {
    type: String,
    default: 'blue',
    validator: (value) => ['blue', 'gray', 'green', 'red', 'yellow', 'purple', 'pink', 'indigo', 'white'].includes(value)
  },
  text: {
    type: String,
    default: null
  },
  center: {
    type: Boolean,
    default: false
  },
  overlay: {
    type: Boolean,
    default: false
  }
})

const sizeClasses = {
  xs: 'w-3 h-3',
  sm: 'w-4 h-4',
  md: 'w-6 h-6',
  lg: 'w-8 h-8',
  xl: 'w-12 h-12'
}

const colorClasses = {
  blue: 'text-blue-600',
  gray: 'text-gray-600',
  green: 'text-green-600',
  red: 'text-red-600',
  yellow: 'text-yellow-600',
  purple: 'text-purple-600',
  pink: 'text-pink-600',
  indigo: 'text-indigo-600',
  white: 'text-white'
}

const textSizeClasses = {
  xs: 'text-xs',
  sm: 'text-sm',
  md: 'text-sm',
  lg: 'text-base',
  xl: 'text-lg'
}

const containerClasses = computed(() => {
  const classes = ['inline-flex items-center']

  if (props.center) {
    classes.push('justify-center')
  }

  if (props.overlay) {
    classes.push('fixed inset-0 bg-black/50 z-50 flex-col')
  }

  return classes.join(' ')
})

const spinnerClasses = computed(() => {
  const classes = [
    'animate-spin',
    sizeClasses[props.size],
    colorClasses[props.color]
  ]

  return classes.join(' ')
})

const textClasses = computed(() => {
  const classes = [
    'ml-2',
    textSizeClasses[props.size],
    colorClasses[props.color]
  ]

  if (props.overlay) {
    classes.push('mt-4 ml-0 text-white')
  }

  return classes.join(' ')
})
</script>

<style scoped>
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
