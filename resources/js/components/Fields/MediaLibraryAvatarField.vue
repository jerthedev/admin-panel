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
    <div class="media-library-avatar-field">
      <!-- Current Avatar Display -->
      <div class="flex items-start space-x-4 mb-4">
        <!-- Avatar Preview -->
        <div class="relative">
          <div
            class="avatar-container"
            :class="{
              'avatar-container-small': size === 'small',
              'avatar-container-large': size === 'large',
              'avatar-container-dark': isDarkTheme
            }"
          >
            <img
              :src="currentAvatarUrl"
              :alt="field.name || 'Avatar'"
              class="avatar-image"
              @error="handleImageError"
              @click="openLightbox"
            />

            <!-- Upload overlay on hover -->
            <div
              v-if="!readonly"
              class="avatar-overlay"
              @click="openFileDialog"
            >
              <CameraIcon class="h-6 w-6 text-white" />
              <span class="text-xs text-white mt-1">Change</span>
            </div>
          </div>

          <!-- Remove button -->
          <button
            v-if="hasCurrentAvatar && !readonly"
            type="button"
            class="absolute -top-2 -right-2 p-1 bg-red-500 rounded-full text-white hover:bg-red-600 transition-colors shadow-lg"
            @click="removeAvatar"
            title="Remove avatar"
          >
            <XMarkIcon class="h-3 w-3" />
          </button>
        </div>

        <!-- Avatar Info -->
        <div class="flex-1 min-w-0">
          <div class="text-sm font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
            {{ field.name || 'Avatar' }}
          </div>

          <div v-if="avatarMetadata" class="mt-1 space-y-1">
            <p class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
              {{ avatarMetadata.human_readable_size }}
              <span v-if="avatarMetadata.dimensions"> • {{ avatarMetadata.dimensions }}</span>
            </p>
            <p v-if="avatarMetadata.created_at" class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
              Uploaded {{ formatDate(avatarMetadata.created_at) }}
            </p>
          </div>

          <div v-if="!hasCurrentAvatar" class="mt-1">
            <p class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
              No avatar uploaded
            </p>
          </div>
        </div>
      </div>

      <!-- Upload Area (when no avatar or in replace mode) -->
      <div
        v-if="!readonly && (!hasCurrentAvatar || showUploadArea)"
        class="upload-area"
        :class="{
          'upload-area-dragover': isDragOver,
          'upload-area-disabled': disabled,
          'upload-area-dark': isDarkTheme,
          'upload-area-compact': hasCurrentAvatar
        }"
        @dragover.prevent="handleDragOver"
        @dragleave.prevent="handleDragLeave"
        @drop.prevent="handleDrop"
        @click="openFileDialog"
      >
        <input
          :id="fieldId"
          ref="fileInputRef"
          type="file"
          :accept="acceptedTypes"
          :disabled="disabled"
          class="hidden"
          @change="handleFileSelect"
        />

        <div class="upload-content">
          <UserCircleIcon class="h-8 w-8 text-gray-400 mx-auto mb-2" />
          <p class="text-sm text-gray-600 mb-1" :class="{ 'text-gray-300': isDarkTheme }">
            <span class="font-medium">{{ hasCurrentAvatar ? 'Replace avatar' : 'Upload avatar' }}</span>
          </p>
          <p class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
            {{ acceptedTypesText }}
            <span v-if="field.maxFileSize"> • Max {{ formatFileSize(field.maxFileSize * 1024) }}</span>
          </p>
          <p v-if="field.cropAspectRatio" class="text-xs text-gray-500 mt-1" :class="{ 'text-gray-400': isDarkTheme }">
            Recommended ratio: {{ field.cropAspectRatio }}
          </p>
        </div>
      </div>

      <!-- Upload Progress -->
      <div v-if="uploadProgress > 0 && uploadProgress < 100" class="mt-4">
        <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
          <span>Uploading avatar...</span>
          <span>{{ uploadProgress }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: uploadProgress + '%' }"
          ></div>
        </div>
      </div>

      <!-- Upload Error -->
      <div v-if="uploadError" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
        <div class="flex">
          <ExclamationCircleIcon class="h-5 w-5 text-red-400" />
          <div class="ml-3">
            <p class="text-sm text-red-800">{{ uploadError }}</p>
          </div>
        </div>
      </div>

      <!-- Avatar Size Options -->
      <div v-if="hasCurrentAvatar && field.conversions" class="mt-4">
        <div class="text-sm font-medium text-gray-900 mb-2" :class="{ 'text-white': isDarkTheme }">
          Available sizes:
        </div>
        <div class="flex space-x-4">
          <div
            v-for="(conversion, name) in field.conversions"
            :key="name"
            class="text-center"
          >
            <img
              :src="getAvatarUrl(name)"
              :alt="`${field.name} ${name}`"
              class="avatar-size-preview"
              :class="{
                'w-8 h-8': name === 'thumb',
                'w-12 h-12': name === 'medium',
                'w-16 h-16': name === 'large'
              }"
            />
            <p class="text-xs text-gray-500 mt-1 capitalize">{{ name }}</p>
            <p v-if="conversion.width" class="text-xs text-gray-400">{{ conversion.width }}px</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox Modal -->
    <div
      v-if="showLightbox"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75"
      @click="closeLightbox"
    >
      <div class="relative max-w-lg max-h-full p-4">
        <img
          :src="lightboxImageUrl"
          :alt="field.name || 'Avatar'"
          class="max-w-full max-h-full object-contain rounded-lg"
          @click.stop
        />
        <button
          type="button"
          class="absolute top-4 right-4 p-2 bg-black bg-opacity-50 rounded-full text-white hover:bg-opacity-75 transition-all"
          @click="closeLightbox"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * MediaLibraryAvatarField Component
 *
 * An avatar upload field with Media Library integration, single file upload,
 * circular preview, cropping interface, and fallback support.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, ref } from 'vue'
