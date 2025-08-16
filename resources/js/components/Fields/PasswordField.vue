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
    <div class="space-y-4">
      <!-- Password input -->
      <div class="relative">
        <!-- Lock icon -->
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <LockClosedIcon class="h-5 w-5 text-gray-400" />
        </div>

        <input
          :id="fieldId"
          ref="inputRef"
          :type="showPassword ? 'text' : 'password'"
          :value="truncatedValue"
          :placeholder="field.placeholder || 'Enter password'"
          :minlength="field.minLength"
          :maxlength="field.maxLength"
          :disabled="disabled"
          :readonly="readonly"
          class="admin-input w-full pl-10"
          :class="{
            'admin-input-dark': isDarkTheme,
            'pr-10': field.showToggle !== false
          }"
          @input="handleInput"
          @focus="handleFocus"
          @blur="handleBlur"
        />

        <!-- Toggle password visibility -->
        <button
          v-if="field.showToggle !== false"
          type="button"
          class="absolute inset-y-0 right-0 pr-3 flex items-center"
          @click="togglePasswordVisibility"
        >
          <EyeIcon v-if="!showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" />
          <EyeSlashIcon v-else class="h-5 w-5 text-gray-400 hover:text-gray-600" />
        </button>
      </div>

      <!-- Password confirmation -->
      <div v-if="field.requireConfirmation" class="relative">
        <input
          :id="`${fieldId}-confirmation`"
          ref="confirmationRef"
          :type="showConfirmation ? 'text' : 'password'"
          :value="confirmationValue"
          placeholder="Confirm password"
          :disabled="disabled"
          :readonly="readonly"
          class="admin-input w-full pr-10"
          :class="{ 'admin-input-dark': isDarkTheme }"
          @input="handleConfirmationInput"
          @focus="handleFocus"
          @blur="handleBlur"
        />

        <!-- Toggle confirmation visibility -->
        <button
          type="button"
          class="absolute inset-y-0 right-0 pr-3 flex items-center"
          @click="toggleConfirmationVisibility"
        >
          <EyeIcon v-if="!showConfirmation" class="h-5 w-5 text-gray-400 hover:text-gray-600" />
          <EyeSlashIcon v-else class="h-5 w-5 text-gray-400 hover:text-gray-600" />
        </button>
      </div>

      <!-- Password strength indicator -->
      <div v-if="field.showStrengthMeter && modelValue" class="strength-meter space-y-2">
        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-600" :class="{ 'text-gray-400': isDarkTheme }">
            Strength:
          </span>
          <div class="flex-1 bg-gray-200 rounded-full h-2" :class="{ 'bg-gray-700': isDarkTheme }">
            <div
              class="h-2 rounded-full transition-all duration-300"
              :class="strengthBarClasses"
              :style="{ width: `${strengthPercentage}%` }"
            ></div>
          </div>
          <span class="text-sm font-medium" :class="strengthTextClasses">
            {{ strengthText }}
          </span>
        </div>

      </div>

      <!-- Password requirements -->
      <div v-if="field.requirements || field.showRequirements" class="text-xs space-y-1 mt-2">
        <div
          v-if="field.minLength"
          class="flex items-center space-x-2"
          :class="hasMinLength ? 'text-green-600' : 'text-gray-500'"
        >
          <CheckIcon v-if="hasMinLength" class="h-3 w-3" />
          <XMarkIcon v-else class="h-3 w-3" />
          <span>At least {{ field.minLength }} characters</span>
        </div>
        <div
          class="flex items-center space-x-2"
          :class="hasUppercase ? 'text-green-600' : 'text-gray-500'"
        >
          <CheckIcon v-if="hasUppercase" class="h-3 w-3" />
          <XMarkIcon v-else class="h-3 w-3" />
          <span>One uppercase letter</span>
        </div>
        <div
          class="flex items-center space-x-2"
          :class="hasLowercase ? 'text-green-600' : 'text-gray-500'"
        >
          <CheckIcon v-if="hasLowercase" class="h-3 w-3" />
          <XMarkIcon v-else class="h-3 w-3" />
          <span>One lowercase letter</span>
        </div>
        <div
          class="flex items-center space-x-2"
          :class="hasNumber ? 'text-green-600' : 'text-gray-500'"
        >
          <CheckIcon v-if="hasNumber" class="h-3 w-3" />
          <XMarkIcon v-else class="h-3 w-3" />
          <span>One number</span>
        </div>
        <div
          class="flex items-center space-x-2"
          :class="hasSpecialChar ? 'text-green-600' : 'text-gray-500'"
        >
          <CheckIcon v-if="hasSpecialChar" class="h-3 w-3" />
          <XMarkIcon v-else class="h-3 w-3" />
          <span>One special character</span>
        </div>
      </div>

      <!-- Character count -->
      <div
        v-if="field.maxLength && modelValue"
        class="text-xs text-right"
        :class="characterCountClasses"
      >
        {{ characterCount }}/{{ field.maxLength }}
      </div>

      <!-- Confirmation mismatch error -->
      <div
        v-if="field.requireConfirmation && confirmationValue && !passwordsMatch"
        class="text-sm text-red-600"
        :class="{ 'text-red-400': isDarkTheme }"
      >
        Passwords do not match
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * PasswordField Component
 *
 * Password input field with visibility toggle, confirmation,
 * strength indicator, and requirement validation.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import {
  EyeIcon,
  EyeSlashIcon,
  CheckIcon,
  XMarkIcon,
  LockClosedIcon
} from '@heroicons/vue/24/outline'
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
const confirmationRef = ref(null)
const showPassword = ref(false)
const showConfirmation = ref(false)
const confirmationValue = ref('')

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `password-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

// Truncated value for display
const truncatedValue = computed(() => {
  if (!props.modelValue) return props.modelValue
  if (!props.field.maxLength) return props.modelValue
  return props.modelValue.substring(0, props.field.maxLength)
})

// Password strength calculations
const hasMinLength = computed(() => {
  return !props.field.minLength || (props.modelValue?.length || 0) >= props.field.minLength
})

const hasUppercase = computed(() => {
  return /[A-Z]/.test(props.modelValue || '')
})

const hasLowercase = computed(() => {
  return /[a-z]/.test(props.modelValue || '')
})

const hasNumber = computed(() => {
  return /\d/.test(props.modelValue || '')
})

const hasSpecialChar = computed(() => {
  return /[!@#$%^&*(),.?":{}|<>]/.test(props.modelValue || '')
})

const strengthScore = computed(() => {
  let score = 0
  if (hasMinLength.value) score++
  if (hasUppercase.value) score++
  if (hasLowercase.value) score++
  if (hasNumber.value) score++
  if (hasSpecialChar.value) score++
  return score
})

const strengthPercentage = computed(() => {
  return (strengthScore.value / 5) * 100
})

const strengthText = computed(() => {
  switch (strengthScore.value) {
    case 0:
    case 1:
      return 'Weak'
    case 2:
    case 3:
      return 'Medium'
    case 4:
      return 'Strong'
    case 5:
      return 'Very Strong'
    default:
      return 'Weak'
  }
})

const strengthBarClasses = computed(() => {
  switch (strengthScore.value) {
    case 0:
    case 1:
      return 'bg-red-500'
    case 2:
    case 3:
      return 'bg-yellow-500'
    case 4:
      return 'bg-blue-500'
    case 5:
      return 'bg-green-500'
    default:
      return 'bg-red-500'
  }
})

const strengthTextClasses = computed(() => {
  switch (strengthScore.value) {
    case 0:
    case 1:
      return 'text-red-600'
    case 2:
    case 3:
      return 'text-amber-600'
    case 4:
      return 'text-blue-600'
    case 5:
      return 'text-green-600'
    default:
      return 'text-red-600'
  }
})

const passwordsMatch = computed(() => {
  return props.modelValue === confirmationValue.value
})

// Character count
const characterCount = computed(() => {
  return props.modelValue?.length || 0
})

const characterCountClasses = computed(() => {
  if (!props.field.maxLength) return 'text-gray-500'

  const count = characterCount.value
  const max = props.field.maxLength
  const percentage = count / max

  if (percentage >= 0.9) return 'text-red-500'
  if (percentage >= 0.8) return 'text-yellow-500'
  return 'text-gray-500'
})

// Methods
const handleInput = (event) => {
  const value = event.target.value
  emit('update:modelValue', value)
  emit('change', value)
}

const handleConfirmationInput = (event) => {
  confirmationValue.value = event.target.value
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const toggleConfirmationVisibility = () => {
  showConfirmation.value = !showConfirmation.value
}

// Focus method for external use
const focus = () => {
  inputRef.value?.focus()
}

defineExpose({
  focus,
  passwordsMatch
})
</script>

<style scoped>
/* Ensure proper spacing for toggle button */
.pr-10 {
  padding-right: 2.5rem;
}

/* Smooth transitions for strength indicator */
.transition-all {
  transition: all 0.3s ease-in-out;
}
</style>
