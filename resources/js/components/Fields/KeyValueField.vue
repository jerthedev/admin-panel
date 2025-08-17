<template>
  <BaseField
    :field="field"
    :model-value="modelValue"
    :errors="errors"
    :disabled="disabled"
    :readonly="readonly"
    :size="size"
    v-bind="$attrs"
  >
    <!-- Readonly display -->
    <div v-if="readonly" class="space-y-2">
      <div v-if="hasDisplayValues" class="space-y-2">
        <div
          v-for="(pair, index) in displayPairs"
          :key="index"
          class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
          :class="{ 'bg-gray-800': isDarkTheme }"
        >
          <div class="flex-1 mr-4">
            <div class="text-sm font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
              {{ pair.key }}
            </div>
          </div>
          <div class="flex-1">
            <div class="text-sm text-gray-600" :class="{ 'text-gray-300': isDarkTheme }">
              {{ pair.value }}
            </div>
          </div>
        </div>
      </div>
      <div v-else class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
        No data
      </div>
    </div>

    <!-- Editable key-value pairs -->
    <div v-else class="space-y-3">
      <!-- Header row -->
      <div class="grid grid-cols-12 gap-3 text-sm font-medium text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
        <div class="col-span-5">{{ keyLabel }}</div>
        <div class="col-span-6">{{ valueLabel }}</div>
        <div class="col-span-1"></div>
      </div>

      <!-- Key-value pair rows -->
      <div
        v-for="(pair, index) in currentPairs"
        :key="index"
        class="grid grid-cols-12 gap-3 items-center"
      >
        <!-- Key input -->
        <div class="col-span-5">
          <input
            :id="`${fieldId}-key-${index}`"
            ref="keyInputRefs"
            type="text"
            :value="pair.key"
            :disabled="disabled"
            :placeholder="`Enter ${keyLabel.toLowerCase()}...`"
            class="admin-input w-full"
            :class="{ 'admin-input-dark': isDarkTheme }"
            @input="handleKeyInput(index, $event)"
            @focus="handleFocus"
            @blur="handleBlur"
            @keydown="handleKeydown(index, $event)"
          />
        </div>

        <!-- Value input -->
        <div class="col-span-6">
          <input
            :id="`${fieldId}-value-${index}`"
            ref="valueInputRefs"
            type="text"
            :value="pair.value"
            :disabled="disabled"
            :placeholder="`Enter ${valueLabel.toLowerCase()}...`"
            class="admin-input w-full"
            :class="{ 'admin-input-dark': isDarkTheme }"
            @input="handleValueInput(index, $event)"
            @focus="handleFocus"
            @blur="handleBlur"
            @keydown="handleKeydown(index, $event)"
          />
        </div>

        <!-- Remove button -->
        <div class="col-span-1">
          <button
            type="button"
            :disabled="disabled"
            class="p-1 text-gray-400 hover:text-red-500 focus:outline-none focus:text-red-500 transition-colors"
            :class="{ 'opacity-50 cursor-not-allowed': disabled }"
            @click="removePair(index)"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>
      </div>

      <!-- Add row button -->
      <div class="flex justify-start">
        <button
          type="button"
          :disabled="disabled"
          class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
          :class="{ 
            'border-gray-600 bg-gray-700 text-gray-300 hover:bg-gray-600': isDarkTheme,
            'opacity-50 cursor-not-allowed': disabled
          }"
          @click="addPair"
        >
          <PlusIcon class="h-4 w-4 mr-2" />
          {{ actionText }}
        </button>
      </div>

      <!-- Required indicator -->
      <div v-if="isRequired" class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
        <span class="text-red-500">*</span> Required
      </div>
    </div>
  </BaseField>
</template>

<script>
import BaseField from './BaseField.vue'
import { XMarkIcon, PlusIcon } from '@heroicons/vue/24/outline'
import { useAdminStore } from '@/stores/admin'
import { computed, ref, watch } from 'vue'