import {
  UserCircleIcon,
  CameraIcon,
  XMarkIcon,
  ExclamationCircleIcon
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
    type: [String, Object, File],
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
const fileInputRef = ref(null)
const isDragOver = ref(false)
const uploadProgress = ref(0)
const uploadError = ref(null)
const showLightbox = ref(false)
const showUploadArea = ref(false)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `media-library-avatar-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const hasCurrentAvatar = computed(() => {
  return props.modelValue && (
    (typeof props.modelValue === 'object' && props.modelValue.url) ||
    props.modelValue instanceof File
  )
})

const currentAvatarUrl = computed(() => {
  if (!props.modelValue) {
    return props.field.fallbackUrl || '/images/default-avatar.png'
  }

  if (props.modelValue instanceof File) {
    return URL.createObjectURL(props.modelValue)
  }

  if (typeof props.modelValue === 'object') {
    return props.modelValue.medium_url || props.modelValue.url || props.field.fallbackUrl || '/images/default-avatar.png'
  }

  return props.field.fallbackUrl || '/images/default-avatar.png'
})

const lightboxImageUrl = computed(() => {
  if (!props.modelValue) return ''

  if (props.modelValue instanceof File) {
    return URL.createObjectURL(props.modelValue)
  }

  if (typeof props.modelValue === 'object') {
    return props.modelValue.large_url || props.modelValue.url || currentAvatarUrl.value
  }

  return currentAvatarUrl.value
})

const avatarMetadata = computed(() => {
  if (!props.modelValue || props.modelValue instanceof File) return null

  if (typeof props.modelValue === 'object') {
    return {
      name: props.modelValue.name || props.modelValue.file_name,
      size: props.modelValue.size,
      human_readable_size: props.modelValue.human_readable_size || formatFileSize(props.modelValue.size || 0),
      dimensions: props.modelValue.width && props.modelValue.height ? `${props.modelValue.width} × ${props.modelValue.height}` : null,
      created_at: props.modelValue.created_at
    }
  }

  return null
})

const acceptedTypes = computed(() => {
  if (props.field.acceptedMimeTypes && props.field.acceptedMimeTypes.length > 0) {
    return props.field.acceptedMimeTypes.join(',')
  }
  return 'image/*'
})

const acceptedTypesText = computed(() => {
  if (props.field.acceptedMimeTypes && props.field.acceptedMimeTypes.length > 0) {
    const types = props.field.acceptedMimeTypes.map(type => {
      if (type.startsWith('image/jpeg')) return 'JPEG'
      if (type.startsWith('image/jpg')) return 'JPG'
      if (type.startsWith('image/png')) return 'PNG'
      if (type.startsWith('image/webp')) return 'WebP'
      return type.split('/')[1]?.toUpperCase() || type
    })
    return types.join(', ') + ' images'
  }
  return 'Image files'
})

// Methods
const openFileDialog = () => {
  if (!props.disabled && !props.readonly) {
    fileInputRef.value?.click()
  }
}

const handleFileSelect = (event) => {
  const files = event.target.files
  if (files && files.length > 0) {
    handleFile(files[0])
  }
}

const handleDragOver = (event) => {
  if (!props.disabled && !props.readonly) {
    isDragOver.value = true
  }
}

const handleDragLeave = (event) => {
  isDragOver.value = false
}

const handleDrop = (event) => {
  isDragOver.value = false

  if (props.disabled || props.readonly) return

  const files = event.dataTransfer.files
  if (files && files.length > 0) {
    handleFile(files[0])
  }
}

const handleFile = (file) => {
  uploadError.value = null

  // Validate file is an image
  if (!file.type.startsWith('image/')) {
    uploadError.value = 'Please select a valid image file'
    return
  }

  // Validate file
  if (!validateFile(file)) {
    return
  }

  // Update model value
  emit('update:modelValue', file)
  emit('change', file)

  // Hide upload area after successful upload
  showUploadArea.value = false
}

const validateFile = (file) => {
  // Check file size
  if (props.field.maxFileSize && file.size > props.field.maxFileSize * 1024) {
    uploadError.value = `Avatar size exceeds maximum allowed size of ${formatFileSize(props.field.maxFileSize * 1024)}`
    return false
  }

  // Check MIME type
  if (props.field.acceptedMimeTypes && props.field.acceptedMimeTypes.length > 0) {
    if (!props.field.acceptedMimeTypes.includes(file.type)) {
      uploadError.value = `Avatar type not allowed. Accepted types: ${acceptedTypesText.value}`
      return false
    }
  }

  return true
}

const removeAvatar = () => {
  emit('update:modelValue', null)
  emit('change', null)
  showUploadArea.value = false
}

const getAvatarUrl = (conversion = 'medium') => {
  if (!props.modelValue) {
    return props.field.fallbackUrl || '/images/default-avatar.png'
  }

  if (props.modelValue instanceof File) {
    return URL.createObjectURL(props.modelValue)
  }

  if (typeof props.modelValue === 'object') {
    const urlKey = `${conversion}_url`
    return props.modelValue[urlKey] || props.modelValue.url || props.field.fallbackUrl || '/images/default-avatar.png'
  }

  return props.field.fallbackUrl || '/images/default-avatar.png'
}

const openLightbox = () => {
  if (hasCurrentAvatar.value) {
    showLightbox.value = true
  }
}

const closeLightbox = () => {
  showLightbox.value = false
}

const handleImageError = (event) => {
  event.target.src = props.field.fallbackUrl || '/images/default-avatar.png'
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 B'

  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  const power = Math.floor(Math.log(bytes) / Math.log(1024))
  const size = bytes / Math.pow(1024, power)

  return `${size.toFixed(power > 0 ? 1 : 0)} ${units[power]}`
}

const formatDate = (dateString) => {
  if (!dateString) return ''

  try {
    const date = new Date(dateString)
    return date.toLocaleDateString()
  } catch (error) {
    return dateString
  }
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}
</script>

<style scoped>
.avatar-container {
  @apply relative w-20 h-20 rounded-full overflow-hidden bg-gray-200 border-2 border-gray-300 cursor-pointer group;
}

.avatar-container-small {
  @apply w-16 h-16;
}

.avatar-container-large {
  @apply w-24 h-24;
}

.avatar-container-dark {
  @apply bg-gray-700 border-gray-600;
}

.avatar-image {
  @apply w-full h-full object-cover;
}

.avatar-overlay {
  @apply absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer;
}

.avatar-size-preview {
  @apply rounded-full object-cover border border-gray-300;
}

.upload-area {
  @apply border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer transition-colors;
}

.upload-area:hover {
  @apply border-gray-400 bg-gray-50;
}

.upload-area-dragover {
  @apply border-blue-400 bg-blue-50;
}

.upload-area-disabled {
  @apply cursor-not-allowed opacity-50;
}

.upload-area-dark {
  @apply border-gray-600 bg-gray-800;
}

.upload-area-dark:hover {
  @apply border-gray-500 bg-gray-700;
}

.upload-area-compact {
  @apply p-3;
}

.upload-content {
  @apply pointer-events-none;
}
</style>
