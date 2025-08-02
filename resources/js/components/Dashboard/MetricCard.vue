<template>
  <Card :variant="variant" :hoverable="hoverable" :clickable="clickable" @click="handleClick">
    <div class="flex items-center justify-between">
      <div class="flex-1">
        <div class="flex items-center">
          <div
            v-if="icon"
            :class="iconContainerClasses"
          >
            <component
              :is="icon"
              :class="iconClasses"
              aria-hidden="true"
            />
          </div>
          <div :class="{ 'ml-4': icon }">
            <p :class="labelClasses">
              {{ label }}
            </p>
            <div class="flex items-baseline space-x-2">
              <p :class="valueClasses">
                {{ formattedValue }}
              </p>
              <div v-if="trend" class="flex items-center">
                <component
                  :is="trendIcon"
                  :class="trendIconClasses"
                  aria-hidden="true"
                />
                <span :class="trendTextClasses">
                  {{ Math.abs(trend.percentage) }}%
                </span>
              </div>
            </div>
            <p v-if="subtitle" :class="subtitleClasses">
              {{ subtitle }}
            </p>
          </div>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading" class="ml-4">
        <LoadingSpinner size="sm" />
      </div>
      
      <!-- Additional Content -->
      <div v-if="$slots.actions" class="ml-4">
        <slot name="actions" />
      </div>
    </div>
    
    <!-- Chart or Additional Content -->
    <div v-if="$slots.chart" class="mt-4">
      <slot name="chart" />
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import { ArrowUpIcon, ArrowDownIcon } from '@heroicons/vue/20/solid'
import Card from '../Common/Card.vue'
import LoadingSpinner from '../Common/LoadingSpinner.vue'

const props = defineProps({
  label: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  subtitle: {
    type: String,
    default: null
  },
  icon: {
    type: [String, Object],
    default: null
  },
  color: {
    type: String,
    default: 'blue',
    validator: (value) => ['blue', 'green', 'red', 'yellow', 'purple', 'gray', 'indigo', 'pink'].includes(value)
  },
  trend: {
    type: Object,
    default: null,
    validator: (value) => {
      return value === null || (
        typeof value === 'object' &&
        typeof value.percentage === 'number' &&
        ['up', 'down'].includes(value.direction)
      )
    }
  },
  formatter: {
    type: Function,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  },
  variant: {
    type: String,
    default: 'default'
  },
  hoverable: {
    type: Boolean,
    default: false
  },
  clickable: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['click'])

const colorClasses = {
  blue: {
    iconBg: 'bg-blue-100',
    iconText: 'text-blue-600',
    value: 'text-gray-900'
  },
  green: {
    iconBg: 'bg-green-100',
    iconText: 'text-green-600',
    value: 'text-gray-900'
  },
  red: {
    iconBg: 'bg-red-100',
    iconText: 'text-red-600',
    value: 'text-gray-900'
  },
  yellow: {
    iconBg: 'bg-yellow-100',
    iconText: 'text-yellow-600',
    value: 'text-gray-900'
  },
  purple: {
    iconBg: 'bg-purple-100',
    iconText: 'text-purple-600',
    value: 'text-gray-900'
  },
  gray: {
    iconBg: 'bg-gray-100',
    iconText: 'text-gray-600',
    value: 'text-gray-900'
  },
  indigo: {
    iconBg: 'bg-indigo-100',
    iconText: 'text-indigo-600',
    value: 'text-gray-900'
  },
  pink: {
    iconBg: 'bg-pink-100',
    iconText: 'text-pink-600',
    value: 'text-gray-900'
  }
}

const formattedValue = computed(() => {
  if (props.loading) return '...'
  if (props.formatter && typeof props.formatter === 'function') {
    return props.formatter(props.value)
  }
  
  // Default number formatting
  if (typeof props.value === 'number') {
    return props.value.toLocaleString()
  }
  
  return props.value
})

const iconContainerClasses = computed(() => {
  return `flex items-center justify-center w-12 h-12 rounded-lg ${colorClasses[props.color].iconBg}`
})

const iconClasses = computed(() => {
  return `w-6 h-6 ${colorClasses[props.color].iconText}`
})

const labelClasses = computed(() => {
  return 'text-sm font-medium text-gray-600'
})

const valueClasses = computed(() => {
  return `text-2xl font-bold ${colorClasses[props.color].value}`
})

const subtitleClasses = computed(() => {
  return 'text-sm text-gray-500 mt-1'
})

const trendIcon = computed(() => {
  return props.trend?.direction === 'up' ? ArrowUpIcon : ArrowDownIcon
})

const trendIconClasses = computed(() => {
  const baseClasses = 'w-4 h-4'
  const colorClass = props.trend?.direction === 'up' ? 'text-green-500' : 'text-red-500'
  return `${baseClasses} ${colorClass}`
})

const trendTextClasses = computed(() => {
  const baseClasses = 'text-sm font-medium ml-1'
  const colorClass = props.trend?.direction === 'up' ? 'text-green-600' : 'text-red-600'
  return `${baseClasses} ${colorClass}`
})

const handleClick = (event) => {
  if (props.clickable) {
    emit('click', event)
  }
}
</script>

<style scoped>
/* Additional metric card styles if needed */
</style>
