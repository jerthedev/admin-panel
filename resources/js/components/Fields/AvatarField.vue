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
      <!-- Current avatar display -->
      <div
        v-if="currentAvatarUrl"
        class="flex items-center space-x-4"
      >
        <div class="flex-shrink-0">
          <img
            :src="currentAvatarUrl"
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
            Current Avatar
          </p>
          <p class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
            {{ currentAvatarName || 'Uploaded image' }}
          </p>
        </div>
        <button
          v-if="!readonly && !disabled"
          type="button"
          class="text-red-600 hover:text-red-700 text-sm font-medium"
          :class="{ 'text-red-400 hover:text-red-300': isDarkTheme }"
          @click="removeAvatar"
        >
          Remove
        </button>
      </div>

      <!-- Upload area -->
      <div
        v-if="!readonly"
        class="relative"
      >
        <input
          :id="fieldId"
          ref="fileInputRef"
          type="file"
          :accept="field.acceptedTypes || 'image/*'"
          :disabled="disabled"
          class="sr-only"
          @change="handleFileSelect"
        />
        
        <label
          :for="fieldId"
          class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors duration-200"
          :class="[
            { 'bg-gray-800 border-gray-600 hover:bg-gray-700': isDarkTheme },
            { 'opacity-50 cursor-not-allowed': disabled },
            { 'border-red-300': hasError },
            { 'border-blue-400 bg-blue-50': isDragOver && !isDarkTheme },
            { 'border-blue-500 bg-gray-700': isDragOver && isDarkTheme }
          ]"
          @dragover.prevent="handleDragOver"
          @dragleave.prevent="handleDragLeave"
          @drop.prevent="handleDrop"
        >
          <div class="flex flex-col items-center justify-center pt-5 pb-6">
            <CloudArrowUpIcon
              class="w-8 h-8 mb-4 text-gray-500"
              :class="{ 'text-gray-400': isDarkTheme }"
            />
            <p
              class="mb-2 text-sm text-gray-500"
              :class="{ 'text-gray-400': isDarkTheme }"
            >
              <span class="font-semibold">Click to upload</span> or drag and drop
            </p>
            <p
              class="text-xs text-gray-500"
              :class="{ 'text-gray-400': isDarkTheme }"
            >
              {{ acceptedTypesText }}
            </p>
          </div>
        </label>
      </div>

      <!-- File info -->
      <div
        v-if="selectedFile"
        class="flex items-center space-x-3 p-3 bg-blue-50 rounded-lg"
        :class="{ 'bg-blue-900/20': isDarkTheme }"
      >
        <DocumentIcon class="w-5 h-5 text-blue-600" :class="{ 'text-blue-400': isDarkTheme }" />
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-blue-900" :class="{ 'text-blue-100': isDarkTheme }">
            {{ selectedFile.name }}
          </p>
          <p class="text-xs text-blue-700" :class="{ 'text-blue-300': isDarkTheme }">
            {{ formatFileSize(selectedFile.size) }}
          </p>
        </div>
        <button
          type="button"
          class="text-blue-600 hover:text-blue-700"
          :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
          @click="clearSelectedFile"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Upload progress -->
      <div
        v-if="uploadProgress > 0 && uploadProgress < 100"
        class="w-full bg-gray-200 rounded-full h-2"
        :class="{ 'bg-gray-700': isDarkTheme }"
      >
        <div
          class="bg-blue-600 h-2 rounded-full transition-all duration-300"
          :style="{ width: uploadProgress + '%' }"
        ></div>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * AvatarField Component
 * 
 * User avatar field that extends Image with special display features.
 * Optimized for user profile pictures with squared/rounded display options.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { CloudArrowUpIcon, DocumentIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, File],
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
    default: 'default'
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Refs
const fileInputRef = ref(null)
const selectedFile = ref(null)
const isDragOver = ref(false)
const uploadProgress = ref(0)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `avatar-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const hasError = computed(() => {
  return props.errors && Object.keys(props.errors).length > 0
})

const currentAvatarUrl = computed(() => {
  if (selectedFile.value) {
    return URL.createObjectURL(selectedFile.value)
  }
  return props.modelValue && typeof props.modelValue === 'string' ? props.modelValue : null
})

const currentAvatarName = computed(() => {
  if (selectedFile.value) {
    return selectedFile.value.name
  }
  if (typeof props.modelValue === 'string') {
    return props.modelValue.split('/').pop()
  }
  return null
})

const avatarSizeClass = computed(() => {
  const size = props.field.size || 80
  return `w-${Math.min(Math.floor(size / 4), 32)} h-${Math.min(Math.floor(size / 4), 32)}`
})

const avatarShapeClass = computed(() => {
  if (props.field.rounded) {
    return 'rounded-full'
  }
  return props.field.squared ? 'rounded-none' : 'rounded-lg'
})

const acceptedTypesText = computed(() => {
  const types = props.field.acceptedTypes || 'image/*'
  if (types.includes('image/*')) {
    return 'PNG, JPG, WEBP up to 10MB'
  }
  return types.replace(/image\//g, '').toUpperCase()
})

// Methods
const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    processFile(file)
  }
}

const handleDragOver = (event) => {
  isDragOver.value = true
}

const handleDragLeave = (event) => {
  isDragOver.value = false
}

const handleDrop = (event) => {
  isDragOver.value = false
  const file = event.dataTransfer.files[0]
  if (file) {
    processFile(file)
  }
}

const processFile = (file) => {
  // Validate file type
  if (props.field.acceptedTypes && !isFileTypeAccepted(file)) {
    // Handle error - could emit an error event
    return
  }

  selectedFile.value = file
  emit('update:modelValue', file)
  emit('change', file)
}

const isFileTypeAccepted = (file) => {
  const acceptedTypes = props.field.acceptedTypes || 'image/*'
  const fileType = file.type
  
  return acceptedTypes.split(',').some(type => {
    type = type.trim()
    if (type === 'image/*') {
      return fileType.startsWith('image/')
    }
    if (type.startsWith('.')) {
      return file.name.toLowerCase().endsWith(type.toLowerCase())
    }
    return fileType === type
  })
}

const removeAvatar = () => {
  selectedFile.value = null
  emit('update:modelValue', null)
  emit('change', null)
  
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const clearSelectedFile = () => {
  selectedFile.value = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const handleImageError = (event) => {
  // Handle broken image URLs
  event.target.style.display = 'none'
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Focus method for external use
const focus = () => {
  fileInputRef.value?.focus()
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

/* Transition for drag states */
.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
