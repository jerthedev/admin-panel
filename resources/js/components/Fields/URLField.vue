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
    <div class="space-y-3">
      <!-- URL Input -->
      <div class="relative">
        <input
          :id="fieldId"
          ref="inputRef"
          type="url"
          :value="modelValue || ''"
          :disabled="disabled"
          :readonly="readonly"
          :placeholder="field.placeholder || 'https://example.com'"
          :maxlength="field.maxLength"
          class="admin-input w-full pr-10"
          :class="{ 'admin-input-dark': isDarkTheme }"
          @input="handleInput"
          @focus="handleFocus"
          @blur="handleBlur"
          @change="handleChange"
        />

        <!-- Link Icon -->
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
          <LinkIcon class="h-5 w-5 text-gray-400" />
        </div>
      </div>

      <!-- URL Preview/Display -->
      <div v-if="modelValue && (field.clickable || field.showPreview)" class="url-preview">
        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-md" :class="{ 'bg-gray-800': isDarkTheme }">
          <!-- Favicon -->
          <div v-if="field.showFavicon" class="flex-shrink-0">
            <img
              :src="faviconUrl"
              :alt="'Favicon for ' + displayHost"
              class="w-4 h-4"
              @error="handleFaviconError"
            />
          </div>

          <!-- URL Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2">
              <!-- Clickable Link -->
              <a
                v-if="field.clickable && !readonly"
                :href="normalizedUrl"
                :target="field.target || '_self'"
                class="text-blue-600 hover:text-blue-500 font-medium truncate"
                :class="{ 'text-blue-400': isDarkTheme }"
                @click="handleLinkClick"
              >
                {{ linkText }}
              </a>

              <!-- Non-clickable Display -->
              <span
                v-else
                class="text-gray-700 truncate"
                :class="{ 'text-gray-300': isDarkTheme }"
              >
                {{ linkText }}
              </span>

              <!-- External Link Icon -->
              <ArrowTopRightOnSquareIcon
                v-if="field.clickable && field.target === '_blank'"
                class="h-4 w-4 text-gray-400 flex-shrink-0"
              />
            </div>

            <!-- URL Details -->
            <div class="text-xs text-gray-500 mt-1" :class="{ 'text-gray-400': isDarkTheme }">
              <span>{{ displayHost }}</span>
              <span v-if="urlPath" class="opacity-75">{{ urlPath }}</span>
            </div>
          </div>

          <!-- Copy Button -->
          <button
            v-if="!readonly"
            type="button"
            class="flex-shrink-0 p-1 text-gray-400 hover:text-gray-600 rounded"
            :class="{ 'hover:text-gray-300': isDarkTheme }"
            @click="copyUrl"
            title="Copy URL"
          >
            <ClipboardIcon class="h-4 w-4" />
          </button>
        </div>
      </div>

      <!-- URL Validation Status -->
      <div v-if="validationStatus" class="flex items-center space-x-2 text-xs">
        <div
          class="flex items-center space-x-1"
          :class="{
            'text-green-600': validationStatus === 'valid',
            'text-red-600': validationStatus === 'invalid',
            'text-yellow-600': validationStatus === 'warning'
          }"
        >
          <CheckCircleIcon v-if="validationStatus === 'valid'" class="h-4 w-4" />
          <XCircleIcon v-if="validationStatus === 'invalid'" class="h-4 w-4" />
          <ExclamationTriangleIcon v-if="validationStatus === 'warning'" class="h-4 w-4" />
          <span>{{ validationMessage }}</span>
        </div>
      </div>

      <!-- URL Length Counter -->
      <div
        v-if="field.maxLength && modelValue"
        class="text-xs text-gray-500 text-right"
        :class="{ 'text-red-500': modelValue.length > field.maxLength }"
      >
        {{ modelValue.length }} / {{ field.maxLength }}
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * URLField Component
 *
 * A URL input field with validation, clickable display, favicon support,
 * and protocol handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, ref, watch } from 'vue'
import {
  LinkIcon,
  ArrowTopRightOnSquareIcon,
  ClipboardIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'
import { useAdminStore } from '@/stores/admin'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: String,
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
const validationStatus = ref(null)
const validationMessage = ref('')

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `url-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const normalizedUrl = computed(() => {
  if (!props.modelValue) return ''

  // Add protocol if missing and normalizeProtocol is enabled
  if (props.field.normalizeProtocol && !props.modelValue.match(/^https?:\/\//)) {
    return `${props.field.protocol || 'https'}://${props.modelValue}`
  }

  return props.modelValue
})

const parsedUrl = computed(() => {
  try {
    return new URL(normalizedUrl.value)
  } catch {
    return null
  }
})

const displayHost = computed(() => {
  return parsedUrl.value?.hostname || props.modelValue || ''
})

const urlPath = computed(() => {
  if (!parsedUrl.value) return ''
  const path = parsedUrl.value.pathname + parsedUrl.value.search + parsedUrl.value.hash
  return path !== '/' ? path : ''
})

const linkText = computed(() => {
  if (props.field.linkText) {
    return props.field.linkText
  }

  // Default to showing the hostname
  return displayHost.value || props.modelValue || ''
})

const faviconUrl = computed(() => {
  if (!parsedUrl.value) return ''
  return `${parsedUrl.value.protocol}//${parsedUrl.value.hostname}/favicon.ico`
})

// Methods
const validateUrl = (url) => {
  if (!url) {
    validationStatus.value = null
    validationMessage.value = ''
    return
  }

  try {
    const parsed = new URL(url.match(/^https?:\/\//) ? url : `https://${url}`)

    if (!parsed.hostname) {
      validationStatus.value = 'invalid'
      validationMessage.value = 'Invalid hostname'
      return
    }

    if (!url.match(/^https?:\/\//) && props.field.normalizeProtocol) {
      validationStatus.value = 'warning'
      validationMessage.value = `Protocol will be added (${props.field.protocol || 'https'}://)`
      return
    }

    validationStatus.value = 'valid'
    validationMessage.value = 'Valid URL'
  } catch (error) {
    validationStatus.value = 'invalid'
    validationMessage.value = 'Invalid URL format'
  }
}

// Watch for URL changes to validate
watch(() => props.modelValue, (newValue) => {
  if (props.field.validateUrl) {
    validateUrl(newValue)
  }
}, { immediate: true })
const handleInput = (event) => {
  const value = event.target.value
  emit('update:modelValue', value || null)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const handleChange = (event) => {
  const value = event.target.value
  emit('update:modelValue', value || null)
  emit('change', value || null)
}

const handleLinkClick = (event) => {
  // Allow default link behavior
  // Could add analytics tracking here if needed
}

const handleFaviconError = (event) => {
  // Hide favicon on error
  event.target.style.display = 'none'
}

const copyUrl = async () => {
  if (!normalizedUrl.value) return

  try {
    await navigator.clipboard.writeText(normalizedUrl.value)
    // Could show a toast notification here
  } catch (error) {
    // Fallback for older browsers
    const textArea = document.createElement('textarea')
    textArea.value = normalizedUrl.value
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
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

.url-preview {
  @apply transition-all duration-200;
}
</style>