export default {
  name: 'KeyValueField',
  
  components: {
    BaseField,
    XMarkIcon,
    PlusIcon,
  },

  props: {
    field: {
      type: Object,
      required: true,
    },
    modelValue: {
      type: Array,
      default: () => [],
    },
    errors: {
      type: Array,
      default: () => [],
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    readonly: {
      type: Boolean,
      default: false,
    },
    size: {
      type: String,
      default: 'default',
    },
  },

  emits: ['update:modelValue', 'change', 'focus', 'blur'],

  setup(props, { emit }) {
    const adminStore = useAdminStore()
    const keyInputRefs = ref([])
    const valueInputRefs = ref([])

    // Generate unique field ID
    const fieldId = computed(() => {
      return `keyvalue-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
    })

    // Check if field is required
    const isRequired = computed(() => {
      return props.field.rules && props.field.rules.includes('required')
    })

    // Dark theme detection
    const isDarkTheme = computed(() => {
      return adminStore.isDarkTheme
    })

    // Get labels from field configuration
    const keyLabel = computed(() => {
      return props.field.keyLabel || 'Key'
    })

    const valueLabel = computed(() => {
      return props.field.valueLabel || 'Value'
    })

    const actionText = computed(() => {
      return props.field.actionText || 'Add row'
    })

    // Get current pairs, ensuring we always have at least one empty pair for editing
    const currentPairs = computed(() => {
      if (props.readonly) {
        return Array.isArray(props.modelValue) ? props.modelValue : []
      }
      
      const pairs = Array.isArray(props.modelValue) ? [...props.modelValue] : []
      
      // Always ensure there's at least one empty pair for adding new entries
      if (pairs.length === 0 || (pairs[pairs.length - 1].key !== '' || pairs[pairs.length - 1].value !== '')) {
        pairs.push({ key: '', value: '' })
      }
      
      return pairs
    })

    // Get display pairs (only non-empty pairs for readonly display)
    const displayPairs = computed(() => {
      if (!Array.isArray(props.modelValue)) {
        return []
      }
      return props.modelValue.filter(pair => pair.key && pair.key.trim() !== '')
    })

    // Check if there are values to display
    const hasDisplayValues = computed(() => {
      return displayPairs.value.length > 0
    })

    // Handle key input change
    const handleKeyInput = (index, event) => {
      if (props.disabled || props.readonly) return
      
      const newPairs = [...currentPairs.value]
      newPairs[index] = { ...newPairs[index], key: event.target.value }
      
      updateModelValue(newPairs)
    }

    // Handle value input change
    const handleValueInput = (index, event) => {
      if (props.disabled || props.readonly) return
      
      const newPairs = [...currentPairs.value]
      newPairs[index] = { ...newPairs[index], value: event.target.value }
      
      updateModelValue(newPairs)
    }

    // Add a new pair
    const addPair = () => {
      if (props.disabled || props.readonly) return
      
      const newPairs = [...currentPairs.value, { key: '', value: '' }]
      updateModelValue(newPairs)
    }

    // Remove a pair
    const removePair = (index) => {
      if (props.disabled || props.readonly) return
      
      const newPairs = [...currentPairs.value]
      newPairs.splice(index, 1)
      
      updateModelValue(newPairs)
    }

    // Update model value, filtering out empty pairs
    const updateModelValue = (pairs) => {
      const filteredPairs = pairs.filter(pair => 
        pair.key && pair.key.trim() !== ''
      )
      
      emit('update:modelValue', filteredPairs)
      emit('change', filteredPairs)
    }

    // Handle keyboard navigation
    const handleKeydown = (index, event) => {
      if (event.key === 'Enter') {
        event.preventDefault()
        
        // If this is the last row and both key and value are filled, add a new row
        const pair = currentPairs.value[index]
        if (index === currentPairs.value.length - 1 && pair.key && pair.value) {
          addPair()
        }
      } else if (event.key === 'Tab') {
        // Let default tab behavior handle navigation
      } else if (event.key === 'Backspace' || event.key === 'Delete') {
        // If both key and value are empty, remove the row (except if it's the only row)
        const pair = currentPairs.value[index]
        if (!pair.key && !pair.value && currentPairs.value.length > 1) {
          event.preventDefault()
          removePair(index)
        }
      }
    }

    // Handle focus
    const handleFocus = (event) => {
      emit('focus', event)
    }

    // Handle blur
    const handleBlur = (event) => {
      emit('blur', event)
    }

    // Focus method for external access
    const focus = () => {
      if (keyInputRefs.value && keyInputRefs.value[0]) {
        keyInputRefs.value[0].focus()
      }
    }

    // Blur method for external access
    const blur = () => {
      const allInputs = [...(keyInputRefs.value || []), ...(valueInputRefs.value || [])]
      allInputs.forEach(ref => {
        if (ref) ref.blur()
      })
    }

    return {
      keyInputRefs,
      valueInputRefs,
      fieldId,
      isRequired,
      isDarkTheme,
      keyLabel,
      valueLabel,
      actionText,
      currentPairs,
      displayPairs,
      hasDisplayValues,
      handleKeyInput,
      handleValueInput,
      addPair,
      removePair,
      handleKeydown,
      handleFocus,
      handleBlur,
      focus,
      blur,
    }
  },
}
</script>

<style scoped>
/* Additional key-value field specific styles if needed */
.keyvalue-field {
  /* Custom styles */
}

/* Ensure proper input styling */
.admin-input {
  @apply block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm;
}

.admin-input-dark {
  @apply border-gray-600 bg-gray-700 text-white placeholder-gray-400;
}

/* Focus styles */
.admin-input:focus {
  outline: none;
}
</style>
