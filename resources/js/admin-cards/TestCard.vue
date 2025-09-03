<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">{{ card.title || 'TestCard' }}</h3>
      <div class="flex items-center space-x-2">
        <svg v-if="card.icon" class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <!-- Icon will be rendered based on card.icon -->
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <span v-if="card.group" class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ card.group }}</span>
      </div>
    </div>
    
    <div class="space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">{{ data.value || 0 }}</div>
          <div class="text-sm text-gray-500">{{ data.label || 'Value' }}</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">{{ data.change || '+0%' }}</div>
          <div class="text-sm text-gray-500">Change</div>
        </div>
      </div>
      
      <div class="text-xs text-gray-400 text-center">
        Last updated: {{ data.timestamp || 'Never' }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props validation
const props = defineProps({
  card: {
    type: Object,
    required: true,
    validator: (card) => {
      return card && typeof card === 'object'
    }
  }
})

// Emits for card interactions
const emit = defineEmits(['refresh', 'configure', 'remove'])

// Reactive data
const isLoading = ref(false)
const error = ref(null)

// Computed properties
const data = computed(() => {
  return props.card.data || {
    value: 42,
    label: 'Test Value',
    change: '+12%',
    timestamp: new Date().toLocaleString()
  }
})

// Methods
const refresh = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    emit('refresh', props.card)
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

const configure = () => {
  emit('configure', props.card)
}

const remove = () => {
  emit('remove', props.card)
}

// Expose methods for parent component access
defineExpose({
  refresh,
  configure,
  remove
})
</script>

<style scoped>
@import '../../css/admin.css' reference;

/* Component-specific styles */
.card-loading {
  opacity: 0.6;
  pointer-events: none;
}

.card-error {
  border-color: #ef4444;
  background-color: #fef2f2;
}
</style>
