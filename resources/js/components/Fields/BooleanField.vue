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
    <div v-if="readonly" class="flex items-center">
      <div
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
        :class="[
          isChecked
            ? 'bg-green-100 text-green-800'
            : 'bg-gray-100 text-gray-800',
          {
            'bg-green-900 text-green-200': isChecked && isDarkTheme,
            'bg-gray-700 text-gray-300': !isChecked && isDarkTheme
          }
        ]"
      >
        <CheckIcon v-if="isChecked" class="w-3 h-3 mr-1" />
        <XMarkIcon v-else class="w-3 h-3 mr-1" />
        {{ displayValue }}
      </div>
    </div>

    <!-- Editable checkbox -->
    <div v-else class="flex items-center">
      <input
        :id="fieldId"
        ref="checkboxRef"
        type="checkbox"
        :checked="isChecked"
        :disabled="disabled"
        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
        :class="{
          'border-gray-600 bg-gray-700': isDarkTheme,
          'opacity-50 cursor-not-allowed': disabled
        }"
        @change="handleChange"
        @focus="handleFocus"
        @blur="handleBlur"
      />

      <label
        :for="fieldId"
        class="ml-2 text-sm font-medium text-gray-900 cursor-pointer"
        :class="{
          'text-white': isDarkTheme,
          'cursor-not-allowed opacity-50': disabled
        }"
      >
        {{ field.name }}
        <span v-if="isRequired" class="text-red-500 ml-1">*</span>
      </label>
    </div>
  </BaseField>
</template>

<script>
import BaseField from './BaseField.vue'
import { CheckIcon, XMarkIcon } from '@heroicons/vue/24/solid'
import { useAdminStore } from '@/stores/admin'
import { computed, ref } from 'vue'

export default {
  name: 'BooleanField',

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
      type: [Boolean, String, Number, null],
      default: null,
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
    const checkboxRef = ref(null)

    // Generate unique field ID
    const fieldId = computed(() => {
      return `boolean-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
    })

    // Check if field is required
    const isRequired = computed(() => {
      return props.field.rules && props.field.rules.includes('required')
    })

    // Dark theme detection
    const isDarkTheme = computed(() => {
      return adminStore.isDarkTheme
    })

    // Get true/false values from field configuration
    const trueValue = computed(() => {
      return props.field.trueValue !== undefined ? props.field.trueValue : true
    })

    const falseValue = computed(() => {
      return props.field.falseValue !== undefined ? props.field.falseValue : false
    })

    // Check if current value matches true value
    const isChecked = computed(() => {
      return props.modelValue == trueValue.value
    })

    // Display value for readonly mode
    const displayValue = computed(() => {
      return isChecked.value ? 'Yes' : 'No'
    })

    // Handle checkbox change
    const handleChange = (event) => {
      if (props.disabled || props.readonly) return

      const newValue = event.target.checked ? trueValue.value : falseValue.value
      emit('update:modelValue', newValue)
      emit('change', newValue)
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
      if (checkboxRef.value) {
        checkboxRef.value.focus()
      }
    }

    // Blur method for external access
    const blur = () => {
      if (checkboxRef.value) {
        checkboxRef.value.blur()
      }
    }

    return {
      checkboxRef,
      fieldId,
      isRequired,
      isDarkTheme,
      trueValue,
      falseValue,
      isChecked,
      displayValue,
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
/* Additional boolean field specific styles if needed */
.boolean-field {
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
