<template>
  <div class="mobile-dashboard-category">
    <button
      v-if="collapsible"
      @click="toggleExpanded"
      class="category-header collapsible"
      :aria-expanded="isExpanded"
    >
      <span class="category-title">{{ category.label || category.name }}</span>
      <ChevronDownIcon
        class="category-chevron"
        :class="{ 'rotate-180': isExpanded }"
      />
    </button>
    <div v-else class="category-header">
      <span class="category-title">{{ category.label || category.name }}</span>
    </div>

    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="max-h-0 opacity-0"
      enter-to-class="max-h-[1000px] opacity-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="max-h-[1000px] opacity-100"
      leave-to-class="max-h-0 opacity-0"
    >
      <div v-if="!collapsible || isExpanded" class="category-items">
        <MobileDashboardItem
          v-for="item in category.items"
          :key="item.id || item.name"
          :item="item"
          :is-active="isItemActive(item)"
          @click="handleItemClick"
        />
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import MobileDashboardItem from './MobileDashboardItem.vue'

const props = defineProps({
  category: {
    type: Object,
    required: true
  },
  collapsible: {
    type: Boolean,
    default: true
  },
  defaultExpanded: {
    type: Boolean,
    default: false
  },
  activeItem: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['item-click'])

const isExpanded = ref(props.defaultExpanded)

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
}

const isItemActive = (item) => {
  if (!props.activeItem) return false
  return item.id === props.activeItem || item.name === props.activeItem
}

const handleItemClick = (item) => {
  emit('item-click', item)
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.mobile-dashboard-category {
  @apply border-b border-gray-200 dark:border-gray-700;
}

.category-header {
  @apply px-4 py-3 text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider;
}

.category-header.collapsible {
  @apply flex items-center justify-between w-full cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800;
}

.category-title {
  @apply block;
}

.category-chevron {
  @apply w-4 h-4 text-gray-400 transition-transform duration-200;
}

.category-items {
  @apply overflow-hidden;
}
</style>