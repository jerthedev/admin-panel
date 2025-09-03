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
      <div v-if="hasDisplayValues" class="space-y-1">
        <div
          v-for="(item, key) in displayValues"
          :key="key"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mr-2 mb-1"
          :class="[
            item.value 
              ? 'bg-green-100 text-green-800' 
              : 'bg-gray-100 text-gray-800',
            {
              'bg-green-900 text-green-200': item.value && isDarkTheme,
              'bg-gray-700 text-gray-300': !item.value && isDarkTheme
            }
          ]"
        >
          <CheckIcon v-if="item.value" class="w-3 h-3 mr-1" />
          <XMarkIcon v-else class="w-3 h-3 mr-1" />
          {{ item.label }}
        </div>
      </div>
      <div v-else class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
        {{ noValueText }}
      </div>
    </div>

    <!-- Editable checkboxes -->
    <div v-else class="space-y-3">
      <div
        v-for="(label, key) in options"
        :key="key"
        class="flex items-center"
      >
        <input
          :id="`${fieldId}-${key}`"
          ref="checkboxRefs"
          type="checkbox"
          :checked="isChecked(key)"
          :disabled="disabled"
          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          :class="{ 
            'border-gray-600 bg-gray-700': isDarkTheme,
            'opacity-50 cursor-not-allowed': disabled
          }"
          @change="handleChange(key, $event)"
          @focus="handleFocus"
          @blur="handleBlur"
        />

        <label
          :for="`${fieldId}-${key}`"
          class="ml-2 text-sm font-medium text-gray-900 cursor-pointer"
          :class="{ 
            'text-white': isDarkTheme, 
            'cursor-not-allowed opacity-50': disabled 
          }"
        >
          {{ label }}
        </label>
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
import { CheckIcon, XMarkIcon } from '@heroicons/vue/24/solid'
import { useAdminStore } from '@/stores/admin'
import { computed, ref } from 'vue'

export default {
  name: 'BooleanGroupField',
  
  components: {
    BaseField,
    CheckIcon,
    XMarkIcon,
  },

  props: {
    field: {
      type: Object,
      required: true,
    },
    modelValue: {
      type: [Object, Array, null],
      default: () => ({}),
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
    const checkboxRefs = ref([])

    // Generate unique field ID
    const fieldId = computed(() => {
      return `boolean-group-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
    })

    // Check if field is required
    const isRequired = computed(() => {
      return props.field.rules && props.field.rules.includes('required')
    })

    // Dark theme detection
    const isDarkTheme = computed(() => {
      return adminStore.isDarkTheme
    })

    // Get options from field configuration
    const options = computed(() => {
      return props.field.options || {}
    })

    // Get hide settings
    const hideFalseValues = computed(() => {
      return props.field.hideFalseValues || false
    })

    const hideTrueValues = computed(() => {
      return props.field.hideTrueValues || false
    })

    // Get no value text
    const noValueText = computed(() => {
      return props.field.noValueText || 'No Data'
    })

    // Get current values as object
    const currentValues = computed(() => {
      if (!props.modelValue || typeof props.modelValue !== 'object') {
        return {}
      }
      return props.modelValue
    })

    // Check if a specific key is checked
    const isChecked = (key) => {
      return Boolean(currentValues.value[key])
    }

    // Get display values for readonly mode
    const displayValues = computed(() => {
      const display = {}
      
      for (const [key, label] of Object.entries(options.value)) {
        const value = Boolean(currentValues.value[key])
        
        // Skip based on hide settings
        if (hideTrueValues.value && value) {
          continue
        }
        
        if (hideFalseValues.value && !value) {
          continue
        }
        
        display[key] = {
          label,
          value,
        }
      }
      
      return display
    })

    // Check if there are values to display
    const hasDisplayValues = computed(() => {
      return Object.keys(displayValues.value).length > 0
    })

    // Handle checkbox change
    const handleChange = (key, event) => {
      if (props.disabled || props.readonly) return
      
      const newValues = { ...currentValues.value }
      newValues[key] = event.target.checked
      
      emit('update:modelValue', newValues)
      emit('change', newValues)
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
      if (checkboxRefs.value && checkboxRefs.value[0]) {
        checkboxRefs.value[0].focus()
      }
    }

    // Blur method for external access
    const blur = () => {
      if (checkboxRefs.value) {
        checkboxRefs.value.forEach(ref => {
          if (ref) ref.blur()
        })
      }
    }

    return {
      checkboxRefs,
      fieldId,
      isRequired,
      isDarkTheme,
      options,
      hideFalseValues,
      hideTrueValues,
      noValueText,
      currentValues,
      isChecked,
      displayValues,
      hasDisplayValues,
      handleChange,
      handleFocus,
      handleBlur,
      focus,
      blur,
    }
  },
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

/* Additional boolean group field specific styles if needed */
.boolean-group-field {
  /* Custom styles */
}

/* Ensure proper checkbox styling */
input[type="checkbox"] {
  flex-shrink: 0;
}

/* Focus styles */
input[type="checkbox"]:focus {
  outline: none;
}
</style>
