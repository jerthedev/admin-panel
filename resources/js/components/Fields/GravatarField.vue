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
      <div v-if="!field.emailAttribute && !readonly">
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

      <!-- Gravatar options -->
      <div
        v-if="showOptions"
        class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg"
        :class="{ 'bg-gray-800': isDarkTheme }"
      >
        <!-- Size selector -->
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1" :class="{ 'text-gray-300': isDarkTheme }">
            Size
          </label>
          <select
            v-model="localSize"
            class="admin-input w-full text-sm"
            :class="{ 'admin-input-dark': isDarkTheme }"
            @change="updateGravatar"
          >
            <option value="40">40px</option>
            <option value="80">80px</option>
            <option value="120">120px</option>
            <option value="200">200px</option>
          </select>
        </div>

        <!-- Default fallback -->
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1" :class="{ 'text-gray-300': isDarkTheme }">
            Default
          </label>
          <select
            v-model="localDefault"
            class="admin-input w-full text-sm"
            :class="{ 'admin-input-dark': isDarkTheme }"
            @change="updateGravatar"
          >
            <option value="mp">Mystery Person</option>
            <option value="identicon">Identicon</option>
            <option value="monsterid">Monster ID</option>
            <option value="wavatar">Wavatar</option>
            <option value="retro">Retro</option>
            <option value="robohash">RoboHash</option>
            <option value="blank">Blank</option>
          </select>
        </div>

        <!-- Rating -->
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1" :class="{ 'text-gray-300': isDarkTheme }">
            Rating
          </label>
          <select
            v-model="localRating"
            class="admin-input w-full text-sm"
            :class="{ 'admin-input-dark': isDarkTheme }"
            @change="updateGravatar"
          >
            <option value="g">G (General)</option>
            <option value="pg">PG (Parental Guidance)</option>
            <option value="r">R (Restricted)</option>
            <option value="x">X (Adult)</option>
          </select>
        </div>
      </div>

      <!-- Toggle options -->
      <div class="flex items-center justify-between">
        <button
          v-if="!readonly"
          type="button"
          class="text-sm text-blue-600 hover:text-blue-700"
          :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
          @click="showOptions = !showOptions"
        >
          {{ showOptions ? 'Hide Options' : 'Show Options' }}
        </button>

        <div class="flex items-center space-x-2">
          <button
            v-if="!readonly"
            type="button"
            class="text-sm text-gray-600 hover:text-gray-700"
            :class="{ 'text-gray-400 hover:text-gray-300': isDarkTheme }"
            @click="refreshGravatar"
          >
            Refresh
          </button>
        </div>
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
 * Gravatar integration field for email-based avatars.
 * Supports various Gravatar options like size, default fallback, and rating.
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
const showOptions = ref(false)
const localSize = ref(props.field.size || 80)
const localDefault = ref(props.field.defaultFallback || 'mp')
const localRating = ref(props.field.rating || 'g')

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
  if (props.field.emailAttribute && props.formData) {
    return props.formData[props.field.emailAttribute]
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
  const size = localSize.value
  const sizeClass = Math.min(Math.floor(size / 4), 32)
  return `w-${sizeClass} h-${sizeClass}`
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
  const params = new URLSearchParams({
    s: localSize.value.toString(),
    d: localDefault.value,
    r: localRating.value
  })
  
  return `https://www.gravatar.com/avatar/${hash}?${params.toString()}`
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

const refreshGravatar = () => {
  // Force refresh by adding a timestamp
  const url = gravatarUrl.value
  if (url) {
    const refreshUrl = url + '&_t=' + Date.now()
    emit('update:modelValue', refreshUrl)
    emit('change', refreshUrl)
  }
}

const handleImageError = (event) => {
  // Handle broken Gravatar URLs
  console.warn('Gravatar image failed to load:', event.target.src)
}

// Watch for changes in email from form data
watch(
  () => props.formData?.[props.field.emailAttribute],
  (newEmail) => {
    if (newEmail && props.field.emailAttribute) {
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
