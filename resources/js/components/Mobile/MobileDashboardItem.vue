<template>
  <component
    :is="item.url ? 'a' : 'button'"
    :href="item.url"
    class="mobile-dashboard-item"
    :class="[
      itemClasses,
      { 'active': isActive }
    ]"
    @click="handleClick"
  >
    <div class="item-content">
      <component
        v-if="item.icon"
        :is="item.icon"
        class="item-icon"
      />
      <span class="item-label">{{ item.label || item.name }}</span>
    </div>
    <ChevronRightIcon v-if="item.url" class="item-arrow" />
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { ChevronRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  isActive: {
    type: Boolean,
    default: false
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'compact', 'large'].includes(value)
  }
})

const emit = defineEmits(['click'])

const itemClasses = computed(() => {
  const base = 'flex items-center justify-between w-full text-left transition-colors'
  
  const variants = {
    default: 'px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800',
    compact: 'px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800',
    large: 'px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-800'
  }
  
  return `${base} ${variants[props.variant]}`
})

const handleClick = (event) => {
  emit('click', props.item)
  
  if (props.item.url && !event.metaKey && !event.ctrlKey) {
    event.preventDefault()
    router.visit(props.item.url)
  }
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.mobile-dashboard-item {
  @apply block w-full;
}

.mobile-dashboard-item.active {
  @apply bg-blue-50 dark:bg-blue-900/20;
  @apply border-l-4 border-blue-600;
}

.item-content {
  @apply flex items-center gap-3;
}

.item-icon {
  @apply w-5 h-5 text-gray-500 dark:text-gray-400;
}

.item-label {
  @apply text-gray-700 dark:text-gray-300;
}

.mobile-dashboard-item.active .item-label {
  @apply text-blue-600 dark:text-blue-400 font-medium;
}

.item-arrow {
  @apply w-4 h-4 text-gray-400;
}
</style>