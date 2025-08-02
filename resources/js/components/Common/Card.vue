<template>
  <div :class="cardClasses">
    <!-- Header -->
    <div v-if="$slots.header || title || subtitle" :class="headerClasses">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <component
            v-if="icon"
            :is="icon"
            :class="iconClasses"
          />
          <div>
            <h3 v-if="title" :class="titleClasses">
              {{ title }}
            </h3>
            <p v-if="subtitle" :class="subtitleClasses">
              {{ subtitle }}
            </p>
          </div>
        </div>
        <div v-if="$slots.actions" class="flex items-center space-x-2">
          <slot name="actions" />
        </div>
      </div>
      <slot name="header" />
    </div>

    <!-- Body -->
    <div :class="bodyClasses">
      <slot />
    </div>

    <!-- Footer -->
    <div v-if="$slots.footer" :class="footerClasses">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    default: null
  },
  subtitle: {
    type: String,
    default: null
  },
  icon: {
    type: [String, Object],
    default: null
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'bordered', 'elevated', 'flat'].includes(value)
  },
  padding: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg', 'xl'].includes(value)
  },
  rounded: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg', 'xl', 'full'].includes(value)
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

const variantClasses = {
  default: 'bg-white shadow-sm border border-gray-200',
  bordered: 'bg-white border-2 border-gray-200',
  elevated: 'bg-white shadow-lg border border-gray-100',
  flat: 'bg-gray-50'
}

const paddingClasses = {
  none: '',
  sm: 'p-3',
  md: 'p-4',
  lg: 'p-6',
  xl: 'p-8'
}

const roundedClasses = {
  none: '',
  sm: 'rounded-sm',
  md: 'rounded-md',
  lg: 'rounded-lg',
  xl: 'rounded-xl',
  full: 'rounded-full'
}

const cardClasses = computed(() => {
  const classes = [
    'overflow-hidden',
    variantClasses[props.variant],
    roundedClasses[props.rounded]
  ]

  if (props.hoverable) {
    classes.push('transition-shadow duration-200 hover:shadow-md')
  }

  if (props.clickable) {
    classes.push('cursor-pointer transition-all duration-200 hover:shadow-md active:scale-[0.98]')
  }

  return classes.join(' ')
})

const headerClasses = computed(() => {
  const classes = ['border-b border-gray-200 bg-gray-50']
  
  if (props.padding !== 'none') {
    classes.push(paddingClasses[props.padding])
  }

  return classes.join(' ')
})

const bodyClasses = computed(() => {
  const classes = []
  
  if (props.padding !== 'none') {
    classes.push(paddingClasses[props.padding])
  }

  return classes.join(' ')
})

const footerClasses = computed(() => {
  const classes = ['border-t border-gray-200 bg-gray-50']
  
  if (props.padding !== 'none') {
    classes.push(paddingClasses[props.padding])
  }

  return classes.join(' ')
})

const titleClasses = computed(() => {
  return 'text-lg font-semibold text-gray-900'
})

const subtitleClasses = computed(() => {
  return 'text-sm text-gray-600 mt-1'
})

const iconClasses = computed(() => {
  return 'h-5 w-5 text-gray-600 mr-3'
})

const handleClick = (event) => {
  if (props.clickable) {
    emit('click', event)
  }
}
</script>

<style scoped>
/* Additional card styles if needed */
</style>
