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
    <div class="relative">
      <!-- Currency Symbol -->
      <div 
        v-if="field.symbol && symbolPosition === 'left'"
        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
      >
        <span class="text-gray-500 text-sm" :class="{ 'text-gray-400': isDarkTheme }">
          {{ field.symbol }}
        </span>
      </div>

      <!-- Input Field -->
      <input
        :id="fieldId"
        ref="inputRef"
        type="number"
        :value="displayValue"
        :min="field.minValue"
        :max="field.maxValue"
        :step="field.step"
        :disabled="disabled"
        :readonly="readonly"
        :placeholder="placeholder"
        class="admin-input w-full"
        :class="{
          'admin-input-dark': isDarkTheme,
          'pl-8': field.symbol && symbolPosition === 'left',
          'pr-12': field.symbol && symbolPosition === 'right'
        }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @change="handleChange"
      />

      <!-- Currency Symbol (Right) -->
      <div 
        v-if="field.symbol && symbolPosition === 'right'"
        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
      >
        <span class="text-gray-500 text-sm" :class="{ 'text-gray-400': isDarkTheme }">
          {{ field.symbol }}
        </span>
      </div>
    </div>

    <!-- Currency Info -->
    <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
      <div class="flex items-center space-x-2">
        <span v-if="field.currency">{{ field.currency }}</span>
        <span v-if="field.locale" class="opacity-75">{{ field.locale }}</span>
      </div>
      <div v-if="field.precision" class="opacity-75">
        {{ field.precision }} decimal{{ field.precision !== 1 ? 's' : '' }}
      </div>
    </div>

    <!-- Formatted Display -->
    <div
      v-if="readonly && modelValue && formattedDisplay"
      class="mt-2 text-sm text-gray-600"
      :class="{ 'text-gray-400': isDarkTheme }"
    >
      Formatted: {{ formattedDisplay }}
    </div>

    <!-- Validation Range -->
    <div
      v-if="(field.minValue !== null || field.maxValue !== null) && !readonly"
      class="mt-1 text-xs text-gray-500"
    >
      Range: {{ formatRange() }}
    </div>
  </BaseField>
</template>

<script setup>
/**
 * CurrencyField Component
 * 
 * A currency input field with locale-aware formatting, currency symbols,
 * and decimal precision support.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, ref } from 'vue'
import BaseField from './BaseField.vue'
import { useAdminStore } from '@/stores/admin'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, Number],
    default: null
  },
  errors: {
    type: Object,
    default: () => ({})
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  size: {
    type: String,
    default: 'default',
    validator: (value) => ['small', 'default', 'large'].includes(value)
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Refs
const inputRef = ref(null)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `currency-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const displayValue = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined) return ''
  return props.modelValue.toString()
})

const placeholder = computed(() => {
  const symbol = props.field.symbol || ''
  // Nova uses 2 decimal places for currency by default
  const example = (0).toFixed(2)
  return `${symbol}${example}`
})

const symbolPosition = computed(() => {
  // Determine symbol position based on currency/locale
  const leftSymbolCurrencies = ['USD', 'CAD', 'AUD', 'GBP']
  return leftSymbolCurrencies.includes(props.field.currency) ? 'left' : 'right'
})

const formattedDisplay = computed(() => {
  if (!props.modelValue) return null

  try {
    const value = parseFloat(props.modelValue)
    if (isNaN(value)) return null

    // Use Intl.NumberFormat for proper locale formatting (Nova standard)
    if (typeof Intl !== 'undefined' && Intl.NumberFormat) {
      const formatter = new Intl.NumberFormat(props.field.locale || 'en-US', {
        style: 'currency',
        currency: props.field.currency || 'USD'
        // Nova determines precision automatically based on currency
      })
      return formatter.format(value)
    }

    // Fallback formatting
    const symbol = props.field.symbol || '$'
    return `${symbol}${value.toFixed(2)}`
  } catch (error) {
    return null
  }
})

// Methods
const handleInput = (event) => {
  let value = event.target.value

  // Convert to number if valid
  if (value !== '' && !isNaN(value)) {
    value = parseFloat(value)
  } else if (value === '') {
    value = null
  }
  // Otherwise keep as string for validation

  emit('update:modelValue', value)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  let value = event.target.value
  
  // Format to proper precision on blur
  if (value !== '' && !isNaN(value)) {
    const numValue = parseFloat(value)
    const precision = props.field.precision || 2
    const formatted = numValue.toFixed(precision)
    
    // Update the input display
    event.target.value = formatted
    emit('update:modelValue', parseFloat(formatted))
  }
  
  emit('blur', event)
}

const handleChange = (event) => {
  let value = event.target.value
  
  if (value !== '' && !isNaN(value)) {
    value = parseFloat(value)
  } else if (value === '') {
    value = null
  }
  
  emit('update:modelValue', value)
  emit('change', value)
}

const formatRange = () => {
  const min = props.field.minValue
  const max = props.field.maxValue
  const symbol = props.field.symbol || ''
  
  if (min !== null && max !== null) {
    return `${symbol}${min} - ${symbol}${max}`
  } else if (min !== null) {
    return `Min: ${symbol}${min}`
  } else if (max !== null) {
    return `Max: ${symbol}${max}`
  }
  
  return ''
}

// Focus method for external use
const focus = () => {
  inputRef.value?.focus()
}

defineExpose({
  focus
})
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.admin-input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm;
}

.admin-input-dark {
  @apply bg-gray-800 border-gray-600 text-white placeholder-gray-400 focus:border-blue-400 focus:ring-blue-400;
}

.admin-input:disabled {
  @apply bg-gray-50 text-gray-500 cursor-not-allowed;
}

.admin-input-dark:disabled {
  @apply bg-gray-900 text-gray-500;
}
</style>
