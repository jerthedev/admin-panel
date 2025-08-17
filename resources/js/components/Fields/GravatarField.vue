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
      <!-- Gravatar display -->
      <div
        v-if="gravatarUrl"
        class="flex items-center space-x-4"
      >
        <div class="flex-shrink-0">
          <img
            :src="gravatarUrl"
            :alt="field.name"
            class="object-cover border border-gray-300"
            :class="[
              avatarSizeClass,
              avatarShapeClass,
              { 'border-gray-600': isDarkTheme }
            ]"
            @error="handleImageError"
          />
        </div>
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
            Gravatar
          </p>
          <p class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
            {{ emailForGravatar || 'Based on email address' }}
          </p>
        </div>
        <a
          :href="gravatarProfileUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="text-blue-600 hover:text-blue-700 text-sm font-medium"
          :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
        >
          Edit on Gravatar
        </a>
      </div>

      <!-- Email input for Gravatar generation -->
      <div v-if="!field.emailColumn && !readonly">
        <label
          :for="emailFieldId"
          class="block text-sm font-medium text-gray-700 mb-2"
          :class="{ 'text-gray-300': isDarkTheme }"
        >
          Email Address
        </label>
        <input
          :id="emailFieldId"
          ref="emailInputRef"
          v-model="emailInput"
          type="email"
          :placeholder="'Enter email for Gravatar...'"
          :disabled="disabled"
          class="admin-input w-full"
          :class="[
            { 'admin-input-dark': isDarkTheme },
            { 'border-red-300': hasError }
          ]"
          @input="handleEmailInput"
          @focus="handleFocus"
          @blur="handleBlur"
        />
      </div>



      <!-- Gravatar info -->
      <div
        v-if="gravatarUrl"
        class="text-xs text-gray-500 space-y-1"
        :class="{ 'text-gray-400': isDarkTheme }"
      >
        <p>
          Gravatar is a service that provides globally recognized avatars based on email addresses.
        </p>
        <p>
          <a
            href="https://gravatar.com"
            target="_blank"
            rel="noopener noreferrer"
            class="text-blue-600 hover:text-blue-700"
            :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
          >
            Create or update your Gravatar
          </a>
        </p>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * GravatarField Component
 *
 * Nova-compatible Gravatar field for displaying email-based avatars.
 * The Gravatar field does not correspond to any column in your application's database.
 * Instead, it will display the "Gravatar" image of the model it is associated with.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, watch } from 'vue'
import { useAdminStore } from '@/stores/admin'
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
  },
  formData: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Refs
const emailInputRef = ref(null)
const emailInput = ref('')

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const emailFieldId = computed(() => {
  return `gravatar-email-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const hasError = computed(() => {
  return props.errors && Object.keys(props.errors).length > 0
})

const emailForGravatar = computed(() => {
  if (props.field.emailColumn && props.formData) {
    return props.formData[props.field.emailColumn]
  }
  return emailInput.value
})

const gravatarUrl = computed(() => {
  const email = emailForGravatar.value
  if (!email) return null
  
  return generateGravatarUrl(email)
})

const gravatarProfileUrl = computed(() => {
  const email = emailForGravatar.value
  if (!email) return '#'
  
  const hash = generateEmailHash(email)
  return `https://gravatar.com/${hash}`
})

const avatarSizeClass = computed(() => {
  // Use a standard size for Nova compatibility
  return 'w-16 h-16'
})

const avatarShapeClass = computed(() => {
  if (props.field.rounded) {
    return 'rounded-full'
  }
  return props.field.squared ? 'rounded-none' : 'rounded-lg'
})

// Methods
const generateEmailHash = (email) => {
  // Simple MD5 hash implementation for email
  // In a real implementation, you'd use a proper crypto library
  return btoa(email.toLowerCase().trim()).replace(/[^a-zA-Z0-9]/g, '').toLowerCase()
}

const generateGravatarUrl = (email) => {
  const hash = generateEmailHash(email)
  return `https://www.gravatar.com/avatar/${hash}`
}

const handleEmailInput = (event) => {
  emailInput.value = event.target.value
  updateGravatar()
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const updateGravatar = () => {
  const url = gravatarUrl.value
  emit('update:modelValue', url)
  emit('change', url)
}



const handleImageError = (event) => {
  // Handle broken Gravatar URLs
  console.warn('Gravatar image failed to load:', event.target.src)
}

// Watch for changes in email from form data
watch(
  () => props.formData?.[props.field.emailColumn],
  (newEmail) => {
    if (newEmail && props.field.emailColumn) {
      updateGravatar()
    }
  },
  { immediate: true }
)

// Focus method for external use
const focus = () => {
  emailInputRef.value?.focus()
}

defineExpose({
  focus
})
</script>

<style scoped>
/* Ensure proper spacing */
.space-y-4 > * + * {
  margin-top: 1rem;
}

.space-y-1 > * + * {
  margin-top: 0.25rem;
}

/* Grid gap for options */
.gap-4 {
  gap: 1rem;
}
</style>
