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
      <input
        :id="fieldId"
        ref="inputRef"
        type="password"
        :value="modelValue"
        :placeholder="field.placeholder || field.name"
        :minlength="field.minLength"
        :disabled="disabled"
        :readonly="readonly"
        class="admin-input w-full pr-10"
        :class="[
          { 'admin-input-dark': isDarkTheme },
          { 'border-red-300': hasError },
          { 'border-green-300': isValid && modelValue }
        ]"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
      />

      <!-- Toggle visibility button -->
      <button
        type="button"
        class="absolute inset-y-0 right-0 pr-3 flex items-center"
        @click="toggleVisibility"
      >
        <EyeIcon
          v-if="showPassword"
          class="h-5 w-5 text-gray-400 hover:text-gray-600"
          :class="{ 'text-gray-500 hover:text-gray-300': isDarkTheme }"
        />
        <EyeSlashIcon
          v-else
          class="h-5 w-5 text-gray-400 hover:text-gray-600"
          :class="{ 'text-gray-500 hover:text-gray-300': isDarkTheme }"
        />
      </button>
    </div>

    <!-- Password strength indicator -->
    <div
      v-if="field.showStrengthIndicator && modelValue"
      class="mt-2"
    >
      <div class="flex items-center space-x-2">
        <div class="flex-1 bg-gray-200 rounded-full h-2" :class="{ 'bg-gray-700': isDarkTheme }">
          <div
            class="h-2 rounded-full transition-all duration-300"
            :class="strengthBarClass"
            :style="{ width: strengthPercentage + '%' }"
          ></div>
        </div>
        <span
          class="text-xs font-medium"
          :class="strengthTextClass"
        >
          {{ strengthText }}
        </span>
      </div>
    </div>

    <!-- Character count -->
    <div
      v-if="field.minLength && showCharacterCount"
      class="mt-1 text-xs text-gray-500"
      :class="{ 'text-gray-400': isDarkTheme }"
    >
      {{ characterCount }}/{{ field.minLength }} minimum characters
    </div>
  </BaseField>
</template>

<script setup>
/**
 * PasswordConfirmationField Component
 * 
 * Password confirmation input field with strength indicator and visibility toggle.
 * Used for password verification alongside Password fields.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: String,
    default: ''
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
    default: 'default'
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Refs
const inputRef = ref(null)
const showPassword = ref(false)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `password-confirmation-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const hasError = computed(() => {
  return props.errors && Object.keys(props.errors).length > 0
})

const characterCount = computed(() => {
  return String(props.modelValue || '').length
})

const showCharacterCount = computed(() => {
  return props.field.minLength && characterCount.value < props.field.minLength
})

const isValid = computed(() => {
  return props.field.minLength ? characterCount.value >= props.field.minLength : characterCount.value > 0
})

// Password strength calculation
const strengthScore = computed(() => {
  const password = props.modelValue || ''
  let score = 0
  
  if (password.length >= 8) score += 1
  if (password.length >= 12) score += 1
  if (/[a-z]/.test(password)) score += 1
  if (/[A-Z]/.test(password)) score += 1
  if (/[0-9]/.test(password)) score += 1
  if (/[^A-Za-z0-9]/.test(password)) score += 1
  
  return score
})

const strengthPercentage = computed(() => {
  return Math.min((strengthScore.value / 6) * 100, 100)
})

const strengthText = computed(() => {
  if (strengthScore.value <= 2) return 'Weak'
  if (strengthScore.value <= 4) return 'Medium'
  return 'Strong'
})

const strengthBarClass = computed(() => {
  if (strengthScore.value <= 2) return 'bg-red-500'
  if (strengthScore.value <= 4) return 'bg-yellow-500'
  return 'bg-green-500'
})

const strengthTextClass = computed(() => {
  const baseClasses = isDarkTheme.value ? 'text-gray-300' : 'text-gray-700'
  if (strengthScore.value <= 2) return `${baseClasses} text-red-500`
  if (strengthScore.value <= 4) return `${baseClasses} text-yellow-500`
  return `${baseClasses} text-green-500`
})

// Methods
const handleInput = (event) => {
  const value = event.target.value
  emit('update:modelValue', value)
  emit('change', value)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const handleKeydown = (event) => {
  // Allow common keyboard shortcuts
  if (event.ctrlKey || event.metaKey) {
    return
  }
}

const toggleVisibility = () => {
  showPassword.value = !showPassword.value
  const input = inputRef.value
  if (input) {
    input.type = showPassword.value ? 'text' : 'password'
  }
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
/* Ensure proper spacing */
.relative {
  position: relative;
}

/* Transition for strength indicator */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
