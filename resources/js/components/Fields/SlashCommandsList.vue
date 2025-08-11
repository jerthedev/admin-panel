<template>
  <div
    v-if="items.length > 0"
    class="slash-commands-popup bg-white border border-gray-200 rounded-lg shadow-lg py-2 max-h-64 overflow-y-auto z-50"
    :style="{ top: `${position.top}px`, left: `${position.left}px` }"
  >
    <div
      v-for="(item, index) in items"
      :key="item.title"
      :class="[
        'px-4 py-2 cursor-pointer flex flex-col',
        index === selectedIndex ? 'bg-blue-50 text-blue-900' : 'hover:bg-gray-50'
      ]"
      @click="selectItem(index)"
    >
      <div class="font-medium text-sm">{{ item.title }}</div>
      <div class="text-xs text-gray-500">{{ item.description }}</div>
    </div>
  </div>
</template>

<script setup>
/**
 * SlashCommandsList Component
 *
 * Displays a popup list of available slash commands for the markdown editor.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'

// Props
const props = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  command: {
    type: Function,
    required: true
  },
  position: {
    type: Object,
    default: () => ({ top: 0, left: 0 })
  }
})

// State
const selectedIndex = ref(0)

// Methods
const selectItem = (index) => {
  const item = props.items[index]
  if (item) {
    props.command(item)
  }
}

const onKeyDown = (event) => {
  if (props.items.length === 0) return

  if (event.key === 'ArrowUp') {
    event.preventDefault()
    selectedIndex.value = selectedIndex.value > 0 ? selectedIndex.value - 1 : props.items.length - 1
  } else if (event.key === 'ArrowDown') {
    event.preventDefault()
    selectedIndex.value = selectedIndex.value < props.items.length - 1 ? selectedIndex.value + 1 : 0
  } else if (event.key === 'Enter') {
    event.preventDefault()
    selectItem(selectedIndex.value)
  } else if (event.key === 'Escape') {
    event.preventDefault()
    // Let parent handle escape
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', onKeyDown)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeyDown)
})

// Watch for items changes to reset selection
watch(() => props.items, () => {
  selectedIndex.value = 0
})

// Expose methods for parent component
defineExpose({
  selectItem,
  selectedIndex
})
</script>

<style scoped>
.slash-commands-popup {
  position: fixed;
  min-width: 200px;
  max-width: 300px;
}
</style>
